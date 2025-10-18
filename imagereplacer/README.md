# Moodle Image Replacer Plugin

A powerful Moodle plugin that allows administrators to search and replace images across their Moodle installation based on filename patterns. The plugin automatically resizes and converts images to match the format and dimensions of the target images.

**Developed by:** [G Wiz IT Solutions](https://gwizit.com)  
**Plugin Type:** Local  
**Compatibility:** Moodle 5.1 and above

---

## Features

- 🔍 **Smart Search**: Find images by filename pattern across your entire Moodle installation
- 🗄️ **Database Integration**: Search both file system and Moodle's database file storage
- 🎨 **Format Conversion**: Automatically converts replacement images to match target format (JPEG, PNG, WebP)
- 📐 **Auto-Resizing**: Intelligently resizes images to match target dimensions
- 🔒 **Safe Preview Mode**: Test replacements without making any changes
- 📊 **Detailed Logging**: Track all operations with comprehensive logs
- 🔐 **Permission Control**: File permissions are preserved during replacement
- 🌍 **Transparency Support**: Maintains transparency for PNG and WebP images
- 📝 **Operation History**: Database logging of all replacement operations
- ⚡ **Batch Processing**: Replace multiple images in a single operation

---

## Installation

### Method 1: Via Moodle Plugin Installer (Recommended)

1. Download the plugin ZIP file
2. Log in to your Moodle site as an administrator
3. Go to **Site administration** → **Plugins** → **Install plugins**
4. Upload the ZIP file
5. **If prompted** with "Unable to detect the plugin type":
   - Select **"Local plugin (local)"** from the "Plugin type" dropdown
   - Confirm the plugin folder name shows as **"imagereplacer"**
6. Click "Install plugin from the ZIP file"
7. Follow the on-screen instructions to complete the installation

**Note**: Some Moodle installations require manual plugin type selection for security. This is normal behavior.

### Method 2: Manual Installation (If ZIP upload fails)

**Recommended if you get "corrupted_archive_structure" error:**

1. Extract the plugin ZIP file
2. Copy the `imagereplacer` folder to `[moodle-root]/local/`
3. Log in to your Moodle site as an administrator
4. Navigate to **Site administration** → **Notifications**
5. Click **"Upgrade Moodle database now"**
6. Follow the on-screen instructions to complete the installation

**Tip**: On Windows, you can use the included `manual_install.ps1` helper script.

### Method 3: Via Command Line

```bash
cd [moodle-root]/local/
git clone [repository-url] imagereplacer
cd [moodle-root]
php admin/cli/upgrade.php
```

---

## Configuration

After installation, configure the plugin defaults:

1. Go to **Site administration** → **Plugins** → **Local plugins** → **Image Replacer**
2. Configure the following settings:
   - **Default search term**: Default term to search for in filenames
   - **Default execution mode**: Preview or Execute (Preview recommended for safety)
   - **Preserve permissions by default**: Keep original file permissions
   - **Search database by default**: Include Moodle's file storage
   - **Search file system by default**: Include file system directories

---

## Usage

### Accessing the Tool

1. Log in as a site administrator or manager
2. Go to **Site administration** → **Server** → **Image Replacer**

### Running a Replacement

1. **Enter Search Term**: Type the text pattern to search for in image filenames (e.g., "logo", "BACB", "banner")
2. **Upload Replacement Image**: Select the image that will replace all matching images
   - Supported formats: JPEG, PNG, WebP
   - Maximum file size: 10MB
3. **Choose Execution Mode**:
   - **Preview Only**: See what would be changed without modifying files (recommended first)
   - **Execute Changes**: Actually perform the replacements
4. **Select Options**:
   - ☑️ Preserve file permissions
   - ☑️ Include database images
   - ☑️ Include file system
5. Click **Find matching images** to preview, or **Replace images** to execute

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

The plugin defines two capabilities:

- **`local/imagereplacer:view`**: View the image replacer tool
- **`local/imagereplacer:manage`**: Perform image replacement operations

By default, these are granted to the **Manager** role at the system context level.

---

## Best Practices

### Safety First
1. ✅ Always run in **Preview mode** first
2. ✅ Back up your Moodle data before running replacements
3. ✅ Test with a specific search term on a small set of images
4. ✅ Review the list of found images before executing

### Performance
1. ⚡ For large sites, consider searching database OR file system separately
2. ⚡ Use specific search terms to limit the number of matches
3. ⚡ Run operations during low-traffic periods

### Image Quality
1. 🎨 Use high-quality source images that scale well
2. 🎨 Source images should ideally be larger than target images
3. 🎨 For transparent images, use PNG or WebP source format
4. 🎨 JPEG is best for photographs, PNG for graphics with transparency

---

## Requirements

### Moodle Requirements
- **Moodle version**: 5.1 or higher
- **PHP version**: 7.4 or higher (8.0+ recommended)

### PHP Extensions Required
- GD Library (with JPEG, PNG support)
- Optional: WebP support for WebP image handling

### Server Requirements
- Write permissions to Moodle's `dataroot/filedir` directory
- Sufficient PHP memory limit (128MB minimum, 256MB+ recommended)
- PHP `max_execution_time` sufficient for batch operations

---

## Troubleshooting

### Images Not Found
- Check that search term matches image filenames exactly
- Verify you have selected the correct search locations
- Ensure images are in supported formats (JPEG, PNG, WebP)

### Permission Denied Errors
- Verify you have the `local/imagereplacer:manage` capability
- Check file system permissions on Moodle directories
- Ensure web server has write access to target directories

### Memory Errors
- Increase PHP memory limit in php.ini
- Process fewer images at once
- Use lower quality source images

### WebP Not Supported
- Install PHP WebP extension
- Use JPEG or PNG as source format instead

---

## Support

For issues, questions, or feature requests:

- **Website**: [https://gwizit.com](https://gwizit.com)
- **Email**: Contact through gwizit.com
- **Moodle Plugins**: [Plugin page on Moodle.org]

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

**`mdl_local_imagereplacer_log`**
- Stores operation history
- Tracks search terms, files processed, success/failure counts
- Includes dry run indicator and timestamp information

### File Structure
```
imagereplacer/
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
│       └── local_imagereplacer.php
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

Contributions are welcome! Please contact G Wiz IT Solutions through [gwizit.com](https://gwizit.com) for more information.

---

**Thank you for using Image Replacer by G Wiz IT Solutions!** 🎓
