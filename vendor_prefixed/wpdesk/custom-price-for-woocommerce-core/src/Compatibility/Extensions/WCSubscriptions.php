<?php

/**
 * Subscriptions Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since    3.0.0
 * @version  3.3.3
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Product;
use WC_Subscription;
use WC_Subscriptions_Admin;
use WC_Product_Variation;
use WC_Order_Item_Product;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Core\CoreCompatibility;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
/**
 * The Main WCSubscriptions class
 **/
class WCSubscriptions implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * WCSubscriptions Constructor
     */
    public function hooks()
    {
        // Extra 'Allow Switching' checkboxes.
        \add_filter('woocommerce_subscriptions_allow_switching_options', [$this, 'allow_switching_options']);
        // Handle subscription price switching.
        \add_filter('wcs_is_product_switchable', [$this, 'is_switchable'], 10, 3);
        \add_filter('woocommerce_subscriptions_add_switch_query_args', [$this, 'add_switch_query_args'], 10, 3);
        \add_action('woocommerce_variable-subscription_add_to_cart', [$this, 'customize_single_variable_product']);
        \add_filter('woocommerce_subscriptions_switch_is_identical_product', [$this, 'is_identical_product'], 10, 6);
        \add_filter('woocommerce_subscriptions_switch_error_message', [$this, 'switch_validation'], 10, 6);
        // Don't show edit link when resubscribing.
        \add_filter('wc_cpw_show_edit_link_in_cart', [$this, 'hide_edit_link_in_cart'], 10, 2);
    }
    /**
     * @param array $data
     *
     * @return array
     */
    public function allow_switching_options(array $data) : array
    {
        return \array_merge($data, [['id' => 'cpw_price', 'label' => \__('Change Custom Price subscription amount', 'custom-price-for-woocommerce')]]);
    }
    /**
     * Ensures that NYP products are allowed to be switched
     *
     * @param bool $is_switchable
     * @param WC_Product
     * @param mixed null|WC_Product_Variation
     *
     * @return bool
     */
    public function is_switchable($is_switchable, $product, $variation)
    {
        $_cpw_product = $variation instanceof \WC_Product_Variation ? $variation : $product;
        if (self::supports_cpw_switching() && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($_cpw_product)) {
            $is_switchable = \true;
        }
        return $is_switchable;
    }
    /**
     * Add the existing price/period to switch link to pre-populate values
     *
     * @param string $permalink
     * @param int    $subscription_id
     * @param        $item_id (the order item)
     *
     * @return string
     */
    public function add_switch_query_args($permalink, $subscription_id, $item_id)
    {
        $subscription = wcs_get_subscription($subscription_id);
        $existing_item = wcs_get_order_item($item_id, $subscription);
        $args = [];
        $cpw_product = $existing_item->get_product();
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($cpw_product)) {
            $prefix = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suffix($cpw_product);
            $args['cpw' . $prefix] = $subscription->get_item_subtotal($existing_item, $subscription->get_prices_include_tax());
            if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($cpw_product)) {
                $args['cpw-period' . $prefix] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Core\CoreCompatibility::get_prop($subscription, 'billing_period');
            }
            $permalink = \add_query_arg($args, $permalink);
        }
        return $permalink;
    }
    /**
     * Disable the attribute select if switching is not allowed
     */
    public function customize_single_variable_product()
    {
        // phpcs:disable WordPress.Security.NonceVerification
        if (isset($_GET['switch-subscription']) && isset($_GET['item'])) {
            $subscription = wcs_get_subscription(\absint($_GET['switch-subscription']));
            $existing_item = wcs_get_order_item(\absint($_GET['item']), $subscription);
            // Get the product/variation ID of this item.
            $cpw_product = $existing_item->get_product();
            if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($cpw_product)) {
                if (!self::supports_variable_switching()) {
                    \add_filter('woocommerce_dropdown_variation_attribute_options_html', [$this, 'disable_attributes']);
                    \add_filter('woocommerce_reset_variations_link', '__return_null');
                }
                if (!self::supports_cpw_switching()) {
                    \add_filter('wc_cpw_price_input_attributes', [$this, 'disable_input']);
                }
            }
        }
    }
    /**
     * Disable the print input select if switching is not allowed
     *
     * @param array $attributes The input attributes
     *
     * @return array
     */
    public function disable_input($attributes)
    {
        $attributes['disabled'] = 'disabled';
        return $attributes;
    }
    /**
     * Disable the attribute select if switching is not allowed
     *
     * @param string $html
     *
     * @return string
     */
    public function disable_attributes($html)
    {
        return \str_replace('<select', '<select disabled="disabled"', $html);
    }
    /**
     * Test if is identical product
     *
     * @param bool       $is_identical
     * @param WC_Product $product
     *
     * @return bool
     * @throws \Exception when the subscription is the same as the current subscription
     */
    public function is_identical_product($is_identical, $product_id, $quantity, $variation_id, $subscription, $item)
    {
        if ($is_identical && self::supports_cpw_switching()) {
            $cpw_id = $variation_id ? $variation_id : $product_id;
            if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($cpw_id)) {
                $prefix = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suffix($cpw_id);
                $cpw_product = \wc_get_product($cpw_id);
                $initial_subscription_price = \floatval($subscription->get_item_subtotal($item, $subscription->get_prices_include_tax()));
                $new_subscription_price = \floatval(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_price($cpw_id, $prefix));
                $initial_subscription_period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Core\CoreCompatibility::get_prop($subscription, 'billing_period');
                $new_subscription_period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_period($cpw_id, $prefix);
                // If variable billing period check both price and billing period.
                if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($cpw_id) && $new_subscription_price === $initial_subscription_price && $new_subscription_period === $initial_subscription_period) {
                    throw new \Exception(\__('Please modify the price or billing period so that it is not the same as your existing subscription.', 'custom-price-for-woocommerce'));
                    // Check price only.
                } elseif ($new_subscription_price === $initial_subscription_price) {
                    throw new \Exception(\__('Please modify the price so that it is not the same as your existing subscription.', 'custom-price-for-woocommerce'));
                    // If the price/period is different then this is NOT and identical product. Do not remove!
                } else {
                    $is_identical = \false;
                }
            }
        }
        return $is_identical;
    }
    /**
     * Test if the switching subscription is valid
     * if already valid (ie: changing variation), then skip
     * if not already valid, check that price or period is changed
     *
     * @param string                $error_message
     * @param int                   $product_id
     * @param int                   $quantity
     * @param int                   $variation_id - is a null '' if not a variation.
     * @param WC_Subscription       $subscription
     * @param WC_Order_Item_Product $sub_order_item
     *
     * @return string
     */
    public function switch_validation($error_message, $product_id, $quantity, $variation_id, $subscription, $sub_order_item)
    {
        if (empty($error_message)) {
            // If NYP-only switching, ensure product/variation IDs are the same.
            if (self::supports_cpw_switching()) {
                if ($variation_id && !self::supports_variable_switching() && $variation_id !== $sub_order_item->get_variation_id()) {
                    $error_message = \__('You are only allowed to change this subscription\'s price.', 'custom-price-for-woocommerce');
                }
            } else {
                $cpw_id = $variation_id ? $variation_id : $product_id;
                if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($cpw_id)) {
                    $error_message = \__('You do not have permission to modify the price of this subscription.', 'custom-price-for-woocommerce');
                }
            }
        }
        return $error_message;
    }
    /**
     * Is NYP switching enabled
     *
     * @param bool $is_product_switchable
     * @param      $product
     *
     * @return bool
     */
    public function supports_cpw_switching()
    {
        return \wc_string_to_bool(\get_option(\WC_Subscriptions_Admin::$option_prefix . '_allow_switching_cpw_price', 'no'));
    }
    /**
     * Is variable switching enabled
     *
     * @param bool       $is_product_switchable
     * @param WC_Product $product
     *
     * @return bool
     */
    public function supports_variable_switching()
    {
        $allow_switching = \get_option(\WC_Subscriptions_Admin::$option_prefix . '_allow_switching', 'no');
        return \strpos($allow_switching, 'variable') !== \false;
    }
    /**
     * Don't show edit link when resubscribing
     *
     * @param bool  $show
     * @param array $cart_item
     *
     * @return bool
     */
    public function hide_edit_link_in_cart($show, $cart_item)
    {
        if (isset($cart_item['subscription_resubscribe'])) {
            $show = \false;
        }
        return $show;
    }
}
