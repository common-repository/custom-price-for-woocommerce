<?php

/**
 * WooCommerce Custom Price Settings
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Settings\Tabs;

use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Integration;
use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
class GeneralTab extends \CPWFreeVendor\WPDesk\Library\CustomPrice\Settings\Tabs\BaseTab implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    public function __construct()
    {
        $this->tab_id = '';
        $this->tab_label = \__('General', 'woocommerce');
    }
    public function hooks()
    {
        parent::hooks();
        \add_filter('custom_price/settings/default', [$this, 'add_fields'], 10, 2);
    }
    /**
     * Get settings array
     *
     * @param array<int, array<string, string>> $settings
     * @param string $current_section
     *
     * @return array<int, array<string, string>>
     */
    public function add_fields($settings, $current_section)
    {
        if ($current_section !== $this->tab_id) {
            return $settings;
        }
        $docs_url = \get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/docs/wlasna-cena-produktu-woocommerce/?utm_source=custom-price-settings&utm_medium=link&utm_campaign=custom-price-docs-link&utm_content=general-settings' : 'https://wpdesk.net/docs/docs-custom-price-for-woocommerce/?utm_source=custom-price-settings&utm_medium=link&utm_campaign=custom-price-docs-link&utm_content=general-settings';
        $settings[] = ['title' => \__('Custom Price', 'custom-price-for-woocommerce'), 'type' => 'title', 'desc' => \sprintf(
            // translators: Docs link.
            \esc_html__('Read more in the %1$splugin documentation →%2$s', 'custom-price-for-woocommerce'),
            '<a href="' . \esc_url($docs_url) . '" target="_blank" style="color: #f15c8c">',
            '</a>'
        ), 'id' => 'woocommerce_cpw_options'];
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            $settings[] = ['title' => \__('Price Label', 'custom-price-for-woocommerce'), 'desc' => \__('This is the text that appears above the Custom Price input field.', 'custom-price-for-woocommerce'), 'id' => 'woocommerce_cpw_label_text', 'type' => 'text', 'css' => 'min-width:300px;', 'default' => \__('Price', 'custom-price-for-woocommerce'), 'desc_tip' => \true];
        } else {
            $buy_pro_url = \get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sklep/wlasna-cena-produktu-woocommerce-pro/?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=custom-price-pro&utm_content=plugin-settings' : 'https://wpdesk.net/products/custom-price-for-woocommerce-pro/?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=custom-price-pro&utm_content=plugin-settings';
            $settings[] = ['title' => \__('Price Label', 'custom-price-for-woocommerce'), 'desc' => \sprintf(
                // translators: Upgrade to PRO link.
                \esc_html__('%1$sUpgrade to PRO →%2$s and enable options below%3$s', 'custom-price-for-woocommerce'),
                '<b><a href="' . \esc_url($buy_pro_url) . '" target="_blank" style="color: #ff9743;">',
                '</a>',
                '</b>'
            ), 'id' => 'woocommerce_cpw_label_text', 'type' => 'text', 'css' => 'min-width:300px;', 'default' => \__('Price', 'custom-price-for-woocommerce'), 'desc_tip' => \false];
        }
        $settings[] = ['title' => \__('Suggested Price Text', 'custom-price-for-woocommerce'), 'desc' => \__('This is the text to display before the suggested price. You can use the placeholder %price% to display the suggested price.', 'custom-price-for-woocommerce'), 'id' => 'woocommerce_cpw_suggested_text', 'type' => 'text', 'css' => 'min-width:300px;', 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'default' => \__('Suggested price: %price%', 'custom-price-for-woocommerce'), 'desc_tip' => \true];
        $settings[] = ['title' => \__('Minimum Price Text', 'custom-price-for-woocommerce'), 'desc' => \__('This is the text to display before the minimum accepted price. You can use the placeholder %price% to display the minimum price.', 'custom-price-for-woocommerce'), 'id' => 'woocommerce_cpw_minimum_text', 'type' => 'text', 'css' => 'min-width:300px;', 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'default' => \__('Minimum price: %price%', 'custom-price-for-woocommerce'), 'desc_tip' => \true];
        $settings[] = ['title' => \__('Maximum Price Text', 'custom-price-for-woocommerce'), 'desc' => \__('This is the text to display before the maximum accepted price. You can use the placeholder %price% to display the maximum price.', 'custom-price-for-woocommerce'), 'id' => 'woocommerce_cpw_maximum_text', 'type' => 'text', 'css' => 'min-width:300px;', 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'default' => \__('Maximum price: %price%', 'custom-price-for-woocommerce'), 'desc_tip' => \true];
        $settings[] = ['title' => \__('Add to Cart Button Text for Shop', 'custom-price-for-woocommerce'), 'desc' => \__('This is the text that appears on the Add to Cart buttons on the Shop Pages.', 'custom-price-for-woocommerce'), 'id' => 'woocommerce_cpw_button_text', 'type' => 'text', 'css' => 'min-width:300px;', 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'default' => \__('Choose price', 'custom-price-for-woocommerce'), 'placeholder' => \__('Choose price', 'custom-price-for-woocommerce'), 'desc_tip' => \true];
        $settings[] = ['title' => \__('Add to Cart Button Text for Single Product', 'custom-price-for-woocommerce'), 'desc' => \__('This is the text that appears on the Add to Cart buttons on the Single Product Pages. Leave blank to inherit the default add to cart text.', 'custom-price-for-woocommerce'), 'id' => 'woocommerce_cpw_button_text_single', 'type' => 'text', 'css' => 'min-width:300px;', 'custom_attributes' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_custom_attributes(), 'default' => '', 'desc_tip' => \true];
        $settings[] = ['type' => 'sectionend', 'id' => 'woocommerce_cpw_style_options'];
        return $settings;
    }
}
