<?php

namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Admin\Product;

use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Integration;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WP_Post;
class ProductFields implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    public function hooks()
    {
        \add_action('woocommerce_product_after_variable_attributes', [$this, 'add_to_variations_metabox'], 10, 3);
        \add_action('woocommerce_product_options_pricing', [$this, 'add_to_metabox']);
        \add_action('woocommerce_variation_options', [$this, 'product_variations_options'], 10, 3);
    }
    /**
     * Add NYP checkbox to each variation
     *
     * @param string  $loop
     * @param array   $variation_data
     * @param WP_Post $variation
     * return void
     *
     * @since 1.0.0
     */
    public function product_variations_options($loop, $variation_data, $variation)
    {
        $variation_object = \wc_get_product($variation->ID);
        $variation_is_cpw = $variation_object->get_meta('_cpw', 'edit');
        ?>
		<label>
			<input type="checkbox" class="checkbox variation_is_cpw"
				   name="variation_is_cpw[<?php 
        echo \esc_attr($loop);
        ?>]" <?php 
        \checked($variation_is_cpw, 'yes');
        ?> /> <?php 
        \esc_html_e('Custom Price', 'custom-price-for-woocommerce');
        ?>
		</label>
		<?php 
    }
    /**
     * Metabox display callback.
     *
     * @return void
     * @since 1.0
     */
    public function add_to_metabox()
    {
        global $post, $thepostid, $product_object;
        $show_billing_period_options = \apply_filters('wc_cpw_supports_variable_billing_period', \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($product_object));
        ?>
		<div class="options_group show_if_cpw2 hide_if_external">
			<?php 
        $this->add_enable_custom_price_input($product_object);
        ?>
		</div>
		<div class="options_group show_if_cpw">
			<?php 
        $this->get_product_fields($product_object, $show_billing_period_options);
        ?>
		</div>
		<style>
			.woocommerce_options_panel div.cpw-row-flex {
				display: flex;
			}

			.woocommerce_options_panel div.cpw-row-flex > .cpw-col input[type="text"] {
				width: 80%;
			}

			.form-input-checkbox {
				margin-top: 40px;
			}

			.form-input-checkbox label {
				margin: 0 10px;
			}
		</style>
		<?php 
    }
    /**
     * Add NYP price inputs to each variation
     *
     * @param string  $loop
     * @param array   $variation_data
     * @param WP_Post $variation
     *
     * @return void
     * @since 1.0.0
     */
    public function add_to_variations_metabox($loop, $variation_data, $variation)
    {
        $variation_object = \wc_get_product($variation->ID);
        ?>
		<div class="variable_cpw_pricing">
			<?php 
        $this->get_product_fields($variation_object, \false, $loop);
        ?>
		</div>
		<?php 
    }
    public function add_enable_custom_price_input($product_object)
    {
        \woocommerce_wp_checkbox(['id' => '_cpw', 'wrapper_class' => '', 'value' => $product_object->get_meta('_cpw', \true), 'data_type' => 'price', 'label' => \__('Custom price', 'custom-price-for-woocommerce'), 'description' => '<span class="custom_price">' . \__('Turn on custom price', 'custom-price-for-woocommerce') . '</span>', 'default' => 'no']);
    }
    public function get_product_fields($product_object, $show_billing_period_options, $loop = \false)
    {
        $field_value = \sanitize_text_field($product_object->get_meta('_price_label', \true));
        $option_value = \get_option('woocommerce_cpw_label_text', \esc_html__('Price', 'custom-price-for-woocommerce'));
        \woocommerce_wp_text_input(['id' => \is_int($loop) ? "variation_price_label[{$loop}]" : '_price_label', 'class' => 'short', 'wrapper_class' => \is_int($loop) ? 'form-row form-row-full' : '', 'label' => \__('Price Label', 'custom-price-for-woocommerce'), 'desc_tip' => 'true', 'description' => \__('Label for input price.', 'custom-price-for-woocommerce'), 'data_type' => '', 'value' => !empty($field_value) ? $field_value : $option_value]);
        $buy_pro_url = \get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sklep/wlasna-cena-produktu-woocommerce-pro/?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=custom-price-pro&utm_content=edit-product' : 'https://wpdesk.net/products/custom-price-for-woocommerce-pro/?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=custom-price-pro&utm_content=edit-product';
        if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            \printf(
                // translators: Upgrade to PRO link.
                \esc_html__('%1$sUpgrade to PRO →%2$s and enable options below%3$s', 'custom-price-for-woocommerce'),
                '<p class="form-field form-row full pro-description" style="font-weight: bold;"><a href="' . \esc_url($buy_pro_url) . '" target="_blank" style="color: #ff9743;">',
                '</a>',
                '</p>'
            );
        }
        if (\class_exists('\WC_Subscriptions') && $show_billing_period_options) {
            \woocommerce_wp_checkbox(['id' => '_variable_billing', 'wrapper_class' => 'show_if_subscription', 'label' => \__('Variable Billing Period', 'custom-price-for-woocommerce'), 'description' => \__('Allow the customer to set the billing period.', 'custom-price-for-woocommerce')]);
        }
        \woocommerce_wp_select(['id' => \is_int($loop) ? "variation_suggested_price_type[{$loop}]" : '_suggested_price_type', 'wrapper_class' => \is_int($loop) ? 'form-row form-row-first' : '', 'value' => $product_object->get_meta('_suggested_price_type', \true), 'data_type' => 'price', 'label' => \__('Suggested Price', 'custom-price-for-woocommerce'), 'options' => ['0' => \__('Disabled', 'custom-price-for-woocommerce'), '1' => \__('Show as description', 'custom-price-for-woocommerce'), '2' => \__('Set amount to input as value', 'custom-price-for-woocommerce'), '3' => \__('Set amount to input as placeholder', 'custom-price-for-woocommerce')], 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'default' => 'no']);
        \woocommerce_wp_text_input(['id' => \is_int($loop) ? "variation_suggested_price[{$loop}]" : '_suggested_price', 'class' => 'wc_input_price short', 'wrapper_class' => \is_int($loop) ? 'form-row form-row-last' : '', 'label' => \__('Suggested price amount', 'custom-price-for-woocommerce') . ' (' . \get_woocommerce_currency_symbol() . ')', 'desc_tip' => 'true', 'description' => \__('Price to replace the default price string.  Leave blank to not suggest a price.', 'custom-price-for-woocommerce'), 'data_type' => 'price', 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'value' => $product_object->get_meta('_suggested_price', \true)]);
        if (\class_exists('\WC_Subscriptions') && $show_billing_period_options && \false === $loop) {
            \woocommerce_wp_select(['id' => '_suggested_billing_period', 'label' => \__('per', 'custom-price-for-woocommerce'), 'wrapper_class' => 'show_if_subscription', 'options' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings(), 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes()]);
        }
        echo '<div class="cpw-row-flex">';
        echo '<div class="cpw-col">';
        // Minimum Price.
        \woocommerce_wp_text_input(['id' => \is_int($loop) ? "variation_min_price[{$loop}]" : '_min_price', 'class' => 'wc_input_price short', 'wrapper_class' => \is_int($loop) ? 'form-row form-row-first' : '', 'label' => \__('Minimum price', 'custom-price-for-woocommerce') . ' (' . \get_woocommerce_currency_symbol() . ')', 'desc_tip' => 'true', 'description' => \__('Lowest acceptable price for product. Leave blank to not enforce a minimum. Must be less than or equal to the set suggested price.', 'custom-price-for-woocommerce'), 'data_type' => 'price', 'value' => $product_object->get_meta('_min_price', \true), 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes()]);
        if (\class_exists('\WC_Subscriptions') && $show_billing_period_options && \false === $loop) {
            // Minimum Billing Period.
            \woocommerce_wp_select(['id' => '_minimum_billing_period', 'label' => \__('per', 'custom-price-for-woocommerce'), 'wrapper_class' => 'show_if_subscription', 'options' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings(), 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes()]);
        }
        echo '</div>';
        echo '<div class="cpw-col">';
        // Option to hide minimum price.
        \woocommerce_wp_checkbox(['id' => \is_int($loop) ? "variation_hide_cpw_minimum[{$loop}]" : '_hide_cpw_minimum', 'label' => \__('Hide minimum price', 'custom-price-for-woocommerce'), 'wrapper_class' => \is_int($loop) ? 'form-row form-row-last form-input-checkbox' : '', 'description' => \__('Option to not show the minimum price on the front end.', 'custom-price-for-woocommerce'), 'value' => $product_object->get_meta('_hide_cpw_minimum', \true), 'desc_tip' => \true, 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes()]);
        echo '</div>';
        echo '</div>';
        echo '<div class="cpw-row-flex">';
        echo '<div class="cpw-col">';
        // Maximum Price.
        \woocommerce_wp_text_input(['id' => \is_int($loop) ? "variation_maximum_price[{$loop}]" : '_maximum_price', 'class' => 'wc_input_price short', 'wrapper_class' => \is_int($loop) ? 'form-row form-row-first' : '', 'label' => \__('Maximum price', 'custom-price-for-woocommerce') . ' (' . \get_woocommerce_currency_symbol() . ')', 'desc_tip' => 'true', 'description' => \__('Highest acceptable price for product. Leave blank to not enforce a maximum.', 'custom-price-for-woocommerce'), 'data_type' => 'price', 'value' => $product_object->get_meta('_maximum_price', \true), 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes()]);
        echo '</div>';
        echo '<div class="cpw-col">';
        // Option to hide minimum price.
        \woocommerce_wp_checkbox(['id' => \is_int($loop) ? "variation_hide_cpw_maximum[{$loop}]" : '_hide_cpw_maximum', 'label' => \__('Hide maximum price', 'custom-price-for-woocommerce'), 'wrapper_class' => \is_int($loop) ? 'form-row form-row-last form-input-checkbox' : '', 'description' => \__('Option to not show the maximum price on the front end.', 'custom-price-for-woocommerce'), 'value' => $product_object->get_meta('_hide_cpw_maximum', \true), 'desc_tip' => \true, 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes()]);
        echo '</div>';
        echo '</div>';
    }
}
