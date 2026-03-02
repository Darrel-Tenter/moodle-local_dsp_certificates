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
 * External functions for local_dsp_certificates.
 *
 * Provides an AJAX endpoint for the staff member autocomplete selector.
 * Returns tenant-scoped users matching a search query.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dsp_certificates;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API class for the DSP Certificate Lookup autocomplete.
 */
class external extends \core_external\external_api {

    /**
     * Parameters for search_users.
     *
     * @return \external_function_parameters
     */
    public static function search_users_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'query' => new \external_value(PARAM_TEXT, 'Search string', VALUE_REQUIRED),
        ]);
    }

    /**
     * Search for users within the viewer's tenant matching the query string.
     *
     * Returns up to 20 matching users. Results are cached via the
     * tenant_users application cache — the full list is cached, filtering
     * is done in PHP.
     *
     * @param string $query The search string.
     * @return array Array of matching users with id and fullname.
     */
    public static function search_users(string $query): array {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::search_users_parameters(), ['query' => $query]);
        $query  = trim($params['query']);

        // Require the view capability.
        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('local/dsp_certificates:view', $context);

        $helper = new certificate_helper($USER->id);
        $users  = $helper->get_tenant_users();

        if ($query === '') {
            return array_slice($users, 0, 20);
        }

        $lower   = core_text::strtolower($query);
        $matches = array_filter($users, function($u) use ($lower) {
            return strpos(core_text::strtolower($u['fullname']), $lower) !== false;
        });

        return array_slice(array_values($matches), 0, 20);
    }

    /**
     * Return value definition for search_users.
     *
     * @return \external_multiple_structure
     */
    public static function search_users_returns(): \external_multiple_structure {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id'       => new \external_value(PARAM_INT,  'User ID'),
                'fullname' => new \external_value(PARAM_TEXT, 'User full name'),
            ])
        );
    }
}
