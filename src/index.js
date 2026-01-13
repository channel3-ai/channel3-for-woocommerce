/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';
import * as Woo from '@woocommerce/components';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const MyExamplePage = () => (
	<Fragment>
		<Woo.Section component="article">
			<Woo.SectionHeader title={__('Search', 'woo-extension')} />
			<Woo.Search
				type="products"
				placeholder="Search for something"
				selected={[]}
				onChange={(items) => setInlineSelect(items)}
				inlineTags
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader title={__('Dropdown', 'woo-extension')} />
			<Dropdown
				renderToggle={({ isOpen, onToggle }) => (
					<Woo.DropdownButton
						onClick={onToggle}
						isOpen={isOpen}
						labels={['Dropdown']}
					/>
				)}
				renderContent={() => <p>Dropdown content here</p>}
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Pill shaped container', 'woo-extension')}
			/>
			<Woo.Pill className={'pill'}>
				{__('Pill Shape Container', 'woo-extension')}
			</Woo.Pill>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader title={__('Spinner', 'woo-extension')} />
			<Woo.H>I am a spinner!</Woo.H>
			<Woo.Spinner />
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader title={__('Datepicker', 'woo-extension')} />
			<Woo.DatePicker
				text={__('I am a datepicker!', 'woo-extension')}
				dateFormat={'MM/DD/YYYY'}
			/>
		</Woo.Section>
	</Fragment>
);

addFilter('woocommerce_admin_pages_list', 'woo-extension', (pages) => {
	pages.push({
		container: MyExamplePage,
		path: '/woo-extension',
		breadcrumbs: [__('Woo Extension', 'woo-extension')],
		navArgs: {
			id: 'woo_extension',
		},
	});

	return pages;
});
