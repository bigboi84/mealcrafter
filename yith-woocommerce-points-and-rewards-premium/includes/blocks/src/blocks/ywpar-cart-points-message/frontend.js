import { Suspense}  from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import parse from 'html-react-parser';

var ywparcart_points = '';
var ywpar_cart_points_cached_message = '';

const Block = () => {
	var updating = false;

	const message = useSelect( ( select ) => {
		const store = select( 'wc/store/cart' );
		var message = '';
		let carttotalsResolving = store.getResolutionState('getCartTotals');
		if ( carttotalsResolving  ){
			if ( store.getResolutionState('getCartTotals').status == 'finished' ) {
				if ( ywparcart_points == '' || ywparcart_points != store.getCartTotals() ) {
					ywparcart_points = store.getCartTotals();
					if ( updating ) {
						return '';
					}
					updating = true;		

					jQuery.ajax({
						url: ywpar_messages_settings.wc_ajax_url.toString().replace('%%endpoint%%', 'ywpar_update_cart_messages'),
						type: 'POST',
						async : false,
						success: function (res) {
							if ( res ) {
								message = res;
								ywpar_cart_points_cached_message = res
							} else {
								message = 'empty';
							}
							
						}
					});
					
				}

			}
		}
		updating = false;

		return message != 'empty' && '' != message ? message : ywpar_cart_points_cached_message;
	} );

    return (
		<>
		{ message && 
			<div className="wp-block-yith-ywpar-cart-points-message yith-par-message-cart woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-info">
				{ parse(message) }
			</div>
			}
		</>
	);
}

window.addEventListener( 'load', () => {
    const element = document.querySelector(
        '.wp-block-yith-ywpar-cart-points-message_container'
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