import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import attributes from './attributes';
import { BirtdateHeading } from './components/heading';
import { withFilteredAttributes } from '../shared/hocs';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { Input, IconButton } from '@yith/components';
import { CalendarDaysIcon, XMarkIcon } from "../shared/icons";
import { DateFieldsSelector } from "./components/birthdate";
import { date, format, isInTheFuture } from '@wordpress/date';
//import { useOutsideClick } from '../shared/useOutsideClick';
import './style.scss';
import classNames from 'classnames';

const Block = (  {
	title,
	showStepNumber,
	className,
	checkoutExtensionData,
} ) => {
	const [ birthDate, setBirthDate ] = useState('');
	const [ showSelector, setShowSelector ] = useState(false);
	const [ firstLoad, setFirstLoad ] = useState(true);
	const [ futureDateError, setFutureDateError ] = useState(false);
	const { setExtensionData } = checkoutExtensionData;
	const { setValidationErrors, clearValidationError } = useDispatch(
		'wc/store/validation'
	);
	const dateFormat = ywpar_birthdate_settings.date_format;
	const dateFormats = ywpar_birthdate_settings.date_formats;
	//const regex = new RegExp(datePatterns[dateFormat], 'gm')

	const divClassName = classnames(
		'wc-block-components-checkout-step',
		className,
		{
			'wc-block-components-checkout-step--with-step-number':
			showStepNumber,
		}
	);

	const popupClassName = classnames(
		'ywpar_date_selector_popup',
		className,
		{
			'ywpar_date_selector_popup_opened':
			showSelector,
		}
	);

	useEffect( () => {
		setExtensionData(
			'ywpar/birthdate-block',
			'ywpar_birthday',
			'' + birthDate
		);
	},[birthDate])

	/*
	const clickOut = useOutsideClick( () => {
		setShowSelector(false)
	});
	*/


	const showSelectorChange = () => {
		setShowSelector( !showSelector );
	}

	const errorExist = ( error ) => {
		return !! error?.message && ! error?.hidden;
	};

	const { birthdateError } = useSelect( ( select ) => {
		const store = select( 'wc/store/validation' );
		return {
			birthdateError: store.getValidationError( 'ywpar_birthdate' ),
		};
	} );

	const birthDateSelectorChange = ( infos ) => {
		if ( ! firstLoad ) {
			let d = new Date( infos.year + '-' + infos.month + '-' + infos.day);
			setFutureDateError( isInTheFuture(d) );
			if ( errorExist( birthdateError ) ) {
				clearValidationError( 'ywpar_birthdate' );
			}
			setBirthDate( format( dateFormats[dateFormat], d ) )
		} else {
			setFirstLoad(false)
		}
	}

	const cleanBirthDate = () => {
		if ( errorExist( birthdateError ) ) {
			clearValidationError( 'ywpar_birthdate' );
		}
		setBirthDate('')
	}

	const { hasError } = useSelect( ( select ) => {
		const store = select( 'wc/store/checkout' );
		const isBefore = store.isBeforeProcessing();
		let birthdate_error = errorExist( birthdateError );

		if ( isBefore ) {
			if ( ! errorExist( birthdateError ) && futureDateError ) {
				setValidationErrors( {
					ywpar_birthdate: {
						message: __(
							'Please set a valid date',
							'yith-woocommerce-points-and-rewards'
						),
						hidden: false,
					},
				} );
			}
			birthdate_error = true;
		}

		return {
			hasError: birthdate_error,
		};
	} )

	useEffect( () => {
		if ( ! hasError ) {
			clearValidationError( 'ywpar_birthdate' );
		}
	}, [ hasError, clearValidationError ] );

	const hasErrorClassName = classNames(
		{
			'has-error':
			birthdateError?.message
		}
	)

	return (
		<fieldset className={ divClassName }>
			<BirtdateHeading>{title}</BirtdateHeading>
			<div className="wc-block-components-checkout-step__container">
				<div className="wc-block-components-checkout-step__content">
					<div className='wc-block-components-text-input is-active'>
						<div style={{'maxWidth': '340px'}} className={hasErrorClassName}
						>
							<Input
								value={birthDate ? birthDate : '' }
								fullWidth={true}
								disabled={true}
								endAdornment={
									<>
										<IconButton color={birthdateError?.message ? 'error' : 'inherit' } onClick={showSelectorChange} size="sm" sx={{margin: '0 -8px'}}>
											<CalendarDaysIcon />
										</IconButton>
										<IconButton color={birthdateError?.message ? 'error' : 'inherit' } onClick={cleanBirthDate} size="sm" sx={{margin: '0 -8px 0 8px'}}>
											<XMarkIcon />
										</IconButton>
									</>			
								}										
							/>
						
							<div className={popupClassName}>
								<DateFieldsSelector onChange={ birthDateSelectorChange } />
							</div>
						</div>
					</div>
					{ birthdateError?.message && (
						<div className="wc-block-components-validation-error">
							<p>{ birthdateError.message }</p>
						</div>
						)
					}
				</div>
			</div>
		</fieldset>
	);
}

export default withFilteredAttributes( attributes )( Block );