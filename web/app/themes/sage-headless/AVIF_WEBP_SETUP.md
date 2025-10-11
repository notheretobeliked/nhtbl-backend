# AVIF + WebP + Color Analysis Setup

## What Was Configured

### 1. **WebP Uploads Plugin Configuration**
Added filter to ensure both WebP AND AVIF versions are generated for all uploads:

```php
// In setup.php
add_filter('webp_uploads_upload_image_mime_transforms', function($transforms) {
    return [
        'image/jpeg' => ['image/webp', 'image/avif'],
        'image/png' => ['image/webp', 'image/avif'],
        'image/gif' => ['image/webp'],
    ];
});
```

This means:
- ✅ **JPEGs** → Get WebP + AVIF versions
- ✅ **PNGs** → Get WebP + AVIF versions  
- ✅ **GIFs** → Get WebP version only

### 2. **Color Analysis Fallback**
Updated `ImageColors.php` to intelligently handle AVIF images:

**Smart Detection Logic:**
1. Detect AVIF image format
2. Look for WebP alternative in metadata (`sources` array from WebP Uploads plugin)
3. Look for JPEG alternative in metadata
4. Try to find files with same name but `.webp`, `.jpg`, `.jpeg`, `.png` extensions
5. If found → Analyze the alternative format
6. If not found → Use fallback colors (#cccccc)

**Benefits:**
- ✅ No need for Imagick extension
- ✅ Automatic fallback to supported formats
- ✅ Works with existing images
- ✅ No errors in logs

## How It Works

### For New Uploads
```
1. User uploads image.jpg
2. WebP Uploads plugin creates:
   - image.jpg (original)
   - image.webp (WebP version)
   - image.avif (AVIF version)
3. Color analysis runs:
   - Detects AVIF as main format
   - Finds image.webp alternative
   - Analyzes image.webp for colors
   - Stores colors in metadata
4. WordPress serves AVIF to modern browsers, WebP to older ones
```

### For Existing Images

If you have existing images that are AVIF-only (no WebP version), you have two options:

#### Option A: Regenerate with Both Formats
Use a plugin like "Regenerate Thumbnails" or WP-CLI to regenerate images with the new settings:

```bash
wp media regenerate --yes
```

This will create WebP versions alongside existing AVIF images.

#### Option B: Install Imagick (Advanced)
```bash
pecl install imagick
```

Then AVIF images can be analyzed directly.

## Current Status

✅ **WebP Uploads plugin configured** to output both formats  
✅ **Color analysis** will use WebP when AVIF is primary format  
✅ **Fallback colors** for images without alternatives  
✅ **Admin interface** shows helpful information about format support

## Testing

### Test New Upload:
1. Upload a new JPEG or PNG
2. Check that WebP and AVIF versions are created:
   ```bash
   ls -la web/app/uploads/2024/10/ | grep -E "test\.(jpg|webp|avif)"
   ```
3. Go to **Tools → Image Colors** and batch process
4. Verify colors are extracted successfully

### Verify Metadata:
Check that images have `sources` in their metadata:
```php
$metadata = wp_get_attachment_metadata($attachment_id);
print_r($metadata['sources']);
// Should show:
// [
//   'image/webp' => ['file' => 'image.webp', ...],
//   'image/avif' => ['file' => 'image.avif', ...]
// ]
```

## Browser Support

With both WebP and AVIF generated:

| Browser | Format Served | Color Analysis |
|---------|--------------|----------------|
| Chrome 90+ | AVIF | ✅ Via WebP |
| Firefox 93+ | AVIF | ✅ Via WebP |
| Safari 16+ | AVIF | ✅ Via WebP |
| Chrome 70-89 | WebP | ✅ Via WebP |
| Safari 14-15 | WebP | ✅ Via WebP |
| Older browsers | JPEG/PNG | ✅ Direct |

## File Sizes

Typical savings with both formats available:

- **Original JPEG**: 100 KB
- **WebP**: ~70 KB (-30%)
- **AVIF**: ~50 KB (-50%)

Browsers automatically choose the smallest format they support!

## GraphQL Usage

Colors are available regardless of format:

```graphql
query {
  mediaItem(id: "123", idType: DATABASE_ID) {
    sourceUrl        # WordPress serves correct format
    dominantColor    # Extracted from WebP/JPEG
    secondaryColor   # Works for all images
    colorPalette     # Array of colors
  }
}
```

## Troubleshooting

### Images Have No WebP Version
- Regenerate images: `wp media regenerate --yes`
- Or manually re-upload the image

### Colors Still Not Extracting
1. Check admin page: **Tools → Image Colors**
2. Look for specific error messages
3. Verify WebP files exist in uploads directory
4. Check WordPress debug log

### WebP Uploads Not Creating Both Formats
- Verify the filter is active in `setup.php`
- Check if plugin is enabled
- Clear any caching plugins
- Try uploading a test image

## Performance Notes

- Generating both formats adds ~1-2 seconds to upload time
- Disk space increases by ~1.5x (but savings offset this)
- Color analysis is unaffected (uses WebP which is fast)
- End users get faster page loads (smaller images)

## Future Improvements

If you want even better AVIF support:
1. Install Imagick: `pecl install imagick`
2. System will analyze AVIF directly
3. Slightly more accurate colors (analyzing original format)

For now, the WebP fallback provides excellent results!

