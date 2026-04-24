<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Certificate data helper for local_dsp_certificates.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dsp_certificates;


/**
 * Provides data access methods for the DSP Certificate Lookup plugin.
 *
 * All queries are tenant-scoped: results are restricted to users within the
 * same tenant as the viewing admin. The viewer's tenant is resolved via the
 * Workplace tenant API (\tool_tenant\tenancy) which correctly handles
 * default-tenant membership where no explicit {tool_tenant_user} row exists.
 */
class certificate_helper {

    /** @var int The session user ID (the Tenant Admin running the report). */
    private int $viewerId;

    /**
     * Constructor.
     *
     * @param int $viewerid The userid of the admin running the report.
     */
    public function __construct(int $viewerId) {
        $this->viewerId = $viewerId;
    }

    /**
     * Build the base SQL and params for certificate queries.
     *
     * Returns the FROM/JOIN/WHERE fragment and base params array.
     * Callers add their own SELECT and additional WHERE conditions.
     *
     * @param array $filters Associative array of active filter values.
     * @return array [$fromsql, $whereSql, $params]
     */
    private function build_base_sql(array $filters): array {
        global $DB;

        [$tenantjoin, $params] = $this->tenant_sql();

        // Core joins — always present.
        $fromSql = "
            {tool_certificate_issues} ci
            JOIN {tool_certificate_templates} tt ON tt.id = ci.templateid
            JOIN {user} u ON u.id = ci.userid
            {$tenantjoin}
        ";

        $where = ["u.deleted = 0"];

        // Suspended filter.
        $suspended = $filters['suspended'] ?? '';
        if ($suspended === '0') {
            $where[] = 'u.suspended = 0';
        } else if ($suspended === '1') {
            $where[] = 'u.suspended = 1';
        }

        // User filter — always applied when a userid is provided.
        if (!empty($filters['userid'])) {
            $where[] = 'u.id = :userid';
            $params['userid'] = (int)$filters['userid'];
        }

        // Source type filter.
        $sourceType = $filters['sourcetype'] ?? '';
        if ($sourceType === 'course') {
            $where[] = "ci.component = 'mod_coursecertificate'";
        } else if ($sourceType === 'certification') {
            $where[] = "ci.component = 'tool_dynamicrule'";
        }

        // Status filter.
        $status = $filters['status'] ?? '';
        if ($status === 'valid') {
            $where[] = "(ci.expires = 0 OR ci.expires > :now_valid)";
            $params['now_valid'] = time();
        } else if ($status === 'expired') {
            $where[] = "(ci.expires != 0 AND ci.expires <= :now_expired)";
            $params['now_expired'] = time();
        }

        // Expires before filter.
        if (!empty($filters['expiresbefore'])) {
            $ts = strtotime($filters['expiresbefore']);
            if ($ts) {
                $where[] = "(ci.expires != 0 AND ci.expires <= :expiresbefore)";
                $params['expiresbefore'] = $ts;
            }
        }

        $whereSql = implode(' AND ', $where);

        return [$fromSql, $whereSql, $params];
    }

    /**
     * The CASE expression that resolves the human-readable source name from JSON.
     *
     * For course certificates: extracts $.coursefullname from the data column.
     * For certification certificates (issued by Dynamic Rules): extracts $.certificationname.
     * Falls back to the template name for any other component.
     *
     * @return string SQL fragment.
     */
    public static function source_name_sql(): string {
        return "
            CASE
                WHEN ci.component = 'mod_coursecertificate'
                    THEN JSON_UNQUOTE(JSON_EXTRACT(ci.data, '$.coursefullname'))
                WHEN ci.component = 'tool_dynamicrule'
                    THEN JSON_UNQUOTE(JSON_EXTRACT(ci.data, '$.certificationname'))
                ELSE tt.name
            END
        ";
    }

    /**
     * The CASE expression that resolves the source type label key.
     *
     * Returns a string key corresponding to a lang string:
     *   'course'        → mod_coursecertificate
     *   'certification' → tool_dynamicrule
     *   'other'         → anything else
     *
     * @return string SQL fragment.
     */
    public static function source_type_sql(): string {
        return "
            CASE
                WHEN ci.component = 'mod_coursecertificate' THEN 'course'
                WHEN ci.component = 'tool_dynamicrule'      THEN 'certification'
                ELSE 'other'
            END
        ";
    }

    /**
     * Return the SELECT fragment used by both the table and export queries.
     *
     * @return string SQL SELECT fragment (without SELECT keyword).
     */
    public static function select_fields(): string {
        return "
            ci.id,
            ci.code,
            ci.timecreated,
            ci.expires,
            ci.component,
            tt.name AS templatename,
            u.id        AS userid,
            u.firstname,
            u.lastname,
            " . self::source_name_sql() . " AS sourcename,
            " . self::source_type_sql() . " AS sourcetype
        ";
    }

