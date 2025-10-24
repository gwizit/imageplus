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
 * No files found renderable for ImagePlus plugin.
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
 * No files found renderable class.
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class no_files_found implements renderable, templatable {

    /** @var string Search term */
    protected $search_term;

    /**
     * Constructor.
     *
     * @param string $search_term Search term
     */
    public function __construct($search_term) {
        $this->search_term = $search_term;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        $data->message = get_string('nofilesfound_desc', 'local_imageplus', s($this->search_term));
        $data->startover_url = new \moodle_url('/local/imageplus/index.php', ['startover' => 1]);
        $data->startover_label = get_string('startover', 'local_imageplus');

        return $data;
    }
}
