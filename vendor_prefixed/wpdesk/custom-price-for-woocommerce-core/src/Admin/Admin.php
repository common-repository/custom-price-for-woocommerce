<?php

/**
 * Handle admin display
 * Adds a name your price setting tab, quick edit, bulk edit, loads metabox class.
 *
 * @class   Admin
 * @package WooCommerce Custom Price/Admin
 * @since 1.0.0
 * @version 3.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice\Admin;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use CPWFreeVendor\WPDesk\Library\CustomPrice\Helper;
use WC_Product;
class Admin implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    /**
     * Deprecated 2.7.0, use Helper::get_simple_supported_types()
     *
     * @var $simple_supported_types
     */
    public static $simple_supported_types = ['simple', 'subscription', 'bundle', 'composite', 'deposit', 'mix-and-match'];
    /**
     * @var string
     */
    private $plugin_url;
    /**
     * @var string
     */
    private $plugin_path;
    public function __construct(string $plugin_url, string $plugin_path)
    {
        $this->plugin_url = $plugin_url;
        $this->plugin_path = $plugin_path;
    }
    public function hooks()
    {
        // Admin Scripts.
        \add_action('admin_enqueue_scripts', [$this, 'meta_box_script'], 20);
        // Edit Products screen.
        \add_filter('woocommerce_get_price_html', [$this, 'admin_price_html'], 20, 2);
        // Product Filters.
        \add_filter('woocommerce_product_filters', [$this, 'product_filters']);
        \add_filter('parse_query', [$this, 'product_filters_query']);
        // Quick Edit.
        \add_action('manage_product_posts_custom_column', [$this, 'column_display'], 10, 2);
    }
    /**
     * Javascript to handle the NYP metabox options
     *
     * @param string $hook
     *
     * @return void
     * @since 1.0
     */
    public function meta_box_script($hook)
    {
        // Check if on Edit-Post page (post.php or new-post.php).
        if (!\in_array($hook, ['post-new.php', 'post.php'])) {
            return;
        }
        // Now check to see if the $post type is 'product'.
        global $post;
        if (!isset($post) || 'product' !== $post->post_type) {
            return;
        }
        // Enqueue and localize.
        $suffix = \defined('SCRIPT_DEBUG') && \SCRIPT_DEBUG ? '' : '';
        //'.min';
        \wp_enqueue_script('woocommerce_cpw_metabox', $this->plugin_url . '/assets/js/admin/metabox' . $suffix . '.js', ['jquery'], \time(), \true);
        $strings = ['enter_value' => \__('Enter a value', 'custom-price-for-woocommerce'), 'price_adjust' => \__('Enter a value (fixed or %)', 'custom-price-for-woocommerce'), 'simple_types' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_simple_supported_types(), 'variable_types' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_variable_supported_types()];
        \wp_localize_script('woocommerce_cpw_metabox', 'woocommerce_cpw_metabox', $strings);
    }
    /**
     * ---------------------------------------------------------------------------------
     * Product Overview - edit columns
     * ---------------------------------------------------------------------------------
     */
    /**
     * Change price in edit screen to NYP
     *
     * @param string $price
     * @param object $product
     *
     * @return string
     * @since 1.0
     */
    public function admin_price_html($price, $product)
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product) && !isset($product->is_filtered_price_html)) {
            $price = \wc_get_price_html_from_text() . \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_price_string($product, 'minimum', \true);
        } elseif (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product) && !isset($product->is_filtered_price_html)) {
            $price = \wc_get_price_html_from_text() . \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_price_string($product, 'minimum-variation', \true);
        }
        return $price;
    }
    /**
     * Add NYP as option to product filters in admin
     *
     * @param string $output
     *
     * @return string
     * @since 1.0.0
     */
    public function product_filters($output)
    {
        global $wp_query;
        $startpos = \strpos($output, '<select name="product_type"');
        if (\false !== $startpos) {
            $endpos = \strpos($output, '</select>', $startpos);
            if (\false !== $endpos) {
                $current = isset($wp_query->query['product_type']) ? $wp_query->query['product_type'] : \false;
                $cpw_option = \sprintf('<option value="name-your-price" %s > &#42; %s</option>', \selected('name-your-price', $current, \false), \__('Custom Price', 'custom-price-for-woocommerce'));
                $output = \substr_replace($output, $cpw_option, $endpos, 0);
            }
        }
        return $output;
    }
    /**
     * Filter the products in admin based on options
     *
     * @param mixed $query
     *
     * @since 1.0.0
     */
    public function product_filters_query($query)
    {
        global $typenow;
        if ('product' === $typenow) {
            if (isset($query->query_vars['product_type'])) {
                // Subtypes.
                if ('name-your-price' === $query->query_vars['product_type']) {
                    $query->query_vars['product_type'] = '';
                    $query->is_tax = \false;
                    $meta_query = ['relation' => 'OR', ['key' => '_cpw', 'value' => 'yes', 'compare' => '='], ['key' => '_has_cpw', 'value' => 'yes', 'compare' => '=']];
                    $query->query_vars['meta_query'] = $meta_query;
                }
            }
        }
    }
    /**
     * ---------------------------------------------------------------------------------
     * Quick Edit
     * ---------------------------------------------------------------------------------
     */
    /**
     * Display the column content
     *
     * @param string $column_name
     * @param int    $post_id
     *
     * @return string
     * @since 1.0
     */
    public function column_display($column_name, $post_id)
    {
        switch ($column_name) {
            case 'price':
                $_product = \wc_get_product($post_id);
                // Custom inline data for NYP.
                $cpw = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($_product) ? 'yes' : 'no';
                // If variable billing is enabled, continue to show options. Otherwise, deprecate.
                $is_sub = \wc_bool_to_string(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_subscription($_product));
                $is_variable_billing = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($_product);
                $show_variable_billing = \wc_bool_to_string(\apply_filters('wc_cpw_supports_variable_billing_period', $is_variable_billing));
                $is_variable_billing = \wc_bool_to_string($is_variable_billing);
                $suggested = \wc_format_localized_price(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_price($_product));
                $suggested_period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_billing_period($_product);
                $min = \wc_format_localized_price(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price($_product));
                $min_period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_billing_period($_product);
                $max = \wc_format_localized_price(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_maximum_price($_product));
                $is_cpw_allowed = \wc_bool_to_string($_product->is_type(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_simple_supported_types()));
                $is_minimum_hidden = \wc_bool_to_string(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_minimum_hidden($_product));
                echo '
					<div class="hidden" id="cpw_inline_' . \esc_html($post_id) . '">
						<div class="cpw">' . \esc_html($cpw) . '</div>
						<div class="is_sub">' . \esc_html($is_sub) . '</div>
						<div class="show_variable_billing">' . \esc_html($show_variable_billing) . '</div>
						<div class="is_variable_billing">' . \esc_html($is_variable_billing) . '</div>
						<div class="suggested_price">' . \esc_html($suggested) . '</div>
						<div class="suggested_period">' . \esc_html($suggested_period) . '</div>
						<div class="min_price">' . \esc_html($min) . '</div>
						<div class="min_period">' . \esc_html($min_period) . '</div>
						<div class="max_price">' . \esc_html($max) . '</div>
						<div class="is_cpw_allowed">' . \esc_html($is_cpw_allowed) . '</div>
						<div class="is_minimum_hidden">' . \esc_html($is_minimum_hidden) . '</div>
					</div>
				';
                break;
        }
    }
}
