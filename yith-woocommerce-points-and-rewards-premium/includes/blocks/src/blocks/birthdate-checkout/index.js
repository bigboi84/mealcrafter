import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { yithIcon } from '../../common';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerBlockType(metadata, {
	icon: yithIcon,
	edit: Edit,
	save: Save,
});
