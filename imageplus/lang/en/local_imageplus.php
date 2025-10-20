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
 * English language strings for ImagePlus plugin
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'ImagePlus';
$string['imageplus:manage'] = 'Manage file replacement operations';
$string['imageplus:view'] = 'View ImagePlus tool';

// Main page strings.
$string['heading'] = 'ImagePlus - File Replacement Tool';
$string['description'] = 'Search and replace files containing a specific term in their filename across your Moodle site. Supports images, PDFs, documents, videos, audio files, and archives.<br><br><strong style="color: #d32f2f;">⚠️ IMPORTANT WARNING:</strong> This is a powerful tool that makes <strong>permanent changes</strong> to your Moodle files. Administrators should use this plugin with <strong>extreme caution</strong>. <strong>ALWAYS create a complete backup</strong> of your Moodle site (database and files) before using this tool. File replacements <strong>cannot be undone</strong>. Test in preview mode first, then verify results carefully before executing changes on a production site.';
$string['searchterm'] = 'Search term';
$string['searchterm_help'] = 'Search for files matching this pattern (case-insensitive). Supports wildcards: * (matches any characters) and ? (matches single character). Examples: "logo*" finds logo.png, logo-2024.jpg; "banner?.png" finds banner1.png, banner2.png; "icon" finds any file containing "icon".';
$string['filetype'] = 'File type';
$string['filetype_help'] = 'Select the type of files to search for and replace. Files will only replace other files of the same extension (e.g., PDF to PDF).';
$string['filetype_image'] = 'Images only (JPG, PNG, WebP)';
$string['filetype_pdf'] = 'PDF documents';
$string['filetype_zip'] = 'Archives (ZIP, TAR, RAR, 7Z)';
$string['filetype_doc'] = 'Documents (DOC, DOCX, ODT, TXT)';
$string['filetype_video'] = 'Videos (MP4, AVI, MOV, WebM)';
$string['filetype_audio'] = 'Audio (MP3, WAV, OGG, M4A)';
$string['allowimageconversion'] = 'Allow cross-format image replacement';
$string['allowimageconversion_help'] = 'When enabled, allows replacing images with different formats (e.g., JPG can replace PNG). The source image will be automatically converted to match the target format. When disabled, only exact format matches will be replaced (JPG to JPG only).';
$string['sourceimage'] = 'Replacement image';
$string['sourceimage_help'] = 'Upload the image that will replace all matching images. The image will be automatically resized and converted to match each target image format.';
$string['sourcefile'] = 'Replacement file';
$string['sourcefile_help'] = 'Use the file manager to upload the file that will replace all matching files. For images, the file will be automatically resized and optionally converted to match target formats (see cross-format option). For other file types, only files with the same extension will be replaced.';
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
$string['back'] = 'Back';
$string['next'] = 'Next';
$string['execute_replacement'] = 'Execute Replacement';

// Multi-step wizard.
$string['step1_name'] = 'Search Criteria';
$string['step2_name'] = 'File Selection';
$string['step3_name'] = 'Replacement Options';
$string['step1_header'] = 'Step 1: Define Search Criteria';
$string['step2_header'] = 'Step 2: Select Files to Replace';
$string['step3_header'] = 'Step 3: Replacement Options and Confirmation';
$string['backupconfirm'] = 'I confirm that a recent backup has been made';
$string['backupconfirm_help'] = 'You must confirm that you have a recent backup before proceeding with file replacement. This operation cannot be undone.';
$string['backupconfirm_required'] = 'You must confirm that a backup has been made before proceeding';
$string['final_warning'] = '<strong>WARNING:</strong> This operation will permanently replace the selected files. Make sure you have a recent backup before proceeding. This action cannot be undone!';
$string['nofilesselected'] = 'No files selected';
$string['selectfilestoreplace'] = 'Select the files you want to replace:';
$string['filesselected'] = '{$a} file(s) selected';
$string['uploadfile_instruction'] = 'Upload your replacement file below. <strong>You must upload a {$a} file</strong> to match your Step 1 selection.';
$string['filetype_image_name'] = 'IMAGE (JPEG, PNG, or WebP)';
$string['filetype_pdf_name'] = 'PDF';
$string['filetype_zip_name'] = 'ZIP archive';
$string['filetype_doc_name'] = 'DOCUMENT (DOC, DOCX, ODT, or TXT)';
$string['filetype_video_name'] = 'VIDEO (MP4, AVI, MOV, or WebM)';
$string['filetype_audio_name'] = 'AUDIO (MP3, WAV, OGG, or M4A)';

// Results page.
$string['resultstitle'] = 'Results';
$string['filesystemresults'] = 'File System Results';
$string['databaseresults'] = 'Database Results';
$string['processingoutput'] = 'Processing Output';
$string['replacementlog'] = 'Replacement Log';
$string['replacementlog_header'] = 'Detailed replacement status for each file';
$string['replacementlog_summary'] = 'Total: {$a->total} files | Successful: {$a->success} | Failed: {$a->failed}';
$string['filescount'] = 'Files found';
$string['dbimagescount'] = 'Database images found';
$string['nofilesfound'] = 'No matching images found';
$string['nofilesfound_desc'] = 'No image files containing "{$a}" were found in the Moodle installation.';
$string['nofilesreplaced'] = 'No files were replaced';
$string['nofilesreplaced_desc'] = 'No files were successfully replaced. Check the error messages above for details.';
$string['operationcomplete'] = 'Operation completed!';
$string['operationcomplete_preview'] = 'This was a preview - no files were actually modified.';
$string['operationcomplete_execute'] = 'Files have been updated.';
$string['operationcomplete_clearcache'] = 'You may want to <a href="{$a}" target="_blank">clear Moodle caches</a>.';
$string['preview_mode_warning'] = '<strong>PREVIEW MODE:</strong> No files have been replaced. This was a preview run to show what would be changed. To actually replace files, select "Execute changes" mode in Step 3.';
$string['filesreplaced_fs'] = 'Files Replaced (File System)';
$string['filesreplaced_db'] = 'Files Replaced (Database)';
$string['viewfile'] = 'View file';
$string['selectall'] = 'Select / Deselect all';
$string['confirmreplacement'] = 'Replace selected files';
$string['confirmreplacement_confirm'] = 'Are you sure you want to replace';

