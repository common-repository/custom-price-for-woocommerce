<?php

/**
 * Quick View Compatibility
 *
 * @package  WooCommerce Custom Price/Compatibility
 * @since   3.0.0
 * @version  3.0.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Compatibility\Extensions;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Display;
/**
 * The Main QV class
 **/
class QV implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * @var Display
     */
    private $display;
    public function __construct(\CPWFreeVendor\WPDesk\Library\CustomPrice\Display $display)
    {
        $this->display = $display;
    }
    public function hooks()
    {
        \add_action('wc_quick_view_enqueue_scripts', [$this, 'load_scripts']);
    }
    /**
     * Load scripts for use by QV on non-product pages.
     */
    public function load_scripts()
    {
        if (!\is_product()) {
            $this->display->register_scripts();
            $this->display->cpw_scripts();
        }
    }
}
