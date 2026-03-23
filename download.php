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
 * DSP Certificate Lookup — zip download endpoint.
 *
 * Retrieves all certificate PDFs for a given user directly from Moodle's
 * file store using the File API, bundles them into a .zip, and streams
 * the archive to the browser. No external HTTP requests are made.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_dsp_certificates\certificate_helper;

// ── Authentication & authorisation ───────────────────────────────────────────
require_login();

$context = \core\context\system::instance();
require_capability('local/dsp_certificates:view', $context);

// ── Params & sesskey validation ───────────────────────────────────────────────
$userid        = required_param('userid',        PARAM_INT);
$sesskey       = required_param('sesskey',       PARAM_ALPHANUM);
$sourceType    = optional_param('sourcetype',    '',  PARAM_ALPHA);
$status        = optional_param('status',        '',  PARAM_ALPHA);
$expiresBefore = optional_param('expiresbefore', '',  PARAM_TEXT);
$suspended     = optional_param('suspended',     '0', PARAM_TEXT);

// Validate sesskey to prevent CSRF.
if (!confirm_sesskey($sesskey)) {
    throw new \moodle_exception('invalidsesskey');
}

// Sanitise filter values to known-good options only.
$sourceType    = in_array($sourceType, ['course', 'certification', '']) ? $sourceType : '';
$status        = in_array($status,     ['valid',  'expired',        '']) ? $status     : '';
$suspended     = in_array($suspended,  ['0', '1', ''])                   ? $suspended  : '0';

if ($expiresBefore && !strtotime($expiresBefore)) {
    $expiresBefore = '';
}

$filters = [
    'sourcetype'    => $sourceType,
    'status'        => $status,
    'expiresbefore' => $expiresBefore,
    'suspended'     => $suspended,
];

// ── Data helper & tenant validation ──────────────────────────────────────────
$helper = new certificate_helper($USER->id);

if (!$helper->user_in_tenant($userid)) {
    throw new \moodle_exception('errornouser', 'local_dsp_certificates');
}

$targetUser = \core_user::get_user($userid, 'id, firstname, lastname', MUST_EXIST);
$records    = $helper->get_user_certificates($userid, $filters);

if (empty($records)) {
    redirect(
        new moodle_url('/local/dsp_certificates/index.php', ['userid' => $userid]),
        get_string('noresults', 'local_dsp_certificates')
    );
}

// ── Build zip using Moodle File API ──────────────────────────────────────────
if (!class_exists('ZipArchive')) {
    throw new \moodle_exception('errorzipfailed', 'local_dsp_certificates');
}

$zip     = new ZipArchive();
$tmpDir  = make_temp_directory('local_dsp_certificates');
$tmpFile = $tmpDir . '/' . uniqid('dsp_certs_', true) . '.zip';
$opened  = $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

if ($opened !== true) {
    throw new \moodle_exception('errorzipfailed', 'local_dsp_certificates');
}

// Certificate PDFs are stored in Moodle's file store under the system context.
// Verified against production data:
//   component = 'tool_certificate'
//   filearea  = 'issues'
//   itemid    = mdl_tool_certificate_issues.id
//   filepath  = '/'
//   filename  = '{code}.pdf'
$fs           = get_file_storage();
$sysContextId = \core\context\system::instance()->id;
$errors       = 0;

foreach ($records as $record) {
    $file = $fs->get_file(
        $sysContextId,
        'tool_certificate',
        'issues',
        (int) $record->id,
        '/',
        $record->code . '.pdf'
    );

    if (!$file || $file->is_directory()) {
        $errors++;
        continue;
    }

    // Build a descriptive filename for each PDF entry inside the zip.
    // Pattern: LASTNAME_FIRSTNAME_SOURCENAME_DATEISSUED.pdf
    $sourcePart = clean_filename($record->sourcename ?? $record->templatename);
    $datePart   = date('Y-m-d', $record->timecreated);
    $pdfName    = clean_filename(
        $targetUser->lastname . '_' . $targetUser->firstname
        . '_' . $sourcePart . '_' . $datePart . '.pdf'
    );

    $zip->addFromString($pdfName, $file->get_content());
}

$zip->close();

if ($errors > 0 && filesize($tmpFile) < 22) {
    // Zip is empty — all file lookups failed.
    @unlink($tmpFile);
    throw new \moodle_exception('errorpdffailed', 'local_dsp_certificates');
}

// ── Stream zip to browser ─────────────────────────────────────────────────────
$zipFilename = clean_filename(
    $targetUser->lastname . '_' . $targetUser->firstname . '_certificates.zip'
);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tmpFile);

@unlink($tmpFile);
exit;
