<?php

/**
 * Variable Products Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since   3.0.0
 * @version  3.0.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
use WC_Product;
/**
 * The Main VariableProducts class
 **/
class VariableProducts implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * VariableProducts Constructor
     *
     * @since 1.0.0
     */
    public function hooks()
    {
        \add_action('woocommerce_variable_product_sync_data', [$this, 'variable_sync_has_cpw_status']);
    }
    /**
     * Sync variable product has_cpw status.
     *
     * @param WC_Product $product
     *
     * @return  void
     * @since 1.0.0
     */
    public function variable_sync_has_cpw_status(\WC_Product $product)
    {
        $product->delete_meta_data('_has_cpw');
        $product->delete_meta_data('_cpw_hide_variable_price');
        // Only run on supported types.
        if ($product->is_type(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_variable_supported_types())) {
            global $wpdb;
            $variation_ids = $product ? $product->get_children() : [];
            if (empty($variation_ids)) {
                return;
            }
            $variation_id_placeholders = \implode(', ', \array_fill(0, \count($variation_ids), '%d'));
            $cpw_variation_count = $wpdb->get_var($wpdb->prepare("SELECT count(post_id) FROM {$wpdb->postmeta} WHERE post_id IN ( {$variation_id_placeholders} ) AND meta_key = '_cpw' AND meta_value = 'yes' LIMIT 1", $variation_ids));
            // Has NYP variations.
            if (0 < $cpw_variation_count) {
                $product->add_meta_data('_has_cpw', 'yes', \true);
                // Check if minimum priced-variation has the minimum hidden or a null minimum.
                $variation_prices = $product->get_variation_prices();
                $min_variation_id = \key($variation_prices['price']);
                $min_variation = \wc_get_product($min_variation_id);
                // If the cheapest variation is NYP and has no price (or min is hidden... save a meta flag on the parent).
                if ($min_variation && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($min_variation)) {
                    if (\false === \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price($min_variation) || \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_minimum_hidden($min_variation)) {
                        $product->add_meta_data('_cpw_hide_variable_price', 'yes', \true);
                    }
                }
            }
        }
    }
}
