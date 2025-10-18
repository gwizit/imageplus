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
 * Image replacer form
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

namespace local_imagereplacer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for image replacer tool
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replacer_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;
        $formdata = $this->_customdata['formdata'];

        // Search term.
        $mform->addElement('text', 'searchterm', get_string('searchterm', 'local_imagereplacer'),
            ['size' => '50']);
        $mform->setType('searchterm', PARAM_TEXT);
        $mform->addRule('searchterm', null, 'required', null, 'client');
        $mform->addHelpButton('searchterm', 'searchterm', 'local_imagereplacer');
        $mform->setDefault('searchterm', $formdata->searchterm);

        // File type selector.
        $filetypeoptions = [
            'all' => get_string('filetype_all', 'local_imagereplacer'),
            'image' => get_string('filetype_image', 'local_imagereplacer'),
            'pdf' => get_string('filetype_pdf', 'local_imagereplacer'),
            'zip' => get_string('filetype_zip', 'local_imagereplacer'),
            'doc' => get_string('filetype_doc', 'local_imagereplacer'),
            'video' => get_string('filetype_video', 'local_imagereplacer'),
            'audio' => get_string('filetype_audio', 'local_imagereplacer'),
        ];
        $mform->addElement('select', 'filetype', get_string('filetype', 'local_imagereplacer'),
            $filetypeoptions);
        $mform->addHelpButton('filetype', 'filetype', 'local_imagereplacer');
        $mform->setDefault('filetype', isset($formdata->filetype) ? $formdata->filetype : 'image');

        // Source image - use filemanager with minimal options to avoid repository picker.
        $mform->addElement('filemanager', 'sourceimage', get_string('sourcefile', 'local_imagereplacer'),
            null, [
                'subdirs' => 0,
                'maxbytes' => 52428800, // 50MB
                'maxfiles' => 1,
                'accepted_types' => '*', // Accept all file types
                'return_types' => FILE_INTERNAL,
            ]);
        $mform->addRule('sourceimage', null, 'required', null, 'client');
        $mform->addHelpButton('sourceimage', 'sourcefile', 'local_imagereplacer');

        // Execution mode.
        $modeoptions = [
            'preview' => get_string('mode_preview', 'local_imagereplacer'),
            'execute' => get_string('mode_execute', 'local_imagereplacer'),
        ];
        $mform->addElement('select', 'executionmode', get_string('executionmode', 'local_imagereplacer'),
            $modeoptions);
        $mform->addHelpButton('executionmode', 'executionmode', 'local_imagereplacer');
        $mform->setDefault('executionmode', $formdata->executionmode);

        // Preserve permissions.
        $mform->addElement('advcheckbox', 'preservepermissions',
            get_string('preservepermissions', 'local_imagereplacer'));
        $mform->addHelpButton('preservepermissions', 'preservepermissions', 'local_imagereplacer');
        $mform->setDefault('preservepermissions', $formdata->preservepermissions);

        // Search database.
        $mform->addElement('advcheckbox', 'searchdatabase',
            get_string('searchdatabase', 'local_imagereplacer'));
        $mform->addHelpButton('searchdatabase', 'searchdatabase', 'local_imagereplacer');
        $mform->setDefault('searchdatabase', $formdata->searchdatabase);

        // Search file system.
        $mform->addElement('advcheckbox', 'searchfilesystem',
            get_string('searchfilesystem', 'local_imagereplacer'));
        $mform->addHelpButton('searchfilesystem', 'searchfilesystem', 'local_imagereplacer');
        $mform->setDefault('searchfilesystem', $formdata->searchfilesystem);

        // Action buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'searchbtn', get_string('findbtn', 'local_imagereplacer'),
            ['onclick' => 'this.form.action.value="search"']);
        $buttonarray[] = $mform->createElement('submit', 'replacebtn', get_string('replacebtn', 'local_imagereplacer'),
            ['onclick' => 'this.form.action.value="replace"']);
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden action field.
        $mform->addElement('hidden', 'action', 'search');
        $mform->setType('action', PARAM_ALPHA);

        // Session key.
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);
    }

    /**
     * Validation
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['searchterm'])) {
            $errors['searchterm'] = get_string('required');
        }

        if (!isset($data['searchdatabase']) && !isset($data['searchfilesystem'])) {
            $errors['searchdatabase'] = get_string('error', 'local_imagereplacer');
        }

        return $errors;
    }
}
