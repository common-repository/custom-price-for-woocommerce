<?php

/**
 * Handle front-end display
 *
 * @class    Display
 * @package  WooCommerce Custom Price/Classes
 * @since    1.0.0
 * @version  3.3.8
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Product;
use WC_Subscriptions_Product;
/**
 * Display class.
 */
class Display implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
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
        // Single Product Display.
        \add_action('wp_enqueue_scripts', [$this, 'register_scripts'], 20);
        \add_action('woocommerce_before_single_product', [$this, 'replace_price_template']);
        \add_action('woocommerce_before_add_to_cart_button', [$this, 'display_price_input'], 9);
        \add_action('wc_cpw_after_price_input', [$this, 'display_variable_billing_periods'], 5, 2);
        \add_action('wc_cpw_after_price_input', [$this, 'display_minimum_price']);
        \add_action('wc_cpw_after_price_input', [$this, 'display_error_holder'], 30);
        // NYP has_options temp fix for product block.
        \add_filter('woocommerce_product_has_options', [$this, 'has_options'], 10, 2);
        // Edit in cart.
        \add_filter('woocommerce_quantity_input_args', [$this, 'edit_quantity'], 10, 2);
        \add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'single_add_to_cart_text'], 10, 2);
        \add_filter('wc_cpw_minimum_price_html', [$this, 'add_price_terms_html'], 10, 2);
        // Display NYP Prices.
        \add_filter('woocommerce_get_price_html', [$this, 'cpw_price_html'], 10, 2);
        \add_filter('woocommerce_variable_subscription_price_html', [$this, 'variable_subscription_cpw_price_html'], 10, 2);
        // Loop Display.
        \add_filter('woocommerce_product_add_to_cart_text', [$this, 'add_to_cart_text'], 10, 2);
        \add_filter('woocommerce_product_add_to_cart_url', [$this, 'add_to_cart_url'], 10, 2);
        // Kill AJAX add to cart WC2.5+.
        \add_filter('woocommerce_product_supports', [$this, 'supports_ajax_add_to_cart'], 10, 3);
        // Post class.
        \add_filter('post_class', [$this, 'add_post_class'], 30, 3);
        // Variable products.
        \add_action('woocommerce_single_variation', [$this, 'display_variable_price_input'], 12);
        \add_filter('woocommerce_variation_is_visible', [$this, 'variation_is_visible'], 10, 4);
        \add_filter('woocommerce_available_variation', [$this, 'available_variation'], 10, 3);
        \add_filter('woocommerce_get_variation_price', [$this, 'get_variation_price'], 10, 4);
        \add_filter('woocommerce_get_variation_regular_price', [$this, 'get_variation_price'], 10, 4);
        // Cart display.
        \add_filter('woocommerce_cart_item_price', [$this, 'add_edit_link_in_cart'], 10, 3);
    }
    /**
     * ---------------------------------------------------------------------------------
     * Single Product Display Functions
     * ---------------------------------------------------------------------------------
     */
    /**
     * Register the scripts and styles.
     *
     * @since 1.0.0
     */
    public function register_scripts()
    {
        $this->cpw_style();
        \wp_register_script('accounting', $this->plugin_url . 'assets/js/frontend/accounting.js', ['jquery'], '0.4.2', \true);
        $suffix = \defined('SCRIPT_DEBUG') && \SCRIPT_DEBUG ? '' : '';
        //'.min';
        \wp_register_script('woocommerce-cpw', $this->plugin_url . 'assets/js/frontend/front' . $suffix . '.js', ['jquery', 'accounting'], \time(), \true);
    }
    /**
     * Load a little stylesheet.
     *
     * @return void
     * @since 1.0
     */
    public function cpw_style()
    {
        $suffix = \defined('SCRIPT_DEBUG') && \SCRIPT_DEBUG ? '' : '';
        //'.min';
        \wp_enqueue_style('woocommerce-cpw', $this->plugin_url . 'assets/css/frontend/front' . $suffix . '.css', \false, \time());
        \wp_style_add_data('woocommerce-cpw', 'rtl', 'replace');
        if ($suffix) {
            \wp_style_add_data('woocommerce-cpw', 'suffix', '.min');
        }
    }
    /**
     * Load price input script.
     *
     * @return void
     */
    public function cpw_scripts()
    {
        \wp_enqueue_script('accounting');
        \wp_enqueue_script('woocommerce-cpw');
        $params = [
            'currency_format_num_decimals' => \wc_get_price_decimals(),
            'currency_format_symbol' => \get_woocommerce_currency_symbol(),
            'currency_format_decimal_sep' => \wc_get_price_decimal_separator(),
            'currency_format_thousand_sep' => \wc_get_price_thousand_separator(),
            'currency_format' => \str_replace(['%1$s', '%2$s'], ['%s', '%v'], \get_woocommerce_price_format()),
            // For accounting.js.
            'annual_price_factors' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::annual_price_factors(),
            'i18n_subscription_string' => \__('%price / %period', 'custom-price-for-woocommerce'),
            'trim_zeros' => \apply_filters('woocommerce_price_trim_zeros', \false) && \wc_get_price_decimals() > 0,
        ];
        \wp_localize_script('woocommerce-cpw', 'woocommerce_cpw_params', \apply_filters('wc_cpw_script_params', $params));
    }
    /**
     * Remove the default price template.
     *
     * @since 1.0.0
     */
    public function replace_price_template()
    {
        global $product;
        if (\apply_filters('wc_cpw_replace_price_template', \true, $product) && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product) && \has_action('woocommerce_single_product_summary', 'woocommerce_template_single_price')) {
            // Move price template to before NYP input.
            \remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
            \add_action('woocommerce_before_add_to_cart_form', [$this, 'display_suggested_price']);
            // Restore price template to original.
            \add_action('woocommerce_after_single_product', [$this, 'restore_price_template']);
        }
    }
    /**
     * Restore the default price template.
     *
     * @since 1.0.0
     */
    public function restore_price_template()
    {
        \add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        \remove_action('woocommerce_before_add_to_cart_form', [$this, 'display_suggested_price']);
    }
    /**
     * Call the Price Input Template.
     *
     * @param mixed obj|int $product
     * @param array $args - specific values for this display
     *                    {
     *                    'suffix' => string key to integration with Bundles, OPC, etc - anywhere more than 1 input may
     *                    appear
     *                    'force' => bool
     *                    }
     *
     * @return  void
     * @since 1.0
     */
    public function display_price_input($product = \false, $args = [])
    {
        // Backcompat handling of string suffix.
        if (\is_string($args)) {
            $args = ['suffix' => $args];
        }
        $product = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::maybe_get_product_instance($product);
        if (!$product) {
            global $product;
        }
        // If not NYP quit right now. Also distinguish if we're a variable product vs simple.
        if (!$product || 'woocommerce_single_variation' === \current_action() && !\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product) || 'woocommerce_single_variation' !== \current_action() && !\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product) && !\apply_filters('wc_cpw_force_display_price_input', isset($args['force']) ? \boolval($args['force']) : \false, $product)) {
            return;
        }
        $suffix = isset($args['suffix']) ? $args['suffix'] : '';
        //$price    = Helper::get_price_value_attr( $product, $suffix );
        $counter = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_counter();
        $input_id = 'cpw-' . $counter;
        $type = (int) $product->get_meta('_suggested_price_type', \true);
        $defaults = [
            'product_id' => $product->get_id(),
            'cpw_product' => $product,
            'counter' => $counter,
            'input_id' => $input_id,
            'input_name' => 'cpw' . $suffix,
            'input_value' => $type === 2 ? \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::format_price(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_price($product)) : '',
            'input_label' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_price_input_label_text($product),
            'classes' => ['input-text', 'amount', 'cpw-input', 'text'],
            'aria-describedby' => ['cpw-minimum-price-' . $input_id, 'cpw-error-' . $input_id],
            'custom_attributes' => [],
            'suffix' => $suffix,
            'placeholder' => $type === 3 ? \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::format_price(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_price($product)) : '',
            'prefix' => $suffix,
            // For backcompat.
            'updating_cart_key' => isset($_GET['update-price']) && \WC()->cart->find_product_in_cart(\sanitize_key($_GET['update-price'])) ? \sanitize_key($_GET['update-price']) : '',
            '_cpwnonce' => isset($_GET['_cpwnonce']) ? \sanitize_key($_GET['_cpwnonce']) : '',
        ];
        /**
         * Filter wc_cpw_price_input_attributes
         *
         * @param array  $attributes The array of attributes for the NYP div
         * @param        $product    WC_Product The product object
         * @param string $suffix     - needed for grouped, composites, bundles, etc.
         *
         * @return string
         * @since 1.0.0
         */
        $args = (array) \apply_filters('wc_cpw_price_input_attributes', \wp_parse_args($args, $defaults), $product, $suffix);
        // Load up the NYP scripts.
        $this->cpw_scripts();
        // Get the price input template.
        \wc_get_template('single-product/price-input.php', $args, \false, \dirname(__DIR__) . '/templates/');
        /**
         * Filter woocommerce_get_price_input
         *
         * @param string $html       - the resulting input's html.
         * @param int    $product_id - the product id.
         * @param string $suffix     - needed for grouped, composites, bundles, etc.
         *
         * @return string
         * @deprecated 3.0.0
         */
        if (\has_filter('woocommerce_get_price_input')) {
            \wc_doing_it_wrong(__FUNCTION__, 'woocommerce_get_price_input filter has been removed for security reasons! Please consider using the wc_cpw_price_input_attributes filter to modify attributes or overriding the price-input.php template.', '3.0');
        }
        \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::increase_counter();
    }
    /**
     * Display the suggested price.
     *
     * @param mixed obj|int $product
     *
     * @return  void
     * @since 1.0
     */
    public function display_suggested_price($product = \false)
    {
        $product = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::maybe_get_product_instance($product);
        if (!$product) {
            global $product;
        }
        $suggested_price_html = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_price_html($product);
        if (!$suggested_price_html && !\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product)) {
            return;
        }
        if ((int) $product->get_meta('_suggested_price_type', \true) === 1) {
            echo '<p class="price suggested-price">' . \wp_kses_post($suggested_price_html) . '</p>';
        }
    }
    /**
     * Display minimum price plus any subscription terms if applicable.
     *
     * @param mixed obj|int $product
     *
     * @return  void
     * @since 1.0
     */
    public function display_minimum_price($product = \false)
    {
        $product = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::maybe_get_product_instance($product);
        if (!$product) {
            global $product;
        }
        $minimum_price_html = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price_html($product);
        if ($minimum_price_html && !\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product)) {
            // Get the minimum price template.
            \wc_get_template('single-product/minimum-price.php', ['product_id' => $product->get_id(), 'cpw_product' => $product, 'counter' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_counter()], \false, $this->plugin_path . '/templates/');
        }
        $maximum_price_html = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_maximum_price_html($product);
        if ($maximum_price_html && !\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product)) {
            // Get the maximum price template.
            \wc_get_template('single-product/maximum-price.php', ['product_id' => $product->get_id(), 'cpw_product' => $product, 'counter' => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_counter()], \false, $this->plugin_path . '/templates/');
        }
    }
    /**
     * Show the empty error-holding div.
     *
     * @since 1.0.0
     */
    public function display_error_holder()
    {
        \printf('<div id="cpw-error-%s" class="woocommerce-cpw-message" aria-live="assertive" style="display: none"><ul class="woocommerce-error wc-cpw-error"></ul></div>', \esc_attr(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_counter()));
    }
    /**
     * Tell WooCommerce that a NYP product has options, forces All Products blocks to redirect to single page.
     *
     * @param bool   $has_options
     * @param object $product
     *
     * @return bool
     * @since 1.0.0
     */
    public function has_options(bool $has_options, $product) : bool
    {
        return !$has_options && \defined('CPWFreeVendor\\REST_REQUEST') && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product) ? \true : $has_options;
    }
    /**
     * Carry cart quantity back to single product page when editing.
     *
     * @param array  $args
     * @param object $product
     *
     * @return array
     * @since 1.0.0
     */
    public function edit_quantity(array $args, $product) : array
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            if (isset($_GET['update-price']) && isset($_GET['quantity']) && isset($_GET['_cpwnonce']) && \wp_verify_nonce(\sanitize_key($_GET['_cpwnonce']), 'cpw-nonce')) {
                $updating_cart_key = \wc_clean(\wp_unslash($_GET['update-price']));
                if (isset(\WC()->cart->cart_contents[$updating_cart_key])) {
                    $qty = \wc_clean(\wp_unslash($_GET['quantity']));
                    $args['input_value'] = $qty;
                }
            }
        }
        return $args;
    }
    /**
     * If NYP change the single item's add to cart button text.
     * Don't include on variations as you can't be sure all the variations are NYP.
     * Variations will be handled via JS.
     *
     * @param string $text
     * @param object $product
     *
     * @return string
     * @since 1.0.0
     */
    public function single_add_to_cart_text(string $text, $product) : string
    {
        //pre( $product );
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            $cpw_text = \trim(\apply_filters('wc_cpw_single_add_to_cart_text', \get_option('woocommerce_cpw_button_text_single', \__('Add to cart', 'woocommerce')), $product));
            if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
                $cpw_text = \__('Add to cart', 'woocommerce');
            }
            if ('' !== $cpw_text) {
                $text = $cpw_text;
            }
            if (isset($_GET['update-price']) && isset($_GET['_cpwnonce']) && \wp_verify_nonce(\sanitize_key($_GET['_cpwnonce']), 'cpw-nonce')) {
                $updating_cart_key = \wc_clean(\wp_unslash($_GET['update-price']));
                if (isset(\WC()->cart->cart_contents[$updating_cart_key])) {
                    $text = \apply_filters('wc_cpw_single_update_cart_text', \__('Update Cart', 'custom-price-for-woocommerce'), $product);
                }
            }
        }
        return $text;
    }
    /**
     * Add subscription terms to minimum price html
     *
     * @param string     $html
     * @param WC_Product $product
     *
     * @return  string
     * @since 1.0.0
     */
    public function add_price_terms_html(string $html, \WC_Product $product) : string
    {
        $subscription_terms = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_terms_html($product);
        if ($html && $subscription_terms) {
            // Translators: %1$s is minimum price html. %2$s subscription terms.
            $html = \sprintf(\__('%1$s %2$s', 'custom-price-for-woocommerce'), $html, $subscription_terms);
        } elseif ($subscription_terms) {
            $html = $subscription_terms;
        }
        return $html;
    }
    /**
     * ---------------------------------------------------------------------------------
     * Display NYP Price HTML
     * ---------------------------------------------------------------------------------
     */
    /**
     * Filter the Price HTML.
     *
     * @param string $html
     * @param object $product
     *
     * @return string
     * @since 1.0.0
     */
    public function cpw_price_html(string $html, $product) : string
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            $html = \apply_filters('wc_cpw_price_html', \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_price_html($product), $product);
        } elseif (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product) && !$product->is_type('variable-subscription')) {
            $min_variation_string = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_variable_price_hidden($product) ? '' : \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_price_string($product, 'minimum-variation');
            $html = '' !== $min_variation_string ? \wc_get_price_html_from_text() . $min_variation_string : '';
            $html = \apply_filters('wc_cpw_variable_price_html', $html, $product);
        }
        return $html;
    }
    /**
     * Filter the Price HTML for Variable Subscriptions.
     *
     * @param string $html
     * @param object $product
     *
     * @return string
     * @since   1.0
     * @renamed in 2.0
     */
    public function variable_subscription_cpw_price_html(string $html, $product) : string
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product) && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_variable_price_hidden($product) && \intval(\WC_Subscriptions_Product::get_sign_up_fee($product)) === 0 && \WC_Subscriptions_Product::get_trial_length($product) === 0) {
            $html = '';
        }
        return \apply_filters('wc_cpw_variable_subscription_cpw_html', $html, $product);
    }
    /**
     * ---------------------------------------------------------------------------------
     * Loop Display Functions
     * ---------------------------------------------------------------------------------
     */
    /**
     * If NYP change the loop's add to cart button text.
     *
     * @param string $text
     * @param        $product
     *
     * @return string
     * @since 1.0
     */
    public function add_to_cart_text(string $text, $product) : string
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            $cpw_text = \trim(\apply_filters('wc_cpw_add_to_cart_text', \get_option('woocommerce_cpw_button_text', \__('Choose price', 'custom-price-for-woocommerce')), $product));
            if ('' !== $cpw_text) {
                $text = $cpw_text;
            } else {
                $text = \__('Choose price', 'custom-price-for-woocommerce');
            }
        }
        return $text;
    }
    /**
     * If NYP change the loop's add to cart button URL.
     * Disable ajax add to cart and redirect to product page.
     * Supported by WC<2.5.
     *
     * @param string $url
     *
     * @return string
     * @since 1.0
     */
    public function add_to_cart_url($url, $product = null)
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            $url = \get_permalink($product->get_id());
        }
        return $url;
    }
    /**
     * Disable ajax add to cart and redirect to product page.
     * Supported by WC2.5+
     *
     * @param string $url
     *
     * @return string
     * @since 1.0
     */
    public function supports_ajax_add_to_cart($supports_ajax, $feature, $product)
    {
        if ('ajax_add_to_cart' === $feature && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($product)) {
            $supports_ajax = \false;
        }
        return $supports_ajax;
    }
    /**
     * ---------------------------------------------------------------------------------
     * Post Class
     * ---------------------------------------------------------------------------------
     */
    /**
     * Add cpw to post class.
     *
     * @param array  $classes - post classes
     * @param string $class
     * @param int    $post_id
     *
     * @return array
     * @since 1.0.0
     */
    public function add_post_class($classes, $class = '', $post_id = '')
    {
        if (!$post_id || \get_post_type($post_id) !== 'product') {
            return $classes;
        }
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($post_id) || \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($post_id)) {
            $classes[] = 'cpw-product';
        }
        return $classes;
    }
    /**
     * ---------------------------------------------------------------------------------
     * Variable Product Display Functions
     * ---------------------------------------------------------------------------------
     */
    /**
     * Call the Price Input Template for Variable products.
     *
     * @param mixed obj|int $product
     * @param string $suffix - suffix is key to integration with Bundles
     *
     * @since 1.0.0
     */
    public function display_variable_price_input($product = \false, $suffix = \false)
    {
        $this->display_price_input($product, $suffix);
    }
    /**
     * Make NYP variations visible.
     *
     * @param bool $visible - whether to display this variation or not
     * @param int  $variation_id
     * @param int  $product_id
     * @param WC_Product_Variation
     *
     * @return boolean
     * @since 1.0.0
     */
    public function variation_is_visible($visible, $variation_id, $product_id, $variation)
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($variation)) {
            $visible = \true;
        }
        return $visible;
    }
    /**
     * Add cpw data to json encoded variation form.
     *
     * @param array  $data - this is the variation's json data
     * @param object $product
     * @param object $variation
     *
     * @return array
     * @since 1.0.0
     */
    public function available_variation($data, $product, $variation)
    {
        $is_cpw = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($variation);
        $cpw_data = ['is_cpw' => $is_cpw];
        if ($is_cpw) {
            $cpw_data['minimum_price'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price($variation);
            $cpw_data['maximum_price'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_maximum_price($variation);
            $cpw_data['initial_price'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_initial_price($variation);
            $cpw_data['price_label'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_price_input_label_text($variation);
            $cpw_data['posted_price'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_posted_price($variation);
            $cpw_data['display_price'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_price_value_attr($variation);
            $cpw_data['display_regular_price'] = $cpw_data['display_price'];
            $cpw_data['price_html'] = \apply_filters('woocommerce_show_variation_price', \true, $product, $variation) ? '<span class="price">' . \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suggested_price_html($variation) . '</span>' . '<p>' . \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price_html($variation) . '</p><p>' . \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_maximum_price_html($variation) . '<p>' : '';
            $cpw_data['minimum_price_html'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price_html($variation);
            $cpw_data['hide_minimum'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_minimum_hidden($variation);
            $cpw_data['hide_maximum'] = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_maximum_hidden($variation);
            $cpw_data['add_to_cart_text'] = $variation->single_add_to_cart_text();
        }
        return \array_merge($data, $cpw_data);
    }
    /**
     * Get the NYP min price of the lowest-priced variation.
     *
     * @param string $price
     * @param string $min_or_max - min or max
     * @param bool   $display    Whether the value is going to be displayed
     *
     * @return string
     * @since 1.0.0
     */
    public function get_variation_price($price, $product, $min_or_max, $display)
    {
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::has_cpw($product) && 'min' === $min_or_max) {
            $prices = $product->get_variation_prices();
            if (\is_array($prices) && isset($prices['price'])) {
                // Get the ID of the variation with the minimum price.
                \reset($prices['price']);
                $min_id = \key($prices['price']);
                // If the minimum variation is an NYP variation then get the minimum price. This lets you distinguish between 0 and null.
                if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($min_id)) {
                    $price = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price($min_id);
                }
            }
        }
        return $price;
    }
    /**
     * ---------------------------------------------------------------------------------
     * Variable Billing Period Display Functions
     * ---------------------------------------------------------------------------------
     */
    /**
     * Display the period options for variable billint periods.
     *
     * @param mixed obj|int $product
     * @param string $suffix - suffix is key to integration with Bundles
     *
     * @since 1.0.0
     */
    public function display_variable_billing_periods($product, $suffix)
    {
        $product = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::maybe_get_product_instance($product);
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($product)) {
            // Create the dropdown select element.
            $period = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_period_value_attr($product, $suffix);
            // The pre-selected value.
            $selected = $period ? $period : 'month';
            // Get list of available periods from Subscriptions plugin.
            $periods = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_subscription_period_strings();
            if ($periods) {
                \printf('<span class="cpw-billing-period"><span class="per"> / </span><select id="cpw-period%s" name="%s" class="cpw-period">', \intval(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_counter()), \esc_attr('cpw-period' . $suffix));
                foreach ($periods as $i => $period) {
                    \printf('<option value="%s" %s>%s</option>', \esc_attr($i), \selected($i, $selected, \false), \esc_html($period));
                }
                echo '</select></span>';
                /**
                 * Filter wc_cpw_subscription_period_input
                 *
                 * @param string $period_input - the resulting input's html.
                 * @param        $product      - the product object.
                 * @param string $suffix       - needed for grouped, composites, bundles, etc.
                 *
                 * @return string
                 * @deprecated 3.0.0
                 */
                if (\has_filter('wc_cpw_subscription_period_input')) {
                    \wc_doing_it_wrong(__FUNCTION__, 'woocommerce_get_price_input filter has been removed for security reasons!', '3.0');
                }
            }
        }
    }
    /**
     * ---------------------------------------------------------------------------------
     * Cart Display Functions
     * ---------------------------------------------------------------------------------
     */
    /**
     * Add edit link to cart items.
     *
     * @param string $content
     * @param array  $cart_item
     * @param string $cart_item_key
     *
     * @return string
     */
    public function add_edit_link_in_cart($content, $cart_item, $cart_item_key)
    {
        if (isset($cart_item['cpw']) && \apply_filters('wc_cpw_show_edit_link_in_cart', \true, $cart_item, $cart_item_key)) {
            if (\function_exists('is_cart') && \is_cart() && !$this->is_cart_widget()) {
                $cpw_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];
                $_product = $cart_item['data'];
                $suffix = \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_suffix($cpw_id);
                $edit_in_cart_link = \add_query_arg(['update-price' => $cart_item_key, 'cpw' . $suffix => \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::format_price($cart_item['cpw'], ['thousand_separator' => '']), '_cpwnonce' => \wp_create_nonce('cpw-nonce'), 'quantity' => $cart_item['quantity']], $_product->get_permalink());
                if (isset($cart_item['cpw_period'])) {
                    $edit_in_cart_link = \add_query_arg('cpw-period' . $suffix, $cart_item['cpw_period'], $edit_in_cart_link);
                }
                $edit_in_cart_text = \_x('Edit', 'edit in cart link text', 'custom-price-for-woocommerce');
                // Translators: %1$s Original cart price string. %2$s URL for edit price link. %3$s text for edit price link.
                $content = \sprintf(\_x('%1$s<br/><a class="edit_price_in_cart_text edit_in_cart_text" href="%2$s"><small>%3$s</small></a>', 'edit in cart text', 'custom-price-for-woocommerce'), $content, \esc_url($edit_in_cart_link), $edit_in_cart_text);
            }
        }
        return $content;
    }
    /**
     * Rendering cart widget?
     *
     * @return boolean
     * @since 1.4.0
     */
    public function is_cart_widget()
    {
        return \did_action('woocommerce_before_mini_cart') > \did_action('woocommerce_after_mini_cart');
    }
}
