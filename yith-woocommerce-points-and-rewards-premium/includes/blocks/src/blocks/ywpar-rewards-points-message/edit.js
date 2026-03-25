import { __ } from '@wordpress/i18n';
import './editor.scss';

import parse from 'html-react-parser';

export default function Edit() {
	const message = ywpar_blocks_settings.rewards_message;
	return (
		<div id="yith-ywpar-message-reward-cart" className="yith-ywpar-message-reward-cart woocommerce-cart-notice woocommerce-info" >
			{ parse(message) }
		</div>
	);
}
