import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import './editor.scss';
import parse from 'html-react-parser';

export default function Edit() {
	var message = '';
	const blocks = select("core/block-editor").getBlocks();
	if ( blocks.length ) {
		blocks.forEach(block => {
			if ( 'woocommerce/cart' === block.name ){
				message = ywpar_blocks_settings.points_message_on_cart;
			} else {
				message = ywpar_blocks_settings.points_message_on_checkout;
			}
		});
	}
	return (
		<div id="yith-par-message-cart" className="yith-par-message-cart woocommerce-cart-notice woocommerce-info" >
			{ parse(message) }
		</div>
	);
}
