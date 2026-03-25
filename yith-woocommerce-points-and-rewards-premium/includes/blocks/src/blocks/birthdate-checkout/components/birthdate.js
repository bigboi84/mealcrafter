import { __ } from '@wordpress/i18n';
import { Select } from '@yith/components';
import { useEffect, useState } from '@wordpress/element';
import { __experimentalGetSettings } from '@wordpress/date';

export const DateFieldsSelector = ( {onChange} ) => {
    const [ day, setDay ] = useState( 1 );
    const [ month, setMonth ] = useState( 1 );
    const [ year, setYear ] = useState( 1990 );

	const getDaysInMonth = (month, year) => {
		return new Date(year, month, 0).getDate();
	}

    const daysInMonth = getDaysInMonth( month, year );

    const days = [ ...( new Array( daysInMonth ).keys() ) ]
        .map( ( index ) => ( { value: String( index + 1 ), label: ( index + 1 < 10 ? '0' : '' ) + ( index + 1 ) } ) );

    const months = __experimentalGetSettings().l10n.months
        .map( ( label, index ) => ( { value: String( index + 1 ), label: label } ) );

	const years_count = () => {
		let this_year = new Date().getFullYear();
		return new Array( this_year - 1900 + 1 ).keys()
	}

    const years = [ ...( years_count() ) ]
        .map( ( index ) => ( { value: String( index + 1900 ), label: String( index + 1900 ) } ) );
		
    useEffect( () => {
        if ( day > daysInMonth ) {
            setDay( daysInMonth );
        }

		onChange( { 
			'day': day,
			'month': month,
			'year': year
		} )
    }, [ day, month, year ] )

    return (
	<div className="ywpar_birthdate_field" style={{'display': 'flex', 'gap': '5px'}}>
        <div style={{'position': 'relative'}}>
            <label htmlFor='day'>{ __( 'Day', 'yith-woocommerce-points-and-rewards' ) }</label>
            <Select
                id='day'
                options={ days }
                sx={ { width: 80 } }
                size='lg'
                value={ String( day ) }
                onChange={ (value) => setDay( Number( value ) ) }
            />
        </div>
        <div>
            <label htmlFor='month'>{ __( 'Month', 'yith-woocommerce-points-and-rewards' ) }</label>
            <Select
                id='month'
                options={ months }
                sx={ { width: 150 } }
                size='lg'
                value={ String( month ) }
                onChange={ (value) => setMonth( Number( value ) ) }
            />
        </div>
        <div>
            <label htmlFor='year'>{ __( 'Year', 'yith-woocommerce-points-and-rewards' ) }</label>
            <Select
                id='year'
                options={ years }
                sx={ { width: 100 } }
                size='lg'
                value={ String( year ) }
                onChange={ (value) => setYear( Number( value ) ) }
            />
        </div>
    </div>
	)
}