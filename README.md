# Softicon Model Viewer

A WordPress block that shows an interactive 3D model (GLB or glTF). Visitors can rotate and zoom it, and place it in their own room with augmented reality on phones (Android Scene Viewer, WebXR and iOS Quick Look).

## Videos

| Promo (0:33) | Tutorial (1:25) |
|---|---|
| [![Softicon Model Viewer promo](https://img.youtube.com/vi/EG2niVE-vQE/maxresdefault.jpg)](https://www.youtube.com/watch?v=EG2niVE-vQE) | [![How to add a 3D model to WordPress](https://img.youtube.com/vi/zlLBBaGMtHE/maxresdefault.jpg)](https://www.youtube.com/watch?v=zlLBBaGMtHE) |

**Download:** [wordpress.org/plugins/softicon-model-viewer](https://wordpress.org/plugins/softicon-model-viewer/) · **Support:** softiconai@gmail.com

## Features

- One block, **3D Model Viewer**: upload a model, pick one from the media library or add one from a URL
- Alternative text and an optional poster image while the model loads
- Rotate, zoom, automatic rotation and a drag hint
- Augmented reality on or off, with your own button text
- Height, brightness, shadow strength, lighting style and starting camera angle
- The viewer only loads on pages that use the block, and lazy-loads the model

No settings page, no admin notices, no account and no external service.

## Requirements

- WordPress 6.5+
- PHP 7.4+

## Development

The block's own code in `block/` has no build step. The 3D viewer is Google's [`<model-viewer>`](https://modelviewer.dev/) web component, bundled in `vendor/model-viewer/` (Apache-2.0, see its `LICENSE` and `THIRD-PARTY-NOTICES.txt`).

## License

GPLv2 or later. See <https://www.gnu.org/licenses/gpl-2.0.html>.
