<?php

/**
 * CoCart Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since   3.1.0
 * @version  3.1.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Cart;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
use WP_Error;
/**
 * The Main CoCart class
 **/
class CoCart implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * @var Cart
     */
    private $cart;
    public function __construct(\CPWFreeVendor\WPDesk\Library\CustomPrice\Cart $cart)
    {
        $this->cart = $cart;
    }
    /**
     * CoCart Constructor
     */
    public function hooks()
    {
        \add_filter('cocart_add_to_cart_validation', [$this, 'add_to_cart_validation'], 10, 6);
    }
    /**
     * Validate an NYP product before adding to cart.
     *
     * @param int   $product_id     - Contains the ID of the product.
     * @param int   $quantity       - Contains the quantity of the item.
     * @param int   $variation_id   - Contains the ID of the variation.
     * @param array $variation      - Attribute values.
     * @param array $cart_item_data - Extra cart item data we want to pass into the item.
     *
     * @return bool|WP_Error
     */
    public function add_to_cart_validation($passed, $product_id, $quantity, $variation_id = '', $variations = '', $cart_item_data = [])
    {
        $cpw_id = $variation_id ? $variation_id : $product_id;
        $product = \wc_get_product($cpw_id);
        // Skip if not a NYP product - send original status back.
        if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            return $passed;
        }
        $suffix = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suffix($cpw_id);
        // Get_posted_price() runs the price through the standardize_number() helper.
        $price = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_price($product, $suffix);
        // Get the posted billing period.
        $period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_period($product, $suffix);
        // Validate.
        $is_valid = $this->cart->validate_price($product, $quantity, $price, $period, 'cocart', \true);
        // Return error response.
        if (\is_string($is_valid)) {
            return new \WP_Error('cocart_cannot_add_product_to_cart', $is_valid, ['status' => 500]);
        } else {
            return \boolval($is_valid);
        }
    }
}
