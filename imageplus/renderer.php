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
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for image replacer plugin
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_imageplus_renderer extends plugin_renderer_base {

    /**
     * Render results page using the output API and templates.
     *
     * @param \local_imageplus\replacer $replacer Replacer instance
     * @param array $filesystem_files File system files
     * @param array $database_files Database files
     * @param bool $scan_only Whether this is scan only
     * @return string HTML output
     */
    public function render_results($replacer, $filesystem_files, $database_files, $scan_only) {
        // Use the new output renderable and template.
        $results = new \local_imageplus\output\results($replacer, $filesystem_files, $database_files, $scan_only);
        return $this->render_from_template('local_imageplus/results', $results->export_for_template($this));
    }

    /**
     * Render file selection page using the output API and templates.
     *
     * @param array $filesystem_files Filesystem files
     * @param array $database_files Database files
     * @param string $search_term Search term
     * @return string HTML output
     */
    public function render_file_selection($filesystem_files, $database_files, $search_term = '') {
        $fileselection = new \local_imageplus\output\file_selection($filesystem_files, $database_files, $search_term);
        return $this->render_from_template('local_imageplus/file_selection', $fileselection->export_for_template($this));
    }

    /**
     * Render step indicator using the output API and templates.
     *
     * @param int $current_step Current step number
     * @return string HTML output
     */
    public function render_step_indicator($current_step) {
        $stepindicator = new \local_imageplus\output\step_indicator($current_step);
        return $this->render_from_template('local_imageplus/step_indicator', $stepindicator->export_for_template($this));
    }

    /**
     * Render no files found message using the output API and templates.
     *
     * @param string $search_term Search term
     * @return string HTML output
     */
    public function render_no_files_found($search_term) {
        $nofilesfound = new \local_imageplus\output\no_files_found($search_term);
        return $this->render_from_template('local_imageplus/no_files_found', $nofilesfound->export_for_template($this));
    }
}
