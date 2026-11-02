const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// the block's files come from block.json; the WooCommerce scripts are added next to them
module.exports = {
	...defaultConfig,
	entry: () => ({
		...defaultConfig.entry(),
		'woocommerce/view': './src/woocommerce/view.js',
		'woocommerce/admin': './src/woocommerce/admin.js'
	})
};
