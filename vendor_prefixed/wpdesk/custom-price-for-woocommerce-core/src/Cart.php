<?php

/**
 * Interact with WooCommerce cart
 *
 * @class    Cart
 * @package  WooCommerce Custom Price/Classes
 * @since  1.0.0
 * @version  3.1.2
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Product;
use Exception;
use WC_Subscriptions_Product;
/**
 * Cart class.
 */
class Cart implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    public function hooks()
    {
        // Functions for cart actions - ensure they have a priority before addons (10).
        \add_filter('woocommerce_is_purchasable', [$this, 'is_purchasable'], 5, 2);
        \add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 5, 3);
        \add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_cart_item_from_session'], 11, 2);
        \add_filter('woocommerce_add_cart_item', [$this, 'set_cart_item'], 11, 1);
        \add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_cart_item'], 5, 6);
        // Re-validate prices in cart.
        \add_action('woocommerce_check_cart_items', [$this, 'check_cart_items']);
    }
    /**
     * ---------------------------------------------------------------------------------
     * Cart Filters
     * ---------------------------------------------------------------------------------
     */
    /**
     * Override woo's is_purchasable in cases of cpw products.
     *
     * @param bool       $is_purchasable
     * @param WC_Product $product
     *
     * @return  boolean
     * @since 1.0
     */
    public function is_purchasable(bool $is_purchasable, \WC_Product $product) : bool
    {
        if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product) && !\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product)) {
            return $is_purchasable;
        }
        /*
         * All CPW products should be purchasable.
         *
         * HACK: The exception is all-products woocommerce block, wchich does not respect
         * add_to_cart.url property and tries to add the product to the cart (ajax) anyway,
         * instead of redirecting it to the product page. To prevent that we can make it
         * unpurchasable for that specific case
         */
        global $wp;
        if (isset($wp->query_vars['rest_route']) && \false !== \strpos($wp->query_vars['rest_route'], '/products')) {
            // '/wc/store/v1/products'
            return \false;
        }
        return \true;
    }
    /**
     * Redirect to the cart when editing a price "in-cart".
     *
     * @param string $url
     *
     * @return string
     * @since 1.0.0
     */
    public function edit_in_cart_redirect(string $url) : string
    {
        return \wc_get_cart_url();
    }
    /**
     * Filter the displayed notice after redirecting to the cart when editing a price "in-cart".
     *
     * @param $message
     *
     * @return string
     * @since 1.0.0
     */
    public function edit_in_cart_redirect_message($message) : string
    {
        return \__('Cart updated.', 'custom-price-for-woocommerce');
    }
    /**
     * Add cart session data.
     *
     * @param array $cart_item_data extra cart item data we want to pass into the item.
     * @param int   $product_id     contains the id of the product to add to the cart.
     * @param int   $variation_id   ID of the variation being added to the cart.
     *
     * @since 1.0
     */
    public function add_cart_item_data(array $cart_item_data, $product_id, $variation_id) : array
    {
        // phpcs:disable WordPress.Security.NonceVerification
        // phpcs:enable
        if (!\is_scalar($product_id) || !\is_scalar($variation_id)) {
            return $cart_item_data;
        }
        $product_id = (int) $product_id;
        $variation_id = (int) $variation_id;
        // An NYP item can either be a product or variation.
        $cpw_id = $variation_id ? $variation_id : $product_id;
        $suffix = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suffix($cpw_id);
        $product = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::maybe_get_product_instance($cpw_id);
        // get_posted_price() removes the thousands separators.
        $posted_price = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_price($product, $suffix);
        // Is this an NYP item?
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($cpw_id) && $posted_price) {
            // Updating container in cart?
            if (isset($_POST['update-price']) && isset($_POST['_cpwnonce']) && \wp_verify_nonce(\sanitize_key($_POST['_cpwnonce']), 'cpw-nonce')) {
                $updating_cart_key = \wc_clean(\wp_unslash($_POST['update-price']));
                if (\WC()->cart->find_product_in_cart($updating_cart_key)) {
                    // Remove.
                    \WC()->cart->remove_cart_item($updating_cart_key);
                    // Redirect to cart.
                    \add_filter('woocommerce_add_to_cart_redirect', [$this, 'edit_in_cart_redirect']);
                    // Edit notice.
                    \add_filter('wc_add_to_cart_message_html', [$this, 'edit_in_cart_redirect_message']);
                }
            }
            // No need to check is_cpw b/c this has already been validated by validate_add_cart_item().
            $cart_item_data['cpw'] = (float) $posted_price;
        }
        // Add the subscription billing period (the input name is cpw-period).
        $posted_period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_period($product, $suffix);
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_subscription($cpw_id) && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($cpw_id) && $posted_period && \array_key_exists($posted_period, \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings())) {
            $cart_item_data['cpw_period'] = $posted_period;
        }
        return $cart_item_data;
    }
    /**
     * Adjust the product based on cart session data.
     *
     * @param array $cart_item $cart_item['data'] is product object in session
     * @param array $values    cart item array
     *
     * @since 1.0
     */
    public function get_cart_item_from_session(array $cart_item, array $values) : array
    {
        // No need to check is_cpw b/c this has already been validated by validate_add_cart_item().
        if (isset($values['cpw'])) {
            $cart_item['cpw'] = $values['cpw'];
            // Add the subscription billing period.
            if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_subscription($cart_item['data']) && isset($values['cpw_period']) && \array_key_exists($values['cpw_period'], \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings())) {
                $cart_item['cpw_period'] = $values['cpw_period'];
            }
            $cart_item = $this->set_cart_item($cart_item);
        }
        return $cart_item;
    }
    /**
     * Change the price of the item in the cart.
     *
     * @param array $cart_item
     *
     * @return  array
     * @since 1.0.0
     */
    public function set_cart_item(array $cart_item) : array
    {
        // Adjust price in cart if cpw is set.
        if (isset($cart_item['cpw']) && isset($cart_item['data'])) {
            $product = $cart_item['data'];
            $product->set_price($cart_item['cpw']);
            $product->set_sale_price($cart_item['cpw']);
            $product->set_regular_price($cart_item['cpw']);
            // Subscription-specific price and variable billing period.
            if ($product->is_type(['subscription', 'subscription_variation'])) {
                $product->update_meta_data('_subscription_price', $cart_item['cpw']);
                if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($product) && isset($cart_item['cpw_period'])) {
                    // Length may need to be re-calculated. Hopefully no one is using the length but who knows.
                    // v3.1.3 disables the length selector when in variable billing mode.
                    $original_period = \WC_Subscriptions_Product::get_period($product);
                    $original_length = \WC_Subscriptions_Product::get_length($product);
                    if ($original_length > 0 && $original_period && $cart_item['cpw_period'] !== $original_period) {
                        $factors = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::annual_price_factors();
                        $new_length = $original_length * $factors[$cart_item['cpw_period']] / $factors[$original_period];
                        $product->update_meta_data('_subscription_length', \floor($new_length));
                    }
                    // Set period to the chosen period.
                    $product->update_meta_data('_subscription_period', $cart_item['cpw_period']);
                    // Variable billing period is always a "per" interval.
                    $product->update_meta_data('_subscription_period_interval', 1);
                }
            }
        }
        return $cart_item;
    }
    /**
     * Validate an NYP product before adding to cart.
     *
     * @param        $passed
     * @param int    $product_id     - Contains the ID of the product.
     * @param int    $quantity       - Contains the quantity of the item.
     * @param string $variation_id   - Contains the ID of the variation.
     * @param string $variations
     * @param array  $cart_item_data - Extra cart item data we want to pass into the item.
     *
     * @return bool
     * @throws Exception
     * @since 1.0
     */
    public function validate_add_cart_item($passed, int $product_id, int $quantity, $variation_id = '', $variations = '', $cart_item_data = [])
    {
        $cpw_id = $variation_id ? $variation_id : $product_id;
        $product = \wc_get_product($cpw_id);
        // Skip if not a NYP product - send original status back.
        if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            return $passed;
        }
        $suffix = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suffix($cpw_id);
        // Get_posted_price() runs the price through the standardize_number() helper.
        $price = isset($cart_item_data['cpw']) ? $cart_item_data['cpw'] : \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_price($product, $suffix);
        // Get the posted billing period.
        $period = isset($cart_item_data['cpw_period']) ? $cart_item_data['cpw_period'] : \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_period($product, $suffix);
        return $this->validate_price($product, $quantity, $price, $period);
    }
    /**
     * Re-validate prices on cart load.
     * Specifically we are looking to prevent smart/quick pay gateway buttons completing an order that is invalid.
     */
    public function check_cart_items()
    {
        foreach (\WC()->cart->cart_contents as $cart_item_key => $cart_item) {
            if (isset($cart_item['cpw'])) {
                $period = isset($cart_item['cpw_period']) ? $cart_item['cpw_period'] : '';
                $this->validate_price($cart_item['data'], $cart_item['quantity'], $cart_item['cpw'], $period, 'cart');
            }
        }
    }
    /**
     * Validate an NYP product's price is valid.
     *
     * @param mixed  $product
     * @param int    $quantity
     * @param string $price
     * @param string $period
     * @param string $context
     * @param bool   $return_error - When true returns the string error message.
     *
     * @return boolean|string
     * @throws Exception When the entered price is not valid
     * @since 1.0.0
     */
    public function validate_price($product, $quantity, string $price, $period = '', $context = 'add-to-cart', $return_error = \false)
    {
        $is_configuration_valid = \true;
        try {
            // Sanity check.
            $product = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::maybe_get_product_instance($product);
            if (!$product instanceof \WC_Product) {
                $notice = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::error_message('invalid-product');
                throw new \Exception($notice);
            }
            $product_id = $product->get_id();
            $product_title = $product->get_title();
            // Get minimum price.
            $minimum = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price($product);
            // Get maximum price.
            $maximum = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_maximum_price($product);
            // Minimum error template.
            $error_template = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_minimum_hidden($product) ? 'hide_minimum' : 'minimum';
            // Check that it is a positive numeric value.
            if (!\is_numeric($price) || \is_infinite($price) || \floatval($price) < 0) {
                $notice = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::error_message('invalid', ['%%TITLE%%' => $product_title], $product, $context);
                throw new \Exception($notice);
                // Check that it is greater than minimum price for variable billing subscriptions.
            } elseif ($minimum && $period && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_subscription($product) && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($product)) {
                // Minimum billing period.
                $minimum_period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_billing_period($product);
                // Annual minimum.
                $minimum_annual = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::annualize_price($minimum, $minimum_period);
                // Annual price.
                $annual_price = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::annualize_price($price, $period);
                // By standardizing the prices over the course of a year we can safely compare them.
                if ($annual_price < $minimum_annual) {
                    $factors = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::annual_price_factors();
                    // If set period is in the $factors array we can calc the min price shown in the error according to entered period.
                    if (isset($factors[$period])) {
                        $error_price = $minimum_annual / $factors[$period];
                        $error_period = $period;
                        // Otherwise, just show the saved minimum price and period.
                    } else {
                        $error_price = $minimum;
                        $error_period = $minimum_period;
                    }
                    // The minimum is a combo of price and period.
                    $minimum_error = \wc_price($error_price) . ' / ' . $error_period;
                    $notice = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::error_message($error_template, ['%%TITLE%%' => $product_title, '%%MINIMUM%%' => $minimum_error], $product, $context);
                    throw new \Exception($notice);
                }
                // Check that it is greater than minimum price.
            } elseif ($minimum && \floatval($price) < \floatval($minimum)) {
                $notice = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::error_message($error_template, ['%%TITLE%%' => $product_title, '%%MINIMUM%%' => \wc_price($minimum)], $product, $context);
                throw new \Exception($notice);
                // Check that it is less than maximum price.
            } elseif ($maximum && \floatval($price) > \floatval($maximum)) {
                $error_template = '' !== $context ? 'maximum-' . $context : 'maximum';
                $notice = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::error_message('error_template', ['%%TITLE%%' => $product_title, '%%MAXIMUM%%' => \wc_price($maximum)], $product, $context);
                throw new \Exception($notice);
            }
        } catch (\Exception $e) {
            $notice = $e->getMessage();
            if ($notice) {
                if ($return_error) {
                    return $notice;
                }
                \wc_add_notice($notice, 'error');
            }
            $is_configuration_valid = \false;
        } finally {
            return $is_configuration_valid;
        }
    }
}
