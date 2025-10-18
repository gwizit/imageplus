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
 * Image replacer renderer
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for image replacer plugin
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_imagereplacer_renderer extends plugin_renderer_base {

    /**
     * Render results page
     *
     * @param \local_imagereplacer\replacer $replacer Replacer instance
     * @param array $filesystemimages File system images
     * @param array $databaseimages Database images
     * @param bool $scanonly Whether this is scan only
     * @return string HTML output
     */
    public function render_results($replacer, $filesystemimages, $databaseimages, $scanonly) {
        global $PAGE;

        $output = '';

        // Add custom CSS.
        $output .= html_writer::start_tag('style');
        $output .= '
            .stats-container { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
            .stat-card { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;
                        min-width: 120px; border-left: 4px solid #3498db; }
            .stat-number { font-size: 24px; font-weight: bold; color: #2c3e50; }
            .stat-label { color: #6c757d; font-size: 14px; }
            .file-list { background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;
                        max-height: 400px; overflow-y: auto; }
            .file-item { padding: 10px; border-bottom: 1px solid #dee2e6; font-family: monospace; }
            .file-item:last-child { border-bottom: none; }
            .output-console { background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 6px;
                            font-family: monospace; white-space: pre-wrap; max-height: 500px; overflow-y: auto;
                            margin: 20px 0; }
            .output-line { margin: 2px 0; }
            .output-info { color: #90cdf4; }
            .output-success { color: #68d391; }
            .output-warning { color: #fbd38d; }
            .output-error { color: #fc8181; }
        ';
        $output .= html_writer::end_tag('style');

        $output .= $this->heading(get_string('resultstitle', 'local_imagereplacer'));

        $stats = $replacer->get_stats();

        // Statistics.
        $output .= html_writer::start_div('stats-container');

        $output .= $this->render_stat_card(count($filesystemimages),
            get_string('stats_found', 'local_imagereplacer'));

        if (!empty($databaseimages)) {
            $output .= $this->render_stat_card(count($databaseimages),
                get_string('stats_dbfound', 'local_imagereplacer'));
        }

        if (!$scanonly) {
            $output .= $this->render_stat_card($stats['files_replaced'],
                get_string('stats_replaced', 'local_imagereplacer'));

            if ($stats['db_files_replaced'] > 0) {
                $output .= $this->render_stat_card($stats['db_files_replaced'],
                    get_string('stats_dbreplaced', 'local_imagereplacer'));
            }

            if ($stats['files_failed'] > 0) {
                $output .= $this->render_stat_card($stats['files_failed'],
                    get_string('stats_failed', 'local_imagereplacer'));
            }
        }

        $output .= html_writer::end_div();

        // No files found.
        if (empty($filesystemimages) && empty($databaseimages)) {
            $output .= $this->notification(get_string('nofilesfound', 'local_imagereplacer'),
                \core\output\notification::NOTIFY_INFO);
            $output .= $this->single_button(new moodle_url('/local/imagereplacer/index.php'),
                get_string('startover', 'local_imagereplacer'));
            return $output;
        }

        // File system results.
        if (!empty($filesystemimages)) {
            $output .= $this->heading(get_string('filesystemresults', 'local_imagereplacer'), 3);
            $output .= html_writer::start_div('file-list');
            foreach ($filesystemimages as $file) {
                $output .= html_writer::div(htmlspecialchars($file), 'file-item');
            }
            $output .= html_writer::end_div();
        }

        // Database results.
        if (!empty($databaseimages)) {
            $output .= $this->heading(get_string('databaseresults', 'local_imagereplacer'), 3);
            $output .= html_writer::start_div('file-list');
            foreach ($databaseimages as $file) {
                $fileinfo = html_writer::tag('strong', htmlspecialchars($file->filename)) . html_writer::empty_tag('br');
                $fileinfo .= html_writer::tag('small',
                    htmlspecialchars($file->component) . ' / ' . htmlspecialchars($file->filearea) .
                    ' • ' . htmlspecialchars($file->mimetype) . ' • ' . number_format($file->filesize) . ' bytes',
                    ['style' => 'color: #666;']);
                $output .= html_writer::div($fileinfo, 'file-item');
            }
            $output .= html_writer::end_div();
        }

        // Processing output.
        if (!$scanonly) {
            $output .= $this->heading(get_string('processingoutput', 'local_imagereplacer'), 3);
            $output .= html_writer::start_div('output-console');
            foreach ($replacer->get_output() as $msg) {
                $class = 'output-' . $msg['type'];
                $output .= html_writer::div(htmlspecialchars($msg['message']), 'output-line ' . $class);
            }
            $output .= html_writer::end_div();

            // Completion message.
            $completemsg = get_string('operationcomplete', 'local_imagereplacer') . ' ';
            if ($stats['files_replaced'] > 0 || $stats['db_files_replaced'] > 0) {
                $completemsg .= get_string('operationcomplete_execute', 'local_imagereplacer');
            } else {
                $completemsg .= get_string('operationcomplete_preview', 'local_imagereplacer');
            }
            $output .= $this->notification($completemsg, \core\output\notification::NOTIFY_SUCCESS);
        }

        // Back button.
        $output .= html_writer::div(
            $this->single_button(new moodle_url('/local/imagereplacer/index.php'),
                get_string('startover', 'local_imagereplacer'), 'get'),
            'mt-3'
        );

        return $output;
    }

    /**
     * Render a stat card
     *
     * @param int $number Number to display
     * @param string $label Label for the stat
     * @return string HTML output
     */
    private function render_stat_card($number, $label) {
        $output = html_writer::start_div('stat-card');
        $output .= html_writer::div($number, 'stat-number');
        $output .= html_writer::div($label, 'stat-label');
        $output .= html_writer::end_div();
        return $output;
    }
}
