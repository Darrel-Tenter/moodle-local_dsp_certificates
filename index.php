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
 * DSP Certificate Lookup — main page.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

use local_dsp_certificates\certificate_helper;
use local_dsp_certificates\certificates_table;

// ── Authentication & authorisation ──────────────────────────────────────────
require_login();

$context = \core\context\system::instance();
require_capability('local/dsp_certificates:view', $context);

// ── Page setup ───────────────────────────────────────────────────────────────
$PAGE->set_url(new moodle_url('/local/dsp_certificates/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pagetitle', 'local_dsp_certificates'));
$PAGE->set_heading(get_string('pageheading', 'local_dsp_certificates'));

// ── Filter params ────────────────────────────────────────────────────────────
$userid        = optional_param('userid',        0,   PARAM_INT);
$sourceType    = optional_param('sourcetype',    '',  PARAM_ALPHA);
$status        = optional_param('status',        '',  PARAM_ALPHA);
$expiresBefore = optional_param('expiresbefore', '',  PARAM_TEXT);
$suspended     = optional_param('suspended',     '0', PARAM_TEXT);

// Sanitise sourceType and status to known values only.
$sourceType    = in_array($sourceType, ['course', 'certification', '']) ? $sourceType : '';
$status        = in_array($status,     ['valid',  'expired',        '']) ? $status     : '';
$suspended     = in_array($suspended,  ['0', '1', ''])                   ? $suspended  : '0';

// Validate expiresBefore is a parseable date or empty.
if ($expiresBefore && !strtotime($expiresBefore)) {
    $expiresBefore = '';
}

$filters = [
    'userid'        => $userid,
    'sourcetype'    => $sourceType,
    'status'        => $status,
    'expiresbefore' => $expiresBefore,
    'suspended'     => $suspended,
];

// ── Data helper ───────────────────────────────────────────────────────────────
$helper = new certificate_helper($USER->id);

