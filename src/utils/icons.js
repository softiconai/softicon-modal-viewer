import { SVG, Path } from '@wordpress/primitives';

export const blockIcon = <SVG viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
	<Path d='M12 2.5l8.5 4.7v9.6L12 21.5l-8.5-4.7V7.2L12 2.5zm0 1.7L5.6 7.7 12 11.2l6.4-3.5L12 4.2zM5 9v6.9l6.25 3.45v-6.9L5 9zm8.75 10.35L20 15.9V9l-6.25 3.45v6.9z' />
</SVG>;

const icon = (d, evenOdd = false) => <SVG viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg' aria-hidden='true' focusable='false'>
	<Path d={d} fillRule={evenOdd ? 'evenodd' : undefined} />
</SVG>;

// settings tiles (the shared icons are in vendor/softicon-tools/components/icons.js)
export const icons = {
	softLight: icon('M12 4a8 8 0 1 1 0 16 8 8 0 0 1 0-16m0 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13M12 7a5 5 0 0 1 0 10z', true),
	classicLight: icon('M11.25 2h1.5v3h-1.5zm0 17h1.5v3h-1.5zM2 11.25h3v1.5H2zm17 0h3v1.5h-3zM4.4 5.5l1.1-1.1 2.1 2.1-1.1 1.1zm12 12 1.1-1.1 2.1 2.1-1.1 1.1zM4.4 18.5l2.1-2.1 1.1 1.1-2.1 2.1zm12-12 2.1-2.1 1.1 1.1-2.1 2.1zM12 7.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9')
};
