<?php

/**
 * Plugin main class.
 *
 * @package InvoicesWooCommerce
 */

namespace WPDesk\WPDeskCPWFree;

use CPWFreeVendor\WPDesk\Dashboard\DashboardWidget;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Admin\Install;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Integration;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Activateable;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\HookableCollection;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\HookableParent;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\AbstractPlugin;
use CPWFreeVendor\WPDesk_Plugin_Info;

/**
 * Main plugin class. The most important flow decisions are made here.
 */
class Plugin extends AbstractPlugin implements HookableCollection, Activateable {

	use HookableParent;

	/**
	 * @param WPDesk_Plugin_Info $plugin_info Plugin data.
	 */
	public function __construct( $plugin_info ) {
		$this->plugin_info = $plugin_info;
		parent::__construct( $this->plugin_info );

		$this->settings_url = admin_url( 'admin.php?page=wc-settings&tab=custom_price' );
		$this->docs_url     = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/docs/wlasna-cena-produktu-woocommerce' : 'https://www.wpdesk.net/docs/docs-custom-price-for-woocommerce';
	}

	/**
	 * Integrate with WordPress and with other plugins using action/filter system.
	 *
	 * @return void
	 */
	public function hooks() {
		parent::hooks();
		$this->add_hookable( new Integration() );
		$this->hooks_on_hookable_objects();

		( new DashboardWidget() )->hooks();
	}

	public function activate() {
		( new Install() )->add_settings();
	}

	/**
	 * Links filter.
	 *
	 * @param array $links Links.
	 *
	 * @return array
	 */
	public function links_filter( $links ) {
		$links = parent::links_filter( $links );
		// remove support link
		unset( $links[2] );

		$start_here_url  = admin_url( 'admin.php?page=wc-settings&tab=custom_price&section=support' );
		$start_here_link = '<a style="font-weight:700; color: #007050" href="' . $start_here_url . '">' . __( 'Start Here', 'custom-price-for-woocommerce' ) . '</a>';
		// first link
		array_unshift( $links, $start_here_link );

		$upgrade_url = \get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sklep/wlasna-cena-produktu-woocommerce-pro/?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=custom-price-pro&utm_content=plugin-list' : 'https://wpdesk.net/products/custom-price-for-woocommerce-pro/?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=custom-price-pro&utm_content=plugin-list';
		if ( ! \is_plugin_active( 'custom-price-for-woocommerce-pro/custom-price-for-woocommerce-pro.php' ) ) {
			$links[] = '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" style="color:#FF9743;font-weight:bold;">' . __( 'Upgrade to PRO', 'custom-price-for-woocommerce' ) . ' &rarr;</a>';
		}

		if ( array_key_exists( 'deactivate', $links ) ) {
			$deactivate_value = $links['deactivate'];
			unset( $links['deactivate'] );
			$links['deactivate'] = $deactivate_value;
		}

		return $links;
	}
}
