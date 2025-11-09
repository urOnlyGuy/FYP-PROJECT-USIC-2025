# Category Placeholder Images

This folder contains placeholder images for post categories when no header image is uploaded.

## Required Files:

Create these placeholder images (600x400px recommended):

- `general.jpg` - For General category posts
- `finance.jpg` - For Finance category posts
- `academic.jpg` - For Academic category posts
- `events.jpg` - For Events category posts

## Quick Setup Options:

### Option 1: Use Free Stock Images
Download from:
- Unsplash: https://unsplash.com
- Pexels: https://pexels.com
- Pixabay: https://pixabay.com

### Option 2: Use Solid Color Placeholders
You can use these online generators:
- https://placeholder.com/600x400
- https://via.placeholder.com/600x400

### Option 3: Temporary Solution
Until you have proper images, the system will use the path.
Make sure to add images before going live!

## Example URLs (if you want to use online placeholders temporarily):

```php
// In includes/helpers.php, update get_category_placeholder():

$placeholders = [
    'Finance' => 'https://via.placeholder.com/600x400/28a745/ffffff?text=Finance',
    'Academic' => 'https://via.placeholder.com/600x400/007bff/ffffff?text=Academic',
    'Events' => 'https://via.placeholder.com/600x400/ffc107/000000?text=Events',
    'General' => 'https://via.placeholder.com/600x400/6c757d/ffffff?text=General'
];
```

## Folder Structure:

```
/project-root
└── assets
    └── placeholders
        ├── README.md (this file)
        ├── general.jpg
        ├── finance.jpg
        ├── academic.jpg
        └── events.jpg
```