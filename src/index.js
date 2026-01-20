/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { Button, Card, CardBody, CardHeader, Notice } from '@wordpress/components';
import { check, warning, external } from '@wordpress/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Get connection data from localized script.
 */
/**
 * Channel3 base URL - can be overridden via wp_localize_script.
 * Default points to production integrations page.
 */
const CHANNEL3_DEFAULT_URL = 'https://trychannel3.com/brands/xxxx/integrations';

const getConnectionData = () => {
	return window.channel3Data || {
		isConnected: false,
		connectionData: {},
		settingsUrl: '',
		channel3Url: CHANNEL3_DEFAULT_URL,
	};
};

/**
 * Connection Status Component
 */
const ConnectionStatus = ({ isConnected, connectionData }) => {
	if (isConnected) {
		return (
			<div className="channel3-status channel3-status--connected">
				<Icon icon={check} size={24} />
				<div className="channel3-status__content">
					<strong>{__('Connected to Channel3', 'channel3-for-woocommerce')}</strong>
					{connectionData.connected_at && (
						<p className="channel3-status__date">
							{__('Connected on:', 'channel3-for-woocommerce')} {connectionData.connected_at}
						</p>
					)}
				</div>
			</div>
		);
	}

	return (
		<div className="channel3-status channel3-status--disconnected">
			<Icon icon={warning} size={24} />
			<div className="channel3-status__content">
				<strong>{__('Not Connected', 'channel3-for-woocommerce')}</strong>
				<p>{__('Connect your store to start syncing products.', 'channel3-for-woocommerce')}</p>
			</div>
		</div>
	);
};

/**
 * Main Channel3 Page Component
 */
const Channel3Page = () => {
	const { isConnected, connectionData, settingsUrl, channel3Url } = getConnectionData();

	// Check for disconnect success message in URL
	const urlParams = new URLSearchParams(window.location.search);
	const justDisconnected = urlParams.get('disconnected') === '1';

	return (
		<Fragment>
			{justDisconnected && (
				<Notice status="success" isDismissible onRemove={() => {}}>
					{__('Successfully disconnected from Channel3.', 'channel3-for-woocommerce')}
				</Notice>
			)}

			<div className="channel3-page">
				<Card>
					<CardHeader>
						<h2>{__('Channel3 Integration', 'channel3-for-woocommerce')}</h2>
					</CardHeader>
					<CardBody>
						<ConnectionStatus 
							isConnected={isConnected} 
							connectionData={connectionData} 
						/>

						<div className="channel3-description">
							<p>
								{__('Channel3 helps you sync your WooCommerce product catalog to reach more customers across multiple sales channels.', 'channel3-for-woocommerce')}
							</p>
						</div>

						<div className="channel3-actions">
							{isConnected ? (
								<Fragment>
									<Button
										variant="primary"
										href={channel3Url}
										target="_blank"
									>
										{__('Go to Channel3 Dashboard', 'channel3-for-woocommerce')}
										<Icon icon={external} size={16} />
									</Button>
									<Button
										variant="secondary"
										href={settingsUrl}
									>
										{__('Manage Settings', 'channel3-for-woocommerce')}
									</Button>
								</Fragment>
							) : (
								<Fragment>
									<Button
										variant="primary"
										href={channel3Url}
										target="_blank"
									>
										{__('Connect with Channel3', 'channel3-for-woocommerce')}
										<Icon icon={external} size={16} />
									</Button>
									<p className="channel3-help-text">
										{__('Log in to your Channel3 account and click "Connect WooCommerce Store" to get started.', 'channel3-for-woocommerce')}
									</p>
								</Fragment>
							)}
						</div>
					</CardBody>
				</Card>

				<Card className="channel3-info-card">
					<CardHeader>
						<h3>{__('What Channel3 Can Access', 'channel3-for-woocommerce')}</h3>
					</CardHeader>
					<CardBody>
						<div className="channel3-permissions">
							<div className="channel3-permissions__section">
								<h4>{__('Read-Only Access To:', 'channel3-for-woocommerce')}</h4>
								<ul>
									<li>{__('Product names and descriptions', 'channel3-for-woocommerce')}</li>
									<li>{__('Product prices and inventory', 'channel3-for-woocommerce')}</li>
									<li>{__('Product images and categories', 'channel3-for-woocommerce')}</li>
								</ul>
							</div>
							<div className="channel3-permissions__section channel3-permissions__section--no-access">
								<h4>{__('No Access To:', 'channel3-for-woocommerce')}</h4>
								<ul>
									<li>{__('Customer personal data', 'channel3-for-woocommerce')}</li>
									<li>{__('Order information', 'channel3-for-woocommerce')}</li>
									<li>{__('Payment details', 'channel3-for-woocommerce')}</li>
								</ul>
							</div>
						</div>
						<p className="channel3-privacy-link">
							<a href="https://trychannel3.com/privacy" target="_blank" rel="noopener noreferrer">
								{__('View Channel3 Privacy Policy', 'channel3-for-woocommerce')}
								<Icon icon={external} size={14} />
							</a>
						</p>
					</CardBody>
				</Card>
			</div>
		</Fragment>
	);
};

/**
 * Register the page with WooCommerce Admin
 */
addFilter('woocommerce_admin_pages_list', 'channel3-for-woocommerce', (pages) => {
	pages.push({
		container: Channel3Page,
		path: '/channel3',
		breadcrumbs: [__('Channel3', 'channel3-for-woocommerce')],
		navArgs: {
			id: 'channel3',
		},
	});

	return pages;
});

/**
 * Add store management quick link when connected
 * This appears on the WooCommerce Home screen after setup tasks are complete
 */
addFilter('woocommerce_admin_homescreen_quicklinks', 'channel3-for-woocommerce', (quickLinks) => {
	const { isConnected, settingsUrl } = getConnectionData();

	if (isConnected) {
		quickLinks.push({
			title: __('Channel3', 'channel3-for-woocommerce'),
			href: settingsUrl,
			icon: external,
		});
	}

	return quickLinks;
});
