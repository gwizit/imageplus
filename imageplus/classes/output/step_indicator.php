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
 * Step indicator renderable for ImagePlus plugin.
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
 * Step indicator renderable class.
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class step_indicator implements renderable, templatable {

    /** @var int Current step */
    protected $current_step;

    /**
     * Constructor.
     *
     * @param int $current_step Current step number
     */
    public function __construct($current_step) {
        $this->current_step = $current_step;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        $steps = [
            1 => get_string('step1_name', 'local_imageplus'),
            2 => get_string('step2_name', 'local_imageplus'),
            3 => get_string('step3_name', 'local_imageplus'),
        ];

        $data->steps = [];
        foreach ($steps as $num => $name) {
            $status = 'upcoming';
            if ($num == $this->current_step) {
                $status = 'current';
            } else if ($num < $this->current_step) {
                $status = 'completed';
            }

            $data->steps[] = [
                'number' => $num,
                'name' => $name,
                'status' => $status
            ];
        }

        return $data;
    }
}
