# MarkupBluesky for ProcessWire

The **MarkupBluesky** module allows you to embed and manage content from [Bluesky Social](https://bsky.app) within your ProcessWire pages using a custom fieldtype. Designed with flexibility in mind, it supports fetching posts from a given Bluesky handle and includes configurable options such as number of posts and whether to include reposts.

## Features

- Custom ProcessWire fieldtype with options for:
  - Setting a Bluesky handle
  - Choosing how many posts to show
  - Toggling inclusion of reposts
- Outputs formatted markup via `MarkupBluesky` for front-end rendering
- Stores settings per-page using JSON in the database

## Installation

1. Copy all module files to `site/modules/BlueskyFeed/`
2. Inside ProcessWire Admin:
   - Go to **Modules > Refresh**
   - Install `MarkupBluesky` (this will also install `InputfieldBluesky` and `FieldTypeBluesky`)

## Usage

### 1. Add Field to Template
- Create a new field of type `Bluesky`
- Add it to any template where you want to configure a Bluesky feed

### 2. Configure Per Page
Once added, you can:
- Enter the Bluesky handle (e.g. `@yourusername.bsky.social`)
- Set the number of posts (default: 5)
- Choose whether to include reposts

### 4. Rendering on Frontend

When using the rendering two dependencies are needed:
- FancyBox (to post images to be displayed full screen)
- HLS (to allow users to play emebedded videos)

These can be added to the head of the page:
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
```
 
To output the feed to the page:
```php
echo $modules->get('MarkupBluesky')->renderFeed($page->bluesky);
```

### License

MIT — do whatever you want, just don’t blame me if it breaks 😅

### Credits

Built by TwoWheelDev 💙

Powered by ProcessWire and fueled by curiosity.