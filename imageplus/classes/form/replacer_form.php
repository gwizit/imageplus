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
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

namespace local_imageplus\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for image replacer tool
 *
 * @package    local_imageplus
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
        $step = isset($this->_customdata['step']) ? $this->_customdata['step'] : 1;

        // Add step indicator.
        $mform->addElement('html', $this->render_step_indicator($step));

        if ($step == 1) {
            $this->definition_step1($formdata);
        } else if ($step == 2) {
            $this->definition_step2($formdata);
        } else if ($step == 3) {
            $this->definition_step3($formdata);
        }

        // Session key.
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        // Current step.
        $mform->addElement('hidden', 'step', $step);
        $mform->setType('step', PARAM_INT);
    }

    /**
     * Step 1: Search criteria
     */
    protected function definition_step1($formdata) {
        $mform = $this->_form;

        // Section header.
        $mform->addElement('header', 'step1header', get_string('step1_header', 'local_imageplus'));
        $mform->setExpanded('step1header', true);

        // Search term.
        $mform->addElement('text', 'searchterm', get_string('searchterm', 'local_imageplus'),
            ['size' => '50']);
        $mform->setType('searchterm', PARAM_TEXT);
        $mform->addRule('searchterm', null, 'required', null, 'client');
        $mform->addHelpButton('searchterm', 'searchterm', 'local_imageplus');
        $mform->setDefault('searchterm', $formdata->searchterm);

        // File type selector.
        $filetypeoptions = [
            'image' => get_string('filetype_image', 'local_imageplus'),
            'pdf' => get_string('filetype_pdf', 'local_imageplus'),
            'zip' => get_string('filetype_zip', 'local_imageplus'),
            'doc' => get_string('filetype_doc', 'local_imageplus'),
            'video' => get_string('filetype_video', 'local_imageplus'),
            'audio' => get_string('filetype_audio', 'local_imageplus'),
        ];
        $mform->addElement('select', 'filetype', get_string('filetype', 'local_imageplus'),
            $filetypeoptions);
        $mform->addHelpButton('filetype', 'filetype', 'local_imageplus');
        $mform->setDefault('filetype', isset($formdata->filetype) ? $formdata->filetype : 'image');

        // Search database.
        $mform->addElement('advcheckbox', 'searchdatabase',
            get_string('searchdatabase', 'local_imageplus'));
        $mform->addHelpButton('searchdatabase', 'searchdatabase', 'local_imageplus');
        $mform->setDefault('searchdatabase', $formdata->searchdatabase);

        // Search file system.
        $mform->addElement('advcheckbox', 'searchfilesystem',
            get_string('searchfilesystem', 'local_imageplus'));
        $mform->addHelpButton('searchfilesystem', 'searchfilesystem', 'local_imageplus');
        $mform->setDefault('searchfilesystem', $formdata->searchfilesystem);

        // Action button.
        $this->add_action_buttons(false, get_string('findbtn', 'local_imageplus'));
    }

    /**
     * Step 2: File selection
     */
    protected function definition_step2($formdata) {
        $mform = $this->_form;

        // Section header.
        $mform->addElement('header', 'step2header', get_string('step2_header', 'local_imageplus'));
        $mform->setExpanded('step2header', true);

        // Display search criteria summary.
        $summary = \html_writer::div(
            \html_writer::tag('strong', get_string('searchterm', 'local_imageplus') . ': ') . 
            s($formdata->searchterm) . '<br>' .
            \html_writer::tag('strong', get_string('filetype', 'local_imageplus') . ': ') . 
            s($formdata->filetype),
            'alert alert-info'
        );
        $mform->addElement('html', $summary);

        // Files will be displayed via custom rendering in index.php
        // This is just a placeholder for the form structure
        $mform->addElement('html', '<div id="file-selection-area"></div>');

        // Hidden fields to preserve step 1 data.
        $mform->addElement('hidden', 'searchterm', $formdata->searchterm);
        $mform->setType('searchterm', PARAM_TEXT);
        $mform->addElement('hidden', 'filetype', $formdata->filetype);
        $mform->setType('filetype', PARAM_ALPHA);
        $mform->addElement('hidden', 'searchdatabase', $formdata->searchdatabase);
        $mform->setType('searchdatabase', PARAM_INT);
        $mform->addElement('hidden', 'searchfilesystem', $formdata->searchfilesystem);
        $mform->setType('searchfilesystem', PARAM_INT);

        // Action buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'backbtn', get_string('back', 'local_imageplus'));
        $buttonarray[] = $mform->createElement('submit', 'nextbtn', get_string('next', 'local_imageplus'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Step 3: Replacement options and confirmation
     */
    protected function definition_step3($formdata) {
        $mform = $this->_form;

        // Section header.
        $mform->addElement('header', 'step3header', get_string('step3_header', 'local_imageplus'));
        $mform->setExpanded('step3header', true);

        // Determine accepted file types and instruction message based on Step 1 selection.
        $filetype = isset($formdata->filetype) ? $formdata->filetype : 'image';
        $acceptedtypes = '*';
        $filetypename = '';
        
        switch ($filetype) {
            case 'image':
                $acceptedtypes = ['.jpg', '.jpeg', '.png', '.webp'];
                $filetypename = get_string('filetype_image_name', 'local_imageplus');
                break;
            case 'pdf':
                $acceptedtypes = ['.pdf'];
                $filetypename = get_string('filetype_pdf_name', 'local_imageplus');
                break;
            case 'zip':
                $acceptedtypes = ['.zip'];
                $filetypename = get_string('filetype_zip_name', 'local_imageplus');
                break;
            case 'doc':
                $acceptedtypes = ['.doc', '.docx', '.odt', '.txt'];
                $filetypename = get_string('filetype_doc_name', 'local_imageplus');
                break;
            case 'video':
                $acceptedtypes = ['.mp4', '.avi', '.mov', '.webm'];
                $filetypename = get_string('filetype_video_name', 'local_imageplus');
                break;
            case 'audio':
                $acceptedtypes = ['.mp3', '.wav', '.ogg', '.m4a'];
                $filetypename = get_string('filetype_audio_name', 'local_imageplus');
                break;
        }
        
        // Add instruction message.
        $instruction = \html_writer::div(
            get_string('uploadfile_instruction', 'local_imageplus', $filetypename),
            'alert alert-info'
        );
        $mform->addElement('html', $instruction);

        // Convert accepted types array to string format for HTML5 accept attribute.
        $acceptstring = '';
        if (is_array($acceptedtypes)) {
            $acceptstring = implode(',', $acceptedtypes);
        } else {
            $acceptstring = $acceptedtypes;
        }

        // Source file - use filepicker with better UI (similar to plugin upload page).
        $mform->addElement('filepicker', 'sourceimage', get_string('sourcefile', 'local_imageplus'),
            null, [
                'maxbytes' => 52428800, // 50MB
                'accepted_types' => $acceptedtypes, // Restrict to correct file types
            ]);
        $mform->addRule('sourceimage', null, 'required', null, 'client');
        $mform->addHelpButton('sourceimage', 'sourcefile', 'local_imageplus');

        // Check GD library availability.
        $gdavailable = \local_imageplus\replacer::is_gd_available();
        
        // Show GD warning if not available.
        if (!$gdavailable) {
            $mform->addElement('static', 'gd_warning', '',
                \html_writer::div(get_string('warning_nogd', 'local_imageplus'), 'alert alert-warning'));
        }

        // Preserve permissions.
        $mform->addElement('advcheckbox', 'preservepermissions',
            get_string('preservepermissions', 'local_imageplus'));
        $mform->addHelpButton('preservepermissions', 'preservepermissions', 'local_imageplus');
        $mform->setDefault('preservepermissions', $formdata->preservepermissions);

        // Execution mode.
        $modeoptions = [
            'preview' => get_string('mode_preview', 'local_imageplus'),
            'execute' => get_string('mode_execute', 'local_imageplus'),
        ];
        $mform->addElement('select', 'executionmode', get_string('executionmode', 'local_imageplus'),
            $modeoptions);
        $mform->addHelpButton('executionmode', 'executionmode', 'local_imageplus');
        $mform->setDefault('executionmode', $formdata->executionmode);

        // Allow cross-format image replacement (only for images and if GD is available).
        if ($gdavailable && isset($formdata->filetype) && $formdata->filetype === 'image') {
            $mform->addElement('advcheckbox', 'allowimageconversion',
                get_string('allowimageconversion', 'local_imageplus'));
            $mform->addHelpButton('allowimageconversion', 'allowimageconversion', 'local_imageplus');
            $mform->setDefault('allowimageconversion', isset($formdata->allowimageconversion) ? $formdata->allowimageconversion : 1);
        }

        // Backup confirmation checkbox.
        $mform->addElement('advcheckbox', 'backupconfirm',
            get_string('backupconfirm', 'local_imageplus'));
        $mform->addRule('backupconfirm', get_string('backupconfirm_required', 'local_imageplus'), 'required', null, 'client');
        $mform->addHelpButton('backupconfirm', 'backupconfirm', 'local_imageplus');

        // Final warning.
        $warning = \html_writer::div(
            get_string('final_warning', 'local_imageplus'),
            'alert alert-danger'
        );
        $mform->addElement('html', $warning);

        // Hidden fields to preserve previous steps data.
        $mform->addElement('hidden', 'searchterm', $formdata->searchterm);
        $mform->setType('searchterm', PARAM_TEXT);
        $mform->addElement('hidden', 'filetype', $formdata->filetype);
        $mform->setType('filetype', PARAM_ALPHA);
        $mform->addElement('hidden', 'searchdatabase', $formdata->searchdatabase);
        $mform->setType('searchdatabase', PARAM_INT);
        $mform->addElement('hidden', 'searchfilesystem', $formdata->searchfilesystem);
        $mform->setType('searchfilesystem', PARAM_INT);

        // Hidden field for selected files (will be populated from session).
        if (isset($formdata->selectedfiles)) {
            $mform->addElement('hidden', 'selectedfiles', $formdata->selectedfiles);
            $mform->setType('selectedfiles', PARAM_RAW);
        }

        // Action buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'backbtn', get_string('back', 'local_imageplus'));
        $buttonarray[] = $mform->createElement('submit', 'executebtn', get_string('execute_replacement', 'local_imageplus'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->setType('backbtn', PARAM_RAW);
        
        // Add start over link.
        $startoverlink = \html_writer::link(
            new \moodle_url('/local/imageplus/index.php', ['startover' => 1]),
            get_string('startover', 'local_imageplus'),
            ['class' => 'btn btn-secondary ml-2']
        );
        $mform->addElement('html', \html_writer::div($startoverlink, 'mt-2'));
    }

    /**
     * Render step indicator
     */
    protected function render_step_indicator($currentstep) {
        $steps = [
            1 => get_string('step1_name', 'local_imageplus'),
            2 => get_string('step2_name', 'local_imageplus'),
            3 => get_string('step3_name', 'local_imageplus'),
        ];

        $html = '<div class="step-indicator mb-4">';
        $html .= '<ol class="list-inline">';
        foreach ($steps as $num => $name) {
            $class = 'list-inline-item badge ';
            if ($num == $currentstep) {
                $class .= 'badge-primary';
            } else if ($num < $currentstep) {
                $class .= 'badge-success';
            } else {
                $class .= 'badge-secondary';
            }
            $html .= '<li class="' . $class . '">' . $num . '. ' . s($name) . '</li>';
        }
        $html .= '</ol>';
        $html .= '</div>';

        return $html;
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
            $errors['searchdatabase'] = get_string('error', 'local_imageplus');
        }

        return $errors;
    }
}
