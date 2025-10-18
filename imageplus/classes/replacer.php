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
    private $moodleroot;

    /** @var string Source file path */
    private $sourcefilepath;

    /** @var array Source image information */
    private $sourceimage;

    /** @var resource GD image resource for source image */
    private $sourceimageresource;

    /** @var array Statistics */
    private $stats;

    /** @var array Replacement log entries */
    private $replacementlog;

    /** @var \file_storage Moodle file storage */
    private $filestorage;

    /** @var array Output messages */
    private $output;

    /** @var array Supported image formats */
    private $supportedformats = ['.jpg', '.jpeg', '.png', '.webp'];

    /** @var array Supported PDF formats */
    private $supportedpdfformats = ['.pdf'];

    /** @var array Supported ZIP formats */
    private $supportedzipformats = ['.zip', '.tar', '.gz', '.rar', '.7z'];

    /** @var array Supported document formats */
    private $supporteddocformats = ['.doc', '.docx', '.odt', '.txt', '.rtf'];

    /** @var array Supported video formats */
    private $supportedvideoformats = ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.webm'];

    /** @var array Supported audio formats */
    private $supportedaudioformats = ['.mp3', '.wav', '.ogg', '.m4a', '.flac'];

    /** @var array All supported formats combined */
    private $allsupportedformats = [];

    /** @var string Selected file type filter */
    private $filetype = 'image';

    /** @var array Directories to search */
    private $searchdirectories = [
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

        $this->moodleroot = $CFG->dirroot;
        $this->filestorage = get_file_storage();

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
        $this->filetype = $this->config['file_type'];
        $this->allsupportedformats = array_merge(
            $this->supportedformats,
            $this->supportedpdfformats,
            $this->supportedzipformats,
            $this->supporteddocformats,
            $this->supportedvideoformats,
            $this->supportedaudioformats
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
        $this->replacementlog = [];
    }

    /**
     * Get active file formats based on selected file type
     *
     * @return array Array of file extensions to search for
     */
    private function get_active_formats() {
        switch ($this->filetype) {
            case 'image':
                return $this->supportedformats;
            case 'pdf':
                return $this->supportedpdfformats;
            case 'zip':
                return $this->supportedzipformats;
            case 'doc':
                return $this->supporteddocformats;
            case 'video':
                return $this->supportedvideoformats;
            case 'audio':
                return $this->supportedaudioformats;
            default:
                // Default to images if invalid type provided
                return $this->supportedformats;
        }
    }

    /**
     * Check if file is an image based on extension
     *
     * @param string $filepath File path
     * @return bool True if image
     */
    private function is_image_file($filepath) {
        $extension = strtolower('.' . pathinfo($filepath, PATHINFO_EXTENSION));
        return in_array($extension, $this->supportedformats);
    }

    /**
     * Check if filename matches search term with wildcard support
     * Supports * (any characters) and ? (single character)
     *
     * @param string $filename Filename to check
     * @param string $searchterm Search term with optional wildcards
     * @return bool True if filename matches
     */
    private function matches_search_term($filename, $searchterm) {
        $filename = strtolower($filename);
        $searchterm = strtolower($searchterm);

        // If search term contains wildcards, use pattern matching
        if (strpos($searchterm, '*') !== false || strpos($searchterm, '?') !== false) {
            // Check if pattern already covers full filename (starts/ends with wildcards)
            $hasLeadingWildcard = (substr($searchterm, 0, 1) === '*');
            $hasTrailingWildcard = (substr($searchterm, -1) === '*');
            
            // If no leading wildcard, add one to allow matching anywhere in filename
            if (!$hasLeadingWildcard) {
                $searchterm = '*' . $searchterm;
            }
            
            // If no trailing wildcard, add one to allow matching anywhere in filename
            if (!$hasTrailingWildcard) {
                $searchterm = $searchterm . '*';
            }
            
            // Use fnmatch with FNM_CASEFOLD for case-insensitive matching if available
            if (defined('FNM_CASEFOLD')) {
                return fnmatch($searchterm, $filename, FNM_CASEFOLD);
            } else {
                // Fallback: both already lowercase, use fnmatch
                return fnmatch($searchterm, $filename);
            }
        }

        // Otherwise use simple substring match
        return strpos($filename, $searchterm) !== false;
    }

    /**
     * Validate that source and target file extensions match
     * For images, allows cross-format replacement if configured
     *
     * @param string $sourcepath Source file path
     * @param string $targetpath Target file path
     * @return bool True if replacement is allowed
     */
    private function validate_extension_match($sourcepath, $targetpath) {
        $sourceext = strtolower('.' . pathinfo($sourcepath, PATHINFO_EXTENSION));
        $targetext = strtolower('.' . pathinfo($targetpath, PATHINFO_EXTENSION));

        // If extensions match, always allow
        if ($sourceext === $targetext) {
            return true;
        }

        // For images, check if cross-format conversion is allowed
        if ($this->is_image_file($sourcepath) && 
            $this->is_image_file($targetpath) && 
            $this->config['allow_image_conversion']) {
            return true;
        }

        // Extensions don't match and not allowed for this type
        $this->add_output(
            get_string('error_extensionmismatch', 'local_imageplus', 
                ['source' => ltrim($sourceext, '.'), 'target' => ltrim($targetext, '.')]),
            'error'
        );
        return false;
    }

    /**
     * Load and validate the source file
     *
     * @param string $filepath Path to source file
     * @return bool Success status
     */
    public function load_source_file($filepath) {
        if (!file_exists($filepath)) {
            $this->add_output("Error: Source file not found: $filepath", 'error');
            return false;
        }

        // Store the source file path for later validation
        $this->sourcefilepath = $filepath;

        // Check if this is an image file that needs special handling
        if ($this->is_image_file($filepath)) {
            // Check if GD library is available for image processing
            if (!self::is_gd_available()) {
                $this->add_output("Warning: GD library not available. Image processing limited to same-format replacement only.", 'warning');
                // Store basic info without GD processing
                $this->sourceimage = [
                    'filepath' => $filepath,
                    'filename' => basename($filepath),
                    'filesize' => filesize($filepath),
                    'is_image' => false, // Treat as regular file without GD
                ];
                $this->add_output("Loaded source file: " . basename($filepath), 'success');
                return true;
            }

            $imageinfo = getimagesize($filepath);
            if ($imageinfo === false) {
                $this->add_output("Error: Invalid image file: $filepath", 'error');
                return false;
            }

            // Load the image resource based on type.
            switch ($imageinfo[2]) {
                case IMAGETYPE_JPEG:
                    $this->sourceimageresource = imagecreatefromjpeg($filepath);
                    break;
                case IMAGETYPE_PNG:
                    $this->sourceimageresource = imagecreatefrompng($filepath);
                    imagesavealpha($this->sourceimageresource, true);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $this->sourceimageresource = imagecreatefromwebp($filepath);
                    } else {
                        $this->add_output("Error: WebP support not available in this PHP installation", 'error');
                        return false;
                    }
                    break;
                default:
                    $this->add_output("Error: Unsupported image format. Supported: JPEG, PNG, WebP", 'error');
                    return false;
            }

            if (!$this->sourceimageresource) {
                $this->add_output("Error: Failed to load source image", 'error');
                return false;
            }

            $this->sourceimage = [
                'width' => $imageinfo[0],
                'height' => $imageinfo[1],
                'type' => $imageinfo[2],
                'mime' => $imageinfo['mime'],
                'is_image' => true,
            ];

            $this->add_output("Loaded source image: " . basename($filepath), 'success');
            $this->add_output("Format: " . $this->get_format_name($imageinfo[2]) .
                ", Size: " . $imageinfo[0] . "x" . $imageinfo[1], 'info');
        } else {
            // For non-image files, just store basic info
            $this->sourceimage = [
                'filepath' => $filepath,
                'filename' => basename($filepath),
                'filesize' => filesize($filepath),
                'is_image' => false,
            ];

            $this->add_output("Loaded source file: " . basename($filepath), 'success');
            $this->add_output("File size: " . $this->format_file_size(filesize($filepath)), 'info');
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

        $matchingfiles = [];
        $searchtermLower = strtolower($this->config['search_term']);

        foreach ($this->searchdirectories as $directory) {
            $fullpath = $this->moodleroot . '/' . $directory;
            if (is_dir($fullpath)) {
                $this->add_output("Scanning: $directory/", 'info');
                $files = $this->scan_directory_recursive($fullpath, $searchtermLower);
                $matchingfiles = array_merge($matchingfiles, $files);
            }
        }

        // Scan root directory.
        $this->add_output("Scanning: / (root)", 'info');
        $rootfiles = $this->scan_directory($this->moodleroot, $searchtermLower, false);
        $matchingfiles = array_merge($matchingfiles, $rootfiles);

        $this->add_output("Found " . count($matchingfiles) . " matching files", 'success');

        return $matchingfiles;
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
            $mimetypefilter = '';
            switch ($this->filetype) {
                case 'image':
                    $mimetypefilter = "AND f.mimetype LIKE 'image/%'";
                    break;
                case 'pdf':
                    $mimetypefilter = "AND f.mimetype = 'application/pdf'";
                    break;
                case 'zip':
                    $mimetypefilter = "AND (f.mimetype LIKE 'application/zip%' OR f.mimetype LIKE 'application/x-%')";
                    break;
                case 'doc':
                    $mimetypefilter = "AND (f.mimetype LIKE 'application/msword%' OR f.mimetype LIKE 'application/vnd.%' OR f.mimetype = 'text/plain')";
                    break;
                case 'video':
                    $mimetypefilter = "AND f.mimetype LIKE 'video/%'";
                    break;
                case 'audio':
                    $mimetypefilter = "AND f.mimetype LIKE 'audio/%'";
                    break;
                default:
                    // Default to images if invalid type provided
                    $mimetypefilter = "AND f.mimetype LIKE 'image/%'";
                    break;
            }

            // Convert wildcards to SQL LIKE pattern
            $searchpattern = $this->config['search_term'];
            // If user provided wildcards, convert them to SQL LIKE pattern
            if (strpos($searchpattern, '*') !== false || strpos($searchpattern, '?') !== false) {
                // Check if pattern already covers full filename (starts/ends with wildcards)
                $hasLeadingWildcard = (substr($searchpattern, 0, 1) === '*');
                $hasTrailingWildcard = (substr($searchpattern, -1) === '*');
                
                // Escape SQL special characters
                $searchpattern = str_replace(['%', '_'], ['\%', '\_'], $searchpattern);
                // Convert wildcards: * to %, ? to _
                $searchpattern = str_replace(['*', '?'], ['%', '_'], $searchpattern);
                
                // If no leading wildcard, add one to allow matching anywhere in filename
                if (!$hasLeadingWildcard) {
                    $searchpattern = '%' . $searchpattern;
                }
                
                // If no trailing wildcard, add one to allow matching anywhere in filename
                if (!$hasTrailingWildcard) {
                    $searchpattern = $searchpattern . '%';
                }
            } else {
                // No wildcards, use substring match
                $searchpattern = '%' . $searchpattern . '%';
            }

            $sql = "SELECT f.id, f.contenthash, f.filename, f.filesize, f.mimetype,
                           f.contextid, f.component, f.filearea, f.itemid, f.filepath
                    FROM {files} f
                    WHERE " . $DB->sql_like('f.filename', ':searchterm', false) . "
                    $mimetypefilter
                    AND f.filename != '.'
                    ORDER BY f.filename";

            $results = $DB->get_records_sql($sql, ['searchterm' => $searchpattern]);

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
        if (empty($imagefiles)) {
            return true;
        }

        $this->add_output("\nProcessing " . count($imagefiles) . " file system images...", 'info');

        foreach ($imagefiles as $index => $filepath) {
            $this->stats['files_found']++;
            $filename = basename($filepath);
            $relativepath = str_replace($this->moodleroot . '/', '', $filepath);

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
    public function process_database_files($dbfiles) {
        global $CFG;

        if (empty($dbimages)) {
            return true;
        }

        $this->add_output("\nProcessing " . count($dbfiles) . " database files...", 'info');

        foreach ($dbfiles as $index => $filerecord) {
            $this->stats['db_files_found']++;

            $this->add_output("\nProcessing DB file " . ($index + 1) . "/" . count($dbfiles) .
                ": " . $filerecord->filename, 'info');
            $this->add_output("Context: " . $filerecord->component . "/" . $filerecord->filearea, 'info');
            $this->add_output("Size: " . $this->format_file_size($filerecord->filesize), 'info');
            $this->add_output("MIME: " . $filerecord->mimetype, 'info');

            if ($this->replace_database_file($filerecord)) {
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
    private function replace_filesystem_file($targetpath) {
        try {
            // Validate extension match before proceeding
            if (!$this->validate_extension_match($this->sourcefilepath, $targetpath)) {
                $this->stats['files_failed']++;
                $this->add_to_replacement_log($targetpath, 'filesystem', false, 'Extension mismatch');
                return false;
            }

            // Check if we're dealing with an image file
            if ($this->is_image_file($targetpath) && isset($this->sourceimage['is_image']) && $this->sourceimage['is_image']) {
                // Image-to-image replacement with resizing
                $targetinfo = getimagesize($targetpath);
                if ($targetinfo === false) {
                    $this->add_output("Could not read target image dimensions", 'error');
                    return false;
                }

                $targetwidth = $targetinfo[0];
                $targetheight = $targetinfo[1];
                $targettype = $targetinfo[2];

                $this->add_output("Target format: " . $this->get_format_name($targettype) .
                    ", Target size: {$targetwidth}x{$targetheight}", 'info');

                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace with converted image", 'warning');
                    return true;
                }

                $originalperms = fileperms($targetpath);

                $resizedimage = $this->resize_image(
                    $this->sourceimageresource,
                    $this->sourceimage['width'],
                    $this->sourceimage['height'],
                    $targetwidth,
                    $targetheight
                );

                $success = $this->save_image($resizedimage, $targetpath, $targettype);

                if ($resizedimage !== $this->sourceimageresource) {
                    imagedestroy($resizedimage);
                }

                if ($success) {
                    if ($this->config['preserve_permissions'] && $originalperms !== false) {
                        chmod($targetpath, $originalperms);
                    }
                    $this->add_output("Successfully replaced image", 'success');
                    $this->add_to_replacement_log($targetpath, 'filesystem', true, 'Image replaced successfully');
                    return true;
                } else {
                    $this->add_output("Failed to save converted image", 'error');
                    $this->add_to_replacement_log($targetpath, 'filesystem', false, 'Failed to save converted image');
                    return false;
                }
            } else {
                // Non-image file replacement - simple copy
                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace file", 'warning');
                    return true;
                }

                $originalperms = fileperms($targetpath);
                
                if (!copy($this->sourceimage['filepath'], $targetpath)) {
                    $this->add_output("Failed to copy file", 'error');
                    $this->add_to_replacement_log($targetpath, 'filesystem', false, 'Failed to copy file');
                    return false;
                }

                if ($this->config['preserve_permissions'] && $originalperms !== false) {
                    chmod($targetpath, $originalperms);
                }

                $this->add_output("Successfully replaced file", 'success');
                $this->add_to_replacement_log($targetpath, 'filesystem', true, 'File replaced successfully');
                return true;
            }

        } catch (\Exception $e) {
            $this->add_output("Error replacing file: " . $e->getMessage(), 'error');
            $this->add_to_replacement_log($targetpath, 'filesystem', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Replace a database file
     *
     * @param object $filerecord File record from database
     * @return bool Success status
     */
    private function replace_database_file($filerecord) {
        global $CFG, $DB;

        try {
            $filepath = $this->get_file_path_from_hash($filerecord->contenthash);

            if (!file_exists($filepath)) {
                $this->add_output("Physical file not found: $filepath", 'error');
                $this->add_to_replacement_log($filerecord->filename, 'database', false, 
                    'Physical file not found (ID: ' . $filerecord->id . ')', $filerecord);
                return false;
            }

            // Validate extension match before proceeding
            if (!$this->validate_extension_match($this->sourcefilepath, $filerecord->filename)) {
                $this->stats['files_failed']++;
                $this->add_to_replacement_log($filerecord->filename, 'database', false, 
                    'Extension mismatch (ID: ' . $filerecord->id . ')', $filerecord);
                return false;
            }

            // Check if this is an image file requiring special handling
            $isimagerecord = strpos($filerecord->mimetype, 'image/') === 0;
            
            if ($isimagerecord && isset($this->sourceimage['is_image']) && $this->sourceimage['is_image']) {
                // Image-to-image replacement with resizing
                $originalinfo = getimagesize($filepath);
                if ($originalinfo === false) {
                    $this->add_output("Could not read original image dimensions", 'error');
                    return false;
                }

                $targetwidth = $originalinfo[0];
                $targetheight = $originalinfo[1];
                $targettype = $originalinfo[2];

                $this->add_output("Target format: " . $this->get_format_name($targettype) .
                    ", Target size: {$targetwidth}x{$targetheight}", 'info');

                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace database image", 'warning');
                    return true;
                }

                $resizedimage = $this->resize_image(
                    $this->sourceimageresource,
                    $this->sourceimage['width'],
                    $this->sourceimage['height'],
                    $targetwidth,
                    $targetheight
                );

                $tempfile = make_temp_directory('imagereplacer') . '/' . uniqid('img_');
                $success = $this->save_image($resizedimage, $tempfile, $targettype);

                if ($resizedimage !== $this->sourceimageresource) {
                    imagedestroy($resizedimage);
                }

                if (!$success) {
                    $this->add_output("Failed to create replacement image", 'error');
                    @unlink($tempfile);
                    return false;
                }

                $newcontenthash = sha1_file($tempfile);
                $newfilesize = filesize($tempfile);
                $newfilepath = $this->get_file_path_from_hash($newcontenthash);
                $newfiledir = dirname($newfilepath);

                if (!is_dir($newfiledir)) {
                    mkdir($newfiledir, 0755, true);
                }

                if (!copy($tempfile, $newfilepath)) {
                    $this->add_output("Failed to copy new file to storage", 'error');
                    @unlink($tempfile);
                    return false;
                }

                @unlink($tempfile);

                // Update database record.
                $DB->set_field('files', 'contenthash', $newcontenthash, ['id' => $filerecord->id]);
                $DB->set_field('files', 'filesize', $newfilesize, ['id' => $filerecord->id]);
                $DB->set_field('files', 'timemodified', time(), ['id' => $filerecord->id]);

                $this->add_output("Successfully replaced database image", 'success');
                $this->add_to_replacement_log($filerecord->filename, 'database', true, 
                    'Image replaced successfully (ID: ' . $filerecord->id . ')', $filerecord);
                return true;
            } else {
                // Non-image file replacement
                if ($this->config['dry_run']) {
                    $this->add_output("[DRY RUN] Would replace database file", 'warning');
                    return true;
                }

                $newcontenthash = sha1_file($this->sourceimage['filepath']);
                $newfilesize = filesize($this->sourceimage['filepath']);
                $newfilepath = $this->get_file_path_from_hash($newcontenthash);
                $newfiledir = dirname($newfilepath);

                if (!is_dir($newfiledir)) {
                    mkdir($newfiledir, 0755, true);
                }

                if (!copy($this->sourceimage['filepath'], $newfilepath)) {
                    $this->add_output("Failed to copy new file to storage", 'error');
                    $this->add_to_replacement_log($filerecord->filename, 'database', false, 
                        'Failed to copy file to storage (ID: ' . $filerecord->id . ')', $filerecord);
                    return false;
                }

                // Update database record.
                $DB->set_field('files', 'contenthash', $newcontenthash, ['id' => $filerecord->id]);
                $DB->set_field('files', 'filesize', $newfilesize, ['id' => $filerecord->id]);
                $DB->set_field('files', 'timemodified', time(), ['id' => $filerecord->id]);

                $this->add_output("Successfully replaced database file", 'success');
                $this->add_to_replacement_log($filerecord->filename, 'database', true, 
                    'File replaced successfully (ID: ' . $filerecord->id . ')', $filerecord);
                return true;
            }

        } catch (\Exception $e) {
            $this->add_output("Error replacing database file: " . $e->getMessage(), 'error');
            $this->add_to_replacement_log($filerecord->filename, 'database', false, 
                'Error: ' . $e->getMessage() . ' (ID: ' . $filerecord->id . ')', $filerecord);
            return false;
        }
    }

    /**
     * Scan directory recursively for matching images
     *
     * @param string $directory Directory to scan
     * @param string $searchtermLower Search term in lowercase
     * @return array Array of file paths
     */
    private function scan_directory_recursive($directory, $searchtermLower) {
        $matchingfiles = [];
        $activeformats = $this->get_active_formats();

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    $extension = strtolower('.' . $file->getExtension());

                    if (in_array($extension, $activeformats) &&
                        $this->matches_search_term($filename, $searchtermLower)) {
                        $matchingfiles[] = $file->getPathname();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->add_output("Error scanning directory $directory: " . $e->getMessage(), 'error');
        }

        return $matchingfiles;
    }

    /**
     * Scan single directory (non-recursive)
     *
     * @param string $directory Directory to scan
     * @param string $searchtermLower Search term in lowercase
     * @param bool $recursive Whether to scan recursively
     * @return array Array of file paths
     */
    private function scan_directory($directory, $searchtermLower, $recursive = true) {
        $matchingfiles = [];
        $activeformats = $this->get_active_formats();

        if (!is_dir($directory)) {
            return $matchingfiles;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullpath = $directory . '/' . $file;

            if (is_file($fullpath)) {
                $extension = strtolower('.' . pathinfo($file, PATHINFO_EXTENSION));

                if (in_array($extension, $activeformats) &&
                    $this->matches_search_term($file, $searchtermLower)) {
                    $matchingfiles[] = $fullpath;
                }
            }
        }

        return $matchingfiles;
    }

    /**
     * Resize image
     *
     * @param resource $sourceresource Source image resource
     * @param int $sourcewidth Source width
     * @param int $sourceheight Source height
     * @param int $targetwidth Target width
     * @param int $targetheight Target height
     * @return resource Resized image resource
     */
    private function resize_image($sourceresource, $sourcewidth, $sourceheight, $targetwidth, $targetheight) {
        if ($sourcewidth == $targetwidth && $sourceheight == $targetheight) {
            return $sourceresource;
        }

        $this->add_output("Resizing from {$sourcewidth}x{$sourceheight} to {$targetwidth}x{$targetheight}", 'info');

        $resized = imagecreatetruecolor($targetwidth, $targetheight);

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
        imagealphablending($resized, true);

        imagecopyresampled(
            $resized,
            $sourceresource,
            0, 0, 0, 0,
            $targetwidth,
            $targetheight,
            $sourcewidth,
            $sourceheight
        );

        return $resized;
    }

    /**
     * Save image to file
     *
     * @param resource $imageresource Image resource
     * @param string $filepath File path
     * @param int $targettype Image type constant
     * @return bool Success status
     */
    private function save_image($imageresource, $filepath, $targettype) {
        switch ($targettype) {
            case IMAGETYPE_JPEG:
                $jpegimage = imagecreatetruecolor(imagesx($imageresource), imagesy($imageresource));
                imagefill($jpegimage, 0, 0, imagecolorallocate($jpegimage, 255, 255, 255));
                imagecopy($jpegimage, $imageresource, 0, 0, 0, 0,
                    imagesx($imageresource), imagesy($imageresource));
                $result = imagejpeg($jpegimage, $filepath, 95);
                imagedestroy($jpegimage);
                return $result;

            case IMAGETYPE_PNG:
                imagesavealpha($imageresource, true);
                return imagepng($imageresource, $filepath, 9);

            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    return imagewebp($imageresource, $filepath, 95);
                } else {
                    return imagepng($imageresource, $filepath, 9);
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
    private function get_file_path_from_hash($contenthash) {
        global $CFG;
        $l1 = substr($contenthash, 0, 2);
        $l2 = substr($contenthash, 2, 2);
        return $CFG->dataroot . '/filedir/' . $l1 . '/' . $l2 . '/' . $contenthash;
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
        return $this->replacementlog;
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
    private function add_to_replacement_log($filename, $type, $success, $message, $filerecord = null) {
        $entry = [
            'filename' => $filename,
            'type' => $type,
            'success' => $success,
            'message' => $message,
            'timestamp' => time(),
        ];

        if ($filerecord) {
            $entry['component'] = $filerecord->component ?? '';
            $entry['filearea'] = $filerecord->filearea ?? '';
            $entry['filepath'] = $filerecord->filepath ?? '';
            $entry['contextid'] = $filerecord->contextid ?? 0;
            $entry['itemid'] = $filerecord->itemid ?? 0;
        }

        $this->replacementlog[] = $entry;
    }

    /**
     * Log operation to database
     *
     * @param int $userid User ID
     * @return bool Success status
     */
    public function log_operation($userid) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->searchterm = $this->config['search_term'];
        $record->filesreplaced = $this->stats['files_replaced'];
        $record->dbfilesreplaced = $this->stats['db_files_replaced'];
        $record->filesfailed = $this->stats['files_failed'];
        $record->dryrun = $this->config['dry_run'] ? 1 : 0;
        $record->searchdatabase = $this->config['search_database'] ? 1 : 0;
        $record->searchfilesystem = $this->config['search_filesystem'] ? 1 : 0;
        $record->sourceimageinfo = json_encode($this->sourceimage);
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
        if ($this->sourceimageresource) {
            imagedestroy($this->sourceimageresource);
        }
    }
}
