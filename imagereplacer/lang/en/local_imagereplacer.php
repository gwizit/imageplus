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
 * English language strings for Image Replacer plugin
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Image Replacer';
$string['imagereplacer:manage'] = 'Manage image replacement operations';
$string['imagereplacer:view'] = 'View image replacer tool';

// Main page strings.
$string['heading'] = 'Image Replacer Tool';
$string['description'] = 'Search and replace images containing a specific term in their filename across your Moodle site.';
$string['searchterm'] = 'Search term';
$string['searchterm_help'] = 'Files with filenames containing this term will be found and replaced (case-insensitive).';
$string['filetype'] = 'File type';
$string['filetype_help'] = 'Select the type of files to search for and replace.';
$string['filetype_all'] = 'All file types';
$string['filetype_image'] = 'Images only (JPG, PNG, WebP)';
$string['filetype_pdf'] = 'PDF documents';
$string['filetype_zip'] = 'Archives (ZIP, TAR, RAR, 7Z)';
$string['filetype_doc'] = 'Documents (DOC, DOCX, ODT, TXT)';
$string['filetype_video'] = 'Videos (MP4, AVI, MOV, WebM)';
$string['filetype_audio'] = 'Audio (MP3, WAV, OGG, M4A)';
$string['sourceimage'] = 'Replacement image';
$string['sourceimage_help'] = 'Upload the image that will replace all matching images. The image will be automatically resized and converted to match each target image format.';
$string['sourcefile'] = 'Replacement file';
$string['sourcefile_help'] = 'Upload the file that will replace all matching files. For images, the file will be automatically resized and converted to match each target format. For other file types, the file will be copied as-is.';
$string['sourceimage_maxsize'] = 'Maximum file size: {$a}. Supported formats: JPEG, PNG, WebP';
$string['executionmode'] = 'Execution mode';
$string['executionmode_help'] = 'Preview mode lets you see what would be changed without making any modifications. Execute mode will actually replace the files.';
$string['preservepermissions'] = 'Preserve file permissions';
$string['preservepermissions_help'] = 'Keep the original file permissions when replacing files.';
$string['searchdatabase'] = 'Include database files';
$string['searchdatabase_help'] = 'Also search Moodle\'s file storage system (mdl_files table) for matching files.';
$string['searchfilesystem'] = 'Include file system';
$string['searchfilesystem_help'] = 'Search the Moodle installation directories for matching files.';

// Execution mode options.
$string['mode_preview'] = 'Preview only (safe - no changes)';
$string['mode_execute'] = 'Execute changes (will modify files)';

// Button labels.
$string['findbtn'] = 'Find matching files';
$string['replacebtn'] = 'Replace files';
$string['startover'] = 'Start over';

// Results page.
$string['resultstitle'] = 'Results';
$string['filesystemresults'] = 'File System Results';
$string['databaseresults'] = 'Database Results';
$string['processingoutput'] = 'Processing Output';
$string['filescount'] = 'Files found';
$string['dbimagescount'] = 'Database images found';
$string['nofilesfound'] = 'No matching images found';
$string['nofilesfound_desc'] = 'No image files containing "{$a}" were found in the Moodle installation.';
$string['operationcomplete'] = 'Operation completed!';
$string['operationcomplete_preview'] = 'This was a preview - no files were actually modified.';
$string['operationcomplete_execute'] = 'Files have been updated. You may want to clear Moodle caches.';

// Statistics.
$string['stats_found'] = 'Images found';
$string['stats_replaced'] = 'Images replaced';
$string['stats_failed'] = 'Images failed';
$string['stats_dbfound'] = 'DB images found';
$string['stats_dbreplaced'] = 'DB images replaced';
$string['stats_dbfailed'] = 'DB images failed';

// Directories scanned.
$string['directoriesscanned'] = 'Directories scanned';
$string['directories_list'] = 'theme/, pix/, mod/, blocks/, local/, course/, user/, backup/, repository/, and root directory';

// Errors.
$string['error_nosourceimage'] = 'Please select a valid image file to upload.';
$string['error_nosourcefile'] = 'Please select a valid file to upload.';
$string['error_invalidfiletype'] = 'Invalid file type. Please upload a JPEG, PNG, or WebP image.';
$string['error_invalidfile'] = 'Invalid file. Please check the file and try again.';
$string['error_uploadfailed'] = 'Failed to save uploaded file.';
$string['error_nopermission'] = 'You do not have permission to use this tool.';

// Settings.
$string['settingstitle'] = 'Image Replacer Settings';
$string['defaultsearchterm'] = 'Default search term';
$string['defaultsearchterm_desc'] = 'The default term to search for in image filenames.';
$string['defaultmode'] = 'Default execution mode';
$string['defaultmode_desc'] = 'Whether to run in preview mode by default (recommended).';
$string['defaultpreservepermissions'] = 'Preserve permissions by default';
$string['defaultpreservepermissions_desc'] = 'Whether to preserve original file permissions when replacing images.';
$string['defaultsearchdatabase'] = 'Search database by default';
$string['defaultsearchdatabase_desc'] = 'Whether to include Moodle\'s file storage system in searches by default.';
$string['defaultsearchfilesystem'] = 'Search file system by default';
$string['defaultsearchfilesystem_desc'] = 'Whether to include file system directories in searches by default.';

// Capabilities.
$string['imagereplacer_manage_desc'] = 'Allow user to perform image replacement operations';
$string['imagereplacer_view_desc'] = 'Allow user to view the image replacer tool';

// Privacy.
$string['privacy:metadata:local_imagereplacer_log'] = 'Log of image replacement operations';
$string['privacy:metadata:local_imagereplacer_log:userid'] = 'The user who performed the operation';
$string['privacy:metadata:local_imagereplacer_log:searchterm'] = 'The search term used';
$string['privacy:metadata:local_imagereplacer_log:filesreplaced'] = 'Number of files replaced';
$string['privacy:metadata:local_imagereplacer_log:timemodified'] = 'When the operation was performed';

// Log strings.
$string['eventimagereplaced'] = 'Images replaced';

// Credits.
$string['credits'] = 'Developed by <a href="https://gwizit.com" target="_blank">G Wiz IT Solutions</a>';
