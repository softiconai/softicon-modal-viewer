// Points model-viewer at the Draco and KTX2 decoders in this plugin, so compressed models never load them from gstatic.com.
// Runs before the library: its UMD build replaces window.ModelViewerElement, and each <model-viewer> reads the locations from it.
( function () {
	var script = document.currentScript;
	if ( ! script || ! script.src ) {
		return;
	}

	var locations = {
		dracoDecoderLocation: new URL( '../../vendor/model-viewer/decoders/draco/', script.src ).href,
		ktx2TranscoderLocation: new URL( '../../vendor/model-viewer/decoders/basis/', script.src ).href
	};
	var current = window.ModelViewerElement;

	// the library is already here (another copy loaded it first): set the locations on it directly
	if ( current && current.ModelViewerElement ) {
		Object.assign( current, locations );
		Object.assign( current.ModelViewerElement, locations );
		return;
	}

	var store = current && 'object' === typeof current ? Object.assign( current, locations ) : current;
	Object.defineProperty( window, 'ModelViewerElement', {
		configurable: true,
		enumerable: true,
		get: function () {
			return store;
		},
		set: function ( value ) {
			store = value && 'object' === typeof value ? Object.assign( value, locations ) : value;
		}
	} );
} )();
