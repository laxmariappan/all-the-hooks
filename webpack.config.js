const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		admin: path.resolve( process.cwd(), 'src/admin', 'index.js' ),
	},
	output: {
		filename: '[name]/index.js',
		path: path.resolve( process.cwd(), 'build' ),
	},
};
