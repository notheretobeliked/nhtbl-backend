# Image Color Analysis

Automatically extracts dominant colors from images for progressive enhancement on the frontend.

## Features

- ✅ **Automatic Analysis**: New uploads are analyzed automatically
- ✅ **Batch Processing**: Process existing images via admin tool
- ✅ **GraphQL Integration**: Colors available in GraphQL queries
- ✅ **Progressive Enhancement**: Perfect for loading states
- ✅ **Smart Filtering**: Excludes colors that are too light/dark

## Installation

Already installed! The Color Thief PHP library has been added via Composer.

## How It Works

When an image is uploaded (or batch processed), the system:

1. Extracts the **primary dominant color**
2. Finds the **secondary dominant color**
3. Generates a **palette of 8 additional colors**
4. Filters out colors that are too light (>90% lightness) or too dark (<15% lightness)
5. Stores colors in attachment metadata as hex values

## Usage

### Admin Interface

Navigate to **Tools → Image Colors** in WordPress admin to:

- View statistics (total, analyzed, pending)
- Batch process unanalyzed images
- Re-analyze all images
- Preview sample results

### GraphQL Queries

Query dominant colors alongside your images:

```graphql
query {
  page(id: "123", idType: DATABASE_ID) {
    featuredImage {
      node {
        sourceUrl(size: LARGE)
        dominantColor      # "#FF5733"
        secondaryColor     # "#33FF57"
        colorPalette       # ["#5733FF", "#FF3357", ...]
      }
    }
  }
}
```

### Frontend Implementation (SvelteKit Example)

```svelte
<script>
  export let image;
</script>

<div 
  class="image-wrapper"
  style="background-color: {image.dominantColor}"
>
  <img 
    src={image.sourceUrl} 
    alt={image.altText}
    loading="lazy"
  />
</div>

<style>
  .image-wrapper {
    position: relative;
    overflow: hidden;
    /* Background color shows while image loads */
  }
  
  img {
    width: 100%;
    height: auto;
    display: block;
    /* Fade in when loaded */
    animation: fadeIn 0.3s ease-in;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
</style>
```

### Advanced: Gradient Backgrounds

Use primary and secondary colors for a gradient:

```svelte
<div 
  style="background: linear-gradient(135deg, {image.dominantColor}, {image.secondaryColor})"
>
  <img src={image.sourceUrl} alt="" />
</div>
```

### Advanced: Color Palette for UI

Use the full palette for themed UI elements:

```svelte
<script>
  export let image;
  const [primary, ...palette] = [
    image.dominantColor,
    image.secondaryColor,
    ...image.colorPalette
  ];
</script>

<div class="card" style="--primary: {primary}; --secondary: {palette[0]}">
  <img src={image.sourceUrl} alt="" />
  <div class="card-content">
    <!-- Content styled with CSS custom properties -->
  </div>
</div>
```

## Color Data Structure

Colors are stored in attachment metadata:

```php
[
  'dominant_colors' => [
    'primary' => '#FF5733',      // Most dominant color
    'secondary' => '#33FF57',    // Second most dominant
    'palette' => [               // 8 additional colors
      '#5733FF',
      '#FF3357',
      // ... 6 more
    ],
    'version' => '1.0',          // Algorithm version
    'analyzed_at' => '2024-01-01 12:00:00'
  ]
]
```

## Best Practices

### 1. **Always Provide Fallbacks**
```svelte
<div style="background-color: {image?.dominantColor || '#cccccc'}">
```

### 2. **Consider Color Contrast**
If using dominant color for text backgrounds, check contrast:
```js
// Simple lightness check
const isLight = (hex) => {
  const rgb = parseInt(hex.slice(1), 16);
  const r = (rgb >> 16) & 0xff;
  const g = (rgb >> 8) & 0xff;
  const b = (rgb >> 0) & 0xff;
  const luma = 0.299 * r + 0.587 * g + 0.114 * b;
  return luma > 186;
};

const textColor = isLight(dominantColor) ? '#000' : '#fff';
```

### 3. **Blur Placeholder Technique**
Combine with tiny base64 preview for smoother loading:
```svelte
<div 
  class="blur-load"
  style="
    background-color: {image.dominantColor};
    background-image: url({image.tinyPreview});
    background-size: cover;
  "
>
  <img 
    src={image.sourceUrl}
    loading="lazy"
    onload="this.parentElement.classList.add('loaded')"
  />
</div>

<style>
  .blur-load {
    filter: blur(10px);
    transition: filter 0.3s;
  }
  
  .blur-load.loaded {
    filter: blur(0);
  }
  
  .blur-load.loaded img {
    opacity: 1;
  }
</style>
```

## Performance Notes

- Analysis happens **on upload** - no frontend impact
- Colors are **cached** in attachment metadata
- Batch processing uses **50 images per batch** by default (configurable)
- Only images are processed (skips PDFs, videos, etc.)
- Re-analysis only when forced or algorithm version changes

## Troubleshooting

### Colors Not Appearing in GraphQL

1. Check that images have been analyzed (Tools → Image Colors)
2. Verify GraphQL cache is cleared
3. Check WordPress debug log for errors

### Analysis Failing

- Ensure GD or Imagick extension is installed in PHP
- Check file permissions on uploads directory
- Verify image files exist and are readable

### Too Many Light/Dark Colors

The algorithm filters colors between 15-90% lightness. If you need different thresholds, edit the `get_lightness()` check in `ImageColors.php`:

```php
// Current: 15% to 90%
return $lightness >= 15 && $lightness <= 90;

// More permissive: 10% to 95%
return $lightness >= 10 && $lightness <= 95;
```

## Technical Details

- **Library**: Color Thief PHP (v2.0+)
- **Algorithm**: Modified median cut with lightness filtering
- **Quality**: 10 (good balance of accuracy and performance)
- **Palette Size**: 10 colors extracted, filtered to 8 final colors
- **Storage**: WordPress attachment metadata
- **GraphQL**: Custom fields on MediaItem type

## Version History

- **1.0** - Initial release with primary, secondary, and palette colors

