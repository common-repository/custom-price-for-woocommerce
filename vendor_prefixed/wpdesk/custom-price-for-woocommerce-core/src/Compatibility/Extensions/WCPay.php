<?php

/**
 * WooCommerce Payments Gateway Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since    3.3.7
 * @version  3.3.7
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Product;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
/**
 * The Main WCPay class
 **/
class WCPay implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * WCPay Constructor
     */
    public function hooks()
    {
        \add_filter('wcpay_payment_request_is_product_supported', [$this, 'hide_request_buttons'], 10, 2);
    }
    /**
     * @param bool       $supported
     * @param WC_Product $product
     *
     * @return  bool
     */
    public function hide_request_buttons($supported, \WC_Product $product) : bool
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product) || \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product)) {
            $supported = \false;
        }
        return (bool) $supported;
    }
}
