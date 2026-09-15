# WebFolderPlayer

A minimal PHP media player. It scans a `media/` folder recursively for
video and image files and renders them as a collapsible folder tree in a
sidebar, with a video player next to it.

## Requirements

- PHP with a webserver (or `php -S 127.0.0.1:8000` for local testing)
- A `media/` folder (or symlink) in the project root containing your
  video/image files. It is not part of this repository — each machine
  provides its own.

Supported file extensions: `mp4, webm, webp, mkv, jpeg, jpg, png, gif, bmp`.

## Features

- Recursive folder tree with collapsible sections
- Shuffle, repeat, and auto-fullscreen toggles
- "Shuffle & Go" button: enables shuffle and immediately plays a random item
- Now-playing label in the control panel, always visible; in auto-fullscreen
  mode a matching toast is overlaid on the video for a few seconds instead
- Prev/next controls that skip images and remember the current position
- Resume: the last played video is tracked in `localStorage`
- Hidden folders: drop an empty `.hidden` file into any folder under
  `media/` to exclude it (and its subfolders) from the sidebar and
  playback list by default. The "Show hidden" checkbox reveals them
  again for the current session.
- README: a `media/README` / `README.md` / `README.txt` file is shown
  as plain text where the player normally sits, until the first video
  is played — then it's gone for the rest of the session.

## Setup on a new machine

1. Clone this repository.
2. Create `media/` in the project root — either a real folder or a
   symlink to wherever your media library lives on that machine.
3. Serve the folder with PHP (built-in server, Apache, nginx+php-fpm, ...).

### Running manually with PHP's built-in server

From the project root:

```
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/` in a browser. Stop the server with
Ctrl+C. Use `0.0.0.0` instead of `127.0.0.1` to make it reachable from
other devices on the network (e.g. a smart TV):

```
php -S 0.0.0.0:8000
```

Then open `http://<this machine's LAN IP>:8000/` from the other device.

## Customizing (per machine)

Copy `custom.example.css` to `custom.css` and edit it. `custom.css` is
gitignored and loaded after every other stylesheet, so it survives
`git pull` and can override or add any rule — the `--accent` CSS variable
covers the common case (accent color used for highlights), but it's a
normal stylesheet so anything is fair game.

The app background is a single diagonal gradient behind the sidebar,
control panel, and video area (all three are semi-transparent so it
shows through consistently). Set `--bg-angle`, `--bg-from`, and `--bg-to`
to change it; both colors default to the same value, which looks flat
until you set them apart.

## Files

- `index.php` — page markup, PHP file scanning, and player logic
- `folder.css`, `common.css` — styling
- `custom.example.css` — template for `custom.css`, your per-machine overrides
- `folder.js`, `common.js` — legacy helpers (currently unused by `index.php`)
