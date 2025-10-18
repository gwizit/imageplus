# Moodle ImagePlus Plugin - Package Creation Script
# Run this script from PowerShell to create a distribution ZIP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Moodle ImagePlus Plugin Packager" -ForegroundColor Cyan
Write-Host "By G Wiz IT Solutions" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Set locations
$PluginDir = "F:\moodleimagereplacer\imageplus"
$OutputDir = "F:\moodleimagereplacer"
$OutputFile = "$OutputDir\moodle-local_imageplus-v3.0.0.zip"

# Check if plugin directory exists
if (-not (Test-Path $PluginDir)) {
    Write-Host "ERROR: Plugin directory not found at $PluginDir" -ForegroundColor Red
    exit 1
}

Write-Host "Plugin directory: $PluginDir" -ForegroundColor Green
Write-Host "Output file: $OutputFile" -ForegroundColor Green
Write-Host ""

# Remove old ZIP if exists
if (Test-Path $OutputFile) {
    Write-Host "Removing existing ZIP file..." -ForegroundColor Yellow
    try {
        # Wait a moment for any file handles to close
        Start-Sleep -Milliseconds 500
        Remove-Item $OutputFile -Force -ErrorAction Stop
    } catch {
        Write-Host "Could not remove existing file. Trying alternate name..." -ForegroundColor Yellow
        $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
        $OutputFile = "$OutputDir\moodle-local_imageplus-v3.0.0-$timestamp.zip"
        Write-Host "New output file: $OutputFile" -ForegroundColor Cyan
    }
}

# Create ZIP file
Write-Host "Creating ZIP package..." -ForegroundColor Yellow

try {
    # Load ZIP assemblies
    Add-Type -Assembly System.IO.Compression
    Add-Type -Assembly System.IO.Compression.FileSystem
    
    # Remove temp file if exists
    $tempZip = "$OutputDir\temp_plugin.zip"
    if (Test-Path $tempZip) { Remove-Item $tempZip -Force }
    
    # Create ZIP manually to ensure proper structure
    # Moodle expects: zipfile/imageplus/version.php
    $zip = [System.IO.Compression.ZipFile]::Open($tempZip, 'Create')
    
    # Add all files from imageplus folder
    $files = Get-ChildItem -Path "$OutputDir\imageplus" -Recurse -File
    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($OutputDir.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relativePath, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
    
    $zip.Dispose()
    
    # Move to final location
    if (Test-Path $OutputFile) { Remove-Item $OutputFile -Force }
    Move-Item $tempZip $OutputFile
    
    Write-Host "✓ Package created successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "ZIP File: $OutputFile" -ForegroundColor Cyan
    
    # Get file size
    $FileSize = (Get-Item $OutputFile).Length / 1KB
    Write-Host "File Size: $([math]::Round($FileSize, 2)) KB" -ForegroundColor Cyan
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Upload the ZIP to your Moodle site" -ForegroundColor White
    Write-Host "   (Site admin → Plugins → Install plugins)" -ForegroundColor White
    Write-Host "2. Or extract and copy 'imageplus' folder" -ForegroundColor White
    Write-Host "   to [moodle]/local/ directory" -ForegroundColor White
    Write-Host "========================================" -ForegroundColor Cyan
    
} catch {
    Write-Host "ERROR: Failed to create ZIP package" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
