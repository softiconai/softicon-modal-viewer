( function ( blocks, blockEditor, components, compose, element, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var BlockControls = blockEditor.BlockControls;
	var MediaPlaceholder = blockEditor.MediaPlaceholder;
	var MediaReplaceFlow = blockEditor.MediaReplaceFlow;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var RichText = blockEditor.RichText;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var Button = components.Button;
	var ExternalLink = components.ExternalLink;
	var UnitControl = components.__experimentalUnitControl || components.UnitControl;

	var ALLOWED_TYPES = [ 'model/gltf-binary', 'model/gltf+json' ];
	var ACCEPT = '.glb,.gltf,model/gltf-binary,model/gltf+json';
	var TUTORIAL_URL = 'https://www.youtube.com/watch?v=zlLBBaGMtHE';

	// The viewer library sits next to this file; the editor canvas is an iframe, so it may need loading there too.
	var LIBRARY_URL = document.currentScript ? new URL( '../vendor/model-viewer/model-viewer.min.js', document.currentScript.src ).href : '';

	var icon = el(
		'svg',
		{ width: 24, height: 24, viewBox: '0 0 24 24', 'aria-hidden': true, focusable: false },
		el( 'path', { d: 'M12 2.5l8.5 4.7v9.6L12 21.5l-8.5-4.7V7.2L12 2.5zm0 1.7L5.6 7.7 12 11.2l6.4-3.5L12 4.2zM5 9v6.9l6.25 3.45v-6.9L5 9zm8.75 10.35L20 15.9V9l-6.25 3.45v6.9z' } )
	);

	function useViewerLibrary() {
		return compose.useRefEffect( function ( node ) {
			var doc = node.ownerDocument;
			var win = doc.defaultView;
			if ( ! LIBRARY_URL || ( win.customElements && win.customElements.get( 'model-viewer' ) ) || doc.getElementById( 'softicon-model-viewer-library-editor' ) ) {
				return;
			}
			var script = doc.createElement( 'script' );
			script.id = 'softicon-model-viewer-library-editor';
			script.src = LIBRARY_URL;
			doc.head.appendChild( script );
		}, [] );
	}

	function Edit( props ) {
		var a = props.attributes;
		var setAttributes = props.setAttributes;
		var libraryRef = useViewerLibrary();
		var blockProps = useBlockProps( { className: 'softicon-model-viewer', ref: libraryRef } );

		function onSelectModel( media ) {
			if ( ! media || ! media.url ) {
				return;
			}
			setAttributes( {
				url: media.url,
				id: media.id,
				alt: a.alt || media.alt || '',
			} );
		}

		function onSelectURL( url ) {
			setAttributes( { url: url, id: undefined } );
		}

		if ( ! a.url ) {
			return el(
				'figure',
				blockProps,
				el(
					MediaPlaceholder,
					{
						icon: icon,
						labels: {
							title: __( '3D Model Viewer', 'softicon-model-viewer' ),
							instructions: __( 'Upload a GLB or glTF file, pick one from the media library, or add one from a URL.', 'softicon-model-viewer' ),
						},
						onSelect: onSelectModel,
						onSelectURL: onSelectURL,
						accept: ACCEPT,
						allowedTypes: ALLOWED_TYPES,
					},
					el(
						'p',
						{ className: 'softicon-model-viewer__help' },
						el( ExternalLink, { href: TUTORIAL_URL }, __( 'Watch the video tutorial', 'softicon-model-viewer' ) )
					)
				)
			);
		}

		var viewer = el( 'model-viewer', {
			key: a.url,
			src: a.url,
			alt: a.alt,
			poster: a.posterUrl || undefined,
			exposure: String( a.exposure ),
			'shadow-intensity': String( a.shadowIntensity ),
			'environment-image': a.environment,
			'interaction-prompt': a.interactionPrompt ? 'auto' : 'none',
			'camera-orbit': a.cameraOrbit || undefined,
			'camera-controls': a.cameraControls ? '' : undefined,
			'auto-rotate': a.autoRotate ? '' : undefined,
			style: { height: a.height || '520px' },
		} );

		var heightControl = UnitControl
			? el( UnitControl, {
				label: __( 'Height', 'softicon-model-viewer' ),
				value: a.height,
				units: [ { value: 'px', label: 'px', default: 520 }, { value: 'vh', label: 'vh', default: 70 } ],
				onChange: function ( value ) {
					setAttributes( { height: value || '520px' } );
				},
				__next40pxDefaultSize: true,
			} )
			: el( TextControl, {
				label: __( 'Height', 'softicon-model-viewer' ),
				value: a.height,
				onChange: function ( value ) {
					setAttributes( { height: value } );
				},
			} );

		return el(
			Fragment,
			null,
			el(
				BlockControls,
				{ group: 'other' },
				el( MediaReplaceFlow, {
					mediaId: a.id,
					mediaURL: a.url,
					allowedTypes: ALLOWED_TYPES,
					accept: ACCEPT,
					onSelect: onSelectModel,
					onSelectURL: onSelectURL,
					name: __( 'Replace model', 'softicon-model-viewer' ),
				} )
			),
			el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'Model', 'softicon-model-viewer' ) },
					el( TextareaControl, {
						label: __( 'Alternative text', 'softicon-model-viewer' ),
						help: __( 'Describe the model for people who cannot see it.', 'softicon-model-viewer' ),
						value: a.alt,
						onChange: function ( value ) {
							setAttributes( { alt: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el(
						MediaUploadCheck,
						null,
						el( MediaUpload, {
							allowedTypes: [ 'image' ],
							value: a.posterId,
							onSelect: function ( media ) {
								setAttributes( { posterUrl: media.url, posterId: media.id } );
							},
							render: function ( obj ) {
								return el(
									'div',
									{ className: 'softicon-model-viewer__poster' },
									el( 'p', null, __( 'Poster image, shown while the model loads.', 'softicon-model-viewer' ) ),
									el( Button, { variant: 'secondary', onClick: obj.open }, a.posterUrl ? __( 'Replace poster', 'softicon-model-viewer' ) : __( 'Set poster', 'softicon-model-viewer' ) ),
									a.posterUrl
										? el( Button, {
											variant: 'link',
											isDestructive: true,
											onClick: function () {
												setAttributes( { posterUrl: '', posterId: undefined } );
											},
										}, __( 'Remove', 'softicon-model-viewer' ) )
										: null
								);
							},
						} )
					)
				),
				el(
					PanelBody,
					{ title: __( 'Interaction', 'softicon-model-viewer' ) },
					el( ToggleControl, {
						label: __( 'Let visitors rotate and zoom', 'softicon-model-viewer' ),
						checked: a.cameraControls,
						onChange: function ( value ) {
							setAttributes( { cameraControls: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Rotate automatically', 'softicon-model-viewer' ),
						checked: a.autoRotate,
						onChange: function ( value ) {
							setAttributes( { autoRotate: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Show a hint to drag', 'softicon-model-viewer' ),
						checked: a.interactionPrompt,
						onChange: function ( value ) {
							setAttributes( { interactionPrompt: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'View in your room (AR)', 'softicon-model-viewer' ),
						help: __( 'The button appears on phones and tablets that support augmented reality.', 'softicon-model-viewer' ),
						checked: a.ar,
						onChange: function ( value ) {
							setAttributes( { ar: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					a.ar
						? el( TextControl, {
							label: __( 'AR button text', 'softicon-model-viewer' ),
							placeholder: __( 'View in your room', 'softicon-model-viewer' ),
							value: a.arLabel,
							onChange: function ( value ) {
								setAttributes( { arLabel: value } );
							},
							__nextHasNoMarginBottom: true,
						} )
						: null
				),
				el(
					PanelBody,
					{ title: __( 'Appearance', 'softicon-model-viewer' ) },
					heightControl,
					el( RangeControl, {
						label: __( 'Brightness', 'softicon-model-viewer' ),
						value: a.exposure,
						min: 0.2,
						max: 2,
						step: 0.05,
						onChange: function ( value ) {
							setAttributes( { exposure: value === undefined ? 1 : value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( RangeControl, {
						label: __( 'Shadow', 'softicon-model-viewer' ),
						value: a.shadowIntensity,
						min: 0,
						max: 2,
						step: 0.05,
						onChange: function ( value ) {
							setAttributes( { shadowIntensity: value === undefined ? 1 : value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( SelectControl, {
						label: __( 'Lighting', 'softicon-model-viewer' ),
						value: a.environment,
						options: [
							{ value: 'neutral', label: __( 'Soft studio light', 'softicon-model-viewer' ) },
							{ value: 'legacy', label: __( 'Classic light', 'softicon-model-viewer' ) },
						],
						onChange: function ( value ) {
							setAttributes( { environment: value } );
						},
						__nextHasNoMarginBottom: true,
					} )
				),
				el(
					PanelBody,
					{ title: __( 'Camera', 'softicon-model-viewer' ), initialOpen: false },
					el( TextControl, {
						label: __( 'Starting angle', 'softicon-model-viewer' ),
						help: __( 'For example: 30deg 75deg 105%. Leave empty for an automatic angle.', 'softicon-model-viewer' ),
						value: a.cameraOrbit,
						onChange: function ( value ) {
							setAttributes( { cameraOrbit: value } );
						},
						__nextHasNoMarginBottom: true,
					} )
				)
			),
			el(
				'figure',
				blockProps,
				viewer,
				( props.isSelected || a.caption ) &&
					el( RichText, {
						tagName: 'figcaption',
						className: 'wp-element-caption',
						placeholder: __( 'Add caption', 'softicon-model-viewer' ),
						value: a.caption,
						onChange: function ( value ) {
							setAttributes( { caption: value } );
						},
						allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
					} )
			)
		);
	}

	blocks.registerBlockType( 'softicon/model-viewer', {
		icon: icon,
		edit: Edit,
		save: function () {
			return null;
		},
	} );
}( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.compose, window.wp.element, window.wp.i18n ) );
