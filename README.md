# Softicon Model Viewer

A WordPress block that shows an interactive 3D model (GLB or glTF). Visitors can rotate and zoom it, and place it in their own room with augmented reality on phones (Android Scene Viewer, WebXR and iOS Quick Look).

## Videos

| Promo (0:31) | Tutorial (1:21) |
|---|---|
| [![3D & AR product viewer for WooCommerce](https://img.youtube.com/vi/y3ep9HGLhso/maxresdefault.jpg)](https://www.youtube.com/watch?v=y3ep9HGLhso) | [![How to add 3D models & AR to WooCommerce and WordPress](https://img.youtube.com/vi/9WtqRuBFUv8/maxresdefault.jpg)](https://www.youtube.com/watch?v=9WtqRuBFUv8) |

**Download:** [wordpress.org/plugins/softicon-model-viewer](https://wordpress.org/plugins/softicon-model-viewer/) · **Support:** softiconai@gmail.com

## Features

- One block, **3D Model Viewer**: upload a model, pick one from the media library or add one from a URL
- Alternative text and an optional poster image while the model loads
- Rotate, zoom, automatic rotation and a drag hint
- Augmented reality on or off, with your own button text
- Height, brightness, shadow strength, lighting style and starting camera angle
- Hotspots: numbered points with a label and text that turn with the model
- The viewer only loads on pages that use the block, and lazy-loads the model

No settings page and no account. An optional, opt-in usage data share through Freemius; visitors are never tracked.

## Requirements

- WordPress 6.5+
- PHP 7.4+

## Development

The block's source is in `src/` and builds into `build/` with `@wordpress/scripts`:

```sh
npm install
npm run build      # or npm start while working
npm run i18n-pot   # refresh languages/softicon-model-viewer.pot
```

The settings sidebar uses the shared SoftIcon panels: `npm run build` copies them from `../softicon-tools` into `src/vendor/softicon-tools/` first, so edit them there, not the copy.

The 3D viewer is Google's [`<model-viewer>`](https://modelviewer.dev/) web component, bundled in `vendor/model-viewer/` (Apache-2.0, see its `LICENSE` and `THIRD-PARTY-NOTICES.txt`).

## License

GPLv2 or later. See <https://www.gnu.org/licenses/gpl-2.0.html>.
