# Adding TRMNL Recipe Plugins in LaraPaper

## End-to-end steps: adding a recipe plugin to a device

1. **Navigate to Plugins & Recipes** — click "Plugins & Recipes" in the top nav bar, or go to `/plugins`.

2. **Click "+ Add Recipe"** (top-right orange button with dropdown arrow). This opens the recipe catalog from TRMNL's community recipes.

3. **Pick a recipe** — browse or search the catalog. Each recipe card shows its name and icon (e.g. "XKCD Comic (with hover text)"). Click the one you want. It will be added to your Plugins & Recipes list.

4. **Configure the plugin** — after the recipe is added, click its card on the `/plugins` page to open its settings at `/plugins/recipe/<id>`. On this page you can:
   - Rename the plugin.
   - Open **Configuration Fields** (modal) to set recipe-specific options.
   - Set the **Data Strategy** (Polling / Webhook / Static).
   - For Polling: set the URL, verb (GET/POST), headers, and staleness interval.
   - Edit the **Markup** (Liquid or Blade template) and choose layout sizes (Full, Half Horizontal, Half Vertical, Quadrant, Shared).
   - Toggle **Screen Settings** (bleed margin, dark mode).
   - Click **Save** when done.

5. **Fetch initial data** — scroll to the "Fetch data now" button under the Polling URL section and click it. The Data Payload panel (right side) will populate with the JSON response and show a timestamp.

6. **Preview the render** — click the **Preview** button (eye icon, top of page). A dialog opens showing:
   - Device selector (e.g. "TRMNL OG (2-bit)")
   - **Render Image** button — click this to generate a fresh PNG
   - The rendered image preview with the comic/content displayed as it would appear on the e-ink screen.

7. **Add to a playlist** — click the **"Add to Playlist"** button (top-right, next to Preview). Select an existing playlist or create a new one.

8. **Assign playlist to a device** — go to **Devices** → select your device → under "Device Playlists", create or edit a playlist and toggle the plugin on. The device will pick up the image on its next refresh cycle.

---

## XKCD plugin specifics

### Configuration Fields

The XKCD Comic recipe has two optional config fields (accessible via the `{x} Configuration Fields` button):

| Field | Description | Default |
|-------|-------------|---------|
| **Show Title** | Show the comic title above the image | `show` |
| **Show Hover Text** | Show the hover/alt text below the image | `show` |

Both are dropdowns with `show`/`hide` options.

### Data Strategy

- **Type**: Polling
- **URL**: `https://xkcd.com/info.0.json`
- **Verb**: GET
- **Staleness**: 480 minutes (8 hours)
- **Headers**: Authorization Bearer token + Content-Type application/json (pre-filled by the recipe)

### Markup

Uses **Liquid** template language. The full-size layout code:

```liquid
<div class="view view--{{ size }}">
  {% render "comic" img: img, title: title, alt: alt %}
</div>
```

This renders a `comic` partial that maps XKCD JSON fields (`img`, `title`, `alt`) to the display.

---

## Verifying a plugin renders correctly

1. **Check the Data Payload** — on the plugin settings page (`/plugins/recipe/<id>`), confirm the right-side JSON panel has fresh data and a recent timestamp (e.g. "5 minutes ago").

2. **Use the Preview dialog** — click Preview → Render Image. The rendered image should show the expected content (for XKCD: comic title, image, hover text). If it's blank or shows an error, check the markup and data payload.

3. **Check the generated image directly** — each plugin instance has a UUID. Visit `/storage/images/generated/<uuid>.png` in the browser to see the raw PNG. The image should be 600×800 pixels for a full-size TRMNL display.

4. **Check the device page** — go to Devices → your device. Under "Screen", "Next Image" should reference the plugin. The "last seen" timestamp and MAC address confirm the device is polling.

---

## Gotchas and issues

- **First URL 404'd** — the image UUID contains a specific hash. If a render hasn't happened yet, the generated image file won't exist at the expected path. Use the Preview → Render Image button to force generation.

- **Image URL varies by plugin instance** — each plugin instance gets its own UUID. The URL pattern is `/storage/images/generated/<uuid>.png`. Find the correct UUID from the plugin settings or device page.

- **Dark mode inverts colors** — the XKCD comic renders with inverted colors (white-on-black) suitable for e-ink. This is controlled by the "Enable Dark Mode?" checkbox in Screen Settings. The raw PNG on disk may look inverted compared to the original comic.

- **Data staleness** — the default 480-minute (8-hour) polling interval means XKCD data refreshes ~3 times per day. New comics won't appear instantly. Use "Fetch data now" to force a data refresh, then "Render Image" to regenerate.

- **Polling headers** — the recipe pre-fills Authorization and Content-Type headers. For public APIs like XKCD's `info.0.json`, the auth header is ignored by the upstream server but is still sent. This is harmless.

- **Layout sizes** — recipes can define markup for multiple layout sizes (Full, Half Horizontal, Half Vertical, Quadrant). If a layout tab has no markup, that size won't render. Make sure the layout you're using in your playlist has corresponding markup defined.

- **Recipe vs custom plugin** — recipes are community-maintained templates imported from TRMNL's catalog. You can also create fully custom plugins using the Markup, API, or Image Webhook types from the Plugins & Recipes page.
