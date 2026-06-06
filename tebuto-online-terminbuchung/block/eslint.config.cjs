const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		rules: {
			'import/no-unresolved': [
				'error',
				{
					ignore: [ '^@wordpress/' ],
				},
			],
		},
	},
];
