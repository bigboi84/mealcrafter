import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { BirtdateHeading } from './components/heading';
import { InspectorControls, useBlockProps, PlainText } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Disabled } from '@wordpress/components';
import { Input, IconButton } from '@yith/components';
import { CalendarDaysIcon, XMarkIcon } from "../shared/icons";
import { Root } from '../shared/components/root';

export const Edit = ( { attributes, setAttributes, className = '' } ) => {
	const { title = '', showStepNumber = true } = attributes;
	
	const blockProps = useBlockProps( {
		className: classnames( 'wc-block-components-checkout-step', className, {
			'wc-block-components-checkout-step--with-step-number':
            showStepNumber,
		} ),
	} );

	return (
		<div {...blockProps} style={{ display: 'block' }}>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Form Step Options',
						'yith-woocommerce-points-and-rewards'
					) }
				>
					<ToggleControl
						label={ __(
							'Show step number',
							'yith-woocommerce-points-and-rewards'
						) }
						checked={ showStepNumber }
						onChange={ () =>
								setAttributes( {
									showStepNumber: ! showStepNumber,
								} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<BirtdateHeading>
				<PlainText
					className={ '' }
					value={ title }
					onChange={ ( value ) => setAttributes( { title: value } ) }
					style={ { backgroundColor: 'transparent' } }
				/>
			</BirtdateHeading>
			<Root>
				<Disabled>
					<div className="wc-block-components-checkout-step__container">
						<div className="wc-block-components-checkout-step__content">
							<div className='wc-block-components-text-input is-active'>
								<div style={{'max-width': '340px'}} >
									<Input
										value=''
										fullWidth={true}
										disabled={true}
										endAdornment={
											<>
											<IconButton color="inherit" size="sm" sx={{margin: '0 -8px'}}>
												<CalendarDaysIcon />
											</IconButton>
											<IconButton color="inherit" size="sm" sx={{margin: '0 -8px 0 8px'}}>
												<XMarkIcon />
											</IconButton>
											</>			
										}										
									/>
								</div>
							</div>
						</div>
					</div>
				</Disabled>
			</Root>
		</div>
	);
}

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
