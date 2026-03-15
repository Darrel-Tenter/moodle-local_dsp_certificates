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
 * table_sql subclass for the DSP Certificate Lookup results table.
 *
 * @package   local_dsp_certificates
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dsp_certificates;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Renders the paginated, sortable certificate results table.
 *
 * One row per issued certificate. Columns: source type/name, status,
 * date issued, expiration date, verify code link, PDF download link.
 *
 * The COUNT query is disabled — single-user lookups never exceed a few
 * dozen rows, making pagination counts unnecessary overhead.
 */
class certificates_table extends \table_sql {

    /** @var array Active filter values passed from index.php. */
    private array $filters;

    /**
     * Constructor.
     *
     * @param string $uniqueid  Unique ID for this table instance.
     * @param array  $filters   Active filter values.
     * @param certificate_helper $helper Data helper instance.
     */
    public function __construct(string $uniqueid, array $filters, certificate_helper $helper) {
        parent::__construct($uniqueid);

        $this->filters = $filters;

        // Column definitions.
        $this->define_columns([
            'sourcetype',
            'status',
            'timecreated',
            'expires',
            'code',
            'pdf',
        ]);

        $this->define_headers([
            get_string('colsource',      'local_dsp_certificates'),
            get_string('colstatus',      'local_dsp_certificates'),
            get_string('coldateissued',  'local_dsp_certificates'),
            get_string('colexpiration',  'local_dsp_certificates'),
            get_string('colverifycode',  'local_dsp_certificates'),
            get_string('colpdf',         'local_dsp_certificates'),
        ]);

        // Sortable columns — only those that map directly to DB fields.
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('status');
        $this->no_sorting('code');
        $this->no_sorting('pdf');

        // Styling.
        $this->collapsible(false);
        $this->set_attribute('class', 'table table-hover generaltable');

        // Disable COUNT query — single user, small result set.
        $this->pageable(false);

        // Build and set SQL.
        // get_table_sql returns [fields, fromsql, wheresql, params] ready for set_sql().
        [$fields, $fromsql, $wheresql, $params] = $helper->get_table_sql($filters);

        $this->set_sql($fields, $fromsql, $wheresql, $params);
        $this->set_count_sql('SELECT COUNT(1) FROM ' . $fromsql . ' WHERE ' . $wheresql, $params);
    }

    /**
     * Render the source type + source name cell.
     *
     * Displays the type label (Course / Certification) in small text above
     * the source name.
     *
     * @param \stdClass $row
     * @return string HTML
     */
    public function col_sourcetype(\stdClass $row): string {
        $typelabel = get_string('source' . $row->sourcetype, 'local_dsp_certificates');
        $typehtml  = \html_writer::tag('div', s($typelabel), ['class' => 'small text-muted text-uppercase']);
        $namehtml  = \html_writer::tag('div', s($row->sourcename ?? $row->templatename), ['class' => 'font-weight-medium']);
        return $typehtml . $namehtml;
    }

    /**
     * Render the status cell.
     *
     * @param \stdClass $row
     * @return string HTML
     */
    public function col_status(\stdClass $row): string {
        $key = certificate_helper::status_key($row);

        $classmap = [
            'statusvalid'    => 'text-success font-weight-bold',
            'statusexpired'  => 'text-danger font-weight-bold',
            'statusnoexpiry' => 'text-muted',
        ];

        $class = $classmap[$key] ?? 'text-muted';
        return \html_writer::tag('span', get_string($key, 'local_dsp_certificates'), ['class' => $class]);
    }

    /**
     * Render the date issued cell.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated(\stdClass $row): string {
        return userdate($row->timecreated, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Render the expiration date cell.
     *
     * @param \stdClass $row
     * @return string HTML
     */
    public function col_expires(\stdClass $row): string {
        if (empty($row->expires) || (int)$row->expires === 0) {
            return \html_writer::tag('span', get_string('never', 'local_dsp_certificates'), ['class' => 'text-muted font-italic']);
        }

        $class = ((int)$row->expires <= time()) ? 'text-danger' : '';
        return \html_writer::tag('span', userdate($row->expires, get_string('strftimedatetimeshort', 'langconfig')), ['class' => $class]);
    }

    /**
     * Render the verify code cell — linked to the Moodle verification page.
     *
     * @param \stdClass $row
     * @return string HTML
     */
    public function col_code(\stdClass $row): string {
        $url = certificate_helper::verify_url($row);
        return \html_writer::link(
            $url,
            s($row->code),
            [
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
                'class'  => 'font-monospace small',
                'title'  => get_string('colverifycode', 'local_dsp_certificates'),
            ]
        );
    }

    /**
     * Render the PDF download cell.
     *
     * @param \stdClass $row
     * @return string HTML
     */
    public function col_pdf(\stdClass $row): string {
        $url = certificate_helper::pdf_url($row);
        return \html_writer::link(
            $url,
            \html_writer::tag('i', '', ['class' => 'fa fa-download', 'aria-hidden' => 'true']) .
            ' ' . get_string('btndownloadpdf', 'local_dsp_certificates'),
            [
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
                'class'  => 'btn btn-sm btn-outline-secondary',
                'title'  => get_string('btndownloadpdf', 'local_dsp_certificates'),
            ]
        );
    }
}
