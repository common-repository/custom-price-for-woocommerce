<?php

namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Admin;

class Install
{
    /**
     * @since 1.0.0
     */
    public function add_settings()
    {
        $option_prefix = 'woocommerce_cpw_';
        $label_text = \get_option($option_prefix . 'label_text');
        $suggested_text = \get_option($option_prefix . 'suggested_text');
        $min_text = \get_option($option_prefix . 'minimum_text');
        $max_text = \get_option($option_prefix . 'maximum_text');
        $button_text = \get_option($option_prefix . 'button_text');
        $button_text_single = \get_option($option_prefix . 'button_text_single');
        if (!$label_text) {
            \update_option($option_prefix . 'label_text', \_x('Price', 'Settings string', 'custom-price-for-woocommerce'));
        }
        if (!$suggested_text) {
            \update_option($option_prefix . 'suggested_text', \_x('Suggested price: %price%', 'Settings string', 'custom-price-for-woocommerce'));
        }
        if (!$min_text) {
            \update_option($option_prefix . 'minimum_text', \_x('Minimum price: %price%', 'Settings string', 'custom-price-for-woocommerce'));
        }
        if (!$max_text) {
            \update_option($option_prefix . 'maximum_text', \_x('Maximum price: %price%', 'Settings string', 'custom-price-for-woocommerce'));
        }
        if (!$button_text) {
            \update_option($option_prefix . 'button_text', \__('Add to cart', 'woocommerce'));
        }
        if (!$button_text_single) {
            \update_option($option_prefix . 'button_text_single', \__('Add to cart', 'woocommerce'));
        }
    }
}
