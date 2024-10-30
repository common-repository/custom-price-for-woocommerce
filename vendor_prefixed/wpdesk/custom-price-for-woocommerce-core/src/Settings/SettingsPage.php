<?php

namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Settings;

class SettingsPage extends \WC_Settings_Page
{
    public function __construct()
    {
        $this->id = 'custom_price';
        $this->label = \__('Custom Price', 'custom-price-for-woocommerce');
        parent::__construct();
    }
    /**
     * Default section settings.
     *
     * @return array<mixed>
     */
    protected function get_settings_for_default_section() : array
    {
        return \apply_filters('custom_price/settings/default', [], '');
    }
}
