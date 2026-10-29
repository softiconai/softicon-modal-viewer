=== Softicon Model Viewer – Interactive 3D & AR Product Viewer ===
Contributors: softiconai
Tags: 3d, model viewer, glb, augmented reality, block
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A block that shows an interactive 3D model. Visitors rotate and zoom it, and place it in their own room with AR on phones.

== Description ==

**Let people see your product from every side, and then in their own room.**

Softicon Model Viewer adds one block, **3D Model Viewer**, to the WordPress editor. Drop in a GLB or glTF file and your visitors can:

* **Rotate** the model and **zoom** into every detail.
* Watch it **turn slowly on its own**.
* Tap **View in your room** on a phone or tablet and place the model in their own space with **augmented reality** (Android Scene Viewer, iPhone and iPad Quick Look, and WebXR).

It is perfect for furniture, lighting, decor, fashion, art, products and anything people want to look at from every side.

https://www.youtube.com/watch?v=EG2niVE-vQE

= Everything in one block =

* **Your model:** upload a GLB or glTF file, pick one from the media library, or add one from a URL.
* **Accessibility:** alternative text, plus an optional poster image that shows while the model loads.
* **Interaction:** rotate and zoom, automatic rotation, and a small hint that invites visitors to drag.
* **Augmented reality:** turn the "View in your room" button on or off, and write your own button text.
* **Appearance:** height, brightness, shadow strength, and two lighting styles (soft studio or classic).
* **Camera:** choose the starting angle, or let the viewer pick one for you.
* **Design:** caption, and wide or full width.

= Why you'll like it =

* **One light block, nothing else.** No settings page, no admin notices, no account and no external service.
* **Everything is free.** Augmented reality, posters, lighting and every other setting are included. There is no Pro version.
* **Fast pages.** The 3D viewer only loads on pages that use the block, and the model is lazy-loaded.
* **Works with any theme**, and with the block editor you already know.
* **Readable code.** The block's own code has no build step, so it is easy to review and extend.

= Video tutorial =

See every step, from adding the block to publishing the page, in about 90 seconds:

https://www.youtube.com/watch?v=zlLBBaGMtHE

= Privacy =

This plugin does not collect data, set cookies or contact any external service. Models are loaded from your own media library or from the URL you enter.

= Support =

Need help, found a bug, or have an idea for a new feature? We are happy to help.

