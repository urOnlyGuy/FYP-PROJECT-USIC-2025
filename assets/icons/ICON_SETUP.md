# PWA Icon Setup Guide

## Quick Setup (5 minutes)
### Option 1: Use Online Icon Generator (EASIEST) ⭐

1. **Create a simple logo/icon:**
   - Use Canva (free): https://canva.com
   - Or use any design tool
   - Size: 512x512px minimum
   - Simple design works best

2. **Generate all sizes automatically:**
   - Go to: https://www.pwabuilder.com/imageGenerator
   - Upload your 512x512 icon
   - Download the generated package
   - Extract all icons to `/assets/icons/` folder

### Option 2: Use Favicon Generator

1. Go to: https://realfavicongenerator.net/
2. Upload your icon
3. Download the package
4. Extract to `/assets/icons/`

### Option 3: Manual Creation (if you have design skills)

Create these sizes manually:
- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png
- icon-384x384.png
- icon-512x512.png

### Option 4: Use Placeholder Icons (TEMPORARY)

For testing, create a simple colored square:

1. Go to: https://via.placeholder.com/512x512/0d6efd/ffffff?text=UPTM
2. Save as `icon-512x512.png`
3. Resize it to other sizes using: https://www.iloveimg.com/resize-image

### Recommended Icon Design:

```
- Background: Blue (#0d6efd) - Bootstrap primary color
- Text/Symbol: White
- Content: "UPTM" or university logo
- Style: Simple, flat design
- No gradients (they don't scale well)
```

## Folder Structure

```
/project-root
└── assets
    └── icons
        ├── ICON_SETUP.md (this file)
        ├── icon-72x72.png
        ├── icon-96x96.png
        ├── icon-128x128.png
        ├── icon-144x144.png
        ├── icon-152x152.png
        ├── icon-192x192.png
        ├── icon-384x384.png
        └── icon-512x512.png
```

## Quick Test Icons (Copy-Paste URLs)

For quick testing, you can temporarily use these placeholder URLs in `manifest.json`:

```json
"icons": [
  {
    "src": "https://via.placeholder.com/72x72/0d6efd/ffffff?text=U",
    "sizes": "72x72",
    "type": "image/png"
  },
  {
    "src": "https://via.placeholder.com/192x192/0d6efd/ffffff?text=UPTM",
    "sizes": "192x192",
    "type": "image/png"
  },
  {
    "src": "https://via.placeholder.com/512x512/0d6efd/ffffff?text=UPTM",
    "sizes": "512x512",
    "type": "image/png"
  }
]
```

## Important Notes:

- Icons must be PNG format
- Transparent background recommended
- Keep design simple for visibility at small sizes
- Test on both light and dark backgrounds
- Icon will appear on user's home screen