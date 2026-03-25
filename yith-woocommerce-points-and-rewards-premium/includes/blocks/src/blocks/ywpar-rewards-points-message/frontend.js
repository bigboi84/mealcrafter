import { Suspense } from '@wordpress/element';
import { useEffect } from 'react'
import { useSelect } from '@wordpress/data';
import parse from 'html-react-parser';

var ywparcart_rewards = '';
var ywpar_reward_cached_message = '';

const Block = () => {
	var updating = false;

	useEffect( ()=> {
		/* force the right field points value */
		var inputmaxpoints = document.querySelector('.wp-block-yith-ywpar-rewards-points-message input[name="ywpar_points_max"]');
		var pointsfield = document.querySelector('#ywpar-points-max');
		if ( inputmaxpoints ) {
			pointsfield.value = inputmaxpoints.value;
			inputmaxpoints.dispatchEvent(new Event('change'));
		}
	})

	const message = useSelect( ( select ) => {
		const store = select( 'wc/store/cart' );
		var message = '';
		let carttotalsResolving = store.getResolutionState('getCartTotals');
		if ( carttotalsResolving  ){
			if ( store.getResolutionState('getCartTotals').status == 'finished' ) {

				if ( ywparcart_rewards == '' || ywparcart_rewards != store.getCartTotals() ) {
					ywparcart_rewards = store.getCartTotals();
					if ( updating ) {
						return '';
					}
					updating = true;		

					jQuery.ajax({
						url: ywpar_messages_settings.wc_ajax_url.toString().replace('%%endpoint%%', 'ywpar_update_cart_rewards_messages'),
						type: 'POST',
						async : false,
						success: function (res) {
							if ( res ) {
								message = res;
								ywpar_reward_cached_message = res
							} else {
								message = 'empty';
							}
						}
					});
				}
			}
		}
		updating = false;
		return message != 'empty' && '' != message ? message : ywpar_reward_cached_message;
	} );

    return (
		<>
		{ message && 
			<div className="wp-block-yith-par-message-reward-cart yith-par-message-reward-cart default-layout woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-info">
				{ parse(message) }
			</div>
		}
		</>
	);
}

window.addEventListener( 'load', () => {
	const element = document.querySelector(
        '.wp-block-yith-par-message-reward-cart_container'
    );

    if ( element ) {
		const root = ReactDOM.createRoot(element);
		root.render(
			<Suspense fallback={ <div className="wp-block-placeholder" /> }>
				<Block />
			</Suspense>
		);
	}
} );


