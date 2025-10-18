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
 * Image replacer renderer
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for image replacer plugin
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_imageplus_renderer extends plugin_renderer_base {

    /**
     * Render results page
     *
     * @param \local_imageplus\replacer $replacer Replacer instance
     * @param array $filesystemfiles File system files
     * @param array $databasefiles Database files
     * @param bool $scanonly Whether this is scan only
     * @param array $formdata Form data to preserve for confirmation
     * @return string HTML output
     */
    public function render_results($replacer, $filesystemfiles, $databasefiles, $scanonly, $formdata = []) {
        global $PAGE, $CFG;

        $output = '';

        // Add custom CSS - matching step 2 styling.
        $output .= html_writer::start_tag('style');
        $output .= '
            .stats-container { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
            .stat-card { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;
                        min-width: 120px; border-left: 4px solid #3498db; }
            .stat-number { font-size: 24px; font-weight: bold; color: #2c3e50; }
            .stat-label { color: #6c757d; font-size: 14px; }
            .file-list { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; 
                        margin: 20px 0; max-height: 400px; overflow-y: auto; }
            .file-item { padding: 12px 15px; border-bottom: 1px solid #dee2e6; }
            .file-item:last-child { border-bottom: none; }
            .file-item:hover { background: #f8f9fa; }
            .file-link { color: #0056b3; text-decoration: none; font-family: monospace; 
                        word-break: break-all; }
            .file-link:hover { text-decoration: underline; color: #003d82; }
            .file-details { color: #666; font-size: 0.9em; margin-top: 4px; }
            .preview-warning { background: #fff3cd; border: 2px solid #ffc107; border-radius: 6px;
                             padding: 15px 20px; margin: 20px 0; color: #856404; }
            .preview-warning strong { color: #856404; }
            .output-console { background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 6px;
                            font-family: monospace; white-space: pre-wrap; max-height: 500px; overflow-y: auto;
                            margin: 20px 0; }
            .output-line { margin: 2px 0; }
            .output-info { color: #90cdf4; }
            .output-success { color: #68d391; }
            .output-warning { color: #fbd38d; }
            .output-error { color: #fc8181; }
            .section-header { background: #f8f9fa; padding: 12px 15px; border-bottom: 2px solid #dee2e6;
                            font-weight: bold; margin-top: 20px; border-radius: 6px 6px 0 0; }
        ';
        $output .= html_writer::end_tag('style');

        $output .= $this->heading(get_string('resultstitle', 'local_imageplus'));

        $stats = $replacer->get_stats();
        $replacementlog = $replacer->get_replacement_log();

        // Preview mode warning at the top.
        if ($scanonly) {
            $output .= html_writer::div(
                get_string('preview_mode_warning', 'local_imageplus'),
                'preview-warning'
            );
        }

        // Statistics.
        $output .= html_writer::start_div('stats-container');

        // Filter replacement log for successful replacements only.
        $successfulFs = array_filter($replacementlog, function($entry) {
            return $entry['success'] && $entry['type'] === 'filesystem';
        });
        $successfulDb = array_filter($replacementlog, function($entry) {
            return $entry['success'] && $entry['type'] === 'database';
        });

        if (!$scanonly) {
            $output .= $this->render_stat_card($stats['files_replaced'],
                get_string('stats_replaced', 'local_imageplus'));

            if ($stats['db_files_replaced'] > 0) {
                $output .= $this->render_stat_card($stats['db_files_replaced'],
                    get_string('stats_dbreplaced', 'local_imageplus'));
            }

            if ($stats['files_failed'] > 0) {
                $output .= $this->render_stat_card($stats['files_failed'],
                    get_string('stats_failed', 'local_imageplus'));
            }
        }

        $output .= html_writer::end_div();

        // No files replaced message.
        if (!$scanonly && empty($successfulFs) && empty($successfulDb)) {
            $output .= html_writer::div(
                get_string('nofilesreplaced', 'local_imageplus'),
                'alert alert-warning'
            );
            $output .= html_writer::tag('p', get_string('nofilesreplaced_desc', 'local_imageplus'));
        }

        // File system results - only show successfully replaced files with step 2 styling.
        if (!$scanonly && !empty($successfulFs)) {
            $output .= html_writer::div(
                get_string('filesreplaced_fs', 'local_imageplus'),
                'section-header'
            );
            
            $output .= html_writer::start_div('file-list');
            foreach ($successfulFs as $entry) {
                $filepath = $entry['filename'];
                $safefilepath = s($filepath);
                $basename = basename($filepath);
                
                // Create file URL - use relative path from Moodle root.
                $relativepath = str_replace($CFG->dirroot . '/', '', $filepath);
                $fileurl = new \moodle_url('/' . $relativepath);
                
                $filelink = html_writer::link(
                    $fileurl,
                    s($basename),
                    ['class' => 'file-link', 'target' => '_blank', 'title' => get_string('viewfile', 'local_imageplus')]
                );
                
                $output .= html_writer::start_div('file-item');
                $output .= $filelink;
                $output .= html_writer::div(
                    s($safefilepath) . ' - ' . s($entry['message']),
                    'file-details'
                );
                $output .= html_writer::end_div();
            }
            $output .= html_writer::end_div();
        }

        // Database results - only show successfully replaced files with step 2 styling.
        if (!$scanonly && !empty($successfulDb)) {
            $output .= html_writer::div(
                get_string('filesreplaced_db', 'local_imageplus'),
                'section-header'
            );
            
            $output .= html_writer::start_div('file-list');
            foreach ($successfulDb as $entry) {
                $filename = $entry['filename'];
                
                // Build pluginfile URL for database files using Moodle's proper method.
                $fileurl = null;
                $filedesc = s($filename);
                
                if (!empty($entry['contextid']) && !empty($entry['component']) && !empty($entry['filearea'])) {
                    try {
                        // Use Moodle's file storage to verify file exists and get proper URL.
                        $fs = get_file_storage();
                        $storedfile = $fs->get_file(
                            $entry['contextid'],
                            $entry['component'],
                            $entry['filearea'],
                            isset($entry['itemid']) ? $entry['itemid'] : 0,
                            !empty($entry['filepath']) ? $entry['filepath'] : '/',
                            $filename
                        );
                        
                        if ($storedfile && !$storedfile->is_directory()) {
                            // Use Moodle's proper URL generation method.
                            $fileurl = \moodle_url::make_pluginfile_url(
                                $entry['contextid'],
                                $entry['component'],
                                $entry['filearea'],
                                isset($entry['itemid']) ? $entry['itemid'] : null,
                                !empty($entry['filepath']) ? $entry['filepath'] : '/',
                                $filename,
                                false // Don't force download.
                            );
                        }
                    } catch (\Exception $e) {
                        // If file can't be accessed, URL will remain null.
                        $fileurl = null;
                    }
                    
                    if ($fileurl) {
                        $filelink = html_writer::link(
                            $fileurl,
                            s($filename),
                            ['class' => 'file-link', 'target' => '_blank', 'title' => get_string('viewfile', 'local_imageplus')]
                        );
                    } else {
                        // No valid URL - just show filename.
                        $filelink = html_writer::tag('span', s($filename), ['class' => 'file-link']);
                    }
                    
                    // Add component/filearea details to description.
                    $filedesc = s($entry['component']) . ' / ' . s($entry['filearea']);
                } else {
                    // No URL available - just show filename.
                    $filelink = html_writer::tag('span', s($filename), ['class' => 'file-link']);
                    if (!empty($entry['component']) && !empty($entry['filearea'])) {
                        $filedesc = s($entry['component']) . ' / ' . s($entry['filearea']);
                    }
                }
                
                $output .= html_writer::start_div('file-item');
                $output .= $filelink;
                $output .= html_writer::div(
                    $filedesc . ' - ' . s($entry['message']),
                    'file-details'
                );
                $output .= html_writer::end_div();
            }
            $output .= html_writer::end_div();
        }

        // Processing output.
        if (!$scanonly && !empty($replacer->get_output())) {
            $output .= $this->heading(get_string('processingoutput', 'local_imageplus'), 3);
            $output .= html_writer::start_div('output-console');
            foreach ($replacer->get_output() as $msg) {
                $class = 'output-' . $msg['type'];
                $output .= html_writer::div(htmlspecialchars($msg['message']), 'output-line ' . $class);
            }
            $output .= html_writer::end_div();
        }

        // Completion message.
        if (!$scanonly) {
            $completemsg = get_string('operationcomplete', 'local_imageplus') . ' ';
            if ($stats['files_replaced'] > 0 || $stats['db_files_replaced'] > 0) {
                $completemsg .= get_string('operationcomplete_execute', 'local_imageplus');
                $output .= html_writer::div($completemsg, 'alert alert-success');
            } else {
                $output .= html_writer::div($completemsg, 'alert alert-info');
            }
        }

        // Back button.
        $output .= html_writer::div(
            $this->single_button(new moodle_url('/local/imageplus/index.php'),
                get_string('startover', 'local_imageplus'), 'get'),
            'mt-3'
        );

        return $output;
    }

    /**
     * Render a stat card
     *
     * @param int $number Number to display
     * @param string $label Label for the stat
     * @return string HTML output
     */
    private function render_stat_card($number, $label) {
        $output = html_writer::start_div('stat-card');
        $output .= html_writer::div($number, 'stat-number');
        $output .= html_writer::div($label, 'stat-label');
        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Render confirmation form for selected files
     *
     * @param array $formdata Original form data
     * @return string HTML output
     */
    private function render_confirmation_form($formdata) {
        $output = '';
        
        $output .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url('/local/imageplus/index.php'),
            'id' => 'confirm-replacement-form'
        ]);
        
        // Hidden fields to preserve form data
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'confirm']);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'searchterm', 'value' => $formdata['searchterm'] ?? '']);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'filetype', 'value' => $formdata['filetype'] ?? 'image']);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'allowimageconversion', 'value' => $formdata['allowimageconversion'] ?? 1]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'preservepermissions', 'value' => $formdata['preservepermissions'] ?? 1]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'draftitemid', 'value' => $formdata['draftitemid'] ?? '']);
        
        // Confirmation buttons
        $output .= html_writer::start_div('confirm-buttons');
        $output .= html_writer::tag('button', get_string('confirmreplacement', 'local_imageplus'), 
            ['type' => 'submit', 'class' => 'btn btn-primary']);
        $output .= html_writer::tag('button', get_string('cancel'), 
            ['type' => 'button', 'class' => 'btn btn-secondary', 'onclick' => 'window.location.href="/local/imageplus/index.php"']);
        $output .= html_writer::end_div();
        
        $output .= html_writer::end_tag('form');
        
        // Add JavaScript for select all functionality
        $output .= html_writer::start_tag('script');
        $output .= '
            function toggleAllFilesystem(checkbox) {
                var checkboxes = document.querySelectorAll(".filesystem-checkbox");
                checkboxes.forEach(function(cb) {
                    cb.checked = checkbox.checked;
                });
            }
            
            function toggleAllDatabase(checkbox) {
                var checkboxes = document.querySelectorAll(".database-checkbox");
                checkboxes.forEach(function(cb) {
                    cb.checked = checkbox.checked;
                });
            }
            
            document.getElementById("confirm-replacement-form").addEventListener("submit", function(e) {
                var fsChecked = document.querySelectorAll(".filesystem-checkbox:checked").length;
                var dbChecked = document.querySelectorAll(".database-checkbox:checked").length;
                
                if (fsChecked === 0 && dbChecked === 0) {
                    e.preventDefault();
                    alert("' . get_string('error_nofilesselected', 'local_imageplus') . '");
                    return false;
                }
                
                if (!confirm("' . get_string('confirmreplacement_confirm', 'local_imageplus') . ' " + (fsChecked + dbChecked) + " file(s)?")) {
                    e.preventDefault();
                    return false;
                }
            });
        ';
        $output .= html_writer::end_tag('script');
        
        return $output;
    }

    /**
     * Render replacement log table
     *
     * @param array $log Replacement log entries
     * @return string HTML output
     */
    private function render_replacement_log($log) {
        $output = '';
        
        $output .= $this->heading(get_string('replacementlog', 'local_imageplus'), 3);
        
        $output .= html_writer::start_div('replacement-log');
        
        // Header
        $output .= html_writer::div(
            get_string('replacementlog_header', 'local_imageplus'),
            'replacement-log-header'
        );
        
        // Log entries
        foreach ($log as $entry) {
            $output .= html_writer::start_div('replacement-log-item');
            
            // Status icon
            $statusclass = $entry['success'] ? 'log-status-success' : 'log-status-failed';
            $statusicon = $entry['success'] ? '✓' : '✗';
            $output .= html_writer::div($statusicon, 'log-status-icon ' . $statusclass);
            
            // File information
            $output .= html_writer::start_div('', ['style' => 'flex: 1;']);
            
            // Filename and type badge
            $filenamehtml = html_writer::span(htmlspecialchars($entry['filename']), 'log-filename');
            $typebadge = html_writer::span(
                strtoupper($entry['type']),
                'log-type-badge'
            );
            $output .= html_writer::div($filenamehtml . ' ' . $typebadge);
            
            // Message
            $output .= html_writer::div(htmlspecialchars($entry['message']), 'log-message');
            
            // Additional details for database files
            if ($entry['type'] === 'database' && isset($entry['component'])) {
                $details = $entry['component'] . ' / ' . $entry['filearea'];
                if (!empty($entry['filepath']) && $entry['filepath'] !== '/') {
                    $details .= ' • ' . $entry['filepath'];
                }
                $output .= html_writer::div($details, 'log-details');
            }
            
            $output .= html_writer::end_div(); // End file info div
            
            $output .= html_writer::end_div(); // End log item
        }
        
        $output .= html_writer::end_div(); // End replacement-log
        
        // Summary
        $successcount = count(array_filter($log, function($entry) { return $entry['success']; }));
        $failedcount = count($log) - $successcount;
        
        $summarytext = get_string('replacementlog_summary', 'local_imageplus', [
            'total' => count($log),
            'success' => $successcount,
            'failed' => $failedcount
        ]);
        
        $alertclass = $failedcount > 0 ? 'alert alert-warning' : 'alert alert-info';
        $output .= html_writer::div($summarytext, $alertclass);
        
        return $output;
    }
}
