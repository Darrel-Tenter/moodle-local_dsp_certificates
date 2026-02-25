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
 * Library functions for local_dsp_certificates.
 *
 * Registers the plugin as a child node under the shared "Agency Management"
 * primary navigation parent. Other DSL plugins contribute sibling nodes to
 * the same parent label — Moodle merges them into a single dropdown.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the primary navigation to add Agency Management > DSP Certificates.
 *
 * Only injects the node if the current user holds the view capability.
 * The parent node "Agency Management" is shared across DSL plugins — each
 * plugin contributes its own child independently.
 *
 * @param \core\navigation\views\primary $primarynav
 * @return void
 */
function local_dsp_certificates_extend_primary_navigation(\core\navigation\views\primary $primarynav): void {
    global $USER;

    // Only show to users with the view capability.
    $context = \core\context\system::instance();
    if (!has_capability('local/dsp_certificates:view', $context)) {
        return;
    }

    $parentlabel = get_string('agencymanagement', 'local_dsp_certificates');
    $pluginurl   = new moodle_url('/local/dsp_certificates/index.php');
    $childlabel  = get_string('pluginname', 'local_dsp_certificates');

    // Find or create the shared parent node.
    $parentnode = $primarynav->find('dsl_agency_management', navigation_node::TYPE_CONTAINER);

    if (!$parentnode) {
        $parentnode = navigation_node::create(
            $parentlabel,
            null,
            navigation_node::TYPE_CONTAINER,
            $parentlabel,
            'dsl_agency_management'
        );
        $primarynav->add_node($parentnode);
    }

    // Add this plugin's child node.
    $childnode = navigation_node::create(
        $childlabel,
        $pluginurl,
        navigation_node::TYPE_CUSTOM,
        $childlabel,
        'dsl_certificates'
    );

    $parentnode->add_node($childnode);
}
