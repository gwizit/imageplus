# ImagePlus - Troubleshooting Guide

## Version 3.0.3

This guide covers common issues and solutions for the ImagePlus plugin.

**Compatibility:** Moodle 4.3 to 5.1+  
**Last Updated:** October 21, 2025

---

## Table of Contents

1. [Installation Issues](#installation-issues)
2. [Display Issues](#display-issues)
3. [Access and Permission Issues](#access-and-permission-issues)
4. [File Search Issues](#file-search-issues)
5. [File Replacement Issues](#file-replacement-issues)
6. [Performance Issues](#performance-issues)
7. [Image Quality Issues](#image-quality-issues)
8. [Wizard Navigation Issues](#wizard-navigation-issues)
9. [Security Issues](#security-issues)

---

## Installation Issues

### Plugin Type Not Detected During ZIP Upload

**Symptoms:**
- Message: "Unable to detect the plugin type"

**Cause:** Moodle security feature requires manual plugin type selection.

**Solution:**
1. When prompted, select **"Local plugin (local)"** from the "Plugin type" dropdown
2. Confirm the plugin folder name shows as **"imageplus"**
3. Click "Install plugin from the ZIP file"
4. This is normal behavior for security - not an error

### Installation Completes But Plugin Not Visible

**Solution:**
1. Go to **Site administration → Development → Purge all caches**
2. Click "Purge all caches"
3. Refresh your browser

---

## Display Issues

### Language Strings Showing as [[stringname]]

**Symptoms:**
- Text appears as `[[nofilesreplaced]]` instead of "No files were replaced"
- Text appears as `[[preview_mode_warning]]` instead of the actual warning message

**Cause:** Moodle's language string cache is outdated after plugin installation/update.

**Solution 1: Purge All Caches (RECOMMENDED)**
1. Go to **Site administration → Development → Purge all caches**
2. Click "Purge all caches" button
3. Refresh the ImagePlus page

**Solution 2: CLI Method (Fastest)**
```bash
php admin/cli/purge_caches.php
```

**Prevention:**
- Always purge caches after installing or updating any plugin
- Enable developer mode during development: **Site administration → Development → Debugging**

---

## Installation Issues

### Error: "corrupted_archive_structure"

**Cause:** Moodle's strict ZIP validation or browser upload issues.

**Solution 1: Manual Installation (RECOMMENDED)**

1. Extract the ZIP file
2. Copy the `imageplus` folder to `[your-moodle]/local/`
3. Ensure the path is: `[moodle]/local/imageplus/version.php`
4. Visit **Site administration → Notifications** in Moodle
5. Click **"Upgrade Moodle database now"**

**On Linux/Mac:**
```bash
cd /path/to/moodle/local/
unzip moodle-local_imageplus-v3.0.0.zip
cd ..
sudo chown -R www-data:www-data local/imageplus
sudo chmod -R 755 local/imageplus
php admin/cli/upgrade.php
```

**Solution 2: Try Different Browser**
- Try Chrome, Firefox, or Edge
- Clear browser cache
- Disable browser extensions temporarily

### Error: "Unable to detect the plugin type"

**This is NORMAL** - Moodle 5.x security feature.

**Solution:**
1. When prompted, select **"Local plugin (local)"** from dropdown
2. Verify folder name shows as **"imageplus"**
3. Click "Install plugin from the ZIP file"
4. Continue with installation

### Plugin Not Appearing After Installation

**Solution:**
1. Go to **Site administration → Notifications** and run any upgrades
2. Clear caches: **Site administration → Development → Purge all caches**
3. Verify files exist:
   ```
   [moodle]/local/imageplus/version.php
   [moodle]/local/imageplus/settings.php
   [moodle]/local/imageplus/index.php
   ```
4. Check PHP error logs for issues

---

## Access and Permission Issues

### Error: "Access denied. This tool is only available to site administrators"

**Cause:** User does not have site administrator permissions.

**Solution:**
- This is intentional security restriction in v3.0.0
- Only users with `moodle/site:config` capability can access
- Contact your site administrator if you need access
- Administrators can verify permissions at: **Site administration → Users → Permissions**

**Note:** Unlike previous versions, the `local/imageplus:manage` capability is NOT sufficient. Site administrator permission is required.

### Error: "You do not have permission to use this tool"

**Cause:** Missing plugin-specific capability.

**Solution:**
1. Ensure you are a site administrator first
2. Verify you have `local/imageplus:view` and `local/imageplus:manage` capabilities
3. Check role assignments: **Site administration → Users → Permissions → Assign system roles**

### Session Expired or Invalid Session Key

**Cause:** Form session expired or CSRF protection triggered.

**Solution:**
1. Refresh the page and log in again
2. Don't use browser back button - use wizard's "Back" button
3. Don't let the page sit idle for extended periods
4. Clear browser cookies and log in again

---

## File Search Issues

### No Files Found

**Possible Causes & Solutions:**

1. **Incorrect Search Term**
   - Try using wildcards: `logo*` instead of just `logo`
   - Check spelling and case sensitivity
   - Try a simpler, broader search term first

2. **Wrong File Type Selected**
   - Verify you selected correct type (Images, PDFs, etc.)
   - Remember: Images = JPG/PNG/WebP only

3. **Wrong Search Locations**
   - Check both "Include database files" AND "Include file system" boxes
   - Database files are in Moodle's file storage (`mdl_files` table)
   - Filesystem files are in Moodle installation directories

4. **Files in Unsupported Locations**
   - Plugin only searches specific directories (see README)
   - Custom theme directories may not be scanned

### Wildcard Search Not Working

**Cause:** Incorrect wildcard syntax.

**Correct Syntax:**
- `logo*` - Matches logo.png, logo-2024.jpg, logo_old.gif
- `*logo` - Matches mylogo.png, newlogo.jpg
- `banner?` - Matches banner1, banner2, bannerA
- `ba*er` - Matches banner, batter, badger

**Common Mistakes:**
- Using regex instead of wildcards
- Using `%` instead of `*` (SQL syntax doesn't work here)

### Duplicate Files in Results

**Cause:** Same file exists in both database and filesystem.

**Solution:**
- This is normal - Moodle stores files in database AND filesystem
- Select which instance(s) to replace in Step 2
- Usually safer to replace database version only

---

## File Replacement Issues

### Error: "Please select at least one file to replace"

**Cause:** No checkboxes selected in Step 2.

**Solution:**
1. Go back to Step 2
2. Check at least one file using the checkboxes
3. Or use "Select All" button
4. Click "Next" to proceed

### Error: "Extension mismatch: pdf file cannot replace jpg files"

**Cause:** Uploaded file type doesn't match target files.

**Solution:**
1. Upload file with matching extension:
   - PDF → PDF only
   - ZIP → ZIP only
   - JPG → JPG (or PNG/WebP if cross-format enabled)
2. For images: Enable "Allow cross-format image replacement" in Step 3
3. Verify GD library is available for cross-format conversion

### Error: "Invalid file type. Please upload a JPEG, PNG, or WebP image"

**Cause:** Uploaded file doesn't match selected file type category.

**Solution:**
1. Check file extension matches what you're replacing
2. Verify file is not corrupted
3. For images: Only JPG, PNG, WebP are supported
4. Try converting file to supported format first

### Backup Confirmation Not Working

**Cause:** Checkbox not checked or JavaScript disabled.

**Solution:**
1. Scroll down in Step 3
2. Check the box: "I confirm that a recent backup has been made"
3. If checkbox is missing, enable JavaScript in your browser
4. Read the warning message carefully before proceeding

### Files Not Actually Replaced

**Possible Causes:**

1. **Preview Mode Selected**
   - Solution: Change "Execution mode" to "Execute changes" in Step 3

2. **Permission Errors**
   - Check PHP error logs
   - Verify web server has write permissions
   - On Linux: `sudo chown -R www-data:www-data [moodle-dirroot]`

3. **Memory Limit Exceeded**
   - Increase PHP memory limit in php.ini
   - Process fewer files at once

### Some Files Failed to Replace

**Cause:** Various - check replacement log for specific errors.

**Common Reasons:**
- Permission denied on specific files
- File locked by another process
- Insufficient disk space
- File corrupted or invalid format

**Solution:**
1. Review detailed replacement log on results page
2. Note which specific files failed
3. Check PHP error logs for details
4. Try replacing failed files individually

---

## Performance Issues

### Wizard Slow or Timing Out

**Cause:** Too many files to process or server limitations.

**Solutions:**
1. **Use More Specific Search Terms**
   - Instead of `*`, use `logo*` or `*logo`
   - This reduces number of files to scan

2. **Search One Location at a Time**
   - Try database only first
   - Then filesystem only
   - Don't check both if not needed

3. **Increase PHP Limits** (in php.ini):
   ```ini
   max_execution_time = 300
   memory_limit = 512M
   post_max_size = 100M
   upload_max_filesize = 100M
   ```

4. **Process in Batches**
   - Select fewer files in Step 2
   - Run multiple replacement operations

### Session Lost During Processing

**Cause:** Operation taking too long.

**Solution:**
1. Increase PHP session timeout
2. Process fewer files at once
3. Run during off-peak hours
4. Consider upgrading server resources

---

## Image Quality Issues

### Images Appear Blurry or Low Quality

**Cause:** Source image smaller than targets or compression issues.

**Solutions:**
1. Use source image larger than or equal to target images
2. Use high-quality source images
3. For JPEGs, use quality 90+ source images
4. Consider using PNG for graphics to avoid JPEG compression

### Transparency Lost

**Cause:** Converting from PNG/WebP to JPEG.

**Solutions:**
1. Keep source and target in PNG or WebP format
2. Don't enable cross-format conversion for transparent images
3. JPEG doesn't support transparency
4. Ensure GD library is installed and configured correctly

### Colors Look Different

**Cause:** Color profile or format conversion issues.

**Solutions:**
1. Keep same format (JPG→JPG, PNG→PNG)
2. Use sRGB color profile in source images
3. Test cross-format conversion on a few images first
4. Check GD library configuration

---

## Wizard Navigation Issues

### Can't Go Back to Previous Step

**Cause:** Browser back button used instead of wizard's Back button.

**Solution:**
- Always use the wizard's "Back" button at bottom of form
- Don't use browser navigation buttons
- Session state may be lost if browser back is used

### Lost My Selections When Going Back

**Cause:** Session data cleared or expired.

**Solution:**
- File selections in Step 2 are not preserved when going back to Step 1
- This is intentional - new search may find different files
- Complete the wizard without going back if possible
- Session cleared automatically after 2 hours of inactivity

### Wizard Stuck on a Step

**Cause:** Validation error or missing required fields.

**Solutions:**
1. Check for error messages at top of page
2. Verify all required fields are filled
3. In Step 3, ensure backup confirmation is checked
4. Refresh page and start over if necessary
5. Clear browser cache and cookies

---

## Security Issues

### Getting CSRF/Session Key Errors

**Cause:** Session security validation failing.

**Solutions:**
1. Don't open plugin in multiple browser tabs
2. Don't let page sit idle for extended periods
3. Clear cookies and log in again
4. Check Moodle session configuration in config.php

### Directory Traversal Error

**Cause:** Security validation preventing invalid file paths.

**This is intentional** - protecting against malicious file path manipulation.

**If you get this in error:**
1. Ensure files are within Moodle dirroot
2. Don't manually edit form values
3. Contact administrator if legitimate files are blocked

### XSS Protection Errors

**Cause:** Content sanitization removing unsafe content.

**This is intentional** security protection.

**Note:** All user input and output is sanitized to prevent XSS attacks. If content appears modified, it's for security.

---

## GD Library Issues

### Warning: "GD library is not available"

**Impact:**
- Cross-format image conversion disabled (JPG→PNG, PNG→WebP, etc.)
- Images can still be replaced with exact same format
- Image resizing may not work properly

**Solutions:**

**On Linux (Ubuntu/Debian):**
```bash
sudo apt-get install php-gd
sudo systemctl restart apache2  # or php-fpm
```

**On Windows (XAMPP):**
1. Edit php.ini
2. Find line: `;extension=gd`
3. Remove semicolon: `extension=gd`
4. Restart Apache

**On cPanel:**
1. Go to "Select PHP Version" or "PHP Extensions"
2. Enable "gd" extension
3. Save changes

**Verification:**
Create file `phpinfo.php` with:
```php
<?php phpinfo(); ?>
```
Look for "GD Support: enabled"

---

## Still Having Issues?

### Enable Debugging

1. **Moodle Debugging:**
   - Go to **Site administration → Development → Debugging**
   - Set to "DEVELOPER: extra Moodle debug messages"
   - Set "Display debug messages" to "Yes"

2. **PHP Error Logs:**
   - Linux: `/var/log/apache2/error.log` or `/var/log/php-fpm/error.log`
   - XAMPP: `xampp/apache/logs/error.log`
   - WAMP: `wamp/logs/php_error.log`

3. **Moodle Logs:**
   - **Site administration → Reports → Logs**
   - Filter by user and "local_imageplus"

### Gather Information

When reporting issues, include:
- Moodle version
- Plugin version (v3.0.3)
- PHP version
- Exact error message
- Steps to reproduce
- Browser and OS
- PHP error log entries
- Screenshot if applicable

### Contact Support

- **Bug Tracker:** https://github.com/gwizit/moodle-local_imageplus/issues
- **Source Code:** https://github.com/gwizit/moodle-local_imageplus
- **Website:** https://gwizit.com
- **Documentation:** See README.md, COMPATIBILITY.md, SECURITY_REVIEW.md

---

## Common Error Messages Reference

| Error Message | Most Common Cause | Quick Fix |
|--------------|-------------------|-----------|
| "Access denied" | Not site administrator | Must be site admin (not just manager) |
| "No files selected" | Checkboxes not checked | Select files in Step 2 |
| "Extension mismatch" | Wrong file type uploaded | Upload matching file type |
| "Backup confirmation required" | Checkbox not checked | Check backup confirmation in Step 3 |
| "Session expired" | Page idle too long | Refresh and start over |
| "Invalid file type" | Uploaded file wrong format | Check file mimetype matches |
| "GD library not available" | PHP extension not installed | Install php-gd extension |
| "Permission denied" | File system permissions | Check web server write permissions |
| "Memory limit exceeded" | File too large or too many | Increase PHP memory or process fewer files |

---

*Troubleshooting Guide - ImagePlus v3.0.3*  
*Last Updated: October 21, 2025*  
*G Wiz IT Solutions*

