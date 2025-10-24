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
            false, []);
        
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
    
    // Display step indicator manually for step 2.
    echo '<div class="step-indicator mb-4">';
    echo '<ol class="list-inline">';
    $steps = [
        1 => get_string('step1_name', 'local_imageplus'),
        2 => get_string('step2_name', 'local_imageplus'),
        3 => get_string('step3_name', 'local_imageplus'),
    ];
    foreach ($steps as $num => $name) {
        $class = 'list-inline-item badge ';
        if ($num == 2) {
            $class .= 'badge-primary';
        } else if ($num < 2) {
            $class .= 'badge-success';
        } else {
            $class .= 'badge-secondary';
        }
        echo '<li class="' . $class . '">' . $num . '. ' . s($name) . '</li>';
    }
    echo '</ol>';
    echo '</div>';
    
    $filesystem_files = $wizard_data->filesystemfiles;
    $database_files = $wizard_data->databasefiles;
    
    if (empty($filesystem_files) && empty($database_files)) {
        echo $OUTPUT->notification(
            get_string('nofilesfound_desc', 'local_imageplus', s($wizard_data->searchterm)),
            \core\output\notification::NOTIFY_WARNING
        );
        
        // Show Start Over button when no files found.
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/local/imageplus/index.php', ['startover' => 1]), 
                get_string('startover', 'local_imageplus'),
                ['class' => 'btn btn-primary']
            ),
            'mt-3'
        );
    } else {
        // Add custom CSS for step 2 - matching the results page styling.
        echo html_writer::start_tag('style');
        echo '
            .file-list { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; 
                        margin: 20px 0; max-height: 400px; overflow-y: auto; }
            .file-item { padding: 12px 15px; border-bottom: 1px solid #dee2e6; display: flex;
                        align-items: center; gap: 10px; }
            .file-item:last-child { border-bottom: none; }
            .file-item:hover { background: #f8f9fa; }
            .file-item input[type="checkbox"] { margin: 0; flex-shrink: 0; }
            .file-link { color: #0056b3; text-decoration: none; font-family: monospace; 
                        word-break: break-all; font-weight: 500; }
            .file-link:hover { text-decoration: underline; color: #003d82; }
            .file-details { color: #666; font-size: 0.9em; margin-top: 4px; }
            .section-header { background: #f8f9fa; padding: 12px 15px; border-bottom: 2px solid #dee2e6;
                            font-weight: bold; margin-top: 20px; border-radius: 6px 6px 0 0; }
            .select-all-btn { margin: 10px 0; }
            
            /* Image preview modal */
            .image-preview-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0;
                                  width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.9); }
            .image-preview-content { margin: auto; display: block; max-width: 90vw; max-height: 90vh;
                                    object-fit: contain; position: absolute; top: 50%; left: 50%;
                                    transform: translate(-50%, -50%); }
            .image-preview-close { position: absolute; top: 20px; right: 40px; color: #f1f1f1;
                                  font-size: 40px; font-weight: bold; cursor: pointer; z-index: 10000; }
            .image-preview-close:hover, .image-preview-close:focus { color: #bbb; }
            .image-preview-caption { margin: auto; display: block; width: 80%; max-width: 700px;
                                    text-align: center; color: #ccc; padding: 10px 0; position: absolute;
                                    bottom: 20px; left: 50%; transform: translateX(-50%); }
        ';
        echo html_writer::end_tag('style');
        
        // Add image preview modal HTML
        echo '<div id="imagePreviewModal" class="image-preview-modal">';
        echo '  <span class="image-preview-close">&times;</span>';
        echo '  <img class="image-preview-content" id="imagePreviewImg">';
        echo '  <div class="image-preview-caption" id="imagePreviewCaption"></div>';
        echo '</div>';
        
        // Add JavaScript for image preview (runs once on page load)
        echo html_writer::script("
            (function() {
                var modal = document.getElementById('imagePreviewModal');
                var modalImg = document.getElementById('imagePreviewImg');
                var captionText = document.getElementById('imagePreviewCaption');
                var closeBtn = document.querySelector('.image-preview-close');
                
                // Function to close and clear modal
                function closeModal() {
                    modal.style.display = 'none';
                    modalImg.src = '';  // Clear the image
                    captionText.innerHTML = '';
                }
                
                // Close modal when clicking X or outside image
                closeBtn.onclick = closeModal;
                modal.onclick = function(e) { 
                    if (e.target === modal || e.target === closeBtn) {
                        closeModal();
                    }
                }
                
                // Close on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && modal.style.display === 'block') {
                        closeModal();
                    }
                });
                
                // Add click handlers to all file links
                document.addEventListener('click', function(e) {
                    var target = e.target;
                    if (target.classList.contains('file-link') && target.tagName === 'A') {
                        var href = target.getAttribute('href');
                        var filename = target.textContent || target.innerText;
                        
                        // Check if it's an image file by checking both filename and href
                        if (href && (/\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(filename) || /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(href))) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Clear old image first
                            modalImg.src = '';
                            captionText.innerHTML = filename;
                            
                            // Show modal and load new image
                            modal.style.display = 'block';
                            modalImg.src = href;
                            
                            return false;
                        }
                    }
                });
            })();
        ");

        
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $PAGE->url->out(false),
            'id' => 'fileselectionform'
        ]);
        
        echo html_writer::tag('p', get_string('selectfilestoreplace', 'local_imageplus'), 
            ['class' => 'lead']);
        
        // Filesystem files section.
        if (!empty($filesystem_files)) {
            echo html_writer::div(
                get_string('filesystemresults', 'local_imageplus'),
                'section-header'
            );
            
            echo html_writer::div(
                html_writer::link('#', get_string('selectall', 'local_imageplus'), 
                    ['id' => 'select-all-fs', 'class' => 'btn btn-sm btn-secondary']),
                'select-all-btn'
            );
            
            echo html_writer::start_tag('div', ['class' => 'file-list']);
            foreach ($filesystem_files as $file) {
                // Sanitize file path for display.
                $safe_file = s($file);
                $base_name = basename($file);
                
                // Create file URL - use relative path from Moodle root.
                $relative_path = str_replace($CFG->dirroot . '/', '', $file);
                $file_url = new moodle_url('/' . $relative_path);
                
                echo html_writer::start_div('file-item');
                
                // Checkbox.
                echo html_writer::checkbox('filesystem_files[]', $file, false, '', 
                    ['class' => 'fs-checkbox', 'id' => 'fs_' . md5($file)]);
                
                // File info.
                echo html_writer::start_tag('label', ['for' => 'fs_' . md5($file), 'style' => 'flex: 1; margin: 0; cursor: pointer;']);
                echo html_writer::link(
                    $file_url,
                    s($base_name),
                    ['class' => 'file-link', 'target' => '_blank', 'title' => get_string('viewfile', 'local_imageplus')]
                );
                echo html_writer::div($safe_file, 'file-details');
                echo html_writer::end_tag('label');
                
                echo html_writer::end_div();
            }
            echo html_writer::end_tag('div');
            
            // JavaScript for select all - escape strings properly.
            $select_all_text = addslashes_js(get_string('selectall', 'local_imageplus'));
            $deselect_all_text = 'Deselect All';
            $warning_text = addslashes_js(get_string('warning_selectall', 'local_imageplus'));
            echo html_writer::script("
                document.getElementById('select-all-fs').addEventListener('click', function(e) {
                    e.preventDefault();
                    var checkboxes = document.querySelectorAll('input.fs-checkbox');
                    var allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    
                    if (!allChecked) {
                        // Selecting all - show warning
                        alert('{$warning_text}');
                    }
                    
                    checkboxes.forEach(function(cb) {
                        cb.checked = !allChecked;
                    });
                    this.textContent = allChecked ? '{$select_all_text}' : '{$deselect_all_text}';
                });
            ");
        }
        
        // Database files section.
        if (!empty($database_files)) {
            echo html_writer::div(
                get_string('databaseresults', 'local_imageplus'),
                'section-header'
            );
            
            echo html_writer::div(
                html_writer::link('#', get_string('selectall', 'local_imageplus'),
                    ['id' => 'select-all-db', 'class' => 'btn btn-sm btn-secondary']),
                'select-all-btn'
            );
            
            echo html_writer::start_tag('div', ['class' => 'file-list']);
            foreach ($database_files as $file) {
                // Sanitize all output to prevent XSS.
                $safe_filename = s($file->filename);
                $safe_file_id = (int)$file->id;
                
                // Build pluginfile URL for database files using Moodle's proper method.
                $file_url = null;
                if (!empty($file->contextid) && !empty($file->component) && !empty($file->filearea)) {
                    try {
                        // Use Moodle's file storage to get the stored_file object.
                        $fs = get_file_storage();
                        $stored_file = $fs->get_file(
                            $file->contextid,
                            $file->component,
                            $file->filearea,
                            $file->itemid,
                            $file->filepath,
                            $file->filename
                        );
                        
                        if ($stored_file && !$stored_file->is_directory()) {
                            // Use Moodle's proper URL generation method.
                            $file_url = moodle_url::make_pluginfile_url(
                                $file->contextid,
                                $file->component,
                                $file->filearea,
                                $file->itemid,
                                $file->filepath,
                                $file->filename,
                                false // Don't force download.
                            );
                        }
                    } catch (Exception $e) {
                        // If file can't be accessed, URL will remain null.
                        $file_url = null;
                    }
                }
                
                echo html_writer::start_div('file-item');
                
                // Checkbox.
                echo html_writer::checkbox('database_files[]', $safe_file_id, false, '', 
                    ['class' => 'db-checkbox', 'id' => 'db_' . $safe_file_id]);
                
                // File info.
                echo html_writer::start_tag('label', ['for' => 'db_' . $safe_file_id, 'style' => 'flex: 1; margin: 0; cursor: pointer;']);
                
                if ($file_url) {
                    echo html_writer::link(
                        $file_url,
                        $safe_filename,
                        ['class' => 'file-link', 'target' => '_blank', 'title' => get_string('viewfile', 'local_imageplus')]
                    );
                } else {
                    echo html_writer::tag('span', $safe_filename, ['class' => 'file-link']);
                }
                
                // Build description.
                $file_desc = '';
                if (!empty($file->component) && !empty($file->filearea)) {
                    $file_desc .= s($file->component) . ' / ' . s($file->filearea);
                }
                $file_desc .= ' • ID: ' . $safe_file_id . ' • ' . s(display_size($file->filesize));
                if (!empty($file->mimetype)) {
                    $file_desc .= ' • ' . s($file->mimetype);
                }
                
                echo html_writer::div($file_desc, 'file-details');
                echo html_writer::end_tag('label');
                
                echo html_writer::end_div();
            }
            echo html_writer::end_tag('div');
            
            // JavaScript for select all - escape strings properly.
            $select_all_text = addslashes_js(get_string('selectall', 'local_imageplus'));
            $deselect_all_text = 'Deselect All';
            $warning_text = addslashes_js(get_string('warning_selectall', 'local_imageplus'));
            echo html_writer::script("
                document.getElementById('select-all-db').addEventListener('click', function(e) {
                    e.preventDefault();
                    var checkboxes = document.querySelectorAll('input.db-checkbox');
                    var allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    
                    if (!allChecked) {
                        // Selecting all - show warning
                        alert('{$warningtext}');
                    }
                    
                    checkboxes.forEach(function(cb) {
                        cb.checked = !allChecked;
                    });
                    this.textContent = allChecked ? '{$select_all_text}' : '{$deselect_all_text}';
                });
            ");
        }
        
        // Hidden fields to preserve step data.
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'step', 'value' => 2]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        
        // Buttons.
        echo html_writer::start_div('mt-3');
        echo html_writer::tag('button', get_string('back', 'local_imageplus'), [
            'type' => 'submit',
            'name' => 'backbtn',
            'value' => '1',
            'class' => 'btn btn-secondary mr-2'
        ]);
        echo html_writer::tag('button', get_string('next', 'local_imageplus'), [
            'type' => 'submit',
            'name' => 'nextbtn',
            'value' => '1',
            'class' => 'btn btn-primary'
        ]);
        echo html_writer::end_div();
        
        echo html_writer::end_tag('form');
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
