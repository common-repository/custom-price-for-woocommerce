<?php

/**
 * Stripe Gateway Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since   3.0.0
 * @version  3.0.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WP_Post;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
/**
 * The Main Stripe class
 **/
class Stripe implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    public function hooks()
    {
        \add_filter('wc_stripe_hide_payment_request_on_product_page', [$this, 'hide_request_on_cpw']);
    }
    /**
     * @param bool    $hide
     * @param WP_Post $post
     *
     * @return  bool
     */
    public function hide_request_on_cpw(bool $hide, \WP_Post $post) : bool
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($post->ID) || \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($post->ID)) {
            $hide = \true;
        }
        return $hide;
    }
}
