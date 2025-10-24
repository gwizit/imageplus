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
        $form_data = $this->_customdata['formdata'];
        $step = isset($this->_customdata['step']) ? $this->_customdata['step'] : 1;

        // Add step indicator.
        $mform->addElement('html', $this->render_step_indicator($step));

        if ($step == 1) {
            $this->definition_step1($form_data);
        } else if ($step == 2) {
            $this->definition_step2($form_data);
        } else if ($step == 3) {
            $this->definition_step3($form_data);
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
    protected function definition_step1($form_data) {
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
        $mform->setDefault('searchterm', $form_data->searchterm);

        // File type selector.
        $file_type_options = [
            'image' => get_string('filetype_image', 'local_imageplus'),
            'pdf' => get_string('filetype_pdf', 'local_imageplus'),
            'zip' => get_string('filetype_zip', 'local_imageplus'),
            'doc' => get_string('filetype_doc', 'local_imageplus'),
            'video' => get_string('filetype_video', 'local_imageplus'),
            'audio' => get_string('filetype_audio', 'local_imageplus'),
        ];
        $mform->addElement('select', 'filetype', get_string('filetype', 'local_imageplus'),
            $file_type_options);
        $mform->addHelpButton('filetype', 'filetype', 'local_imageplus');
        $mform->setDefault('filetype', isset($form_data->filetype) ? $form_data->filetype : 'image');

        // Search database.
        $mform->addElement('advcheckbox', 'searchdatabase',
            get_string('searchdatabase', 'local_imageplus'));
        $mform->addHelpButton('searchdatabase', 'searchdatabase', 'local_imageplus');
        $mform->setDefault('searchdatabase', $form_data->searchdatabase);

        // Search file system.
        $mform->addElement('advcheckbox', 'searchfilesystem',
            get_string('searchfilesystem', 'local_imageplus'));
        $mform->addHelpButton('searchfilesystem', 'searchfilesystem', 'local_imageplus');
        $mform->setDefault('searchfilesystem', $form_data->searchfilesystem);

        // Action button.
        $this->add_action_buttons(false, get_string('findbtn', 'local_imageplus'));
    }

    /**
     * Step 2: File selection
     */
    protected function definition_step2($form_data) {
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
        $mform->addElement('hidden', 'searchfilesystem', $form_data->searchfilesystem);
        $mform->setType('searchfilesystem', PARAM_INT);

        // Action buttons.
        $button_array = [];
        $button_array[] = $mform->createElement('submit', 'backbtn', get_string('back', 'local_imageplus'));
        $button_array[] = $mform->createElement('submit', 'nextbtn', get_string('next', 'local_imageplus'));
        $mform->addGroup($button_array, 'buttonar', '', [' '], false);
    }

    /**
     * Step 3: Replacement options and confirmation
     */
    protected function definition_step3($form_data) {
        $mform = $this->_form;

        // Section header.
        $mform->addElement('header', 'step3header', get_string('step3_header', 'local_imageplus'));
        $mform->setExpanded('step3header', true);

        // Determine accepted file types and instruction message based on Step 1 selection.
        $file_type = isset($form_data->filetype) ? $form_data->filetype : 'image';
        $accepted_types = '*';
        $file_type_name = '';
        
        switch ($file_type) {
            case 'image':
                $accepted_types = ['.jpg', '.jpeg', '.png', '.webp'];
                $file_type_name = get_string('filetype_image_name', 'local_imageplus');
                break;
            case 'pdf':
                $accepted_types = ['.pdf'];
                $file_type_name = get_string('filetype_pdf_name', 'local_imageplus');
                break;
            case 'zip':
                $accepted_types = ['.zip'];
                $file_type_name = get_string('filetype_zip_name', 'local_imageplus');
                break;
            case 'doc':
                $accepted_types = ['.doc', '.docx', '.odt', '.txt'];
                $file_type_name = get_string('filetype_doc_name', 'local_imageplus');
                break;
            case 'video':
                $accepted_types = ['.mp4', '.avi', '.mov', '.webm'];
                $file_type_name = get_string('filetype_video_name', 'local_imageplus');
                break;
            case 'audio':
                $accepted_types = ['.mp3', '.wav', '.ogg', '.m4a'];
                $file_type_name = get_string('filetype_audio_name', 'local_imageplus');
                break;
        }
        
        // Add instruction message.
        $instruction = \html_writer::div(
            get_string('uploadfile_instruction', 'local_imageplus', $file_type_name),
            'alert alert-info'
        );
        $mform->addElement('html', $instruction);

        // Convert accepted types array to string format for HTML5 accept attribute.
        $accept_string = '';
        if (is_array($accepted_types)) {
            $accept_string = implode(',', $accepted_types);
        } else {
            $accept_string = $accepted_types;
        }

        // Source file - use filepicker with better UI (similar to plugin upload page).
        $mform->addElement('filepicker', 'sourceimage', get_string('sourcefile', 'local_imageplus'),
            null, [
                'maxbytes' => 52428800, // 50MB
                'accepted_types' => $accepted_types, // Restrict to correct file types
            ]);
        $mform->addRule('sourceimage', null, 'required', null, 'client');
        $mform->addHelpButton('sourceimage', 'sourcefile', 'local_imageplus');

        // Check GD library availability.
        $gd_available = \local_imageplus\replacer::is_gd_available();
        
        // Show GD warning if not available.
        if (!$gd_available) {
            $mform->addElement('static', 'gd_warning', '',
                \html_writer::div(get_string('warning_nogd', 'local_imageplus'), 'alert alert-warning'));
        }

        // Preserve permissions.
        $mform->addElement('advcheckbox', 'preservepermissions',
            get_string('preservepermissions', 'local_imageplus'));
        $mform->addHelpButton('preservepermissions', 'preservepermissions', 'local_imageplus');
        $mform->setDefault('preservepermissions', $form_data->preservepermissions);

        // Execution mode.
        $mode_options = [
            'preview' => get_string('mode_preview', 'local_imageplus'),
            'execute' => get_string('mode_execute', 'local_imageplus'),
        ];
        $mform->addElement('select', 'executionmode', get_string('executionmode', 'local_imageplus'),
            $mode_options);
        $mform->addHelpButton('executionmode', 'executionmode', 'local_imageplus');
        $mform->setDefault('executionmode', $form_data->executionmode);

        // Allow cross-format image replacement (only for images and if GD is available).
        if ($gd_available && isset($form_data->filetype) && $form_data->filetype === 'image') {
            $mform->addElement('advcheckbox', 'allowimageconversion',
                get_string('allowimageconversion', 'local_imageplus'));
            $mform->addHelpButton('allowimageconversion', 'allowimageconversion', 'local_imageplus');
            $mform->setDefault('allowimageconversion', isset($form_data->allowimageconversion) ? $form_data->allowimageconversion : 1);
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
        $mform->addElement('hidden', 'searchterm', $form_data->searchterm);
        $mform->setType('searchterm', PARAM_TEXT);
        $mform->addElement('hidden', 'filetype', $form_data->filetype);
        $mform->setType('filetype', PARAM_ALPHA);
        $mform->addElement('hidden', 'searchdatabase', $form_data->searchdatabase);
        $mform->setType('searchdatabase', PARAM_INT);
        $mform->addElement('hidden', 'searchfilesystem', $form_data->searchfilesystem);
        $mform->setType('searchfilesystem', PARAM_INT);

        // Hidden field for selected files (will be populated from session).
        if (isset($form_data->selectedfiles)) {
            $mform->addElement('hidden', 'selectedfiles', $form_data->selectedfiles);
            $mform->setType('selectedfiles', PARAM_RAW);
        }

        // Action buttons.
        $button_array = [];
        $button_array[] = $mform->createElement('submit', 'backbtn', get_string('back', 'local_imageplus'));
        $button_array[] = $mform->createElement('submit', 'executebtn', get_string('execute_replacement', 'local_imageplus'));
        $mform->addGroup($button_array, 'buttonar', '', [' '], false);
        $mform->setType('backbtn', PARAM_RAW);
        
        // Add start over link.
        $start_over_link = \html_writer::link(
            new \moodle_url('/local/imageplus/index.php', ['startover' => 1]),
            get_string('startover', 'local_imageplus'),
            ['class' => 'btn btn-secondary ml-2']
        );
        $mform->addElement('html', \html_writer::div($start_over_link, 'mt-2'));
    }

    /**
     * Render step indicator
     */
    protected function render_step_indicator($current_step) {
        $steps = [
            1 => get_string('step1_name', 'local_imageplus'),
            2 => get_string('step2_name', 'local_imageplus'),
            3 => get_string('step3_name', 'local_imageplus'),
        ];

        $html = '<div class="step-indicator mb-4">';
        $html .= '<ol class="list-inline">';
        foreach ($steps as $num => $name) {
            $class = 'list-inline-item badge ';
            if ($num == $current_step) {
                $class .= 'badge-primary';
            } else if ($num < $current_step) {
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
