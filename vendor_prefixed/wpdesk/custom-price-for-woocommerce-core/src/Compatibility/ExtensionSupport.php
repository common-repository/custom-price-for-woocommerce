<?php

/**
 * Plugin Cross-Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since    2.7.0
 * @version  3.0.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility;

use CPWFreeVendor\WPDesk\Library\CustomPrice\Integration;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Cart;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Core\CoreCompatibility;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\CoCart;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\GroupedProducts;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\QV;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\Stripe;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\VariableProducts;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\WCPay;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\WCSubscriptions;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Display;
use WC_Subscriptions;
/**
 * Handle loading of extensions depending on active plugins.
 */
class ExtensionSupport implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var Display
     */
    private $display;
    /**
     * @param Cart    $cart
     * @param Display $display
     */
    public function __construct(\CPWFreeVendor\WPDesk\Library\CustomPrice\Cart $cart, \CPWFreeVendor\WPDesk\Library\CustomPrice\Display $display)
    {
        $this->cart = $cart;
        $this->display = $display;
    }
    public function hooks()
    {
        \add_action('plugins_loaded', [$this, 'load_extensions'], 100);
    }
    /**
     * @since 1.0.0
     */
    public function load_extensions()
    {
        // Variable products.
        $extension_classes['variable_products'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\VariableProducts();
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            // CoCart support.
            if (\defined('CPWFreeVendor\\COCART_VERSION')) {
                $extension_classes['cocart'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\CoCart($this->cart);
            }
            // Grouped products.
            if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Core\CoreCompatibility::is_wc_version_gte('3.3.0')) {
                $extension_classes['grouped_products'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\GroupedProducts($this->display);
            }
            // Subscriptions switching.
            if (\class_exists('\WC_Subscriptions') && \version_compare(\WC_Subscriptions::$version, '1.4.0', '>')) {
                $extension_classes['subscriptions'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\WCSubscriptions();
            }
            // Stripe fixes.
            if (\class_exists('CPWFreeVendor\\WC_Stripe')) {
                $extension_classes['stripe'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\Stripe();
            }
            // QuickView support.
            if (\class_exists('CPWFreeVendor\\WC_Quick_View')) {
                $extension_classes['quickview'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\QV($this->display);
            }
            // WooCommerce Payments request buttons.
            if (\class_exists('CPWFreeVendor\\WC_Payments')) {
                $extension_classes['wcpay'] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions\WCPay();
            }
        }
        /**
         * 'wc_cpw_compatibility_modules' filter.
         * Use this to filter the required compatibility modules.
         *
         * @param array $extension_classes
         *
         * @since 1.0.0
         */
        $extension_classes = \apply_filters('wc_cpw_compatibility_modules', $extension_classes);
        foreach ($extension_classes as $name => $extensions) {
            if ($extensions instanceof \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable) {
                $extensions->hooks();
            }
        }
    }
}
