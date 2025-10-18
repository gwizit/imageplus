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
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_imagereplacer_tool');

require_login();
require_capability('local/imagereplacer:view', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/local/imagereplacer/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_imagereplacer'));
$PAGE->set_heading(get_string('heading', 'local_imagereplacer'));
$PAGE->requires->js_call_amd('local_imagereplacer/filepicker_patch', 'init');

// Get default settings.
$defaultsearchterm = get_config('local_imagereplacer', 'defaultsearchterm');
$defaultmode = get_config('local_imagereplacer', 'defaultmode');
$defaultpreservepermissions = get_config('local_imagereplacer', 'defaultpreservepermissions');
$defaultsearchdatabase = get_config('local_imagereplacer', 'defaultsearchdatabase');
$defaultsearchfilesystem = get_config('local_imagereplacer', 'defaultsearchfilesystem');

// Handle form submission.
if ($action === 'search' || $action === 'replace') {
    require_capability('local/imagereplacer:manage', context_system::instance());
    require_sesskey();

    $searchterm = required_param('searchterm', PARAM_TEXT);
    $executionmode = required_param('executionmode', PARAM_ALPHA);
    $filetype = optional_param('filetype', 'image', PARAM_ALPHA);
    $preservepermissions = optional_param('preservepermissions', 0, PARAM_INT);
    $searchdatabase = optional_param('searchdatabase', 0, PARAM_INT);
    $searchfilesystem = optional_param('searchfilesystem', 0, PARAM_INT);

    // Handle file upload from filemanager.
    $draftitemid = file_get_submitted_draft_itemid('sourceimage');
    
    $fs = get_file_storage();
    $usercontext = context_user::instance($USER->id);
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);
    
    if (empty($files)) {
        redirect($PAGE->url, get_string('error_nosourcefile', 'local_imagereplacer'), 
            null, \core\output\notification::NOTIFY_ERROR);
    }

    $file = reset($files);
    
    // For image file types, validate it's actually an image
    if ($filetype === 'image' && !in_array($file->get_mimetype(), ['image/jpeg', 'image/png', 'image/webp'])) {
        redirect($PAGE->url, get_string('error_invalidfiletype', 'local_imagereplacer'), 
            null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Copy file to temp directory.
    $tempfile = make_temp_directory('imagereplacer') . '/' . clean_filename($file->get_filename());
    $file->copy_content_to($tempfile);

    // Create replacer instance.
    $config = [
        'search_term' => $searchterm,
        'dry_run' => ($executionmode === 'preview'),
        'preserve_permissions' => (bool)$preservepermissions,
        'search_database' => (bool)$searchdatabase,
        'search_filesystem' => (bool)$searchfilesystem,
        'file_type' => $filetype,
    ];

    $replacer = new \local_imagereplacer\replacer($config);

    if (!$replacer->load_source_image($tempfile)) {
        @unlink($tempfile);
        redirect($PAGE->url, get_string('error_invalidfile', 'local_imagereplacer'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    // Find images.
    $filesystemimages = $replacer->find_filesystem_images();
    $databaseimages = $replacer->find_database_images();

    if ($action === 'replace') {
        // Process images.
        $replacer->process_filesystem_images($filesystemimages);
        $replacer->process_database_images($databaseimages);

        // Log operation.
        $replacer->log_operation($USER->id);

        // Trigger event.
        $event = \local_imagereplacer\event\images_replaced::create([
            'context' => context_system::instance(),
            'other' => [
                'searchterm' => $searchterm,
                'filesreplaced' => $replacer->get_stats()['files_replaced'],
                'dbfilesreplaced' => $replacer->get_stats()['db_files_replaced'],
            ],
        ]);
        $event->trigger();
    }

    // Clean up temp file.
    @unlink($tempfile);

    // Display results.
    echo $OUTPUT->header();

    $renderer = $PAGE->get_renderer('local_imagereplacer');
    echo $renderer->render_results($replacer, $filesystemimages, $databaseimages, $action === 'search');

    echo $OUTPUT->footer();
    exit;
}

// Display form.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('heading', 'local_imagereplacer'));
echo html_writer::tag('p', get_string('description', 'local_imagereplacer'));

// Credits.
echo html_writer::tag('p', get_string('credits', 'local_imagereplacer'), ['class' => 'alert alert-info']);

// Build form.
$formdata = new stdClass();
$formdata->searchterm = $defaultsearchterm;
$formdata->executionmode = $defaultmode ? 'preview' : 'execute';
$formdata->preservepermissions = $defaultpreservepermissions;
$formdata->searchdatabase = $defaultsearchdatabase;
$formdata->searchfilesystem = $defaultsearchfilesystem;

// Prepare filemanager for source image upload.
$draftitemid = 0;
file_prepare_draft_area($draftitemid, context_system::instance()->id, 'local_imagereplacer', 'sourceimage', 0,
    ['subdirs' => 0, 'maxbytes' => 10485760, 'maxfiles' => 1]);
$formdata->sourceimage = $draftitemid;

$mform = new \local_imagereplacer\form\replacer_form(null, ['formdata' => $formdata]);

$mform->display();

// Display directories info.
echo html_writer::start_div('alert alert-info mt-3');
echo html_writer::tag('strong', get_string('directoriesscanned', 'local_imagereplacer'));
echo html_writer::tag('p', get_string('directories_list', 'local_imagereplacer'));
echo html_writer::end_div();

echo $OUTPUT->footer();
