=== Softicon Model Viewer ===
Contributors: softiconai
Tags: 3d, model viewer, glb, augmented reality, block
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A block that shows an interactive 3D model. Visitors rotate and zoom it, and place it in their own room with AR on phones.

== Description ==

Softicon Model Viewer adds one block, **3D Model Viewer**, to the block editor. Add a GLB or glTF file and your visitors can:

* Rotate the model and zoom in to see the details.
* Watch it turn slowly on its own.
* Tap **View in your room** on a phone or tablet that supports augmented reality, and place the model in their own space (Android Scene Viewer, WebXR and iOS Quick Look).

It works well for furniture, decor, products, art and any object you want people to look at from every side.

= Block settings =

* Model: upload a GLB or glTF file, pick one from the media library or add one from a URL.
* Alternative text and an optional poster image that shows while the model loads.
* Rotate and zoom, automatic rotation and a hint that invites visitors to drag.
* Augmented reality on or off, with your own button text.
* Height, brightness, shadow strength and lighting style.
* Starting camera angle.
* Caption, wide and full alignment, background colour, spacing and border.

= Why this plugin =

* **One light block, nothing else.** No settings page, no admin notices, no account and no external service.
* **Everything is free.** Augmented reality, posters, lighting and every other setting are included, with no Pro version.
* **Fast pages.** The 3D viewer only loads on pages that use the block, and it lazy-loads the model.
* **Readable code.** The block's own code has no build step, so it is easy to review and extend.

The block works with any theme.

= Privacy =

This plugin does not collect data, set cookies or contact any external service. Models are loaded from your own media library or from the URL you enter.

= Support =

Need help, found a bug or have an idea? We are happy to help.

* Email: **softiconai@gmail.com**
* Or open a topic in the plugin's support forum on WordPress.org.

Please include your WordPress version, your theme and a link to the page (or the model file) so we can help you faster.

== Frequently Asked Questions ==

= Which file types can I use? =

GLB (recommended, a single file) and glTF. To place models in a room on iPhone and iPad, the viewer converts the GLB automatically.

= Why does the plugin allow GLB and glTF uploads? =

WordPress does not allow these file types by default, so the plugin adds them to the list of allowed uploads for users who can already upload files. This is the only server-side change it makes.

= Why can't I see the "View in your room" button on my computer? =

Augmented reality needs a phone or tablet with a camera and AR support. On other devices the button stays hidden and visitors can still rotate and zoom the model.

= How large should my model be? =

Keep files small for fast pages, ideally under 5 MB. Compress textures before export.

= How do I get support? =

Send an email to **softiconai@gmail.com** and we will get back to you as soon as we can. You can also post in the plugin's support forum on WordPress.org. Please tell us your WordPress version, your theme and, if possible, share a link to the page or the model file.

== Screenshots ==

1. A copper lamp in the 3D Model Viewer block on the front end, with a gradient background and rounded corners. Visitors drag to rotate it and scroll to zoom.
2. The block in the editor, with the Model and Interaction settings.
3. The Appearance and Camera settings: height, brightness, shadow, lighting and starting angle.

== Changelog ==

= 1.0.1 =
* Renamed the plugin to Softicon Model Viewer (slug: softicon-model-viewer).

= 1.0.0 =
* Initial release.

== Third-party libraries ==

The block loads one bundled library, model-viewer by Google, from the plugin folder. Nothing is loaded from a CDN or another server.

* **@google/model-viewer 4.4.0**, Apache License 2.0. Source: https://github.com/google/model-viewer/tree/v4.4.0
* It includes **three.js r183** (MIT, https://github.com/mrdoob/three.js), **Lit** (BSD-3-Clause, https://github.com/lit/lit) and **@monogrid/gainmap-js** (MIT, https://github.com/MONOGRID/gainmap-js).

The minified file `vendor/model-viewer/model-viewer.min.js` is the unmodified `dist/model-viewer-umd.min.js` from the npm package. The readable, non-minified build of the same version is included next to it as `vendor/model-viewer/model-viewer.js`, together with the Apache license and a list of all bundled projects in `vendor/model-viewer/THIRD-PARTY-NOTICES.txt`.

= Models in the screenshots =

The screenshots and banner use sample models from the Khronos glTF Sample Assets (https://github.com/KhronosGroup/glTF-Sample-Assets): **Anisotropy Barn Lamp** by Eric Chadwick, © 2023 Wayfair LLC (CC BY 4.0) and **Lantern** (CC0). They are not included in the plugin.

= Source code of this plugin =

The block's own code is plain, readable JavaScript and PHP with no build step: `block/index.js` (editor), `block/render.php` (front end), `block/block.json` (block settings) and the CSS files are the source files themselves.
