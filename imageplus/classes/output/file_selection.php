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
 * File selection renderable for ImagePlus plugin.
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
 * File selection renderable class.
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_selection implements renderable, templatable {

    /** @var array Filesystem files */
    protected $filesystem_files;

    /** @var array Database files */
    protected $database_files;

    /** @var string Search term */
    protected $search_term;

    /**
     * Constructor.
     *
     * @param array $filesystem_files Filesystem files
     * @param array $database_files Database files
     * @param string $search_term Search term
     */
    public function __construct($filesystem_files, $database_files, $search_term = '') {
        $this->filesystem_files = $filesystem_files;
        $this->database_files = $database_files;
        $this->search_term = $search_term;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $PAGE;

        $data = new stdClass();

        $data->form_action = $PAGE->url->out(false);
        $data->sesskey = sesskey();
        $data->select_files_message = get_string('selectfilestoreplace', 'local_imageplus');

        // Filesystem files.
        $data->has_filesystem_files = !empty($this->filesystem_files);
        if ($data->has_filesystem_files) {
            $data->filesystem_header = get_string('filesystemresults', 'local_imageplus');
            $data->filesystem_files = [];
            foreach ($this->filesystem_files as $file) {
                $relativepath = str_replace($CFG->dirroot . '/', '', $file);
                $data->filesystem_files[] = [
                    'filepath' => $file,
                    'checkbox_id' => 'fs_' . md5($file),
                    'basename' => basename($file),
                    'url' => new \moodle_url('/' . $relativepath),
                    'view_file_label' => get_string('viewfile', 'local_imageplus')
                ];
            }
        }

        // Database files.
        $data->has_database_files = !empty($this->database_files);
        if ($data->has_database_files) {
            $data->database_header = get_string('databaseresults', 'local_imageplus');
            $data->database_files = [];
            foreach ($this->database_files as $file) {
                $fileurl = null;
                if (!empty($file->contextid) && !empty($file->component) && !empty($file->filearea)) {
                    try {
                        $fs = get_file_storage();
                        $storedfile = $fs->get_file(
                            $file->contextid,
                            $file->component,
                            $file->filearea,
                            $file->itemid,
                            $file->filepath,
                            $file->filename
                        );

                        if ($storedfile && !$storedfile->is_directory()) {
                            $fileurl = \moodle_url::make_pluginfile_url(
                                $file->contextid,
                                $file->component,
                                $file->filearea,
                                $file->itemid,
                                $file->filepath,
                                $file->filename,
                                false
                            );
                        }
                    } catch (\Exception $e) {
                        $fileurl = null;
                    }
                }

                $description = '';
                if (!empty($file->component) && !empty($file->filearea)) {
                    $description .= s($file->component) . ' / ' . s($file->filearea);
                }
                $description .= ' • ID: ' . (int)$file->id . ' • ' . display_size($file->filesize);
                if (!empty($file->mimetype)) {
                    $description .= ' • ' . s($file->mimetype);
                }

                $data->database_files[] = [
                    'file_id' => (int)$file->id,
                    'checkbox_id' => 'db_' . (int)$file->id,
                    'filename' => s($file->filename),
                    'url' => $fileurl,
                    'description' => $description,
                    'view_file_label' => get_string('viewfile', 'local_imageplus')
                ];
            }
        }

        // Labels.
        $data->select_all_label = get_string('selectall', 'local_imageplus');
        $data->deselect_all_label = get_string('deselectall', 'local_imageplus');
        $data->warning_text = get_string('warning_selectall', 'local_imageplus');
        $data->back_label = get_string('back', 'local_imageplus');
        $data->next_label = get_string('next', 'local_imageplus');

        return $data;
    }
}
