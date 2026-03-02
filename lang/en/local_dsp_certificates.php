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
 * Language strings for local_dsp_certificates.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Privacy API.
$string['privacy:metadata'] = 'The DSP Certificate Lookup plugin does not store any personal data. It is a read-only reporting tool that displays data held by Moodle Workplace core subsystems (tool_certificate, tool_tenant). PDF downloads are retrieved from the local Moodle file store only — no data is transmitted externally.';

// Plugin identity.
$string['pluginname']          = 'DSP Certificate Lookup';
$string['plugindescription']   = 'Find, verify, and download certificates for staff members in your agency.';

// Capability.
$string['dsp_certificates:view'] = 'View DSP Certificate Lookup';

// Page.
$string['pageheading']         = 'Certificate Lookup';
$string['pagetitle']           = 'Certificate Lookup';
$string['breadcrumb']          = 'Certificate Lookup';
$string['agencymanagement']    = 'Agency Management';

// Filters.
$string['filtersheading']      = 'Search Filters';
$string['filterstaffmember']   = 'Staff Member';
$string['filterstaffmember_placeholder'] = 'Type to search staff';
$string['filtersourcetype']    = 'Source Type';
$string['filterstatus']        = 'Status';
$string['filterexpiresbefore'] = 'Expires Before';
$string['filtersuspended']     = 'Suspended';

// Filter option values.
$string['optionall']           = 'All';
$string['optioncourse']        = 'Course';
$string['optioncertification'] = 'Certification';
$string['optionvalid']         = 'Valid';
$string['optionexpired']       = 'Expired';
$string['optionno']            = 'No';
$string['optionyes']           = 'Yes';

// Buttons.
$string['btnsearch']           = 'Search';
$string['btnclear']            = 'Clear';
$string['btndownloadall']      = 'Download All Certificates (.zip)';
$string['btndownloadpdf']      = 'Download PDF';
$string['btnexportcsv']        = 'Export CSV';

// Table columns.
$string['colsource']           = 'Source';
$string['colstatus']           = 'Status';
$string['coldateissued']       = 'Date Issued';
$string['colexpiration']       = 'Expiration Date';
$string['colverifycode']       = 'Verify Code';
$string['colpdf']              = 'PDF';

// Source type labels.
$string['sourcecourse']        = 'Course';
$string['sourcecertification'] = 'Certification';
$string['sourceother']         = 'Other';

// Status labels.
$string['statusvalid']         = 'Valid';
$string['statusexpired']       = 'Expired';
$string['statusnoexpiry']      = 'No Expiry';

// Expiration.
$string['never']               = 'Never';

// Results.
$string['resultsfound']        = '{$a} certificate(s) found';
$string['zipfilename']         = '{$a->lastname}_{$a->firstname}_certificates.zip';

// Empty state.
$string['noselectuser']        = 'Select a staff member to begin';
$string['noselectuserdesc']    = 'Use the Staff Member field above to find a DSP. Their certificates will appear here.';
$string['noresults']           = 'No certificates found';
$string['noresultsdesc']       = 'No certificates match the current filters for this staff member.';

// Errors.
$string['errornoaccess']       = 'You do not have permission to view this page.';
$string['errornouser']         = 'The selected user could not be found in your agency.';
$string['errorzipfailed']      = 'The certificate zip file could not be generated. Please try again.';
$string['errorpdffailed']      = 'One or more certificate PDFs could not be retrieved.';
