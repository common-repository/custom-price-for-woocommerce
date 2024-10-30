<?php

namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Settings;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Settings_Page;
class SettingsIntegration implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    public function hooks()
    {
        \add_filter('woocommerce_get_settings_pages', [$this, 'add_settings_page'], 10, 1);
    }
    /**
     * @param array<WC_Settings_Page> $settings
     *
     * @return array<WC_Settings_Page>
     */
    public function add_settings_page($settings)
    {
        $settings[] = new \CPWFreeVendor\WPDesk\Library\CustomPrice\Settings\SettingsPage();
        return $settings;
    }
}
