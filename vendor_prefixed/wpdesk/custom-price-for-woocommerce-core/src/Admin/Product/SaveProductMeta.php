<?php

namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Admin\Product;

use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
use WC_Admin_Meta_Boxes;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Integration;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class SaveProductMeta implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     * @since 1.0
     */
    public function hooks()
    {
        \add_action('woocommerce_admin_process_product_object', [$this, 'save_product_meta']);
        \add_action('woocommerce_save_product_variation', [$this, 'save_product_variation'], 30, 2);
    }
    /**
     * Save extra meta info
     *
     * @param object $product
     *
     * @return void
     * @since 1.0 (renamed in 2.0)
     */
    public function save_product_meta($product)
    {
        // phpcs:disable WordPress.Security.NonceVerification
        $suggested = '';
        $minimum = '';
        if (isset($_POST['_cpw']) && \in_array($product->get_type(), \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_simple_supported_types())) {
            $product->update_meta_data('_cpw', 'yes');
            // Removing the sale price removes NYP items from Sale shortcodes.
            $product->set_sale_price('');
            $product->delete_meta_data('_has_cpw');
        } else {
            $product->update_meta_data('_cpw', 'no');
        }
        $price_label = isset($_POST['_price_label']) ? \esc_html(\wc_clean(\wp_unslash($_POST['_price_label']))) : '';
        $product->update_meta_data('_price_label', $price_label);
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            $price_type = isset($_POST['_suggested_price_type']) ? (int) \wc_clean(\wp_unslash($_POST['_suggested_price_type'])) : '';
            $product->update_meta_data('_suggested_price_type', $price_type);
            $suggested = isset($_POST['_suggested_price']) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['_suggested_price']))) : '';
            $product->update_meta_data('_suggested_price', $suggested);
            $minimum = isset($_POST['_min_price']) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['_min_price']))) : '';
            $product->update_meta_data('_min_price', $minimum);
            // Show error if minimum price is higher than the suggested price.
            if ($suggested && $minimum && $minimum > $suggested) {
                // Translators: %d variation ID.
                $error_notice = \__('The suggested price must be higher than the minimum for Custom Price products. Please review your prices.', 'custom-price-for-woocommerce');
                \WC_Admin_Meta_Boxes::add_error($error_notice);
            }
            // Maximum price.
            $maximum = isset($_POST['_maximum_price']) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['_maximum_price']))) : '';
            $product->update_meta_data('_maximum_price', $maximum);
            // Show error if minimum price is higher than the maximum price.
            if ($maximum && $minimum && $minimum > $maximum) {
                // Translators: %d variation ID.
                $error_notice = \__('The maximum price must be higher than the minimum for Custom Price products. Please review your prices.', 'custom-price-for-woocommerce');
                \WC_Admin_Meta_Boxes::add_error($error_notice);
            }
            // Variable Billing Periods.
            // Save whether subscription is variable billing or not (only for regular subscriptions).
            if (isset($_POST['_variable_billing']) && $product->is_type('subscription')) {
                $product->update_meta_data('_variable_billing', 'yes');
            } else {
                $product->delete_meta_data('_variable_billing');
            }
            // Suggested period - don't save if no suggested price.
            if ($product->is_type('subscription') && isset($_POST['_suggested_billing_period']) && \array_key_exists(\sanitize_key($_POST['_suggested_billing_period']), \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings())) {
                $suggested_period = \sanitize_key($_POST['_suggested_billing_period']);
                $product->update_meta_data('_suggested_billing_period', $suggested_period);
            } else {
                $product->delete_meta_data('_suggested_billing_period');
            }
            // Minimum period - don't save if no minimum price.
            if ($product->is_type('subscription') && $minimum && isset($_POST['_minimum_billing_period']) && \array_key_exists(\sanitize_key($_POST['_minimum_billing_period']), \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings())) {
                $minimum_period = \sanitize_key($_POST['_minimum_billing_period']);
                $product->update_meta_data('_minimum_billing_period', $minimum_period);
            } else {
                $product->delete_meta_data('_minimum_billing_period');
            }
            // Hide or obscure minimum price.
            $hide_min = isset($_POST['_hide_cpw_minimum']) ? 'yes' : 'no';
            $product->update_meta_data('_hide_cpw_minimum', $hide_min);
            // Hide or obscure minimum price.
            $hide_max = isset($_POST['_hide_cpw_maximum']) ? 'yes' : 'no';
            $product->update_meta_data('_hide_cpw_maximum', $hide_max);
        }
        // Set the regular price to enable WC to sort by price.
        if ('yes' === $product->get_meta('_cpw', \true)) {
            // Sort by minimum by default, with option to sort by suggested price.
            $sort_price = \apply_filters('wc_cpw_sort_by_suggested_price', \false, $product) ? $suggested : $minimum;
            /**
             * If nothing is entered for min/suggested, we should still set the _price as 0
             * This will resolve PHP notices for cart subtotals for items that are bundled, but not priced-individually.
             *
             * @see: https://github.com/woocommerce/woocommerce-product-bundles/issues/875
             */
            if ('' === $sort_price) {
                $sort_price = 0;
            }
            $product->set_price($sort_price);
            $product->set_regular_price($sort_price);
            $product->set_sale_price('');
            if ($product->is_type('subscription')) {
                $product->update_meta_data('_subscription_price', $sort_price);
            }
        }
        // Adding an action to trigger the product sync.
        \do_action('wc_cpw_variable_product_sync_data', $product);
    }
    /**
     * Save extra meta info for variable products
     *
     * @param mixed int|WC_Product_Variation $variation
     * @param int $i
     * return void
     *
     * @since 1.0.0
     */
    public function save_product_variation($variation, $i)
    {
        // phpcs:disable WordPress.Security.NonceVerification
        $is_legacy = \false;
        // Need to instantiate the product object on WC<3.8.
        if (\is_numeric($variation)) {
            $variation = \wc_get_product($variation);
            $is_legacy = \true;
        }
        // Set NYP status.
        $variation_is_cpw = isset($_POST['variation_is_cpw'][$i]) ? 'yes' : 'no';
        $variation->update_meta_data('_cpw', $variation_is_cpw);
        // Save suggested price.
        $variation_price_label = isset($_POST['variation_price_label']) && isset($_POST['variation_price_label'][$i]) ? \wc_clean(\wp_unslash($_POST['variation_price_label'][$i])) : '';
        $variation->update_meta_data('_price_label', $variation_price_label);
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            // Save suggested price.
            $variation_suggested_price_type = isset($_POST['variation_suggested_price_type']) && isset($_POST['variation_suggested_price_type'][$i]) ? (int) \wc_clean(\wp_unslash($_POST['variation_suggested_price_type'][$i])) : '';
            $variation->update_meta_data('_suggested_price_type', $variation_suggested_price_type);
            // Save suggested price.
            $variation_suggested_price = isset($_POST['variation_suggested_price']) && isset($_POST['variation_suggested_price'][$i]) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['variation_suggested_price'][$i]))) : '';
            $variation->update_meta_data('_suggested_price', $variation_suggested_price);
            // Save minimum price.
            $variation_min_price = isset($_POST['variation_min_price']) && isset($_POST['variation_min_price'][$i]) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['variation_min_price'][$i]))) : '';
            $variation->update_meta_data('_min_price', $variation_min_price);
            // Save minimum price.
            $variation_max_price = isset($_POST['variation_max_price']) && isset($_POST['variation_max_price'][$i]) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['variation_max_price'][$i]))) : '';
            $variation->update_meta_data('_max_price', $variation_max_price);
            $variation_hide_cpw_minimum = isset($_POST['variation_hide_cpw_minimum']) && isset($_POST['variation_hide_cpw_minimum'][$i]) ? 'yes' : 'no';
            $variation->update_meta_data('_hide_cpw_minimum', $variation_hide_cpw_minimum);
            $variation_max_price = isset($_POST['variation_maximum_price']) && isset($_POST['variation_maximum_price'][$i]) ? \wc_format_decimal(\wc_clean(\wp_unslash($_POST['variation_maximum_price'][$i]))) : '';
            $variation->update_meta_data('_maximum_price', $variation_max_price);
            if ($variation_suggested_price && $variation_min_price && (float) $variation_min_price > (float) $variation_suggested_price) {
                // Translators: %d variation ID.
                $error_notice = \sprintf(\__('The suggested price must be higher than the minimum for Custom Price products. Please review your prices for variation #%d.', 'custom-price-for-woocommerce'), $variation->get_id());
                \WC_Admin_Meta_Boxes::add_error($error_notice);
            }
            if ($variation_max_price && (float) $variation_max_price < (float) $variation_min_price) {
                // Translators: %d variation ID.
                $error_notice = \sprintf(\__('The maximum price must be higher than the minimum for Custom Price products. Please review your prices for variation #%d.', 'custom-price-for-woocommerce'), $variation->get_id());
                \WC_Admin_Meta_Boxes::add_error($error_notice);
            }
        }
        // If NYP, set prices to minimum.
        if ('yes' === $variation_is_cpw) {
            // Sort by minimum by default, with option to sort by suggested price.
            $sort_price = \apply_filters('wc_cpw_sort_by_suggested_price', \false, $variation) ? $variation_suggested_price : $variation_min_price;
            $sort_price = '' === $sort_price ? 0 : $sort_price;
            $variation->set_price($sort_price);
            $variation->set_regular_price($sort_price);
            $variation->set_sale_price('');
            if (isset($_POST['product-type']) && 'variable-subscription' === \sanitize_key($_POST['product-type'])) {
                $variation->update_meta_data('_subscription_price', $sort_price);
            }
        }
        if ($is_legacy) {
            $variation->save();
        }
    }
}
