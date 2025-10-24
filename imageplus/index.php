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
 * Image Replacer main interface
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_imageplus_tool');

require_login();

// Require site administrator permission.
$system_context = context_system::instance();
if (!has_capability('moodle/site:config', $system_context)) {
    // Display error page for non-administrators.
    $PAGE->set_url(new moodle_url('/local/imageplus/index.php'));
    $PAGE->set_context($system_context);
    $PAGE->set_title(get_string('pluginname', 'local_imageplus'));
    $PAGE->set_heading(get_string('heading', 'local_imageplus'));
    
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('error_requiresiteadmin', 'local_imageplus'),
        \core\output\notification::NOTIFY_ERROR
    );
    echo $OUTPUT->footer();
    exit;
}

require_capability('local/imageplus:view', context_system::instance());

$PAGE->set_url(new moodle_url('/local/imageplus/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_imageplus'));
$PAGE->set_heading(get_string('heading', 'local_imageplus'));

// Initialize cache for wizard data.
$cache = cache::make('local_imageplus', 'wizarddata');

// Get default settings.
$default_search_term = get_config('local_imageplus', 'defaultsearchterm');
$default_mode = get_config('local_imageplus', 'defaultmode');
$default_preserve_permissions = get_config('local_imageplus', 'defaultpreservepermissions');
$default_search_database = get_config('local_imageplus', 'defaultsearchdatabase');
$default_search_filesystem = get_config('local_imageplus', 'defaultsearchfilesystem');

// Set defaults if not configured.
if ($default_search_term === false) {
    $default_search_term = '';
}
if ($default_mode === false) {
    $default_mode = 'preview';
}
if ($default_preserve_permissions === false) {
    $default_preserve_permissions = 0;
}
if ($default_search_database === false) {
    $default_search_database = 1;
}
if ($default_search_filesystem === false) {
    $default_search_filesystem = 0;
}

// Get current step.
$step = optional_param('step', 1, PARAM_INT);
$back_btn = optional_param('backbtn', '', PARAM_RAW);
$next_btn = optional_param('nextbtn', '', PARAM_RAW);
$execute_btn = optional_param('executebtn', '', PARAM_RAW);

// Handle "Start Over" by clearing session.
$start_over = optional_param('startover', '', PARAM_RAW);
if ($start_over) {
    $cache->delete('wizard');
    redirect($PAGE->url);
}

// Initialize or retrieve session data.
$wizard_data = $cache->get('wizard');
if (!$wizard_data) {
    $wizard_data = new stdClass();
    $wizard_data->searchterm = $default_search_term;
    $wizard_data->filetype = 'image';
    $wizard_data->searchdatabase = $default_search_database;
    $wizard_data->searchfilesystem = $default_search_filesystem;
    $wizard_data->preservepermissions = $default_preserve_permissions;
    $wizard_data->executionmode = $default_mode;
    $wizard_data->allowimageconversion = 1;
    $wizard_data->filesystemfiles = [];
    $wizard_data->databasefiles = [];
    $wizard_data->selectedfilesystem = [];
    $wizard_data->selecteddatabase = [];
    $cache->set('wizard', $wizard_data);
}

// Prepare form custom data.
$form_data = clone $wizard_data;
$custom_data = [
    'formdata' => $form_data,
    'step' => $step,
];

// Create form.
$mform = new \local_imageplus\form\replacer_form(null, $custom_data);

