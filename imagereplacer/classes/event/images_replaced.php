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
 * Images replaced event
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

namespace local_imagereplacer\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Images replaced event class
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class images_replaced extends \core\event\base {

    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Get name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventimagereplaced', 'local_imagereplacer');
    }

    /**
     * Get description
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' replaced images matching term '{$this->other['searchterm']}'. " .
            "Files replaced: {$this->other['filesreplaced']}, DB files replaced: {$this->other['dbfilesreplaced']}.";
    }

    /**
     * Get URL
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/imagereplacer/index.php');
    }
}
