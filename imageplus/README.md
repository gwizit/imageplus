# Moodle ImagePlus Plugin

A powerful Moodle plugin that allows site administrators to search and replace files (images, PDFs, documents, videos, audio, archives) across their Moodle installation based on filename patterns. Features a user-friendly multi-step wizard interface with comprehensive security controls.

**Developed by:** [G Wiz IT Solutions](https://gwizit.com)  
**Plugin Type:** Local  
**Version:** v3.0.3  
**Compatibility:** Moodle 4.3 to 5.1+  
**License:** GNU GPL v3 or later

**Source Code:** [https://github.com/gwizit/imageplus](https://github.com/gwizit/imageplus)  
**Bug Tracker:** [https://github.com/gwizit/imageplus/issues](https://github.com/gwizit/imageplus/issues)  
**Documentation:** [README.md](https://github.com/gwizit/imageplus/blob/main/imageplus/README.md)

---

## What's New in Version 3.0.0

### 🎯 Multi-Step Wizard Interface
- **Step 1: Search Criteria** - Define what files to find
- **Step 2: File Selection** - Review and select specific files with checkboxes
- **Step 3: Replacement Options** - Configure replacement settings with mandatory backup confirmation
- Visual step indicator showing progress
- Back/Next navigation between steps
- Session-based state management for seamless navigation

### 🔐 Enhanced Security
- **Site Administrator Only Access** - Restricted to users with `moodle/site:config` capability
- **Comprehensive XSS Protection** - All user input and output properly sanitized
- **Input Validation** - Directory traversal prevention and strict file path validation
- **Enhanced File Type Validation** - Comprehensive mimetype checking for all file types
- **Session Key Verification** - Multiple checkpoints throughout the workflow
- **Backup Confirmation** - Mandatory checkbox before executing replacements

### ✨ Improved User Experience
- Progressive disclosure - only relevant options shown at each step
- Interactive file selection with "Select All/Deselect All" functionality
- Clear error messages and user guidance
- Final warning before executing destructive operations
- Better visual feedback at each step

---

## Features

### Core Features
- 🔍 **Smart Search with Wildcards**: Find files by pattern with `*` and `?` wildcard support (e.g., `logo*`, `banner?.png`)
- 🗄️ **Database Integration**: Search both file system and Moodle's database file storage
- 🎨 **Format Conversion**: Automatically converts replacement images to match target format (JPEG, PNG, WebP)
- 📐 **Auto-Resizing**: Intelligently resizes images to match target dimensions
- 🔒 **Safe Preview Mode**: Test replacements without making any changes
- 📊 **Detailed Replacement Log**: See success/failure status for every file replaced
- 🔐 **Permission Control**: File permissions are preserved during replacement
- 🌍 **Transparency Support**: Maintains transparency for PNG and WebP images
- 📝 **Operation History**: Database logging of all replacement operations
- ⚡ **Batch Processing**: Replace multiple files in a single operation

### Multi-File Type Support
- ✅ **Images**: JPG, PNG, WebP with optional cross-format conversion
- ✅ **Documents**: PDF, DOC, DOCX, ODT, TXT
- ✅ **Archives**: ZIP, TAR, RAR, 7Z
- ✅ **Videos**: MP4, AVI, MOV, WebM
- ✅ **Audio**: MP3, WAV, OGG, M4A

---

## Installation

### Method 1: Via Moodle Plugin Installer (Recommended)

1. Download the plugin ZIP file
2. Log in to your Moodle site as an administrator
3. Go to **Site administration** → **Plugins** → **Install plugins**
4. Upload the ZIP file
5. **If prompted** with "Unable to detect the plugin type":
   - Select **"Local plugin (local)"** from the "Plugin type" dropdown
   - Confirm the plugin folder name shows as **"imageplus"**
6. Click "Install plugin from the ZIP file"
7. Follow the on-screen instructions to complete the installation

**Note**: Some Moodle installations require manual plugin type selection for security. This is normal behavior.

### Method 2: Manual Installation (If ZIP upload fails)

**Recommended if you get "corrupted_archive_structure" error:**

1. Extract the plugin ZIP file
2. Copy the `imageplus` folder to `[moodle-root]/local/`
3. Log in to your Moodle site as an administrator
4. Navigate to **Site administration** → **Notifications**
5. Click **"Upgrade Moodle database now"**
6. Follow the on-screen instructions to complete the installation

**Tip**: On Windows, you can use the included `manual_install.ps1` helper script.

### Method 3: Via Command Line

```bash
cd [moodle-root]/local/
git clone [repository-url] imageplus
cd [moodle-root]
php admin/cli/upgrade.php
```

### Post-Installation: Clear Caches

**IMPORTANT:** After installing or updating the plugin, always clear Moodle's caches:

**Method 1: Via Web Interface**
1. Go to **Site administration** → **Development** → **Purge all caches**
2. Click "Purge all caches" button

**Method 2: Via Command Line (Faster)**
```bash
php admin/cli/purge_caches.php
```

**Why?** Moodle caches language strings. If you don't clear caches, you might see text displayed as `[[stringname]]` instead of the actual text. This is normal Moodle behavior for all plugins.

---

## Configuration

After installation, configure the plugin defaults:

1. Go to **Site administration** → **Plugins** → **Local plugins** → **ImagePlus**
2. Configure the following settings:
   - **Default search term**: Default term to search for in filenames
   - **Default execution mode**: Preview or Execute (Preview recommended for safety)
   - **Preserve permissions by default**: Keep original file permissions
   - **Search database by default**: Include Moodle's file storage
   - **Search file system by default**: Include file system directories

---

## Usage

### Accessing the Tool

1. Log in as a **site administrator** (requires `moodle/site:config` capability)
2. Go to **Site administration** → **Server** → **ImagePlus**

**Note:** Non-administrators will see an access denied error. This is intentional for security.

### Using the Multi-Step Wizard

#### Step 1: Define Search Criteria

1. **Enter Search Term**: Type the text pattern to search for in filenames
   - Simple text: `logo`, `banner`, `icon`
   - Wildcards: `logo*` (finds logo.png, logo-2024.jpg), `banner?.png` (finds banner1.png, banner2.png)
   
2. **Select File Type**: Choose what type of files to search for
   - Images (JPG, PNG, WebP)
   - PDF documents
   - ZIP archives
   - Documents (DOC, DOCX, ODT, TXT)
   - Videos (MP4, AVI, MOV, WebM)
   - Audio (MP3, WAV, OGG, M4A)

3. **Choose Search Locations**:
   - ☑️ **Include database files**: Search Moodle's file storage system
   - ☑️ **Include file system**: Search Moodle installation directories

4. Click **Find matching files** to proceed to Step 2

#### Step 2: Select Files to Replace

1. **Review Found Files**: See all files matching your search criteria
   - Filesystem files show full path and filename
   - Database files show filename, ID, size, and context

2. **Select Files**: 
   - Use checkboxes to select specific files to replace
   - Use **Select All/Deselect All** buttons for bulk selection
   - You can select from both filesystem and database results

3. **Navigation**:
   - Click **Back** to modify search criteria
   - Click **Next** to proceed to replacement options

**Note:** At least one file must be selected to proceed.

#### Step 3: Replacement Options and Confirmation

1. **Upload Replacement File**: 
   - Select the file that will replace all selected files
   - Maximum file size: 50MB
   - **Important**: File extension must match target files (unless cross-format is enabled for images)

2. **Configure Options**:
   - ☑️ **Preserve file permissions**: Keep original file permissions when replacing
   - **Execution mode**: 
     - **Preview only**: See what would be changed without modifying files (safe - recommended first)
     - **Execute changes**: Actually perform the replacements
   
3. **Image-Specific Options** (only shown for images when GD library is available):
   - ☑️ **Allow cross-format image replacement**: Enable JPG↔PNG↔WebP conversion

4. **Backup Confirmation** ⚠️:
   - ☑️ **I confirm that a recent backup has been made** (REQUIRED)
   - This checkbox must be checked before proceeding
   - Replacement operations cannot be undone

5. **Final Warning**: Read the warning about irreversible changes

6. Click **Execute Replacement** to complete the operation

### After Execution

- View detailed results showing success/failure for each file
- Review replacement log with statistics
- Click **Start Over** to begin a new replacement operation
- Consider clearing Moodle caches after replacements

### Directories Scanned

The plugin searches the following Moodle directories:
- `theme/` - Theme images
- `pix/` - Moodle icons and graphics
- `mod/` - Module images
- `blocks/` - Block images
- `local/` - Local customizations
- `course/` - Course images
- `user/` - User images
- `backup/` - Backup files
- `repository/` - Repository files
- Root directory (non-recursive)

---

## How It Works

1. **Search Phase**: The plugin scans selected locations for images containing your search term
2. **Analysis**: For each matching image, it determines:
   - Current format (JPEG, PNG, WebP)
   - Current dimensions (width × height)
   - File location (file system or database)
3. **Replacement**: The source image is:
   - Resized to match the target dimensions
   - Converted to match the target format
   - Saved with preserved permissions (if enabled)
4. **Database Updates**: For database files:
   - New content hash is calculated
   - File records are updated in Moodle's files table
   - File storage structure is maintained
5. **Logging**: All operations are logged for audit purposes

---

## Permissions

The plugin defines two capabilities and requires site administrator access:

- **`moodle/site:config`**: **REQUIRED** - Site administrator permission (checked before any access)
- **`local/imageplus:view`**: View the ImagePlus tool
- **`local/imageplus:manage`**: Perform file replacement operations

**Security Note:** Only users with site administrator permissions can access this plugin. Non-administrators will see an error message directing them to contact their site administrator.

---

## Security Features

### Access Control
- Site administrator-only access (`moodle/site:config` capability required)
- Multiple permission checks throughout the workflow
- Session key verification on all form submissions
- Confirm session key on destructive operations (Step 3)

### Input Validation & Sanitization
- All user input validated and sanitized by Moodle form API
- File paths validated with `PARAM_PATH` to prevent directory traversal
- Database file IDs validated as integers with `PARAM_INT`
- File existence and location verification before processing
- Filenames sanitized with `clean_filename()` function

### Output Protection
- All displayed content escaped with `s()` function to prevent XSS
- JavaScript strings escaped with `addslashes_js()`
- HTML output uses Moodle's `html_writer` class
- File paths and names sanitized before display

### File Type Validation
- Comprehensive mimetype checking for all file types:
  - Images: `image/jpeg`, `image/png`, `image/webp`
  - PDFs: `application/pdf`
  - Archives: `application/zip`, `application/x-zip-compressed`
  - Documents: `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, etc.
  - Videos: `video/mp4`, `video/avi`, `video/quicktime`, `video/webm`
  - Audio: `audio/mpeg`, `audio/wav`, `audio/ogg`, `audio/mp4`
- Upload validation ensures file type matches selected category

### Directory Traversal Prevention
- Filesystem paths validated with `realpath()` and `strpos()` checks
- Files must be within Moodle's `$CFG->dirroot`
- File existence verified before processing
- Invalid paths are rejected

### Mandatory Backup Confirmation
- Checkbox confirmation required before executing replacements
- Warning message displayed about irreversible operations
- User must acknowledge backup has been made

---

## Best Practices

### Safety First
1. ✅ Always run in **Preview mode** first to see what will be changed
2. ✅ **BACKUP YOUR MOODLE DATA** before running replacements
3. ✅ Use the backup confirmation checkbox consciously - it's there for a reason
4. ✅ Test with a specific search term on a small set of files first
5. ✅ Review the file selection list carefully in Step 2 before proceeding
6. ✅ Consider running a test on a staging/development environment first

### Performance
1. ⚡ For large sites, search database OR file system separately (don't check both)
2. ⚡ Use specific search terms to limit the number of matches
3. ⚡ Run operations during low-traffic periods
4. ⚡ Process files in smaller batches if you have many matches
5. ⚡ Increase PHP memory limit for large file operations

### Image Quality
1. 🎨 Use high-quality source images that scale well
2. 🎨 Source images should ideally be larger than or equal to target images
3. 🎨 For transparent images, use PNG or WebP source format
4. 🎨 JPEG is best for photographs, PNG for graphics with transparency
5. 🎨 Test cross-format conversion on a few files before bulk operations

### Workflow
1. 📋 **Step 1**: Start with specific search terms, then broaden if needed
2. 📋 **Step 2**: Carefully review and select only the files you intend to replace
3. 📋 **Step 3**: Always check "Preview mode" first, then run again in "Execute mode"
4. 📋 Document what you've changed for future reference
5. 📋 Clear Moodle caches after completing replacements

---

## Requirements

### Moodle Requirements
- **Moodle version**: 4.3 to 5.1+ (fully tested and compatible)
- **PHP version**: 7.4 or higher (8.0+ recommended)

### PHP Extensions
- **GD Library** (recommended but not required)
  - Required for: Image resizing and cross-format conversion (JPG↔PNG↔WebP)
  - Without GD: Images can still be replaced with exact same format
  - With GD: Full image processing including resizing and format conversion
  - Optional: WebP support for WebP image handling

### Server Requirements
- Write permissions to Moodle's `dataroot/filedir` directory
- Sufficient PHP memory limit (128MB minimum, 256MB+ recommended for images)
- PHP `max_execution_time` sufficient for batch operations

---

## Troubleshooting

### Files Not Found
- Check that search term matches filenames (try using wildcards like `logo*`)
- Verify you have selected the correct file type filter
- Verify you have selected the correct search locations
- Ensure files are in supported formats for the selected file type

### GD Library Not Available
- **Symptom**: Warning displayed on main page
- **Impact**: Image cross-format conversion disabled (JPG→JPG only, PNG→PNG only)
- **Solution**: Ask system administrator to install PHP GD extension
- **Workaround**: Replace images with exact same format only

### Extension Mismatch Error
- **Symptom**: "Extension mismatch: pdf file cannot replace jpg files"
- **Cause**: Trying to replace PDF with JPG, or vice versa
- **Solution**: Upload a file with the same extension as target files
- **Note**: Images can cross-convert if "Allow cross-format" is enabled and GD is available

### Permission Denied Errors
- Verify you have the `local/imageplus:manage` capability
- Check file system permissions on Moodle directories
- Ensure web server has write access to target directories

### Memory Errors
- Increase PHP memory limit in php.ini
- Process fewer files at once
- Use selective checkboxes to process files in batches
- Use lower quality source images (for image files)

### No Files Selected Error
- **Symptom**: Alert when clicking "Replace selected files"
- **Cause**: No checkboxes are selected
- **Solution**: Check at least one file to replace

### WebP Not Supported
- Install PHP WebP extension
- Use JPEG or PNG as source format instead
- Check with `php -i | grep -i webp` to verify WebP support

---

## Changelog

### Version 3.0.3 (2025-10-19)
**Compatibility Update**
- ✅ Verified compatibility with Moodle 4.3 through 5.1+
- 📝 Updated version requirements to reflect broader compatibility range
- 🔍 All APIs and features confirmed working on Moodle 4.3+

### Version 3.0.0 to 3.0.2 (2025-10-17 to 2025-10-19)
**Major Wizard Interface & Security Update**

**New Features:**
- 🎯 Multi-step wizard interface (3 steps with visual progress)
- 🔐 Enhanced security with site administrator-only access
- ✨ Interactive file selection with checkboxes
- 📊 Improved results page with clickable file links
- ⚠️ Prominent safety warnings and backup reminders
- 🔒 Mandatory backup confirmation checkbox
- 🎨 Simplified filepicker interface
- 📁 File type restrictions in picker

**Improvements:**
- Better session-based state management
- Comprehensive XSS protection throughout
- Specific validation error messages per file type
- Enhanced user guidance at each step
- Improved documentation with troubleshooting guide

### Version 2.1.0 (2025-10-16)
**Major Feature Update**

**New Features:**
- ✨ Multi-file type support: PDFs, ZIP archives, Documents, Videos, Audio files
- ✨ Wildcard search patterns with `*` and `?` support
- ✨ Selective file replacement with checkboxes
- ✨ "Select All / Deselect All" bulk selection
- ✨ Extension validation to prevent mismatched replacements
- ✨ Optional cross-format image conversion (can be disabled)
- ✨ GD library detection with graceful fallback
- ✨ Detailed replacement log showing success/failure per file
- ✨ Enhanced error messages with context

**Improvements:**
- 🔧 Renamed plugin from "Image Replacer" to "ImagePlus"
- 🔧 Improved form UI with conditional field display
- 🔧 Better file type filtering in database and filesystem searches
- 🔧 Enhanced validation and error handling
- 🔧 Increased max file size to 50MB
- 🔧 Added comprehensive help texts and tooltips

**Bug Fixes:**
- 🐛 Fixed file type filtering to ensure accurate results
- 🐛 Corrected function names to reflect multi-file support
- 🐛 Fixed image conversion logic when GD is unavailable

### Version 2.0.0 (2025-10-15)
- Initial release as ImagePlus
- Multi-file type support
- Database and filesystem search
- Image format conversion
- Auto-resizing for images

### Version 1.0.x
- Legacy "Image Replacer" versions
- Image-only functionality

---

## Support

For issues, questions, or feature requests:

- **Bug Tracker:** [GitHub Issues](https://github.com/gwizit/imageplus/issues)
- **Source Code:** [GitHub Repository](https://github.com/gwizit/imageplus)
- **Website:** [https://gwizit.com](https://gwizit.com)
- **Email:** Contact through gwizit.com

---

## License

This plugin is licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).

---

## Credits

**Developed by:** G Wiz IT Solutions  
**Website:** [https://gwizit.com](https://gwizit.com)  
**Copyright:** 2025 G Wiz IT Solutions

---

## Changelog

### Version 1.0.0 (2025-10-15)
- Initial release
- File system image search and replacement
- Database (Moodle file storage) search and replacement
- Multi-format support (JPEG, PNG, WebP)
- Automatic resizing and format conversion
- Preview mode (dry run)
- Operation logging
- Privacy API implementation
- Full Moodle 5.1 compatibility

---

## Technical Details

### Database Tables

**`mdl_local_imageplus_log`**
- Stores operation history
- Tracks search terms, files processed, success/failure counts
- Includes dry run indicator and timestamp information

### File Structure
```
imageplus/
├── classes/
│   ├── event/
│   │   └── images_replaced.php
│   ├── form/
│   │   └── replacer_form.php
│   ├── privacy/
│   │   └── provider.php
│   └── replacer.php
├── db/
│   ├── access.php
│   └── install.xml
├── lang/
│   └── en/
│       └── local_imageplus.php
├── index.php
├── renderer.php
├── settings.php
├── version.php
└── README.md
```

---

## Privacy

This plugin implements Moodle's Privacy API and is GDPR compliant:
- Logs which user performed replacement operations
- Stores search terms and operation results
- Provides data export for user data
- Supports data deletion requests
- Does not process personal user images unless specifically targeted

---

## Security

- Requires administrator/manager capabilities
- Session key validation on all operations
- File type validation on uploads
- SQL injection protection via Moodle's DML
- XSS protection via proper output escaping
- File system path traversal prevention

---

## Contributing

Contributions are welcome! 

- **Report bugs:** [GitHub Issues](https://github.com/gwizit/imageplus/issues)
- **Submit pull requests:** [GitHub Repository](https://github.com/gwizit/imageplus)
- **Contact us:** Through [gwizit.com](https://gwizit.com)

Please follow Moodle coding standards when contributing.

---

**Thank you for using ImagePlus by G Wiz IT Solutions!** 🎓