// STEP 2: Handle file selection separately (uses custom HTML form, not moodleform)
if ($step == 2 && $next_btn) {
    require_sesskey();
    require_capability('moodle/site:config', context_system::instance());
    require_capability('local/imageplus:manage', context_system::instance());
    
    // Get selected files from submitted form - sanitize input.
    $selected_filesystem = optional_param_array('filesystem_files', [], PARAM_PATH);
    $selected_database = optional_param_array('database_files', [], PARAM_INT);
    
    // Validate at least one file is selected.
    if (empty($selected_filesystem) && empty($selected_database)) {
        redirect($PAGE->url . '?step=2', get_string('error_nofilesselected', 'local_imageplus'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Validate filesystem files exist and are within allowed paths (prevent directory traversal).
    $validated_filesystem = [];
    foreach ($selected_filesystem as $filepath) {
        $clean_path = clean_param($filepath, PARAM_PATH);
        // Ensure the file is within the Moodle dirroot and exists.
        $full_path = realpath($clean_path);
        if ($full_path && strpos($full_path, $CFG->dirroot) === 0 && file_exists($full_path)) {
            $validated_filesystem[] = $clean_path;
        }
    }
    
    // Save validated selections.
    $wizard_data->selectedfilesystem = $validated_filesystem;
    $wizard_data->selecteddatabase = $selected_database;
    $cache->set('wizard', $wizard_data);
    
    // Move to step 3.
    $step = 3;
    $custom_data['step'] = $step;
    $custom_data['formdata'] = $wizard_data;
    
    $mform = new \local_imageplus\form\replacer_form(null, $custom_data);
}

// STEP 2: Handle back button separately
if ($step == 2 && $back_btn) {
    require_sesskey();
    $step = 1;
    $custom_data['step'] = $step;
    $custom_data['formdata'] = $wizard_data;
    $mform = new \local_imageplus\form\replacer_form(null, $custom_data);
}

// STEP 3: Handle back button separately (before form validation)
if ($step == 3 && $back_btn) {
    require_sesskey();
    $step = 2;
    $custom_data['step'] = $step;
    $custom_data['formdata'] = $wizard_data;
    $mform = new \local_imageplus\form\replacer_form(null, $custom_data);
}

// Handle form submission (for steps 1 and 3 only - step 2 handled above)
if ($from_form = $mform->get_data()) {
    require_sesskey();
    
    // Verify site administrator permission for all form submissions.
    if (!has_capability('moodle/site:config', context_system::instance())) {
        throw new moodle_exception('error_requiresiteadmin', 'local_imageplus', '', null, 
            get_string('error_requiresiteadmin_formsubmission', 'local_imageplus'));
    }
    
    // STEP 1: Search for files
    if ($step == 1 && !$back_btn) {
        require_capability('local/imageplus:manage', context_system::instance());
        
        // Save search criteria to cache (already sanitized by moodle form).
        $wizard_data->searchterm = $from_form->searchterm;
        $wizard_data->filetype = $from_form->filetype;
        $wizard_data->searchdatabase = $from_form->searchdatabase;
        $wizard_data->searchfilesystem = $from_form->searchfilesystem;
        
        $config = [
            'search_term' => $from_form->searchterm,
            'dry_run' => true,
            'preserve_permissions' => false,
            'search_database' => (bool)$from_form->searchdatabase,
            'search_filesystem' => (bool)$from_form->searchfilesystem,
            'file_type' => $from_form->filetype,
            'allow_image_conversion' => true,
        ];
        
        $replacer = new \local_imageplus\replacer($config);
        $filesystem_files = $replacer->find_filesystem_files();
        $database_files = $replacer->find_database_files();
        
        // Store found files in cache.
        $wizard_data->filesystemfiles = $filesystem_files;
        $wizard_data->databasefiles = $database_files;
        $cache->set('wizard', $wizard_data);
        
        // Move to step 2.
        $step = 2;
        $custom_data['step'] = $step;
        $custom_data['formdata'] = $wizard_data;
        $mform = new \local_imageplus\form\replacer_form(null, $custom_data);
        
    // STEP 3: Execute replacement
    } else if ($step == 3 && $execute_btn) {
        // Double-check site administrator permission for file replacement.
        if (!has_capability('moodle/site:config', context_system::instance())) {
            throw new moodle_exception('error_requiresiteadmin', 'local_imageplus', '', null,
                get_string('error_requiresiteadmin_filereplacement', 'local_imageplus'));
        }
        
        require_capability('local/imageplus:manage', context_system::instance());
        confirm_sesskey();
        
        // Verify backup confirmation.
        if (empty($from_form->backupconfirm)) {
            redirect($PAGE->url . '?step=3', get_string('backupconfirm_required', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        // Save final options (already sanitized by form).
        $wizard_data->preservepermissions = $from_form->preservepermissions;
        $wizard_data->executionmode = $from_form->executionmode;
        if (isset($from_form->allowimageconversion)) {
            $wizard_data->allowimageconversion = $from_form->allowimageconversion;
        } else {
            // If checkbox not in form (e.g., GD not available), set to 0
            $wizard_data->allowimageconversion = 0;
        }
        $cache->set('wizard', $wizard_data);
        
        // Handle file upload from filepicker.
        $draft_item_id = $from_form->sourceimage;
        
        if (empty($draft_item_id)) {
            redirect($PAGE->url . '?step=3', get_string('error_nosourcefile', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        $fs = get_file_storage();
        $user_context = context_user::instance($USER->id);
        $files = $fs->get_area_files($user_context->id, 'user', 'draft', $draft_item_id, 'id DESC', false);
        
        if (empty($files)) {
            redirect($PAGE->url . '?step=3', get_string('error_nosourcefile', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        $file = reset($files);
        
        // Validate file type against allowed types.
        $file_type = $wizard_data->filetype;
        $allowed_mimetypes = [];
        
        switch ($file_type) {
            case 'image':
                $allowed_mimetypes = ['image/jpeg', 'image/png', 'image/webp'];
                break;
            case 'pdf':
                $allowed_mimetypes = ['application/pdf'];
                break;
            case 'zip':
                $allowed_mimetypes = ['application/zip', 'application/x-zip-compressed'];
                break;
            case 'doc':
                $allowed_mimetypes = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text', 'text/plain'];
                break;
            case 'video':
                $allowed_mimetypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/webm'];
                break;
            case 'audio':
                $allowed_mimetypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'];
                break;
        }
        
        // Validate uploaded file mimetype and show specific error based on selected file type.
        if (!in_array($file->get_mimetype(), $allowed_mimetypes)) {
            $error_key = 'error_invalidfiletype_' . $file_type;
            redirect($PAGE->url . '?step=3', get_string($error_key, 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        // NEW: Validate cross-format compatibility for images when conversion is disabled.
        if ($file_type === 'image') {
            // Check if cross-format conversion is disabled (either by user or by missing GD library).
            $allow_image_conversion = isset($wizard_data->allowimageconversion) 
                ? $wizard_data->allowimageconversion 
                : 1;
            $gd_available = \local_imageplus\replacer::is_gd_available();
            
            // If conversion is disabled or GD is not available, verify all selected files match the uploaded file extension.
            if (!$allow_image_conversion || !$gd_available) {
                // Get uploaded file extension.
                $uploaded_ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
                // Normalize extensions.
                if ($uploaded_ext === 'jpg') {
                    $uploaded_ext = 'jpeg';
                }
                
                // Collect all unique extensions from selected files.
                $target_extensions = [];
                
                // Check filesystem files.
                foreach ($wizard_data->selectedfilesystem as $filepath) {
                    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
                    if ($ext === 'jpg') {
                        $ext = 'jpeg';
                    }
                    $target_extensions[$ext] = $ext;
                }
                
                // Check database files.
                foreach ($wizard_data->databasefiles as $db_file) {
                    if (in_array($db_file->id, $wizard_data->selecteddatabase)) {
                        $ext = strtolower(pathinfo($db_file->filename, PATHINFO_EXTENSION));
                        if ($ext === 'jpg') {
                            $ext = 'jpeg';
                        }
                        $target_extensions[$ext] = $ext;
                    }
                }
                
                // Remove the uploaded extension from target list to check if there are other formats.
                unset($target_extensions[$uploaded_ext]);
                
                // If there are other extensions, show error.
                if (!empty($target_extensions)) {
                    // Add back the uploaded extension for display if some files do match.
                    $all_extensions = $target_extensions;
                    $all_extensions[$uploaded_ext] = $uploaded_ext;
                    
                    $err_data = new stdClass();
                    $err_data->sourceext = strtoupper($uploaded_ext);
                    $err_data->targetexts = strtoupper(implode(', ', array_values($all_extensions)));
                    $err_data->matchingexts = strtoupper(implode(', ', array_values($target_extensions)));
                    $err_data->targetcount = count($wizard_data->selectedfilesystem) + 
                                           count($wizard_data->selecteddatabase);
                    
                    // Different error message depending on whether GD is available or not.
                    if (!$gd_available) {
                        $error_msg = get_string('error_crossformat_nogd', 'local_imageplus', $err_data);
                    } else {
                        $error_msg = get_string('error_crossformat_disabled', 'local_imageplus', $err_data);
                    }
                    
                    redirect($PAGE->url . '?step=3', $error_msg,
                        null, \core\output\notification::NOTIFY_ERROR);
                }
            }
        }
        
        // Sanitize filename to prevent directory traversal.
        $clean_filename = clean_filename($file->get_filename());
        $temp_file = make_temp_directory('imagereplacer') . '/' . $clean_filename;
        $file->copy_content_to($temp_file);
        
        // Create replacer instance with final configuration.
        $config = [
            'search_term' => $wizard_data->searchterm,
            'dry_run' => ($wizard_data->executionmode === 'preview'),
            'preserve_permissions' => (bool)$wizard_data->preservepermissions,
            'search_database' => (bool)$wizard_data->searchdatabase,
            'search_filesystem' => (bool)$wizard_data->searchfilesystem,
            'file_type' => $wizard_data->filetype,
            'allow_image_conversion' => (bool)$wizard_data->allowimageconversion,
        ];
        
        $replacer = new \local_imageplus\replacer($config);
        
        if (!$replacer->load_source_file($temp_file)) {
            @unlink($temp_file);
            redirect($PAGE->url . '?step=3', get_string('error_invalidfile', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        // Get selected files from cache.
        $files_to_process = $wizard_data->selectedfilesystem;
        $db_files_to_process = array_filter($wizard_data->databasefiles, function($file) use ($wizard_data) {
            return in_array($file->id, $wizard_data->selecteddatabase);
        });
        $db_files_to_process = array_values($db_files_to_process);
        
        // Process files.
        $replacer->process_filesystem_files($files_to_process);
        $replacer->process_database_files($db_files_to_process);
        
        // Log operation.
        $replacer->log_operation($USER->id);
        
        // Trigger event.
        $event = \local_imageplus\event\images_replaced::create([
            'context' => context_system::instance(),
            'other' => [
                'searchterm' => $wizard_data->searchterm,
                'filesreplaced' => $replacer->get_stats()['files_replaced'],
                'dbfilesreplaced' => $replacer->get_stats()['db_files_replaced'],
            ],
        ]);
        $event->trigger();
        
        // Clean up temp file.
        @unlink($temp_file);
        
        // Display results.
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_imageplus');
        echo $renderer->render_results($replacer, 
            $wizard_data->filesystemfiles,
            $wizard_data->databasefiles, 
            false);
        
        // Clear cache (Start Over button is rendered by the renderer).
        $cache->delete('wizard');
        
        echo $OUTPUT->footer();
        exit;
    }
}

// Display the form.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('heading', 'local_imageplus'));

// Show description only on step 1.
if ($step == 1) {
    echo html_writer::tag('p', get_string('description', 'local_imageplus'));
    
    // Check GD library availability and display warning if missing.
    if (!\local_imageplus\replacer::is_gd_available()) {
        echo $OUTPUT->notification(get_string('warning_nogd_detailed', 'local_imageplus'),
            \core\output\notification::NOTIFY_WARNING);
    }
    
    // Credits.
    echo html_writer::tag('p', get_string('credits', 'local_imageplus'), ['class' => 'alert alert-info']);
}

// Display the form only for steps 1 and 3 (step 2 uses custom HTML form).
if ($step != 2) {
    $mform->display();
}

// STEP 2: Display file selection checkboxes.
if ($step == 2 && $wizard_data) {
    // Verify user still has permission.
    if (!has_capability('moodle/site:config', context_system::instance())) {
        echo $OUTPUT->notification(
            get_string('error_requiresiteadmin', 'local_imageplus'),
            \core\output\notification::NOTIFY_ERROR
        );
        echo $OUTPUT->footer();
        exit;
    }
    
    // Get renderer.
    $renderer = $PAGE->get_renderer('local_imageplus');
    
    // Display step indicator using template.
    echo $renderer->render_step_indicator(2);
    
    $filesystem_files = $wizard_data->filesystemfiles;
    $database_files = $wizard_data->databasefiles;
    
    if (empty($filesystem_files) && empty($database_files)) {
        // Use template for no files found message.
        echo $renderer->render_no_files_found($wizard_data->searchterm);
    } else {
        // Use template for file selection - CSS and JS now properly separated.
        echo $renderer->render_file_selection($filesystem_files, $database_files, $wizard_data->searchterm);
    }
}

// Display directories info on step 1.
if ($step == 1) {
    echo html_writer::start_div('alert alert-info mt-3');
    echo html_writer::tag('strong', get_string('directoriesscanned', 'local_imageplus'));
    echo html_writer::tag('p', get_string('directories_list', 'local_imageplus'));
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
