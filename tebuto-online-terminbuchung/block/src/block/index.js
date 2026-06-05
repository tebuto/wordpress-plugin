/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( metadata.name, {
	icon: {
		src: (
			<svg
				width="104"
				height="104"
				viewBox="0 0 104 104"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<title>Tebuto</title>
				<g clipPath="url(#clip0_1526_23918)">
					<path
						d="M17.1085 43.4896L16.9985 43.3596C15.163 41.5536 12.7129 40.5077 10.1389 40.4317C7.56498 40.3557 5.05738 41.255 3.11849 42.9496C2.18235 43.7503 1.42242 44.7365 0.886696 45.8458C0.350969 46.955 0.0510823 48.1633 0.00597555 49.3944C-0.0391312 50.6254 0.171522 51.8524 0.624631 52.9979C1.07774 54.1434 1.76346 55.1826 2.63849 56.0496L2.75849 56.1696L34.7585 88.6596L49.1485 75.9496L17.1085 43.4896Z"
						fill="#00B4A9"
						fillOpacity="0.8"
					/>
					<path
						d="M101.678 16.9497C99.8504 15.1423 97.4078 14.0909 94.8385 14.0056C92.2692 13.9204 89.7623 14.8074 87.8184 16.4897C87.6984 16.5897 87.6084 16.6997 87.4984 16.7997L21.3984 75.0497L34.7784 88.6197L101.108 30.1497L101.218 30.0597C102.155 29.259 102.914 28.2729 103.45 27.1636C103.986 26.0543 104.286 24.846 104.331 23.615C104.376 22.3839 104.165 21.1569 103.712 20.0114C103.259 18.8659 102.573 17.8268 101.698 16.9597"
						fill="#00B4A9"
						fillOpacity="0.8"
					/>
				</g>
			</svg>
		),
	},

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save,
} );
