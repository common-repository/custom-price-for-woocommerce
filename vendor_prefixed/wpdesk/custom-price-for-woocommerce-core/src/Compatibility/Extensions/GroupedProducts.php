<?php

/**
 * Grouped Products Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since   3.0.0
 * @version  3.0.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Product;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Display;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
/**
 * The Main GroupedProducts class
 **/
class GroupedProducts implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * @var Display
     */
    private $display;
    public function __construct(\CPWFreeVendor\WPDesk\Library\CustomPrice\Display $display)
    {
        $this->display = $display;
    }
    /**
     * GroupedProducts Constructor
     *
     * @since 1.0.0
     */
    public function hooks()
    {
        \add_filter('woocommerce_grouped_product_list_column_price', [$this, 'display_input'], 10, 2);
        \add_action('woocommerce_grouped_add_to_cart', [$this, 'add_filter_for_cpw_attributes'], 0);
        \add_action('woocommerce_grouped_add_to_cart', [$this, 'remove_filter_for_cpw_attributes'], 9999);
        \add_filter('wc_cpw_field_suffix', [$this, 'grouped_cart_suffix'], 10, 2);
    }
    /**
     * Display the price input with a named suffix to distinguish it from other NYP inputs on the same page.
     *
     * @param string     $html
     * @param WC_Product $product
     *
     * @return string
     */
    public function display_input(string $html, \WC_Product $product) : string
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            $cpw_id = $product->get_id();
            $suffix = '-grouped-' . $cpw_id;
            \ob_start();
            $this->display->display_price_input($cpw_id, $suffix);
            $input = \ob_get_clean();
            $html .= $input;
        }
        return $html;
    }
    /**
     * Check for the suffix when adding to cart.
     *
     * @param string $suffix
     * @param int    $cpw_id the product ID or variation ID of the NYP product being displayed
     *
     * @return string
     */
    public function grouped_cart_suffix(string $suffix, int $cpw_id) : string
    {
        // phpcs:disable WordPress.Security.NonceVerification
        if (!empty($_REQUEST['quantity']) && \is_array($_REQUEST['quantity']) && isset($_REQUEST['quantity'][$cpw_id])) {
            $suffix = '-grouped-' . $cpw_id;
        }
        return $suffix;
    }
    /**
     * Add filter for data attributes.
     *
     * @since 1.0.0
     */
    public function add_filter_for_cpw_attributes()
    {
        \add_filter('wc_cpw_data_attributes', [__CLASS__, 'optional_cpw_attributes']);
    }
    /**
     * Remove filter for data attributes.
     *
     * @since 1.0.0
     */
    public function remove_filter_for_cpw_attributes()
    {
        \remove_filter('wc_cpw_data_attributes', [__CLASS__, 'optional_cpw_attributes']);
    }
    /**
     * Mark products as optional.
     *
     * @param array $attributes - The data attributes on the NYP div.
     *
     * @return array
     * @since 1.0.0
     */
    public function optional_cpw_attributes(array $attributes) : array
    {
        $attributes['optional'] = 'yes';
        return $attributes;
    }
}