// Statistics.
$string['stats_found'] = 'Images found';
$string['stats_replaced'] = 'Files replaced';
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
$string['error_invalidfiletype_image'] = 'Invalid file type. You selected to replace IMAGE files in Step 1. Please upload a JPEG, PNG, or WebP image file.';
$string['error_invalidfiletype_pdf'] = 'Invalid file type. You selected to replace PDF files in Step 1. Please upload a PDF document.';
$string['error_invalidfiletype_zip'] = 'Invalid file type. You selected to replace ZIP files in Step 1. Please upload a ZIP archive.';
$string['error_invalidfiletype_doc'] = 'Invalid file type. You selected to replace DOCUMENT files in Step 1. Please upload a DOC, DOCX, ODT, or TXT file.';
$string['error_invalidfiletype_video'] = 'Invalid file type. You selected to replace VIDEO files in Step 1. Please upload an MP4, AVI, MOV, or WebM video file.';
$string['error_invalidfiletype_audio'] = 'Invalid file type. You selected to replace AUDIO files in Step 1. Please upload an MP3, WAV, OGG, or M4A audio file.';
$string['error_invalidfile'] = 'Invalid file. Please check the file and try again.';
$string['error_extensionmismatch'] = 'Extension mismatch: {$a->source} file cannot replace {$a->target} files. Please upload a file with the correct extension.';
$string['error_crossformat_disabled'] = 'Cross-format image replacement is currently disabled. You have selected {$a->targetcount} file(s) with extension(s): {$a->targetexts}, but uploaded a {$a->sourceext} file. To proceed, either: (1) Enable the "Allow cross-format image replacement" checkbox in Step 3, or (2) Upload a file with a matching extension ({$a->matchingexts}).';
$string['error_crossformat_nogd'] = 'Cross-format image replacement is not available on this server. The PHP GD library is not installed, which is required for converting between image formats. You have selected {$a->targetcount} file(s) with extension(s): {$a->targetexts}, but uploaded a {$a->sourceext} file. Please upload a file with a matching extension ({$a->matchingexts}), or contact your system administrator to install the GD library for PHP.';
$string['error_uploadfailed'] = 'Failed to save uploaded file.';
$string['error_nopermission'] = 'You do not have permission to use this tool.';
$string['error_nofilesselected'] = 'Please select at least one file to replace.';
$string['error_nogd_required'] = 'GD library is required for image processing but is not available on this server. Please contact your system administrator.';
$string['error_requiresiteadmin'] = 'Access denied. This tool is only available to site administrators. Please contact your site administrator if you need access to this functionality.';

// Warnings.
$string['warning_nogd'] = 'GD library is not available. Cross-format image conversion is disabled. Only exact format matching (JPG→JPG, PNG→PNG) is supported.';
$string['warning_nogd_detailed'] = 'Warning: The GD library is not installed or enabled on this server. Image processing features are limited. Cross-format image conversion (e.g., PNG to JPG) is disabled. You can still replace images with the exact same format (e.g., JPG with JPG). For full functionality, please ask your system administrator to install and enable the PHP GD extension.';
$string['warning_selectall'] = '⚠️ You have selected all files. It is strongly recommended to manually review each file before replacing to ensure you are replacing the correct files.';

// Settings.
$string['settingstitle'] = 'ImagePlus Settings';
$string['defaultsearchterm'] = 'Default search term';
$string['defaultsearchterm_desc'] = 'The default term to search for in filenames.';
$string['defaultmode'] = 'Default execution mode';
$string['defaultmode_desc'] = 'Whether to run in preview mode by default (recommended).';
$string['defaultpreservepermissions'] = 'Preserve permissions by default';
$string['defaultpreservepermissions_desc'] = 'Whether to preserve original file permissions when replacing files.';
$string['defaultsearchdatabase'] = 'Search database by default';
$string['defaultsearchdatabase_desc'] = 'Whether to include Moodle\'s file storage system in searches by default.';
$string['defaultsearchfilesystem'] = 'Search file system by default';
$string['defaultsearchfilesystem_desc'] = 'Whether to include file system directories in searches by default.';

// Privacy.
$string['privacy:metadata:local_imageplus_log'] = 'Log of file replacement operations';
$string['privacy:metadata:local_imageplus_log:userid'] = 'The user who performed the operation';
$string['privacy:metadata:local_imageplus_log:searchterm'] = 'The search term used';
$string['privacy:metadata:local_imageplus_log:filesreplaced'] = 'Number of files replaced';
$string['privacy:metadata:local_imageplus_log:timemodified'] = 'When the operation was performed';

// Log strings.
$string['eventimagereplaced'] = 'Images replaced';

// Credits.
$string['credits'] = 'Developed by <a href="https://gwizit.com" target="_blank">G Wiz IT Solutions</a> | <a href="https://square.link/u/9SpmIaIW" target="_blank">💝 Support This Project</a>';
$string['donation_message'] = '💝 <strong>Found this plugin useful?</strong> Please consider <a href="https://square.link/u/9SpmIaIW" target="_blank">making a donation</a> to help us maintain and improve this plugin. Your support keeps this project alive!';
