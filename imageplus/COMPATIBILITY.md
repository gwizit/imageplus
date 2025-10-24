# ImagePlus - Moodle Version Compatibility# ImagePlus - Moodle Version Compatibility



## Version 3.0.3 Compatibility Statement## Version 3.0.3 Compatibility Statement



**Fully Compatible:** Moodle 4.3 through 5.1+**Fully Compatible:** Moodle 4.3 through 5.1+



**Last Updated:** October 21, 2025---



---## Compatibility Analysis



## Quick Summary### Core APIs Used (All Available Since Moodle 2.0+)



✅ **Supported Moodle Versions:** 4.3 - 5.1+  ✅ **File Storage API**

✅ **PHP Requirements:** 7.4+ (8.0+ recommended)  - `get_file_storage()` - File system operations

✅ **Database Support:** MySQL, PostgreSQL, MariaDB  - `$fs->get_file()` - Retrieve stored files

✅ **Testing Status:** Fully tested on Moodle 5.1  - `moodle_url::make_pluginfile_url()` - Generate proper file URLs

✅ **Code Compliance:** All Moodle security and coding standards met- Available since: Moodle 2.0



---✅ **Security Functions**

- `require_capability()` - Permission checking

## Compatibility Analysis- `sesskey()` - Session key generation

- `confirm_sesskey()` - Session key validation

### Core APIs Used (All Stable Since Moodle 2.0+)- `context_system::instance()` - System context

- Available since: Moodle 2.0

✅ **File Storage API**

- `get_file_storage()` - File system operations✅ **Form API**

- `$fs->get_file()` - Retrieve stored files- `moodleform` - Base form class

- `moodle_url::make_pluginfile_url()` - Generate proper file URLs- `MoodleQuickForm` elements - All standard elements used

- Available since: Moodle 2.0- `filepicker` - File upload element

- Available since: Moodle 2.0

✅ **Security Functions**

- `require_login()` - User authentication✅ **Output API**

- `require_capability()` - Permission checking- `html_writer` class - HTML generation

- `sesskey()` - Session key generation- `moodle_url` class - URL handling

- `confirm_sesskey()` - Session key validation- `plugin_renderer_base` - Custom renderers

- `context_system::instance()` - System context- Available since: Moodle 2.0

- Available since: Moodle 2.0

✅ **Other Functions**

✅ **Form API**- `s()` - XSS protection

- `moodleform` - Base form class- `get_string()` - Language strings

- `MoodleQuickForm` elements - All standard elements used- `$PAGE` object - Page rendering

- `filepicker` - File upload element- `$OUTPUT` object - Output functions

- Available since: Moodle 2.0- Available since: Moodle 2.0



✅ **Output API**### Features Requiring No Version-Specific Code

- `html_writer` class - HTML generation

- `moodle_url` class - URL handling✅ **Multi-Step Wizard**

- `plugin_renderer_base` - Custom renderers- Uses Moodle Cache API (MUC) in session mode for state management

- Available since: Moodle 2.0- Form validation through moodleform API

- No Moodle 5.0+ specific features

✅ **Database API (DML)**

- `$DB->get_records()` - Retrieve records✅ **File Operations**

- `$DB->insert_record()` - Insert data- Standard file storage API calls

- `$DB->set_field()` - Update fields- No modern-only methods used

- `$DB->sql_like()` - Cross-DB LIKE queries- Compatible with Moodle 4.3 file handling

- Available since: Moodle 2.0

✅ **Database Operations**

✅ **Privacy API**- Uses `$DB` global (standard since Moodle 2.0)

- `\core_privacy\local\metadata\provider` - Metadata provider- Standard SQL queries compatible with all versions

- `\core_privacy\local\request\plugin\provider` - Request provider- No version-specific database schema requirements

- Available since: Moodle 3.5 (GDPR compliance)

✅ **Security Implementation**

✅ **Events API**- Capability checks using standard API

- `\core\event\base` - Event base class- XSS protection using `s()` function

- Available since: Moodle 2.6- CSRF protection using sesskey() functions

- All methods available in Moodle 4.3+

✅ **Other Core Functions**

- `s()` - XSS protection---

- `get_string()` - Language strings

- `optional_param()` / `required_param()` - Safe input handling## Testing Recommendations

- `$PAGE` object - Page rendering

- `$OUTPUT` object - Output functions### Tested Versions

- Available since: Moodle 2.0- ✅ Moodle 5.1 (Full testing completed)

- ⚠️ Moodle 4.3 - 5.0 (Code review confirms compatibility)

### Features Requiring No Version-Specific Code

### Test Scenarios for Moodle 4.3 - 5.0

✅ **Multi-Step Wizard**1. **Plugin Installation**

- Uses Moodle Cache API (MUC) in session mode for state management   - Install via ZIP upload

- Form validation through moodleform API   - Verify settings page loads

- No Moodle 5.0+ specific features required   - Check language strings render correctly



✅ **File Operations**2. **Wizard Flow**

- Standard file storage API calls   - Step 1: Search criteria form submission

- No modern-only methods used   - Step 2: File selection with checkboxes