// Validate that the requested userid belongs to this admin's tenant.
$targetUser = null;
if ($userid > 0) {
    if (!$helper->user_in_tenant($userid)) {
        throw new \moodle_exception('errornouser', 'local_dsp_certificates');
    }
    $targetUser = \core_user::get_user($userid, 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename', MUST_EXIST);
}

// ── AMD module init ───────────────────────────────────────────────────────────
// Pass only minimal data — the user list is fetched via AJAX to avoid
// exceeding the js_call_amd 1024-character argument limit.
$PAGE->requires->js_call_amd('local_dsp_certificates/filters', 'init', [[
    'selectedUserId'   => $userid,
    'selectedUserName' => $targetUser ? fullname($targetUser) : '',
    'strings'          => [
        'placeholder' => get_string('filterstaffmember_placeholder', 'local_dsp_certificates'),
    ],
]]);

// ── CSV export ────────────────────────────────────────────────────────────────
$export = optional_param('export', '', PARAM_ALPHA);
if ($export === 'csv' && $userid > 0) {
    $records = $helper->get_user_certificates($userid, $filters);

    $filename = clean_filename(
        $targetUser->lastname . '_' . $targetUser->firstname . '_certificates.csv'
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');

    // CSV header row.
    fputcsv($out, [
        get_string('colsource',     'local_dsp_certificates'),
        get_string('colstatus',     'local_dsp_certificates'),
        get_string('coldateissued', 'local_dsp_certificates'),
        get_string('colexpiration', 'local_dsp_certificates'),
        get_string('colverifycode', 'local_dsp_certificates'),
    ]);

    foreach ($records as $r) {
        $statusKey   = certificate_helper::status_key($r);
        $sourceLabel = get_string('source' . $r->sourcetype, 'local_dsp_certificates');
        $expires     = (empty($r->expires) || (int)$r->expires === 0)
            ? get_string('never', 'local_dsp_certificates')
            : userdate($r->expires, get_string('strftimedatetimeshort', 'langconfig'));

        fputcsv($out, [
            $sourceLabel . ': ' . ($r->sourcename ?? $r->templatename),
            get_string($statusKey, 'local_dsp_certificates'),
            userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            $expires,
            (string)certificate_helper::verify_url($r),
        ]);
    }

    fclose($out);
    exit;
}

// ── Build table (only when a user is selected) ────────────────────────────────
$tablehtml = '';

if ($userid > 0) {
    $table = new certificates_table('dsp_certificates_table', $filters, $helper);
    $table->define_baseurl($PAGE->url);
    ob_start();
    $table->out(50, false);
    $tablehtml = ob_get_clean();
}

// ── Output ────────────────────────────────────────────────────────────────────
echo $OUTPUT->header();

// ── Filter form ───────────────────────────────────────────────────────────────
$baseUrl     = new moodle_url('/local/dsp_certificates/index.php');
$downloadUrl = new moodle_url('/local/dsp_certificates/download.php', array_filter([
    'userid'        => $userid,
    'sourcetype'    => $sourceType,
    'status'        => $status,
    'expiresbefore' => $expiresBefore,
    'suspended'     => $suspended,
    'sesskey'       => sesskey(),
]));
$csvUrl = new moodle_url('/local/dsp_certificates/index.php', array_filter([
    'userid'        => $userid,
    'sourcetype'    => $sourceType,
    'status'        => $status,
    'expiresbefore' => $expiresBefore,
    'suspended'     => $suspended,
    'export'        => 'csv',
]));

$templateContext = [
    'actionurl'       => $baseUrl->out(false),
    'sesskey'         => sesskey(),

    // Filter current values.
    'userid'          => $userid,
    'sourcetype'      => $sourceType,
    'status'          => $status,
    'expiresbefore'   => $expiresBefore,
    'suspended'       => $suspended,

    // Dropdown options.
    'sourcetypeopts'  => [
        ['value' => '',              'label' => get_string('optionall',         'local_dsp_certificates'), 'selected' => $sourceType === ''],
        ['value' => 'course',        'label' => get_string('optioncourse',      'local_dsp_certificates'), 'selected' => $sourceType === 'course'],
        ['value' => 'certification', 'label' => get_string('optioncertification','local_dsp_certificates'),'selected' => $sourceType === 'certification'],
    ],
    'statusopts'      => [
        ['value' => '',        'label' => get_string('optionall',     'local_dsp_certificates'), 'selected' => $status === ''],
        ['value' => 'valid',   'label' => get_string('optionvalid',   'local_dsp_certificates'), 'selected' => $status === 'valid'],
        ['value' => 'expired', 'label' => get_string('optionexpired', 'local_dsp_certificates'), 'selected' => $status === 'expired'],
    ],
    'suspendedopts'   => [
        ['value' => '',  'label' => get_string('optionall', 'local_dsp_certificates'), 'selected' => $suspended === ''],
        ['value' => '0', 'label' => get_string('optionno',  'local_dsp_certificates'), 'selected' => $suspended === '0'],
        ['value' => '1', 'label' => get_string('optionyes', 'local_dsp_certificates'), 'selected' => $suspended === '1'],
    ],

    // Strings.
    'str_filtersheading'    => get_string('filtersheading',    'local_dsp_certificates'),
    'str_staffmember'       => get_string('filterstaffmember', 'local_dsp_certificates'),
    'str_sourcetype'        => get_string('filtersourcetype',  'local_dsp_certificates'),
    'str_status'            => get_string('filterstatus',      'local_dsp_certificates'),
    'str_expiresbefore'     => get_string('filterexpiresbefore','local_dsp_certificates'),
    'str_suspended'         => get_string('filtersuspended',   'local_dsp_certificates'),
    'str_search'            => get_string('btnsearch',         'local_dsp_certificates'),
    'str_clear'             => get_string('btnclear',          'local_dsp_certificates'),
    'str_downloadall'       => get_string('btndownloadall',    'local_dsp_certificates'),
    'str_exportcsv'         => get_string('btnexportcsv',      'local_dsp_certificates'),
    'str_noselectuser'      => get_string('noselectuser',      'local_dsp_certificates'),
    'str_noselectuserdesc'  => get_string('noselectuserdesc',  'local_dsp_certificates'),

    // Results state.
    'hasresults'      => ($userid > 0),
    'targetusername'  => $targetUser ? fullname($targetUser) : '',
    'downloadurl'     => ($userid > 0) ? $downloadUrl->out(false) : '',
    'csvurl'          => ($userid > 0) ? $csvUrl->out(false)      : '',
    'tablehtml'       => $tablehtml,
];

echo $OUTPUT->render_from_template('local_dsp_certificates/certificates_page', $templateContext);

echo $OUTPUT->footer();
