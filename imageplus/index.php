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
$systemcontext = context_system::instance();
if (!has_capability('moodle/site:config', $systemcontext)) {
    // Display error page for non-administrators.
    $PAGE->set_url(new moodle_url('/local/imageplus/index.php'));
    $PAGE->set_context($systemcontext);
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

// Get default settings.
$defaultsearchterm = get_config('local_imageplus', 'defaultsearchterm');
$defaultmode = get_config('local_imageplus', 'defaultmode');
$defaultpreservepermissions = get_config('local_imageplus', 'defaultpreservepermissions');
$defaultsearchdatabase = get_config('local_imageplus', 'defaultsearchdatabase');
$defaultsearchfilesystem = get_config('local_imageplus', 'defaultsearchfilesystem');

// Get current step.
$step = optional_param('step', 1, PARAM_INT);
$backbtn = optional_param('backbtn', '', PARAM_RAW);
$nextbtn = optional_param('nextbtn', '', PARAM_RAW);
$executebtn = optional_param('executebtn', '', PARAM_RAW);

// Handle "Start Over" by clearing session.
$startover = optional_param('startover', '', PARAM_RAW);
if ($startover) {
    unset($SESSION->imageplus_wizard);
    redirect($PAGE->url);
}

// Initialize or retrieve session data.
if (!isset($SESSION->imageplus_wizard)) {
    $SESSION->imageplus_wizard = new stdClass();
    $SESSION->imageplus_wizard->searchterm = $defaultsearchterm ?: '';
    $SESSION->imageplus_wizard->filetype = 'image';
    $SESSION->imageplus_wizard->searchdatabase = $defaultsearchdatabase ?: 1;
    $SESSION->imageplus_wizard->searchfilesystem = $defaultsearchfilesystem ?: 0;
    $SESSION->imageplus_wizard->preservepermissions = $defaultpreservepermissions ?: 0;
    $SESSION->imageplus_wizard->executionmode = $defaultmode ?: 'preview';
    $SESSION->imageplus_wizard->allowimageconversion = 1;
    $SESSION->imageplus_wizard->filesystemfiles = [];
    $SESSION->imageplus_wizard->databasefiles = [];
    $SESSION->imageplus_wizard->selectedfilesystem = [];
    $SESSION->imageplus_wizard->selecteddatabase = [];
}

// Prepare form custom data.
$formdata = clone $SESSION->imageplus_wizard;
$customdata = [
    'formdata' => $formdata,
    'step' => $step,
];

// Create form.
$mform = new \local_imageplus\form\replacer_form(null, $customdata);

// STEP 2: Handle file selection separately (uses custom HTML form, not moodleform)
if ($step == 2 && $nextbtn) {
    require_sesskey();
    require_capability('moodle/site:config', context_system::instance());
    require_capability('local/imageplus:manage', context_system::instance());
    
    // Get selected files from submitted form - sanitize input.
    $selectedfilesystem = optional_param_array('filesystem_files', [], PARAM_PATH);
    $selecteddatabase = optional_param_array('database_files', [], PARAM_INT);
    
    // Validate at least one file is selected.
    if (empty($selectedfilesystem) && empty($selecteddatabase)) {
        redirect($PAGE->url . '?step=2', get_string('error_nofilesselected', 'local_imageplus'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Validate filesystem files exist and are within allowed paths (prevent directory traversal).
    $validatedfilesystem = [];
    foreach ($selectedfilesystem as $filepath) {
        $cleanpath = clean_param($filepath, PARAM_PATH);
        // Ensure the file is within the Moodle dirroot and exists.
        $fullpath = realpath($cleanpath);
        if ($fullpath && strpos($fullpath, $CFG->dirroot) === 0 && file_exists($fullpath)) {
            $validatedfilesystem[] = $cleanpath;
        }
    }
    
    // Save validated selections.
    $SESSION->imageplus_wizard->selectedfilesystem = $validatedfilesystem;
    $SESSION->imageplus_wizard->selecteddatabase = $selecteddatabase;
    
    // Move to step 3.
    $step = 3;
    $customdata['step'] = $step;
    $customdata['formdata'] = $SESSION->imageplus_wizard;
    
    $mform = new \local_imageplus\form\replacer_form(null, $customdata);
}

// STEP 2: Handle back button separately
if ($step == 2 && $backbtn) {
    require_sesskey();
    $step = 1;
    $customdata['step'] = $step;
    $customdata['formdata'] = $SESSION->imageplus_wizard;
    $mform = new \local_imageplus\form\replacer_form(null, $customdata);
}

// STEP 3: Handle back button separately (before form validation)
if ($step == 3 && $backbtn) {
    require_sesskey();
    $step = 2;
    $customdata['step'] = $step;
    $customdata['formdata'] = $SESSION->imageplus_wizard;
    $mform = new \local_imageplus\form\replacer_form(null, $customdata);
}

// Handle form submission (for steps 1 and 3 only - step 2 handled above)
if ($fromform = $mform->get_data()) {
    require_sesskey();
    
    // Verify site administrator permission for all form submissions.
    if (!has_capability('moodle/site:config', context_system::instance())) {
        print_error('error_requiresiteadmin', 'local_imageplus');
    }
    
    // STEP 1: Search for files
    if ($step == 1 && !$backbtn) {
        require_capability('local/imageplus:manage', context_system::instance());
        
        // Save search criteria to session (already sanitized by moodle form).
        $SESSION->imageplus_wizard->searchterm = $fromform->searchterm;
        $SESSION->imageplus_wizard->filetype = $fromform->filetype;
        $SESSION->imageplus_wizard->searchdatabase = $fromform->searchdatabase;
        $SESSION->imageplus_wizard->searchfilesystem = $fromform->searchfilesystem;
        
        $config = [
            'search_term' => $fromform->searchterm,
            'dry_run' => true,
            'preserve_permissions' => false,
            'search_database' => (bool)$fromform->searchdatabase,
            'search_filesystem' => (bool)$fromform->searchfilesystem,
            'file_type' => $fromform->filetype,
            'allow_image_conversion' => true,
        ];
        
        $replacer = new \local_imageplus\replacer($config);
        $filesystemfiles = $replacer->find_filesystem_files();
        $databasefiles = $replacer->find_database_files();
        
        // Store found files in session.
        $SESSION->imageplus_wizard->filesystemfiles = $filesystemfiles;
        $SESSION->imageplus_wizard->databasefiles = $databasefiles;
        
        // Move to step 2.
        $step = 2;
        $customdata['step'] = $step;
        $customdata['formdata'] = $SESSION->imageplus_wizard;
        $mform = new \local_imageplus\form\replacer_form(null, $customdata);
        
    // STEP 3: Execute replacement
    } else if ($step == 3 && $executebtn) {
        // Double-check site administrator permission for file replacement.
        if (!has_capability('moodle/site:config', context_system::instance())) {
            print_error('error_requiresiteadmin', 'local_imageplus');
        }
        
        require_capability('local/imageplus:manage', context_system::instance());
        confirm_sesskey();
        
        // Verify backup confirmation.
        if (empty($fromform->backupconfirm)) {
            redirect($PAGE->url . '?step=3', get_string('backupconfirm_required', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        // Save final options (already sanitized by form).
        $SESSION->imageplus_wizard->preservepermissions = $fromform->preservepermissions;
        $SESSION->imageplus_wizard->executionmode = $fromform->executionmode;
        if (isset($fromform->allowimageconversion)) {
            $SESSION->imageplus_wizard->allowimageconversion = $fromform->allowimageconversion;
        }
        
        // Handle file upload from filepicker.
        $draftitemid = $fromform->sourceimage;
        
        if (empty($draftitemid)) {
            redirect($PAGE->url . '?step=3', get_string('error_nosourcefile', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);
        
        if (empty($files)) {
            redirect($PAGE->url . '?step=3', get_string('error_nosourcefile', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        $file = reset($files);
        
        // Validate file type against allowed types.
        $filetype = $SESSION->imageplus_wizard->filetype;
        $allowedmimetypes = [];
        
        switch ($filetype) {
            case 'image':
                $allowedmimetypes = ['image/jpeg', 'image/png', 'image/webp'];
                break;
            case 'pdf':
                $allowedmimetypes = ['application/pdf'];
                break;
            case 'zip':
                $allowedmimetypes = ['application/zip', 'application/x-zip-compressed'];
                break;
            case 'doc':
                $allowedmimetypes = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text', 'text/plain'];
                break;
            case 'video':
                $allowedmimetypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/webm'];
                break;
            case 'audio':
                $allowedmimetypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'];
                break;
        }
        
        // Validate uploaded file mimetype and show specific error based on selected file type.
        if (!in_array($file->get_mimetype(), $allowedmimetypes)) {
            $errorkey = 'error_invalidfiletype_' . $filetype;
            redirect($PAGE->url . '?step=3', get_string($errorkey, 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        // Sanitize filename to prevent directory traversal.
        $cleanfilename = clean_filename($file->get_filename());
        $tempfile = make_temp_directory('imagereplacer') . '/' . $cleanfilename;
        $file->copy_content_to($tempfile);
        
        // Create replacer instance with final configuration.
        $config = [
            'search_term' => $SESSION->imageplus_wizard->searchterm,
            'dry_run' => ($SESSION->imageplus_wizard->executionmode === 'preview'),
            'preserve_permissions' => (bool)$SESSION->imageplus_wizard->preservepermissions,
            'search_database' => (bool)$SESSION->imageplus_wizard->searchdatabase,
            'search_filesystem' => (bool)$SESSION->imageplus_wizard->searchfilesystem,
            'file_type' => $SESSION->imageplus_wizard->filetype,
            'allow_image_conversion' => (bool)$SESSION->imageplus_wizard->allowimageconversion,
        ];
        
        $replacer = new \local_imageplus\replacer($config);
        
        if (!$replacer->load_source_file($tempfile)) {
            @unlink($tempfile);
            redirect($PAGE->url . '?step=3', get_string('error_invalidfile', 'local_imageplus'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
        
        // Get selected files from session.
        $filesToProcess = $SESSION->imageplus_wizard->selectedfilesystem;
        $dbFilesToProcess = array_filter($SESSION->imageplus_wizard->databasefiles, function($file) {
            global $SESSION;
            return in_array($file->id, $SESSION->imageplus_wizard->selecteddatabase);
        });
        $dbFilesToProcess = array_values($dbFilesToProcess);
        
        // Process files.
        $replacer->process_filesystem_files($filesToProcess);
        $replacer->process_database_files($dbFilesToProcess);
        
        // Log operation.
        $replacer->log_operation($USER->id);
        
        // Trigger event.
        $event = \local_imageplus\event\images_replaced::create([
            'context' => context_system::instance(),
            'other' => [
                'searchterm' => $SESSION->imageplus_wizard->searchterm,
                'filesreplaced' => $replacer->get_stats()['files_replaced'],
                'dbfilesreplaced' => $replacer->get_stats()['db_files_replaced'],
            ],
        ]);
        $event->trigger();
        
        // Clean up temp file.
        @unlink($tempfile);
        
        // Display results.
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_imageplus');
        echo $renderer->render_results($replacer, 
            $SESSION->imageplus_wizard->filesystemfiles,
            $SESSION->imageplus_wizard->databasefiles, 
            false, []);
        
        // Clear session (Start Over button is rendered by the renderer).
        unset($SESSION->imageplus_wizard);
        
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
if ($step == 2 && !empty($SESSION->imageplus_wizard)) {
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
    
    $filesystemfiles = $SESSION->imageplus_wizard->filesystemfiles;
    $databasefiles = $SESSION->imageplus_wizard->databasefiles;
    
    if (empty($filesystemfiles) && empty($databasefiles)) {
        echo $OUTPUT->notification(
            get_string('nofilesfound_desc', 'local_imageplus', s($SESSION->imageplus_wizard->searchterm)),
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
        ';
        echo html_writer::end_tag('style');
        
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $PAGE->url->out(false),
            'id' => 'fileselectionform'
        ]);
        
        echo html_writer::tag('p', get_string('selectfilestoreplace', 'local_imageplus'), 
            ['class' => 'lead']);
        
        // Filesystem files section.
        if (!empty($filesystemfiles)) {
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
            foreach ($filesystemfiles as $file) {
                // Sanitize file path for display.
                $safefile = s($file);
                $basename = basename($file);
                
                // Create file URL - use relative path from Moodle root.
                $relativepath = str_replace($CFG->dirroot . '/', '', $file);
                $fileurl = new moodle_url('/' . $relativepath);
                
                echo html_writer::start_div('file-item');
                
                // Checkbox.
                echo html_writer::checkbox('filesystem_files[]', $file, false, '', 
                    ['class' => 'fs-checkbox', 'id' => 'fs_' . md5($file)]);
                
                // File info.
                echo html_writer::start_tag('label', ['for' => 'fs_' . md5($file), 'style' => 'flex: 1; margin: 0; cursor: pointer;']);
                echo html_writer::link(
                    $fileurl,
                    s($basename),
                    ['class' => 'file-link', 'target' => '_blank', 'title' => get_string('viewfile', 'local_imageplus'),
                     'onclick' => 'event.stopPropagation();']
                );
                echo html_writer::div($safefile, 'file-details');
                echo html_writer::end_tag('label');
                
                echo html_writer::end_div();
            }
            echo html_writer::end_tag('div');
            
            // JavaScript for select all - escape strings properly.
            $selectalltext = addslashes_js(get_string('selectall', 'local_imageplus'));
            $deselectalltext = 'Deselect All';
            echo html_writer::script("
                document.getElementById('select-all-fs').addEventListener('click', function(e) {
                    e.preventDefault();
                    var checkboxes = document.querySelectorAll('input.fs-checkbox');
                    var allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    checkboxes.forEach(function(cb) {
                        cb.checked = !allChecked;
                    });
                    this.textContent = allChecked ? '{$selectalltext}' : '{$deselectalltext}';
                });
            ");
        }
        
        // Database files section.
        if (!empty($databasefiles)) {
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
            foreach ($databasefiles as $file) {
                // Sanitize all output to prevent XSS.
                $safefilename = s($file->filename);
                $safefileid = (int)$file->id;
                
                // Build pluginfile URL for database files using Moodle's proper method.
                $fileurl = null;
                if (!empty($file->contextid) && !empty($file->component) && !empty($file->filearea)) {
                    try {
                        // Use Moodle's file storage to get the stored_file object.
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
                            // Use Moodle's proper URL generation method.
                            $fileurl = moodle_url::make_pluginfile_url(
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
                        $fileurl = null;
                    }
                }
                
                echo html_writer::start_div('file-item');
                
                // Checkbox.
                echo html_writer::checkbox('database_files[]', $safefileid, false, '', 
                    ['class' => 'db-checkbox', 'id' => 'db_' . $safefileid]);
                
                // File info.
                echo html_writer::start_tag('label', ['for' => 'db_' . $safefileid, 'style' => 'flex: 1; margin: 0; cursor: pointer;']);
                
                if ($fileurl) {
                    echo html_writer::link(
                        $fileurl,
                        $safefilename,
                        ['class' => 'file-link', 'target' => '_blank', 'title' => get_string('viewfile', 'local_imageplus'),
                         'onclick' => 'event.stopPropagation();']
                    );
                } else {
                    echo html_writer::tag('span', $safefilename, ['class' => 'file-link']);
                }
                
                // Build description.
                $filedesc = '';
                if (!empty($file->component) && !empty($file->filearea)) {
                    $filedesc .= s($file->component) . ' / ' . s($file->filearea);
                }
                $filedesc .= ' • ID: ' . $safefileid . ' • ' . s(display_size($file->filesize));
                if (!empty($file->mimetype)) {
                    $filedesc .= ' • ' . s($file->mimetype);
                }
                
                echo html_writer::div($filedesc, 'file-details');
                echo html_writer::end_tag('label');
                
                echo html_writer::end_div();
            }
            echo html_writer::end_tag('div');
            
            // JavaScript for select all - escape strings properly.
            $selectalltext = addslashes_js(get_string('selectall', 'local_imageplus'));
            $deselectalltext = 'Deselect All';
            echo html_writer::script("
                document.getElementById('select-all-db').addEventListener('click', function(e) {
                    e.preventDefault();
                    var checkboxes = document.querySelectorAll('input.db-checkbox');
                    var allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    checkboxes.forEach(function(cb) {
                        cb.checked = !allChecked;
                    });
                    this.textContent = allChecked ? '{$selectalltext}' : '{$deselectalltext}';
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