    /**
     * Return the full SELECT + FROM + WHERE SQL and params for the results table.
     *
     * Used by certificates_table (extends table_sql) which appends ORDER BY
     * and pagination itself.
     *
     * @param array $filters Active filter values.
     * @return array [$fields, $fromSql, $whereSql, $params]
     */
    public function get_table_sql(array $filters): array {
        [$fromSql, $whereSql, $params] = $this->build_base_sql($filters);
        return [self::select_fields(), $fromSql, $whereSql, $params];
    }

    /**
     * Return all certificate records for a given user, used by the zip/CSV download.
     *
     * @param int   $userid  The target DSP's userid.
     * @param array $filters Active filter values (sourcetype, status, expiresbefore, suspended).
     * @return array Array of stdClass records.
     */
    public function get_user_certificates(int $userid, array $filters): array {
        global $DB;

        $filters['userid'] = $userid;
        [$fromSql, $whereSql, $params] = $this->build_base_sql($filters);

        $sql = 'SELECT ' . self::select_fields() . ' FROM ' . $fromSql . ' WHERE ' . $whereSql . ' ORDER BY ci.timecreated DESC';

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Confirm that a given userid belongs to the viewer's tenant.
     *
     * Uses the Workplace tenant API which correctly resolves default-tenant
     * membership even when no explicit {tool_tenant_user} row exists.
     *
     * @param int $userid The userid to check.
     * @return bool True if the user is in the viewer's tenant.
     */
    public function user_in_tenant(int $userid): bool {
        $viewerTenantId = \tool_tenant\tenancy::get_tenant_id($this->viewerId);
        $userTenantId   = \tool_tenant\tenancy::get_tenant_id($userid);
        return $viewerTenantId === $userTenantId;
    }

    /**
     * Build the pluginfile.php URL for a certificate PDF.
     *
     * Pattern verified against production data:
     * /pluginfile.php/1/tool_certificate/issues/{timecreated}/{code}.pdf
     *
     * @param \stdClass $record A certificate record containing timecreated and code.
     * @return \moodle_url
     */
    public static function pdf_url(\stdClass $record): \moodle_url {
        return new \moodle_url('/pluginfile.php/1/tool_certificate/issues/' .
            $record->timecreated . '/' . $record->code . '.pdf');
    }

    /**
     * Build the certificate verification page URL.
     *
     * @param \stdClass $record A certificate record containing code.
     * @return \moodle_url
     */
    public static function verify_url(\stdClass $record): \moodle_url {
        return new \moodle_url('/admin/tool/certificate/index.php', ['code' => $record->code]);
    }

    /**
     * Return a human-readable status string key for a certificate record.
     *
     * @param \stdClass $record A certificate record containing expires.
     * @return string One of: 'statusvalid', 'statusexpired', 'statusnoexpiry'.
     */
    public static function status_key(\stdClass $record): string {
        if (empty($record->expires) || (int)$record->expires === 0) {
            return 'statusnoexpiry';
        }
        return ((int)$record->expires > time()) ? 'statusvalid' : 'statusexpired';
    }

    /**
     * Build the tenant-scoped JOIN fragment for the viewer.
     *
     * Uses the Workplace tenant API to resolve the viewer's tenant ID,
     * which correctly handles default-tenant users who may not have an
     * explicit row in {tool_tenant_user}.
     *
     * @return array [$joinsql, $params] A JOIN fragment and its bound params.
     */
    private function tenant_sql(): array {
        $tenantId = \tool_tenant\tenancy::get_tenant_id($this->viewerId);

        $joinsql = "JOIN {tool_tenant_user} tu ON tu.userid = u.id AND tu.tenantid = :tenantid";
        $params  = ['tenantid' => $tenantId];

        return [$joinsql, $params];
    }

    /**
     * Fetch tenant users for the autocomplete selector.
     *
     * Results are cached per viewer for 5 minutes (see db/caches.php).
     *
     * @return array Array of ['id' => int, 'fullname' => string] for all
     *               non-deleted users in the viewer's tenant.
     */
    public function get_tenant_users(): array {
        global $DB;

        $cache    = \cache::make('local_dsp_certificates', 'tenant_users');
        $cacheKey = 'tenant_users_' . $this->viewerId;

        $cached = $cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        [$tenantjoin, $tenantparams] = $this->tenant_sql();

        $sql = "
            SELECT u.id, u.firstname, u.lastname,
                   u.firstnamephonetic, u.lastnamephonetic,
                   u.middlename, u.alternatename
              FROM {user} u
                   {$tenantjoin}
             WHERE u.deleted = 0
          ORDER BY u.lastname, u.firstname
        ";

        $records = $DB->get_records_sql($sql, $tenantparams);

        $users = [];
        foreach ($records as $r) {
            $users[] = [
                'id'       => (int)$r->id,
                'fullname' => fullname($r),
            ];
        }

        $cache->set($cacheKey, $users);
        return $users;
    }
}
