# How to Access the Image Replacer Tool

## Quick Access

**Direct URL Method (Fastest):**

Go to: `https://your-moodle-site.com/local/imagereplacer/index.php`

Replace `your-moodle-site.com` with your actual Moodle domain.

---

## Via Admin Menu

The tool should appear in one of these locations:

### Location 1: Server Section (Most Common)
1. Site administration
2. **Server** 
3. **Image Replacer** ← Click here

### Location 2: Tools Section (Alternative)
1. Site administration
2. Expand "Server" or look for **Tools**
3. **Image Replacer** ← Click here

---

## If You Don't See It in the Menu

### Step 1: Clear All Caches
1. Go to: Site administration → Development → **Purge all caches**
2. Click "Purge all caches"
3. Wait for confirmation
4. Refresh your browser (Ctrl+F5 or Cmd+Shift+R)
5. Look for the menu item again

### Step 2: Check Permissions
1. Go to: Site administration → Users → Permissions → **Check system permissions**
2. Search for: `local/imagereplacer:view`
3. Verify it shows "Yes" or "Allow"
4. If not, grant the permission to your role

### Step 3: Use Direct URL (Works Always)
Simply navigate to:
```
[Your Moodle URL]/local/imagereplacer/index.php
```

Example:
- If your Moodle is at `http://localhost/moodle/`
- Go to: `http://localhost/moodle/local/imagereplacer/index.php`

---

## What You Should See

When you access the tool correctly, you'll see:

**✓ Image Replacer Tool** heading  
**✓ A form** with these fields:
- Search term input
- File upload button for replacement image
- Execution mode dropdown (Preview/Execute)
- Checkboxes for options
- "Find matching images" and "Replace images" buttons

---

## Common Menu Locations by Moodle Version

**Moodle 5.x:**
- Site administration → Server → Image Replacer

**Moodle 4.x:**
- Site administration → Plugins → Local plugins → Image Replacer (settings)
- Site administration → Server → Image Replacer (tool)

---

## Settings vs Tool

**Important Distinction:**

📋 **Settings Page** (Plugins → Local plugins → Image Replacer)
- Configuration defaults
- No file upload here
- Just checkboxes and text fields

🛠️ **The Tool** (Server → Image Replacer OR direct URL)
- This is where you upload files
- Where you do the actual replacement
- Has the upload button and search functionality

---

## Quick Start After Finding the Tool

1. **Enter search term** - e.g., "logo" or "BACB"
2. **Upload replacement image** - Click the file chooser
3. **Select mode** - Start with "Preview only"
4. **Click "Find matching images"** to see what will be replaced
5. When ready, switch to "Execute" and click "Replace images"

---

## Still Can't Find It?

**Bookmark this direct URL:**
```
[Your-Moodle]/local/imagereplacer/index.php
```

This will always work as long as:
- ✅ Plugin is installed
- ✅ You're logged in as admin
- ✅ You have the required permissions

---

**Need Help?** Visit https://gwizit.com
