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

$string['agencymanagement']             = 'Agency Management';
$string['breadcrumb']                   = 'Certificate Lookup';
$string['btnclear']                     = 'Clear';
$string['btndownloadall']               = 'Download All Certificates (.zip)';
$string['btndownloadpdf']               = 'Download PDF';
$string['btnexportcsv']                 = 'Export CSV';
$string['btnsearch']                    = 'Search';
$string['coldateissued']                = 'Date Issued';
$string['colexpiration']                = 'Expiration Date';
$string['colpdf']                       = 'PDF';
$string['colsource']                    = 'Source';
$string['colstatus']                    = 'Status';
$string['colverifycode']                = 'Verify Code';
$string['dsp_certificates:view']        = 'View DSP Certificate Lookup';
$string['errornoaccess']                = 'You do not have permission to view this page.';
$string['errornouser']                  = 'The selected user could not be found in your agency.';
$string['errorpdffailed']               = 'One or more certificate PDFs could not be retrieved.';
$string['errorzipfailed']               = 'The certificate zip file could not be generated. Please try again.';
$string['filterexpiresbefore']          = 'Expires Before';
$string['filtersheading']               = 'Search Filters';
$string['filtersourcetype']             = 'Source Type';
$string['filterstaffmember']            = 'Staff Member';
$string['filterstaffmember_placeholder'] = 'Type to search staff';
$string['filterstatus']                 = 'Status';
$string['filtersuspended']              = 'Suspended';
$string['never']                        = 'Never';
$string['noresults']                    = 'No certificates found';
$string['noresultsdesc']                = 'No certificates match the current filters for this staff member.';
$string['noselectuser']                 = 'Select a staff member to begin';
$string['noselectuserdesc']             = 'Use the Staff Member field above to find a DSP. Their certificates will appear here.';
$string['optionall']                    = 'All';
$string['optioncertification']          = 'Certification';
$string['optioncourse']                 = 'Course';
$string['optionexpired']                = 'Expired';
$string['optionno']                     = 'No';
$string['optionvalid']                  = 'Valid';
$string['optionyes']                    = 'Yes';
$string['pageheading']                  = 'Certificate Lookup';
$string['pagetitle']                    = 'Certificate Lookup';
$string['plugindescription']            = 'Find, verify, and download certificates for staff members in your agency.';
$string['pluginname']                   = 'DSP Certificate Lookup';
$string['privacy:metadata']             = 'The DSP Certificate Lookup plugin does not store any personal data. It is a read-only reporting tool that displays data held by Moodle core subsystems (tool_certificate) and MuTMS tenancy (tool_mutenancy). PDF downloads are retrieved from the local Moodle file store only — no data is transmitted externally.';
$string['resultsfound']                 = '{$a} certificate(s) found';
$string['sourcecertification']          = 'Certification';
$string['sourcecourse']                 = 'Course';
$string['sourceother']                  = 'Other';
$string['statusexpired']                = 'Expired';
$string['statusnoexpiry']               = 'No Expiry';
$string['statusvalid']                  = 'Valid';
$string['zipfilename']                  = '{$a->lastname}_{$a->firstname}_certificates.zip';
