import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import edit from './edit';
import save from './save';
import { attributes, supports } from './block.js';
import { yithIcon } from '../../common';

const blockConfig = {
	title: __( 'Points Message for cart and checkout', 'yith-woocommerce-points-and-rewards' ),
	description: __( 'Show points message in cart and checkout.', 'yith-woocommerce-points-and-rewards' ),
	icon: yithIcon,
	parent: [ "woocommerce/checkout-fields-block", "woocommerce/checkout-totals-block", "woocommerce/cart-items-block", "woocommerce/cart-totals-block" ],
	category: 'yith-blocks',
	attributes,
	supports,
	edit,
	save
};

registerBlockType( 'yith/ywpar-cart-points-message', {
		...blockConfig,
} );