- Compatible with Moodle 4.3 file handling   - Step 3: Replacement options and execution

   - Session state preservation between steps

✅ **Database Operations**

- Uses `$DB` global (standard since Moodle 2.0)3. **File Operations**

- Standard SQL queries compatible with all versions   - Search filesystem files

- Parameterized queries for security   - Search database files

- Cross-database compatible SQL   - Replace files in both locations

   - Verify pluginfile URLs work correctly

✅ **Security Implementation**

- Site administrator capability checks (`moodle/site:config`)4. **Security Features**

- Plugin-specific capabilities   - Site admin access restriction

- XSS protection using `s()` function   - Session key validation

- CSRF protection using sesskey() functions   - XSS protection on all inputs/outputs

- All methods available in Moodle 4.3+   - Capability checking



---5. **Results Display**

   - Clickable file links (filesystem)

## Version Requirements   - Clickable file links (database with pluginfile URLs)

   - Statistics display

### Minimum Moodle Version   - Error messages

- **Version Code:** 2023042400 (Moodle 4.3)

- **Release Date:** April 24, 2023---

- **Why This Minimum:** 

  - All APIs used are stable since Moodle 2.0## Version Requirements

  - Set to 4.3 for security best practices

  - Ensures modern PHP support (7.4+)### Minimum Moodle Version

  - Maintains compatibility with currently supported LTS versions- **Version Code:** 2023042400 (Moodle 4.3)

- **Release Date:** April 24, 2023

### Maximum Tested Version- **Why This Minimum:** All APIs used are stable since Moodle 2.0, but we set 4.3 as minimum for security best practices and modern PHP support

- **Version:** Moodle 5.1+

- **Release Date:** November 2024### Maximum Tested Version

- **Testing Status:** ✅ Fully tested and working- **Version:** Moodle 5.1+

- **Release Date:** November 2024

### PHP Requirements- **Testing Status:** Fully tested and working

- **Minimum:** PHP 7.4

- **Recommended:** PHP 8.0 or higher### PHP Requirements

- **Reason:** Modern PHP features for better performance and security- **Minimum:** PHP 7.4

- **Optional:** GD library for image processing (graceful degradation if not available)- **Recommended:** PHP 8.0+

- **Reason:** Modern PHP features for better performance and security

### Database Requirements

- **Supported:**---

  - ✅ MySQL 5.7+

  - ✅ MariaDB 10.4+## Known Compatibility Issues

  - ✅ PostgreSQL 12+

- **Compatibility:** Uses Moodle DML API for cross-database compatibility### None Identified

- No breaking changes between Moodle 4.3 and 5.1 affect this plugin

---- All APIs used are stable and backward compatible

- State management uses Moodle Cache API (MUC) in session mode

## Testing Status

---

### Fully Tested Versions

- ✅ **Moodle 5.1** - Full testing completed (October 2025)## Upgrade Path

  - All wizard steps tested

  - File operations verified### From Moodle 4.3 to 5.x

  - Security features validated- No changes required

  - Performance tested- Plugin works identically across versions

- No database schema changes needed

### Code-Review Confirmed Compatibility

- ✅ **Moodle 4.3 - 5.0**### From Moodle 5.0 to 5.1

  - All APIs used are available- No changes required

  - No version-specific code paths- Fully forward compatible

  - Standard Moodle patterns used throughout

---

---

## Future Compatibility

## Known Compatibility Issues

### Expected Compatibility

### None Identified ✅This plugin should continue working with future Moodle versions (5.2+) because:

1. Uses only core, stable APIs

- ✅ No breaking changes between Moodle 4.3 and 5.1 affect this plugin2. No deprecated function calls

- ✅ All APIs used are stable and backward compatible3. Standard security practices

- ✅ State management uses Moodle Cache API (session mode)4. Follows Moodle coding standards

- ✅ Database queries are cross-DB compatible5. No hard-coded version checks

- ✅ No deprecated function calls

- ✅ Follows current Moodle coding standards### Monitoring Required

- Watch for deprecation notices in future Moodle releases

---- Test with Moodle beta versions when available

- Update if any core APIs change (unlikely for APIs used)

## Support Statement

---

**Developer:** G Wiz IT Solutions  

**Website:** https://gwizit.com  ## Support Statement

**Bug Tracker:** https://github.com/gwizit/imageplus/issues  

**Source Code:** https://github.com/gwizit/imageplus  **G Wiz IT Solutions** commits to maintaining compatibility with:

**License:** GNU GPL v3 or later- Current Moodle LTS version and newer

- Currently: Moodle 4.3+ (LTS released April 2023)

---- Future updates will be released if compatibility breaks



*Compatibility Guide - ImagePlus v3.0.3*  ---

*Last Updated: October 21, 2025*  

*G Wiz IT Solutions*## Contact & Support


- **Developer:** G Wiz IT Solutions
- **Website:** https://gwizit.com
- **License:** GNU GPL v3 or later
- **Support:** For compatibility issues, please test thoroughly in your specific Moodle environment

---

*Last Updated: Version 3.0.3 - October 19, 2025*
