import { __ } from '@wordpress/i18n';

const attributes = {
	title: {
		type: 'string',
		default: __( 'Date of Birth', 'yith-woocommerce-points-and-rewards' ),
	},
	showStepNumber: {
		type: 'boolean',
		default: true,
	},
	className: {
		type: 'string',
		default: '',
	},
};

export default attributes;
