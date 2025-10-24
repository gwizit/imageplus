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
 * Results page renderable for ImagePlus plugin.
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imageplus\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Results page renderable class.
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results implements renderable, templatable {

    /** @var \local_imageplus\replacer Replacer instance */
    protected $replacer;

    /** @var array File system files */
    protected $filesystem_files;

    /** @var array Database files */
    protected $database_files;

    /** @var bool Whether this is scan only */
    protected $scan_only;

    /**
     * Constructor.
     *
     * @param \local_imageplus\replacer $replacer Replacer instance
     * @param array $filesystem_files File system files
     * @param array $database_files Database files
     * @param bool $scan_only Whether this is scan only
     */
    public function __construct($replacer, $filesystem_files, $database_files, $scan_only) {
        $this->replacer = $replacer;
        $this->filesystem_files = $filesystem_files;
        $this->database_files = $database_files;
        $this->scan_only = $scan_only;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = new stdClass();
        $stats = $this->replacer->get_stats();
        $replacement_log = $this->replacer->get_replacement_log();

        // Title.
        $data->title = get_string('resultstitle', 'local_imageplus');
        $data->scan_only = $this->scan_only;

        // Preview warning.
        if ($this->scan_only) {
            $data->preview_warning = get_string('preview_mode_warning', 'local_imageplus');
        }

        // Filter replacement log for successful replacements only.
        $successful_fs = array_filter($replacement_log, function($entry) {
            return $entry['success'] && $entry['type'] === 'filesystem';
        });
        $successful_db = array_filter($replacement_log, function($entry) {
            return $entry['success'] && $entry['type'] === 'database';
        });

        // Statistics.
        $data->stats = [];
        if (!$this->scan_only) {
            $data->stats[] = [
                'number' => $stats['files_replaced'],
                'label' => get_string('stats_replaced', 'local_imageplus')
            ];

            if ($stats['db_files_replaced'] > 0) {
                $data->stats[] = [
                    'number' => $stats['db_files_replaced'],
                    'label' => get_string('stats_dbreplaced', 'local_imageplus')
                ];
            }

            if ($stats['files_failed'] > 0) {
                $data->stats[] = [
                    'number' => $stats['files_failed'],
                    'label' => get_string('stats_failed', 'local_imageplus')
                ];
            }
        }

        // No files replaced message.
        $data->no_files_replaced = !$this->scan_only && empty($successful_fs) && empty($successful_db);
        if ($data->no_files_replaced) {
            $data->no_files_message = get_string('nofilesreplaced', 'local_imageplus') . '<br>' .
                get_string('nofilesreplaced_desc', 'local_imageplus');
        }

        // Filesystem files.
        $data->filesystem_files = [];
        if (!$this->scan_only && !empty($successful_fs)) {
            $data->filesystem_header = get_string('filesreplaced_fs', 'local_imageplus');
            foreach ($successful_fs as $entry) {
                $filepath = $entry['filename'];
                $relativepath = str_replace($CFG->dirroot . '/', '', $filepath);
                $data->filesystem_files[] = [
                    'url' => new \moodle_url('/' . $relativepath),
                    'basename' => basename($filepath),
                    'filepath' => s($filepath),
                    'message' => s($entry['message']),
                    'view_file_label' => get_string('viewfile', 'local_imageplus')
                ];
            }
        }

        // Database files.
        $data->database_files = [];
        if (!$this->scan_only && !empty($successful_db)) {
            $data->database_header = get_string('filesreplaced_db', 'local_imageplus');
            foreach ($successful_db as $entry) {
                $filename = $entry['filename'];
                $fileurl = null;

                if (!empty($entry['contextid']) && !empty($entry['component']) && !empty($entry['filearea'])) {
                    try {
                        $fs = get_file_storage();
                        $storedfile = $fs->get_file(
                            $entry['contextid'],
                            $entry['component'],
                            $entry['filearea'],
                            isset($entry['itemid']) ? $entry['itemid'] : 0,
                            !empty($entry['filepath']) ? $entry['filepath'] : '/',
                            $filename
                        );

                        if ($storedfile && !$storedfile->is_directory()) {
                            $fileurl = \moodle_url::make_pluginfile_url(
                                $entry['contextid'],
                                $entry['component'],
                                $entry['filearea'],
                                isset($entry['itemid']) ? $entry['itemid'] : null,
                                !empty($entry['filepath']) ? $entry['filepath'] : '/',
                                $filename,
                                false
                            );
                        }
                    } catch (\Exception $e) {
                        $fileurl = null;
                    }
                }

                $description = '';
                if (!empty($entry['component']) && !empty($entry['filearea'])) {
                    $description = s($entry['component']) . ' / ' . s($entry['filearea']);
                }

                $data->database_files[] = [
                    'url' => $fileurl,
                    'filename' => s($filename),
                    'description' => $description,
                    'message' => s($entry['message']),
                    'view_file_label' => get_string('viewfile', 'local_imageplus')
                ];
            }
        }

        // Processing output.
        $data->has_output = !$this->scan_only && !empty($this->replacer->get_output());
        if ($data->has_output) {
            $data->output_title = get_string('processingoutput', 'local_imageplus');
            $data->output_lines = [];
            foreach ($this->replacer->get_output() as $msg) {
                $data->output_lines[] = [
                    'type' => $msg['type'],
                    'message' => htmlspecialchars($msg['message'])
                ];
            }
        }

        // Completion message.
        if (!$this->scan_only) {
            $completemsg = get_string('operationcomplete', 'local_imageplus') . ' ';
            if ($stats['files_replaced'] > 0 || $stats['db_files_replaced'] > 0) {
                $completemsg .= get_string('operationcomplete_execute', 'local_imageplus');
                $cachepurgeurl = new \moodle_url('/admin/purgecaches.php', ['confirm' => 1, 'sesskey' => sesskey()]);
                $completemsg .= ' ' . get_string('operationcomplete_clearcache', 'local_imageplus', $cachepurgeurl->out());
                $data->completion_class = 'alert-success';
            } else {
                $data->completion_class = 'alert-info';
            }
            $data->completion_message = $completemsg;
        }

        // Donation message.
        $data->donation_message = get_string('donation_message', 'local_imageplus');

        // Start over button.
        $data->startover_url = new \moodle_url('/local/imageplus/index.php');
        $data->startover_label = get_string('startover', 'local_imageplus');

        return $data;
    }
}
