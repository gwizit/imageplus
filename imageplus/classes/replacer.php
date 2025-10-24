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
 * Image replacer core class
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

namespace local_imageplus;

defined('MOODLE_INTERNAL') || die();

/**
 * Main image replacer class
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replacer {
    /** @var array Configuration options */
    private $config;

    /** @var string Moodle root directory */
    private $moodle_root;

    /** @var string Source file path */
    private $source_file_path;

    /** @var array Source image information */
    private $source_image;

    /** @var resource GD image resource for source image */
    private $source_image_resource;

    /** @var array Statistics */
    private $stats;

    /** @var array Replacement log entries */
    private $replacement_log;

    /** @var \file_storage Moodle file storage */
    private $file_storage;

    /** @var array Output messages */
    private $output;

    /** @var array Supported image formats */
    private $supported_formats = ['.jpg', '.jpeg', '.png', '.webp'];

    /** @var array Supported PDF formats */
    private $supported_pdf_formats = ['.pdf'];

    /** @var array Supported ZIP formats */
    private $supported_zip_formats = ['.zip', '.tar', '.gz', '.rar', '.7z'];

    /** @var array Supported document formats */
    private $supported_doc_formats = ['.doc', '.docx', '.odt', '.txt', '.rtf'];

    /** @var array Supported video formats */
    private $supported_video_formats = ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.webm'];

    /** @var array Supported audio formats */
    private $supported_audio_formats = ['.mp3', '.wav', '.ogg', '.m4a', '.flac'];

    /** @var array All supported formats combined */
    private $all_supported_formats = [];

    /** @var string Selected file type filter */
    private $file_type = 'image';

    /** @var array Directories to search */
    private $search_directories = [
        'theme',
        'pix',
        'mod',
        'blocks',
        'local',
        'course',
        'user',
        'backup',
        'repository',
    ];

    /**
     * Check if GD library is available
     *
     * @return bool True if GD is available
     */
    public static function is_gd_available() {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    /**
     * Check if specific GD function is available
     *
     * @param string $function Function name to check
     * @return bool True if function exists
     */
    public static function is_gd_function_available($function) {
        return function_exists($function);
    }

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct($config = []) {
        global $CFG;

        $this->moodle_root = $CFG->dirroot;
        $this->file_storage = get_file_storage();

        $this->config = array_merge([
            'search_term' => '',
            'dry_run' => true,
            'preserve_permissions' => true,
            'search_database' => true,
            'search_filesystem' => true,
            'file_type' => 'image', // New: file type filter
            'allow_image_conversion' => true, // New: allow cross-format image replacement
        ], $config);

        // Disable image conversion if GD library is not available
        if (!self::is_gd_available() && $this->config['allow_image_conversion']) {
            $this->config['allow_image_conversion'] = false;
            $this->add_output("Warning: GD library not available. Image conversion disabled.", 'warning');
        }

        // Set file type and combine all supported formats
        $this->file_type = $this->config['file_type'];
        $this->all_supported_formats = array_merge(
            $this->supported_formats,
            $this->supported_pdf_formats,
            $this->supported_zip_formats,
            $this->supported_doc_formats,
            $this->supported_video_formats,
            $this->supported_audio_formats
        );

        $this->stats = [
            'files_found' => 0,
            'files_replaced' => 0,
            'files_failed' => 0,
            'db_files_found' => 0,
            'db_files_replaced' => 0,
            'db_files_failed' => 0,
        ];

        $this->output = [];
        $this->replacement_log = [];
    }

    /**
     * Get active file formats based on selected file type
     *
     * @return array Array of file extensions to search for
     */
    private function get_active_formats() {
        switch ($this->file_type) {
            case 'image':
                return $this->supported_formats;
            case 'pdf':
                return $this->supported_pdf_formats;
            case 'zip':
                return $this->supported_zip_formats;
            case 'doc':
                return $this->supported_doc_formats;
            case 'video':
                return $this->supported_video_formats;
            case 'audio':
                return $this->supported_audio_formats;
            default:
                // Default to images if invalid type provided
                return $this->supported_formats;
        }
    }

    /**
     * Check if file is an image based on extension
     *
     * @param string $file_path File path
     * @return bool True if image
     */
    private function is_image_file($file_path) {
        $extension = strtolower('.' . pathinfo($file_path, PATHINFO_EXTENSION));
        return in_array($extension, $this->supported_formats);
    }

    /**
     * Check if filename matches search term with wildcard support
     * Supports * (any characters) and ? (single character)
     *
     * @param string $filename Filename to check
     * @param string $searchterm Search term with optional wildcards
     * @return bool True if filename matches
     */
    private function matches_search_term($filename, $search_term) {
        $filename = strtolower($filename);
        $search_term = strtolower($search_term);

        // If search term contains wildcards, use pattern matching
        if (strpos($search_term, '*') !== false || strpos($search_term, '?') !== false) {
            // Check if pattern already covers full filename (starts/ends with wildcards)
            $has_leading_wildcard = (substr($search_term, 0, 1) === '*');
            $has_trailing_wildcard = (substr($search_term, -1) === '*');
            
            // If no leading wildcard, add one to allow matching anywhere in filename
            if (!$has_leading_wildcard) {
                $search_term = '*' . $search_term;
            }
            
            // If no trailing wildcard, add one to allow matching anywhere in filename
            if (!$has_trailing_wildcard) {
                $search_term = $search_term . '*';
            }
            
            // Use fnmatch with FNM_CASEFOLD for case-insensitive matching if available
            if (defined('FNM_CASEFOLD')) {
                return fnmatch($search_term, $filename, FNM_CASEFOLD);
            } else {
                // Fallback: both already lowercase, use fnmatch
                return fnmatch($search_term, $filename);
            }
        }

        // Otherwise use simple substring match
        return strpos($filename, $search_term) !== false;
    }

    /**
     * Validate that source and target file extensions match
     * For images, allows cross-format replacement if configured
     *
     * @param string $source_path Source file path
     * @param string $target_path Target file path
     * @return bool True if replacement is allowed
     */
    private function validate_extension_match($source_path, $target_path) {
        $source_ext = strtolower('.' . pathinfo($source_path, PATHINFO_EXTENSION));
        $target_ext = strtolower('.' . pathinfo($target_path, PATHINFO_EXTENSION));

        // If extensions match, always allow
        if ($source_ext === $target_ext) {
            return true;
        }

        // For images, check if cross-format conversion is allowed
        if ($this->is_image_file($source_path) && 
            $this->is_image_file($target_path) && 
            $this->config['allow_image_conversion']) {
            return true;
        }

        // Extensions don't match and not allowed for this type
        $this->add_output(
            get_string('error_extensionmismatch', 'local_imageplus', 
                ['source' => ltrim($source_ext, '.'), 'target' => ltrim($target_ext, '.')]),
            'error'
        );
        return false;
    }

    /**
     * Load and validate the source file
     *
     * @param string $file_path Path to source file
     * @return bool Success status
     */
    public function load_source_file($file_path) {
        if (!file_exists($file_path)) {
            $this->add_output("Error: Source file not found: $file_path", 'error');
            return false;
        }

        // Store the source file path for later validation
        $this->source_file_path = $file_path;

        // Check if this is an image file that needs special handling
        if ($this->is_image_file($file_path)) {
            // Check if GD library is available for image processing
            if (!self::is_gd_available()) {
                $this->add_output("Warning: GD library not available. Image processing limited to same-format replacement only.", 'warning');
                // Store basic info without GD processing
                $this->source_image = [
                    'filepath' => $file_path,
                    'filename' => basename($file_path),
                    'filesize' => filesize($file_path),
                    'is_image' => false, // Treat as regular file without GD
                ];
                $this->add_output("Loaded source file: " . basename($file_path), 'success');
                return true;
            }

            $image_info = getimagesize($file_path);
            if ($image_info === false) {
                $this->add_output("Error: Invalid image file: $file_path", 'error');
                return false;
            }

            // Load the image resource based on type.
            switch ($image_info[2]) {
                case IMAGETYPE_JPEG:
                    $this->source_image_resource = imagecreatefromjpeg($file_path);
                    break;
                case IMAGETYPE_PNG:
                    $this->source_image_resource = imagecreatefrompng($file_path);
                    imagesavealpha($this->source_image_resource, true);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $this->source_image_resource = imagecreatefromwebp($file_path);
                    } else {
                        $this->add_output("Error: WebP support not available in this PHP installation", 'error');
                        return false;
                    }
                    break;
                default:
                    $this->add_output("Error: Unsupported image format. Supported: JPEG, PNG, WebP", 'error');
                    return false;
            }

            if (!$this->source_image_resource) {
                $this->add_output("Error: Failed to load source image", 'error');
                return false;
            }

            $this->source_image = [
                'width' => $image_info[0],
                'height' => $image_info[1],
                'type' => $image_info[2],
                'mime' => $image_info['mime'],
                'is_image' => true,
            ];

            $this->add_output("Loaded source image: " . basename($file_path), 'success');
            $this->add_output("Format: " . $this->get_format_name($image_info[2]) .
                ", Size: " . $image_info[0] . "x" . $image_info[1], 'info');
        } else {
            // For non-image files, just store basic info
            $this->source_image = [
                'filepath' => $file_path,
                'filename' => basename($file_path),
                'filesize' => filesize($file_path),
                'is_image' => false,
            ];

            $this->add_output("Loaded source file: " . basename($file_path), 'success');
            $this->add_output("File size: " . $this->format_file_size(filesize($file_path)), 'info');
        }

        return true;
    }

    /**
     * Find all matching files in the file system
     *
     * @return array Array of file paths
     */
    public function find_filesystem_files() {
        if (!$this->config['search_filesystem']) {
            return [];
        }

        $this->add_output("Scanning file system directories...", 'info');

        $matching_files = [];
        $search_term_lower = strtolower($this->config['search_term']);

        foreach ($this->search_directories as $directory) {
            $full_path = $this->moodle_root . '/' . $directory;
            if (is_dir($full_path)) {
                $this->add_output("Scanning: $directory/", 'info');
                $files = $this->scan_directory_recursive($full_path, $search_term_lower);
                $matching_files = array_merge($matching_files, $files);
            }
        }

        // Scan root directory.
        $this->add_output("Scanning: / (root)", 'info');
        $root_files = $this->scan_directory($this->moodle_root, $search_term_lower, false);
        $matching_files = array_merge($matching_files, $root_files);

        $this->add_output("Found " . count($matching_files) . " matching files", 'success');

        return $matching_files;
    }

    /**
     * Find all matching files in the database
     *
     * @return array Array of file records
     */
    public function find_database_files() {
        global $DB;

        if (!$this->config['search_database']) {
            return [];
        }

        $this->add_output("Searching Moodle database for stored files...", 'info');

        try {
            // Build MIME type filter based on file type selection
            $mimetype_filter = '';
            switch ($this->file_type) {
                case 'image':
                    $mimetype_filter = "AND f.mimetype LIKE 'image/%'";
                    break;
                case 'pdf':
                    $mimetype_filter = "AND f.mimetype = 'application/pdf'";
                    break;
                case 'zip':
                    $mimetype_filter = "AND (f.mimetype LIKE 'application/zip%' OR f.mimetype LIKE 'application/x-%')";
                    break;
                case 'doc':
                    $mimetype_filter = "AND (f.mimetype LIKE 'application/msword%' OR f.mimetype LIKE 'application/vnd.%' OR f.mimetype = 'text/plain')";
                    break;
                case 'video':
                    $mimetype_filter = "AND f.mimetype LIKE 'video/%'";
                    break;
                case 'audio':
                    $mimetype_filter = "AND f.mimetype LIKE 'audio/%'";
                    break;
                default:
                    // Default to images if invalid type provided
                    $mimetype_filter = "AND f.mimetype LIKE 'image/%'";
                    break;
            }

            // Convert wildcards to SQL LIKE pattern
            $search_pattern = $this->config['search_term'];
            // If user provided wildcards, convert them to SQL LIKE pattern
            if (strpos($search_pattern, '*') !== false || strpos($search_pattern, '?') !== false) {
                // Check if pattern already covers full filename (starts/ends with wildcards)
                $has_leading_wildcard = (substr($search_pattern, 0, 1) === '*');
                $has_trailing_wildcard = (substr($search_pattern, -1) === '*');
                
                // Escape SQL special characters
                $search_pattern = str_replace(['%', '_'], ['\%', '\_'], $search_pattern);
                // Convert wildcards: * to %, ? to _
                $search_pattern = str_replace(['*', '?'], ['%', '_'], $search_pattern);
                
                // If no leading wildcard, add one to allow matching anywhere in filename
                if (!$has_leading_wildcard) {
                    $search_pattern = '%' . $search_pattern;
                }
                
                // If no trailing wildcard, add one to allow matching anywhere in filename
                if (!$has_trailing_wildcard) {
                    $search_pattern = $search_pattern . '%';
                }
            } else {
                // No wildcards, use substring match
                $search_pattern = '%' . $search_pattern . '%';
            }

            $sql = "SELECT f.id, f.contenthash, f.filename, f.filesize, f.mimetype,
                           f.contextid, f.component, f.filearea, f.itemid, f.filepath
                    FROM {files} f
                    WHERE " . $DB->sql_like('f.filename', ':searchterm', false) . "
                    $mimetype_filter
                    AND f.filename != '.'
                    ORDER BY f.filename";

            $results = $DB->get_records_sql($sql, ['searchterm' => $search_pattern]);

            $this->add_output("Found " . count($results) . " matching files in database", 'success');

            return array_values($results);

        } catch (\Exception $e) {
            $this->add_output("Error searching database: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Process file system files
     *
     * @param array $files Array of file paths
     * @return bool Success status
     */
    public function process_filesystem_files($files) {
        if (empty($files)) {
            return true;
        }

        $this->add_output("\nProcessing " . count($files) . " file system images...", 'info');

        foreach ($files as $index => $filepath) {
            $this->stats['files_found']++;
            $filename = basename($filepath);
            $relativepath = str_replace($this->moodle_root . '/', '', $filepath);

            $this->add_output("\nProcessing file " . ($index + 1) . "/" . count($files) . ": $filename", 'info');
            $this->add_output("Path: $relativepath", 'info');
            $this->add_output("Size: " . $this->format_file_size(filesize($filepath)), 'info');

            if ($this->replace_filesystem_file($filepath)) {
                $this->stats['files_replaced']++;
            } else {
                $this->stats['files_failed']++;
            }
        }

        return true;
    }

    /**
     * Process database files
     *
     * @param array $dbfiles Array of file records
     * @return bool Success status
     */
    public function process_database_files($db_files) {
        global $CFG;

        if (empty($db_files)) {
            return true;
        }

        $this->add_output("\nProcessing " . count($db_files) . " database files...", 'info');

        foreach ($db_files as $index => $file_record) {
            $this->stats['db_files_found']++;

            $this->add_output("\nProcessing DB file " . ($index + 1) . "/" . count($db_files) .
                ": " . $file_record->filename, 'info');
            $this->add_output("Context: " . $file_record->component . "/" . $file_record->filearea, 'info');
            $this->add_output("Size: " . $this->format_file_size($file_record->filesize), 'info');
            $this->add_output("MIME: " . $file_record->mimetype, 'info');

            if ($this->replace_database_file($file_record)) {
                $this->stats['db_files_replaced']++;
            } else {
                $this->stats['db_files_failed']++;
            }
        }

        return true;
    }

    /**
     * Replace a file system file
     *
     * @param string $targetpath Path to target file
     * @return bool Success status
     */
    private function replace_filesystem_file($target_path) {
        try {
            // Validate extension match before proceeding
            if (!$this->validate_extension_match($this->source_file_path, $target_path)) {
                $this->stats['files_failed']++;
                $this->add_to_replacement_log($target_path, 'filesystem', false, 'Extension mismatch');
                return false;
            }

            // Check if we're dealing with an image file
            if ($this->is_image_file($target_path) && isset($this->source_image['is_image']) && $this->source_image['is_image']) {
                // Image-to-image replacement with resizing
                $target_info = getimagesize($target_path);
                if ($target_info === false) {
                    $this->add_output("Could not read target image dimensions", 'error');
                    return false;
                }

                $target_width = $target_info[0];
                $target_height = $target_info[1];
                $target_type = $target_info[2];

                $this->add_output("Target format: " . $this->get_format_name($target_type) .
                    ", Target size: {$target_width}x{$target_height}", 'info');

                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace with converted image", 'warning');
                    return true;
                }

                $original_perms = fileperms($target_path);

                $resized_image = $this->resize_image(
                    $this->source_image_resource,
                    $this->source_image['width'],
                    $this->source_image['height'],
                    $target_width,
                    $target_height
                );

                $success = $this->save_image($resized_image, $target_path, $target_type);

                if ($resized_image !== $this->source_image_resource) {
                    imagedestroy($resized_image);
                }

                if ($success) {
                    if ($this->config['preserve_permissions'] && $original_perms !== false) {
                        chmod($target_path, $original_perms);
                    }
                    $this->add_output("Successfully replaced image", 'success');
                    $this->add_to_replacement_log($target_path, 'filesystem', true, 'Image replaced successfully');
                    return true;
                } else {
                    $this->add_output("Failed to save converted image", 'error');
                    $this->add_to_replacement_log($target_path, 'filesystem', false, 'Failed to save converted image');
                    return false;
                }
            } else {
                // Non-image file replacement - simple copy
                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace file", 'warning');
                    return true;
                }

                $original_perms = fileperms($target_path);
                
                if (!copy($this->source_image['filepath'], $target_path)) {
                    $this->add_output("Failed to copy file", 'error');
                    $this->add_to_replacement_log($target_path, 'filesystem', false, 'Failed to copy file');
                    return false;
                }

                if ($this->config['preserve_permissions'] && $original_perms !== false) {
                    chmod($target_path, $original_perms);
                }

                $this->add_output("Successfully replaced file", 'success');
                $this->add_to_replacement_log($target_path, 'filesystem', true, 'File replaced successfully');
                return true;
            }

        } catch (\Exception $e) {
            $this->add_output("Error replacing file: " . $e->getMessage(), 'error');
            $this->add_to_replacement_log($target_path, 'filesystem', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Replace a database file
     *
     * @param object $filerecord File record from database
     * @return bool Success status
     */
    private function replace_database_file($file_record) {
        global $CFG, $DB;

        try {
            $filepath = $this->get_file_path_from_hash($file_record->contenthash);

            if (!file_exists($filepath)) {
                $this->add_output("Physical file not found: $filepath", 'error');
                $this->add_to_replacement_log($file_record->filename, 'database', false, 
                    'Physical file not found (ID: ' . $file_record->id . ')', $file_record);
                return false;
            }

            // Validate extension match before proceeding
            if (!$this->validate_extension_match($this->source_file_path, $file_record->filename)) {
                $this->stats['files_failed']++;
                $this->add_to_replacement_log($file_record->filename, 'database', false, 
                    'Extension mismatch (ID: ' . $file_record->id . ')', $file_record);
                return false;
            }

            // Check if this is an image file requiring special handling
            $is_image_record = strpos($file_record->mimetype, 'image/') === 0;
            
            if ($is_image_record && isset($this->source_image['is_image']) && $this->source_image['is_image']) {
                // Image-to-image replacement with resizing
                $original_info = getimagesize($filepath);
                if ($original_info === false) {
                    $this->add_output("Could not read original image dimensions", 'error');
                    return false;
                }

                $target_width = $original_info[0];
                $target_height = $original_info[1];
                $target_type = $original_info[2];

                $this->add_output("Target format: " . $this->get_format_name($target_type) .
                    ", Target size: {$target_width}x{$target_height}", 'info');

                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace database image", 'warning');
                    return true;
                }

                $resized_image = $this->resize_image(
                    $this->source_image_resource,
                    $this->source_image['width'],
                    $this->source_image['height'],
                    $target_width,
                    $target_height
                );

                $temp_file = make_temp_directory('imagereplacer') . '/' . uniqid('img_');
                $success = $this->save_image($resized_image, $temp_file, $target_type);

                if ($resized_image !== $this->source_image_resource) {
                    imagedestroy($resized_image);
                }

                if (!$success) {
                    $this->add_output("Failed to create replacement image", 'error');
                    @unlink($temp_file);
                    return false;
                }

                $new_content_hash = sha1_file($temp_file);
                $new_file_size = filesize($temp_file);
                $new_file_path = $this->get_file_path_from_hash($new_content_hash);
                $new_file_dir = dirname($new_file_path);

                if (!is_dir($new_file_dir)) {
                    mkdir($new_file_dir, 0755, true);
                }

                if (!copy($temp_file, $new_file_path)) {
                    $this->add_output("Failed to copy new file to storage", 'error');
                    @unlink($temp_file);
                    return false;
                }

                @unlink($temp_file);

                // Update database record.
                $DB->set_field('files', 'contenthash', $new_content_hash, ['id' => $file_record->id]);
                $DB->set_field('files', 'filesize', $new_file_size, ['id' => $file_record->id]);
                $DB->set_field('files', 'timemodified', time(), ['id' => $file_record->id]);

                $this->add_output("Successfully replaced database image", 'success');
                $this->add_to_replacement_log($file_record->filename, 'database', true, 
                    'Image replaced successfully (ID: ' . $file_record->id . ')', $file_record);
                return true;
            } else {
                // Non-image file replacement
                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace database file", 'warning');
                    return true;
                }

                $new_content_hash = sha1_file($this->source_image['filepath']);
                $new_file_size = filesize($this->source_image['filepath']);
                $new_file_path = $this->get_file_path_from_hash($new_content_hash);
                $new_file_dir = dirname($new_file_path);

                if (!is_dir($new_file_dir)) {
                    mkdir($new_file_dir, 0755, true);
                }

                if (!copy($this->source_image['filepath'], $new_file_path)) {
                    $this->add_output("Failed to copy new file to storage", 'error');
                    $this->add_to_replacement_log($file_record->filename, 'database', false, 
                        'Failed to copy file to storage (ID: ' . $file_record->id . ')', $file_record);
                    return false;
                }

                // Update database record.
                $DB->set_field('files', 'contenthash', $new_content_hash, ['id' => $file_record->id]);
                $DB->set_field('files', 'filesize', $new_file_size, ['id' => $file_record->id]);
                $DB->set_field('files', 'timemodified', time(), ['id' => $file_record->id]);

                $this->add_output("Successfully replaced database file", 'success');
                $this->add_to_replacement_log($file_record->filename, 'database', true, 
                    'File replaced successfully (ID: ' . $file_record->id . ')', $file_record);
                return true;
            }

        } catch (\Exception $e) {
            $this->add_output("Error replacing database file: " . $e->getMessage(), 'error');
            $this->add_to_replacement_log($file_record->filename, 'database', false, 
                'Error: ' . $e->getMessage() . ' (ID: ' . $file_record->id . ')', $file_record);
            return false;
        }
    }

    /**
     * Scan directory recursively for matching images
     *
     * @param string $directory Directory to scan
     * @param string $search_term_lower Search term in lowercase
     * @return array Array of file paths
     */
    private function scan_directory_recursive($directory, $search_term_lower) {
        $matching_files = [];
        $active_formats = $this->get_active_formats();

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    $extension = strtolower('.' . $file->getExtension());

                    if (in_array($extension, $active_formats) &&
                        $this->matches_search_term($filename, $search_term_lower)) {
                        $matching_files[] = $file->getPathname();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->add_output("Error scanning directory $directory: " . $e->getMessage(), 'error');
        }

        return $matching_files;
    }

    /**
     * Scan single directory (non-recursive)
     *
     * @param string $directory Directory to scan
     * @param string $search_term_lower Search term in lowercase
     * @param bool $recursive Whether to scan recursively
     * @return array Array of file paths
     */
    private function scan_directory($directory, $search_term_lower, $recursive = true) {
        $matching_files = [];
        $active_formats = $this->get_active_formats();

        if (!is_dir($directory)) {
            return $matching_files;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $full_path = $directory . '/' . $file;

            if (is_file($full_path)) {
                $extension = strtolower('.' . pathinfo($file, PATHINFO_EXTENSION));

                if (in_array($extension, $active_formats) &&
                    $this->matches_search_term($file, $search_term_lower)) {
                    $matching_files[] = $full_path;
                }
            }
        }

        return $matching_files;
    }

    /**
     * Resize image
     *
     * @param resource $source_resource Source image resource
     * @param int $source_width Source width
     * @param int $source_height Source height
     * @param int $target_width Target width
     * @param int $target_height Target height
     * @return resource Resized image resource
     */
    private function resize_image($source_resource, $source_width, $source_height, $target_width, $target_height) {
        if ($source_width == $target_width && $source_height == $target_height) {
            return $source_resource;
        }

        $this->add_output("Resizing from {$source_width}x{$source_height} to {$target_width}x{$target_height}", 'info');

        $resized = imagecreatetruecolor($target_width, $target_height);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
        imagealphablending($resized, true);

        imagecopyresampled(
            $resized,
            $source_resource,
            0, 0, 0, 0,
            $target_width,
            $target_height,
            $source_width,
            $source_height
        );

        return $resized;
    }

    /**
     * Save image to file
     *
     * @param resource $image_resource Image resource
     * @param string $file_path File path
     * @param int $target_type Image type constant
     * @return bool Success status
     */
    private function save_image($image_resource, $file_path, $target_type) {
        switch ($target_type) {
            case IMAGETYPE_JPEG:
                $jpeg_image = imagecreatetruecolor(imagesx($image_resource), imagesy($image_resource));
                imagefill($jpeg_image, 0, 0, imagecolorallocate($jpeg_image, 255, 255, 255));
                imagecopy($jpeg_image, $image_resource, 0, 0, 0, 0,
                    imagesx($image_resource), imagesy($image_resource));
                $result = imagejpeg($jpeg_image, $file_path, 95);
                imagedestroy($jpeg_image);
                return $result;

            case IMAGETYPE_PNG:
                imagesavealpha($image_resource, true);
                return imagepng($image_resource, $file_path, 9);

            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    return imagewebp($image_resource, $file_path, 95);
                } else {
                    return imagepng($image_resource, $file_path, 9);
                }

            default:
                return false;
        }
    }

    /**
     * Get file path from content hash
     *
     * @param string $contenthash Content hash
     * @return string File path
     */
    private function get_file_path_from_hash($content_hash) {
        global $CFG;
        $l1 = substr($content_hash, 0, 2);
        $l2 = substr($content_hash, 2, 2);
        return $CFG->dataroot . '/filedir/' . $l1 . '/' . $l2 . '/' . $content_hash;
    }

    /**
     * Get format name from image type
     *
     * @param int $type Image type constant
     * @return string Format name
     */
    private function get_format_name($type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return 'JPEG';
            case IMAGETYPE_PNG:
                return 'PNG';
            case IMAGETYPE_WEBP:
                return 'WebP';
            default:
                return 'Unknown';
        }
    }

    /**
     * Format file size
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function format_file_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Add output message
     *
     * @param string $message Message text
     * @param string $type Message type (info, success, warning, error)
     */
    private function add_output($message, $type = 'info') {
        $this->output[] = [
            'message' => $message,
            'type' => $type,
            'time' => time(),
        ];
    }

    /**
     * Get output messages
     *
     * @return array Array of output messages
     */
    public function get_output() {
        return $this->output;
    }

    /**
     * Get statistics
     *
     * @return array Statistics array
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get replacement log
     *
     * @return array Replacement log entries
     */
    public function get_replacement_log() {
        return $this->replacement_log;
    }

    /**
     * Add entry to replacement log
     *
     * @param string $filename File name
     * @param string $type Type of file (filesystem or database)
     * @param bool $success Whether replacement was successful
     * @param string $message Status message
     * @param object|null $filerecord Database file record (for database files)
     */
    private function add_to_replacement_log($filename, $type, $success, $message, $file_record = null) {
        $entry = [
            'filename' => $filename,
            'type' => $type,
            'success' => $success,
            'message' => $message,
            'timestamp' => time(),
        ];

        if ($file_record) {
            $entry['component'] = $file_record->component ?? '';
            $entry['filearea'] = $file_record->filearea ?? '';
            $entry['filepath'] = $file_record->filepath ?? '';
            $entry['contextid'] = $file_record->contextid ?? 0;
            $entry['itemid'] = $file_record->itemid ?? 0;
        }

        $this->replacement_log[] = $entry;
    }

    /**
     * Log operation to database
     *
     * @param int $userid User ID
     * @return bool Success status
     */
    public function log_operation($user_id) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $user_id;
        $record->searchterm = $this->config['search_term'];
        $record->filesreplaced = $this->stats['files_replaced'];
        $record->dbfilesreplaced = $this->stats['db_files_replaced'];
        $record->filesfailed = $this->stats['files_failed'];
        $record->dryrun = $this->config['dry_run'] ? 1 : 0;
        $record->searchdatabase = $this->config['search_database'] ? 1 : 0;
        $record->searchfilesystem = $this->config['search_filesystem'] ? 1 : 0;
        $record->sourceimageinfo = json_encode($this->source_image);
        $record->timecreated = time();
        $record->timemodified = time();

        try {
            $DB->insert_record('local_imageplus_log', $record);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cleanup resources
     */
    public function __destruct() {
        if ($this->source_image_resource) {
            imagedestroy($this->source_image_resource);
        }
    }
}