* **Email:** softiconai@gmail.com
* **Forum:** open a topic in the [support forum](https://wordpress.org/support/plugin/softicon-model-viewer/).
* **Video tutorial:** [watch on YouTube](https://www.youtube.com/watch?v=zlLBBaGMtHE)

To help us answer faster, please include your WordPress version, your theme, and a link to the page (or the model file).

== Installation ==

1. In your WordPress dashboard, go to **Plugins → Add New Plugin** and search for **Softicon Model Viewer**.
2. Click **Install Now**, then **Activate**.
3. Edit any page or post, click the **+** button, and add the **3D Model Viewer** block.
4. Upload a GLB or glTF file, or choose one from the media library, and publish.

You can also upload the plugin folder to `/wp-content/plugins/` and activate it on the **Plugins** screen.

= How to use =

1. Add the **3D Model Viewer** block to a page or post.
2. Click **Upload**, **Media Library** or **Insert from URL** and choose your model.
3. In the block settings on the right, the **General** tab has **Help** (video tutorial and support), **Model** (alternative text and an optional poster image) and **Interaction** (rotate and zoom, automatic rotation, the drag hint, and the AR button).
4. On the **Style** tab, set the height under **Size**, the lighting style, brightness and shadow under **Lighting**, and a starting angle such as `35deg 78deg auto` under **Camera** (or leave it empty).
5. Publish. Visitors can now rotate and zoom the model, and open it in AR on their phone.

== Frequently Asked Questions ==

= Is there a video tutorial? =

Yes. The [step-by-step tutorial](https://www.youtube.com/watch?v=zlLBBaGMtHE) shows the whole process in about 90 seconds.

= Which file types can I use? =

GLB (recommended, a single file) and glTF. To place models in a room on iPhone and iPad, the viewer converts the GLB automatically.

= Why does the plugin allow GLB and glTF uploads? =

WordPress does not allow these file types by default, so the plugin adds them to the list of allowed uploads for users who can already upload files. This is the only server-side change it makes.

= Why can't I see the "View in your room" button on my computer? =

Augmented reality needs a phone or tablet with a camera and AR support. On other devices the button stays hidden, and visitors can still rotate and zoom the model.

= How large should my model be? =

Keep files small for fast pages, ideally under 5 MB. Compress textures before you export the model.

= Does it work with my theme? =

Yes. The block works with any theme. It also supports wide and full alignment, so it can stretch across the page in themes that allow it.

= Is it really free? =

Yes. Every feature, including augmented reality, is free. There is no Pro version and no account to create.

= How do I get support? =

Send an email to **softiconai@gmail.com** and we will get back to you as soon as we can. You can also post in the [support forum](https://wordpress.org/support/plugin/softicon-model-viewer/). Please tell us your WordPress version, your theme and, if possible, share a link to the page or the model file.

== Screenshots ==

1. A copper lamp in the 3D Model Viewer block on the front end, with a gradient background and rounded corners. Visitors drag to rotate it and scroll to zoom.
2. The block in the editor, with the Model and Interaction settings.
3. The Appearance and Camera settings: height, brightness, shadow, lighting and starting angle.

== Changelog ==

= 1.1.1 =
* Fixed: a block added right after installing the plugin from the block inserter's search now shows its styles at once, without reloading the editor.

= 1.1.0 =
* New: redesigned block settings with General and Style tabs, and one section open at a time.
* New: a Help section with the video tutorial, support, rating and more plugins, plus the support email.
* New: lighting style as picture tiles, and Reset buttons for height, brightness and shadow.
* Changed: the background, spacing and border settings are no longer in the sidebar. Blocks that already use them keep their look.
* Changed: the editor code is now built from the readable source in `src/` (see "Source code of this plugin").

= 1.0.2 =
* New: a "Watch the video tutorial" link in the empty block, so you can learn it in about 90 seconds.
* New: "Video tutorial" and "Email support" links under the plugin on the Plugins screen.
* Improved: a clearer plugin page with videos, installation steps, a how-to guide and more answers in the FAQ.

= 1.0.1 =
* Renamed the plugin to Softicon Model Viewer (slug: softicon-model-viewer).

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.1 =
Fixes the block's look in the editor right after installing it from the block inserter's search.

= 1.1.0 =
New, cleaner block settings with General and Style tabs and a Help section. Your existing blocks keep working and look the same.

= 1.0.2 =
Adds quick links to the new video tutorial and to email support. No changes to your existing blocks.

== Third-party libraries ==

The block loads one bundled library, model-viewer by Google, from the plugin folder. Nothing is loaded from a CDN or another server.

* **@google/model-viewer 4.4.0**, Apache License 2.0. Source: https://github.com/google/model-viewer/tree/v4.4.0
* It includes **three.js r183** (MIT, https://github.com/mrdoob/three.js), **Lit** (BSD-3-Clause, https://github.com/lit/lit) and **@monogrid/gainmap-js** (MIT, https://github.com/MONOGRID/gainmap-js).

The minified file `vendor/model-viewer/model-viewer.min.js` is the unmodified `dist/model-viewer-umd.min.js` from the npm package. The readable, non-minified build of the same version is included next to it as `vendor/model-viewer/model-viewer.js`, together with the Apache license and a list of all bundled projects in `vendor/model-viewer/THIRD-PARTY-NOTICES.txt`.

= Models in the screenshots and videos =

The screenshots, banner and videos use sample models from the Khronos glTF Sample Assets (https://github.com/KhronosGroup/glTF-Sample-Assets): **Anisotropy Barn Lamp** by Eric Chadwick, © 2023 Wayfair LLC (CC BY 4.0), **Diffuse Transmission Plant** by Eric Chadwick (CC BY 4.0) and Rico Cilliers (CC0), and **Lantern** (CC0). They are not included in the plugin.

= Source code of this plugin =

The plugin ships the readable source of the block next to the built files:

* `src/` holds the source: `src/index.js` and `src/Components/` (editor, in JSX), `src/render.php` (front end), `src/block.json` (block settings) and the SCSS files. `src/vendor/softicon-tools/` holds the shared settings panel components and styles.
* `build/` holds the files WordPress loads, made from `src/` with `@wordpress/scripts`.

To build it yourself, run `npm install` and then `npm run build` in the plugin folder (see `package.json`).
