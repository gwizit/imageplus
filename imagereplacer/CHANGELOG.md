### Version 1.0.5 - 2025-10-15

#### Patch Release

**Fixed:**
- Hardened filepicker toolbar patch for Moodle 5.x/YUI edge cases
- Prevented `activenode` null exceptions when repository list is empty
- Ensures Add dialog opens even when toolbar markup is missing

**Technical Changes:**
- Converts raw DOM references into YUI nodes before use
- Creates fallback toolbar/search/buttons containers when absent
- Wraps original handlers with defensive error handling

---

### Version 1.0.4 - 2025-10-15

#### Bug Fix Release

**Fixed:**
- Patched Moodle YUI file picker crash (`toolbar.one(...) is null`)
- Restored Add button functionality inside filemanager
- Works alongside drag-and-drop upload without JavaScript errors

**Technical Changes:**
- Added AMD patch to guard `M.core_filepicker.prototype.setup_toolbar`
- Ensured minimal toolbar markup exists before rendering
- Automatically loads patch on tool page

---

### Version 1.0.3 - 2025-10-15

#### Bug Fix Release

**Fixed:**
- **CRITICAL FIX**: Resolved PHP PEAR HTML_QuickForm_file compatibility issue
- Reverted to filemanager with proper configuration to avoid repository picker issues
- File upload now works correctly in Moodle 5.0+ without JavaScript errors
- Properly configured draft file area handling

**Technical Changes:**
- Using filemanager with minimal options (maxfiles=1, no subdirs)
- Proper draft area initialization
- File validation through Moodle file storage API
- Maximum file size: 10MB

---

### Version 1.0.2 - 2025-10-15

#### Bug Fix Release (Deprecated - Use 1.0.3)

**Fixed:**
- Attempted to use plain HTML file input (caused PEAR compatibility issues)
- File upload now uses standard PHP $_FILES handling
- Added file type and size validation

**Technical Changes:**
- Changed from `filepicker` to `file` element type
- Direct file upload handling instead of draft area
- Added MIME type validation
- Maximum file size: 10MB

---

### Version 1.0.1 - 2025-10-15

#### Bug Fix Release

**Fixed:**
- Fixed file picker JavaScript error in Moodle 5.0+
- Changed from filemanager to filepicker element for better compatibility
- Lowered minimum Moodle version requirement to 5.0 (was 5.1)

**Changes:**
- Improved menu location detection (Server → Image Replacer)
- Added fallback to Tools menu if Server category not available

---

### Version 1.0.0 - 2025-10-15

#### Initial Release

**New Features:**
- File system image search and replacement
- Database (Moodle file storage) search and replacement  
- Multi-format support (JPEG, PNG, WebP)
- Automatic resizing to match target dimensions
- Automatic format conversion
- Preview mode (dry run) for safe testing
- File permission preservation
- Transparency support for PNG/WebP
- Detailed operation logging
- Privacy API implementation
- GDPR compliance
- Admin interface with form validation
- Statistics and progress reporting
- Event logging

**Compatibility:**
- Moodle 5.1 and above
- PHP 7.4 and above

**Technical Details:**
- Implements Moodle coding standards
- Full database abstraction using Moodle DML
- Proper capability checks
- Session key validation
- XSS and SQL injection protection

**Credits:**
Developed by G Wiz IT Solutions - https://gwizit.com
