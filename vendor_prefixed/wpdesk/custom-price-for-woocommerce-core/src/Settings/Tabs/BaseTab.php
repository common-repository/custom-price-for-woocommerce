<?php

namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Settings\Tabs;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
abstract class BaseTab implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * @var string
     */
    protected $tab_id;
    /**
     * @var string
     */
    protected $tab_label;
    public function hooks()
    {
        \add_filter('woocommerce_get_sections_custom_price', [$this, 'register_section_name']);
    }
    /**
     * @param array<string, string> $sections already registered sections
     *
     * @return array<string, string> updated sections
     */
    public function register_section_name($sections)
    {
        $sections[$this->tab_id] = $this->tab_label;
        return $sections;
    }
}
