# Troubleshooting Installation Issues

## Error: "corrupted_archive_structure"

If you get this error when uploading the ZIP file, it's usually due to Moodle's strict ZIP validation. Here are solutions:

### Solution 1: Use Manual Installation (RECOMMENDED)

**On Windows:**
1. Run `manual_install.ps1` in PowerShell
2. Enter your Moodle path when prompted (e.g., `C:\xampp\htdocs\moodle`)
3. The script will copy files automatically
4. Visit **Site administration → Notifications** in Moodle
5. Click **"Upgrade Moodle database now"**

**Manual Copy:**
1. Extract the ZIP file
2. Copy the `imagereplacer` folder to `[your-moodle]/local/`
3. Ensure the path is: `[moodle]/local/imagereplacer/version.php`
4. Visit **Site administration → Notifications** in Moodle
5. Click **"Upgrade Moodle database now"**

### Solution 2: Try Different Browser

Sometimes browser-based ZIP uploads have issues:
- Try Google Chrome instead of Firefox (or vice versa)
- Clear browser cache and try again
- Disable browser extensions temporarily

### Solution 3: Check File Permissions

On Linux/Mac servers:
```bash
# Set proper ownership
sudo chown -R www-data:www-data /path/to/moodle/local/imagereplacer

# Set proper permissions
sudo chmod -R 755 /path/to/moodle/local/imagereplacer
```

---

## Error: "Unable to detect the plugin type"

This is NORMAL behavior in Moodle 5.x:

1. When prompted, select **"Local plugin (local)"** from the dropdown
2. Verify the folder name shows as **"imagereplacer"**
3. Click "Install plugin from the ZIP file"
4. Continue with installation

This is a security feature, not an error.

---

## Error: Missing version.php

If Moodle says it can't find version.php:

1. Extract the ZIP and verify structure:
   ```
   imagereplacer/
   ├── version.php
   ├── settings.php
   ├── index.php
   └── ...
   ```

2. The ZIP should contain ONE folder named `imagereplacer` with all files inside

3. Recreate the ZIP by running `create_package.ps1` again

---

## Plugin Not Appearing in Menu

After successful installation:

1. Go to **Site administration → Notifications**
2. Run any pending upgrades
3. Clear Moodle caches: **Site administration → Development → Purge all caches**
4. Check you have the permission: **Site administration → Users → Permissions → Assign system roles**
   - Ensure your role has `local/imagereplacer:view` capability

---

## Manual Installation Verification

After manual installation, verify these files exist:

```
[moodle]/local/imagereplacer/version.php
[moodle]/local/imagereplacer/settings.php
[moodle]/local/imagereplacer/index.php
[moodle]/local/imagereplacer/classes/replacer.php
[moodle]/local/imagereplacer/db/install.xml
[moodle]/local/imagereplacer/db/access.php
[moodle]/local/imagereplacer/lang/en/local_imagereplacer.php
```

---

## Database Errors During Installation

If you get database errors:

1. Check PHP memory limit (should be 256MB+):
   ```php
   memory_limit = 256M
   ```

2. Check database connection in Moodle's config.php

3. Ensure database user has CREATE TABLE permissions

4. Check Moodle debug mode for detailed errors:
   **Site administration → Development → Debugging**
   - Set to "DEVELOPER: extra Moodle debug messages for developers"

---

## Still Having Issues?

1. **Check Moodle logs:**
   Site administration → Reports → Logs

2. **Check PHP error logs:**
   - Linux: `/var/log/apache2/error.log` or `/var/log/php-fpm/error.log`
   - XAMPP: `xampp/apache/logs/error.log`
   - WAMP: `wamp/logs/php_error.log`

3. **Enable Moodle debugging:**
   Add to config.php (temporarily):
   ```php
   $CFG->debug = 32767;
   $CFG->debugdisplay = 1;
   ```

4. **Contact Support:**
   https://gwizit.com

---

## Recommended Installation Method

For most users, **manual installation** is the most reliable:

1. Use `manual_install.ps1` (Windows) 
2. Or manually copy the `imagereplacer` folder
3. Then visit Notifications page in Moodle

This avoids ZIP upload issues entirely and works on all Moodle versions.

---

**Note**: These errors are often related to server configuration or Moodle version-specific quirks, not the plugin itself. Manual installation works 99% of the time.
