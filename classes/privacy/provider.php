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
 * Privacy API provider for local_dsp_certificates.
 *
 * This plugin does not store any personal data of its own. It is a read-only
 * reporting tool that queries existing Moodle Workplace core tables
 * (tool_certificate_issues, tool_certificate_templates, user,
 * tool_tenant_user). All personal data displayed by this plugin is owned
 * and managed by the subsystems that store it.
 *
 * PDF files retrieved via the zip download endpoint are fetched from
 * Moodle's own pluginfile.php endpoint on the same server. No personal
 * data is transmitted to any external location.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dsp_certificates\privacy;

/**
 * Privacy provider declaring that this plugin stores no personal data.
 *
 * Implements \core_privacy\local\metadata\null_provider because:
 *  - The plugin creates no database tables of its own.
 *  - It reads only from core Moodle and Workplace tables whose privacy
 *    is managed by those subsystems.
 *  - PDF downloads are retrieved from the local Moodle file store via
 *    pluginfile.php — no data leaves the Moodle instance.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Return the reason string key explaining why no data is stored.
     *
     * @return string The language string key.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
