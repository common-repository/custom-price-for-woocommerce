<?php

/**
 * Static helper functions for interacting with products
 *
 * @class   Helper
 * @package WooCommerce Custom Price/Classes
 * @since   1.0.0
 * @version 3.3.5
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice;

use WC_Product;
use WC_Subscriptions_Product;
/**
 * Helper class.
 */
class Helper
{
    /**
     * Supported product types.
     * The cpw product type is how the ajax add to cart functionality is disabled in old version of WC.
     *
     * @var array
     */
    private static $simple_supported_types = ['simple', 'subscription', 'bundle', 'composite', 'variation', 'subscription_variation', 'deposit', 'mix-and-match'];
    /**
     * Count the number of instance of an NYP input on a given page.
     *
     * @var int
     */
    private static $counter = 1;
    /**
     * Supported variable product types.
     *
     * @var array
     */
    private static $variable_supported_types = ['variable', 'variable-subscription'];
    /**
     * Get supported "simple" types.
     *
     * @return  array
     * @since 2.7.0
     */
    public static function get_simple_supported_types()
    {
        return \apply_filters('wc_cpw_simple_supported_types', self::$simple_supported_types);
    }
    /**
     * Get supported "variable" types.
     *
     * @return  array
     * @since 2.7.0
     */
    public static function get_variable_supported_types()
    {
        return \apply_filters('wc_cpw_variable_supported_types', self::$variable_supported_types);
    }
    /**
     * @return array|string[]
     */
    public static function get_custom_attributes() : array
    {
        $custom_attributes = [];
        if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            $custom_attributes = ['disabled' => 'disabled'];
        }
        return $custom_attributes;
    }
    /**
     * @return string
     */
    public static function get_wrapper_class() : string
    {
        $wrapper_class = '';
        if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
            $wrapper_class = 'form-row-disabled';
        }
        return $wrapper_class;
    }
    /**
     * Verify this is has custom price
     *
     * @param mixed int|obj $product
     *
     * @return  boolean
     * @since 1.0
     */
    public static function is_cpw($product) : bool
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $is_cpw = $product->is_type(self::get_simple_supported_types()) && \wc_string_to_bool($product->get_meta('_cpw')) ? \true : \false;
        return (bool) \apply_filters('wc_cpw_is_cpw', $is_cpw, $product->get_id(), $product);
    }
    /**
     * Get the suggested price.
     *
     * @param mixed obj|int $product
     *
     * @return  mixed number|FALSE
     * @since 1.0.0
     */
    public static function get_suggested_price($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $suggested = $product->get_meta('_suggested_price', \true, 'edit');
        if (!\is_numeric($suggested)) {
            $suggested = \false;
        }
        // Filter the raw suggested price @since 1.2.
        return \apply_filters('wc_cpw_raw_suggested_price', $suggested, $product->get_id(), $product);
    }
    /**
     * Get the minimum price.
     *
     * @param mixed obj|int $product
     *
     * @return  mixed string|bool
     * @since 1.0.0
     */
    public static function get_minimum_price($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $minimum = $product->get_meta('_min_price', \true, 'edit');
        if (!\is_numeric($minimum)) {
            $minimum = \false;
        }
        // Filter the raw minimum price @since 1.2.
        return \apply_filters('wc_cpw_raw_minimum_price', $minimum, $product->get_id(), $product);
    }
    /**
     * Get the maximum price.
     *
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 2.8.0
     */
    public static function get_maximum_price($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $maximum = $product->get_meta('_maximum_price', \true, 'edit');
        if (!\is_numeric($maximum)) {
            $maximum = \false;
        }
        // Filter the raw maximum price @since 2.8.0.
        return \apply_filters('wc_cpw_raw_maximum_price', $maximum, $product->get_id(), $product);
    }
    /**
     * Get the minimum price for a variable product.
     *
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 2.3
     */
    public static function get_minimum_variation_price($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $minimum = $product->get_variation_price('min');
        // Filter the raw minimum price @since 1.2.
        return \apply_filters('wc_cpw_raw_minimum_variation_price', $minimum, $product->get_id(), $product);
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  boolean returns true for subscription, variable-subscription and subscription_variation
     * @since 1.0.0
     */
    public static function is_subscription($product)
    {
        return \class_exists('\WC_Subscriptions_Product') && \WC_Subscriptions_Product::is_subscription($product);
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 1.0.0
     */
    public static function is_billing_period_variable($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $variable = $product->is_type('subscription') && \wc_string_to_bool($product->get_meta('_variable_billing')) ? \true : \false;
        return \apply_filters('wc_cpw_is_billing_period_variable', $variable, $product->get_id());
    }
    /**
     * @param mixed obj|int $product.
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_suggested_billing_period($product)
    {
        $product = self::maybe_get_product_instance($product);
        $period = $product->get_meta('_suggested_billing_period');
        // Set month as the default billing period.
        if (!$period) {
            $period = 'month';
        }
        // Filter the raw minimum price @since 1.2.
        return \apply_filters('wc_cpw_suggested_billing_period', $period, $product->get_id());
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_minimum_billing_period($product)
    {
        $product = self::maybe_get_product_instance($product);
        $period = $product->get_meta('_minimum_billing_period');
        // Set month as the default billing period.
        if (!$period) {
            $period = 'month';
        }
        // Filter the raw minimum price @since 1.2.
        return \apply_filters('wc_cpw_minimum_billing_period', $period, $product->get_id());
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  boolean
     * @since 1.0.0
     */
    public static function has_cpw($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $has_cpw = $product->is_type(self::get_variable_supported_types()) && \wc_string_to_bool($product->get_meta('_has_cpw', \true, 'edit')) ? \true : \false;
        return \apply_filters('wc_cpw_has_cpw_variations', $has_cpw, $product);
    }
    /**
     * @param mixed int|obj $product
     *
     * @return  boolean
     * @since 1.0.0.0
     */
    public static function is_minimum_hidden($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $is_hidden = $product && \wc_string_to_bool($product->get_meta('_hide_cpw_minimum')) ? \true : \false;
        return \apply_filters('wc_cpw_is_minimum_hidden', $is_hidden, $product->get_id(), $product);
    }
    /**
     * @param mixed int|obj $product
     *
     * @return  boolean
     * @since 1.0.0.0
     */
    public static function is_maximum_hidden($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $is_hidden = $product && \wc_string_to_bool($product->get_meta('_hide_cpw_maximum')) ? \true : \false;
        return \apply_filters('wc_cpw_is_maximum_hidden', $is_hidden, $product->get_id(), $product);
    }
    /**
     * @param mixed int|obj $product
     *
     * @return  boolean
     * @since 1.0.0
     */
    public static function is_variable_price_hidden($product)
    {
        $product = self::maybe_get_product_instance($product);
        if (!$product) {
            return \false;
        }
        $is_hidden = $product && $product->get_meta('_cpw_hide_variable_price') === 'yes' ? \true : \false;
        return \apply_filters('wc_cpw_is_variable_price_hidden', $is_hidden, $product->get_id(), $product);
    }
    /**
     * @return  string
     * @since 1.2.2
     */
    public static function standardize_number($value)
    {
        $value = \trim(\str_replace(\wc_get_price_thousand_separator(), '', \stripslashes($value)));
        return \wc_format_decimal($value);
    }
    /**
     * @return  string
     * @since 1.0.0
     */
    public static function annualize_price($price = \false, $period = null)
    {
        $factors = self::annual_price_factors();
        if (isset($factors[$period]) && $price) {
            $price = $factors[$period] * self::standardize_number($price);
        }
        return \wc_format_decimal($price);
    }
    /**
     * @return  array
     * @since 1.0.0
     */
    public static function annual_price_factors()
    {
        return \array_map('esc_attr', \apply_filters('wc_cpw_annual_factors', ['day' => 365, 'week' => 52, 'month' => 12, 'year' => 1]));
    }
    /**
     * @return  $price string
     * @since   1.0.0
     */
    public static function get_minimum_price_html($product)
    {
        $product = self::maybe_get_product_instance($product);
        // Start the price string.
        $html = '';
        // If not cpw quit early.
        if (!self::is_cpw($product)) {
            return $html;
        }
        // Get the minimum price.
        $minimum = self::get_minimum_price($product);
        if (\false !== $minimum && !self::is_minimum_hidden($product)) {
            // Default minimum text.
            $default_text = \_x('Minimum price: %price%', 'Custom price default minimum text', 'custom-price-for-woocommerce');
            // Get the minimum text option.
            $minimum_text = \stripslashes(\get_option('woocommerce_cpw_minimum_text', $default_text));
            if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
                $minimum_text = $default_text;
            }
            // Replace placeholders.
            $html = \str_replace('%price%', \wc_price($minimum), $minimum_text);
        }
        return \apply_filters('wc_cpw_minimum_price_html', $html, $product);
    }
    /**
     * @return  $price string
     * @since   1.0.0
     */
    public static function get_maximum_price_html($product)
    {
        $product = self::maybe_get_product_instance($product);
        // Start the price string.
        $html = '';
        // If not cpw quit early.
        if (!self::is_cpw($product)) {
            return $html;
        }
        // Get the maximum price.
        $maximum = self::get_maximum_price($product);
        if (\false !== $maximum && !self::is_maximum_hidden($product)) {
            // Default maximum text.
            $default_text = \_x('Maximum price: %price%', 'Custom price default maximum text', 'custom-price-for-woocommerce');
            // Get the maximum text option.
            $maximum_text = \stripslashes(\get_option('woocommerce_cpw_maximum_text', $default_text));
            if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
                $maximum_text = $default_text;
            }
            // Replace placeholders.
            $html = \str_replace('%price%', \wc_price($maximum), $maximum_text);
        }
        return \apply_filters('wc_cpw_maximum_price_html', $html, $product);
    }
    /**
     * @return  string
     * @since 1.0.0
     */
    public static function get_suggested_price_html($product)
    {
        $product = self::maybe_get_product_instance($product);
        // Start the price string.
        $html = '';
        // If not cpw quit early.
        if (!self::is_cpw($product)) {
            return $html;
        }
        // Get suggested price.
        $suggested = self::get_suggested_price($product);
        if (\false !== $suggested) {
            // Default suggested text.
            $default_text = \_x('Suggested price: %price%', 'Custom price default suggested text', 'custom-price-for-woocommerce');
            // Get the suggested text option.
            $suggested_text = \stripslashes(\get_option('woocommerce_cpw_suggested_text', $default_text));
            if (!\CPWFreeVendor\WPDesk\Library\CustomPrice\Integration::is_super()) {
                $suggested_text = $default_text;
            }
            // Replace placeholders.
            $formatted_text = \str_replace('%price%', \wc_price($suggested), $suggested_text);
            // Put it all together.
            $html .= \sprintf('<span class="suggested-text">%s</span>', $formatted_text);
        }
        return \apply_filters('wc_cpw_suggested_price_html', $html, $product);
    }
    /**
     * @return  string
     * @since 1.0.01.0
     */
    public static function get_price_input_label_text($product)
    {
        $product = self::maybe_get_product_instance($product);
        // Start the string.
        $text = '';
        // If not cpw quit early.
        if (!self::is_cpw($product)) {
            return $text;
        }
        $currency_symbol = \get_woocommerce_currency_symbol();
        // For subscriptions add the billing period.
        if (self::is_subscription($product) && !self::is_billing_period_variable($product)) {
            $include = ['price' => \get_woocommerce_currency_symbol(), 'tax_calculation' => \false, 'subscription_price' => \true, 'subscription_period' => \true, 'subscription_length' => \false, 'sign_up_fee' => \false, 'trial_length' => \false];
            $currency_symbol = \WC_Subscriptions_Product::get_price_string($product, $include);
        }
        $field_value = $product->get_meta('_price_label', \true);
        $option_value = \get_option('woocommerce_cpw_label_text', \esc_html__('Custom price', 'custom-price-for-woocommerce'));
        // Translators: %1$s is the currency symbol and %2$s is the currency symbol.
        $text = \sprintf(
            // Translators: %1$s is the label text and %2$s is the currency symbol.
            \_x('%1$s <span class="woocommerce-Price-currencySymbol">( %2$s )</span>', 'Custom price input label', 'custom-price-for-woocommerce'),
            \esc_html(!empty($field_value) ? $field_value : $option_value),
            $currency_symbol
        );
        return \apply_filters('wc_cpw_price_input_label_text', $text, $product);
    }
    /**
     * @param mixed obj|int $product
     * @param string $type              ( minimum or suggested )
     * @param bool   $show_null_as_zero in the admin you may wish to have a null string display as $0.00
     * @param bool   $show_raw_price    (optional) uses the wc_price() if set to false
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_price_string($product, $type = 'suggested', $show_null_as_zero = \false, $show_raw_price = \false)
    {
        // Start the price string.
        $html = '';
        $product = self::maybe_get_product_instance($product);
        // Minimum or suggested price.
        switch ($type) {
            case 'minimum-variation':
                $price = self::get_minimum_variation_price($product);
                break;
            case 'minimum':
                $price = self::get_minimum_price($product);
                break;
            default:
                $price = self::get_suggested_price($product);
                break;
        }
        if ($show_null_as_zero || '' !== $price) {
            $price = $show_raw_price ? $price : \wc_price($price);
            // Set the billing period to either suggested or minimum.
            if (self::is_subscription($product) && self::is_billing_period_variable($product)) {
                // Minimum or suggested period.
                $period = 'minimum' === $type ? self::get_minimum_billing_period($product) : self::get_suggested_billing_period($product);
                $product->update_meta_data('_subscription_period', $period);
            }
            // Get subscription price string.
            // If you filter woocommerce_get_price_html you end up doubling the period $99 / month / week.
            // As Subs add the string after the woocommerce_get_price_html filter has run.
            if (self::is_subscription($product) && 'woocommerce_get_price_html' !== \current_filter()) {
                $include = ['price' => $price, 'subscription_length' => \false, 'sign_up_fee' => \false, 'trial_length' => \false];
                $html = \WC_Subscriptions_Product::get_price_string($product, $include);
                // Non-subscription products.
            } else {
                $html = $price;
            }
        }
        return \apply_filters('wc_cpw_price_string', $html, $product, $price);
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_price_value_attr($product, $suffix = \false)
    {
        $product = self::maybe_get_product_instance($product);
        $posted = self::get_posted_price($product, $suffix);
        if ('' !== $posted) {
            $price = $posted;
        } else {
            $price = self::get_initial_price($product);
        }
        return $price;
    }
    /**
     * Get Posted Price.
     *
     * @param mixed obj|int $product
     * @param string $suffix - needed for composites and bundles
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_posted_price($product = \false, $suffix = \false)
    {
        // phpcs:disable WordPress.Security.NonceVerification
        $product = self::maybe_get_product_instance($product);
        // The $product is now useless, so we can deprecate that in the future? // Leave in Filter.
        $posted_price = isset($_REQUEST['cpw' . $suffix]) ? self::standardize_number(\sanitize_text_field(\wp_unslash($_REQUEST['cpw' . $suffix]))) : '';
        return \apply_filters('wc_cpw_get_posted_price', $posted_price, $product, $suffix);
    }
    /**
     * Get Initial Price
     * As of 3.0 this is now null by default for accessibility reasons.
     *
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_initial_price($product)
    {
        $product = self::maybe_get_product_instance($product);
        return \apply_filters('wc_cpw_get_initial_price', '', $product);
    }
    /**
     * Get Period Value Attribute.
     *
     * @param mixed int|object $product
     *
     * @return  string
     * @since 2.7.0
     */
    public static function get_period_value_attr($product, $suffix = \false)
    {
        $product = self::maybe_get_product_instance($product);
        $posted = self::get_posted_period($product, $suffix);
        if ('' !== $posted) {
            $price = $posted;
        } else {
            $price = self::get_initial_period($product);
        }
        return $price;
    }
    /**
     * @param string $product - not needed
     * @param string $suffix  - needed for composites and bundles
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_posted_period($product = \false, $suffix = \false)
    {
        // phpcs:disable WordPress.Security.NonceVerification
        $product = self::maybe_get_product_instance($product);
        // The $product is now useless, so we can deprecate that in the future?
        $posted_period = isset($_REQUEST['cpw-period' . $suffix]) && \array_key_exists(\sanitize_key($_REQUEST['cpw-period' . $suffix]), self::get_subscription_period_strings()) ? \sanitize_key($_REQUEST['cpw-period' . $suffix]) : '';
        return \apply_filters('wc_cpw_get_posted_period', $posted_period, $product, $suffix);
    }
    /**
     * @param mixed obj|int $product
     * @param string $suffix - needed for composites and bundles
     *
     * @return  string
     * @since 2.7.0
     */
    public static function get_initial_period($product)
    {
        $product = self::maybe_get_product_instance($product);
        $suggested_period = self::get_suggested_billing_period($product);
        $minimum_period = self::get_minimum_billing_period($product);
        // Go through a few options to find the $period we should display.
        if ($suggested_period) {
            $period = $suggested_period;
        } elseif ($minimum_period) {
            $period = $minimum_period;
        } else {
            $period = 'month';
        }
        return $period;
    }
    /**
     * @param mixed obj|int $product
     * @param string $suffix - needed for composites and bundles
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_price_input($product, $suffix = \false)
    {
        \wc_deprecated_function('Helper::get_price_input()', '3.0.0', 'Input HTML is displayed directly in the price-input.php template where it can be safely escaped.');
        $product = self::maybe_get_product_instance($product);
        $price = self::get_price_value_attr($product, $suffix);
        $counter = self::get_counter();
        $attributes = ['id' => 'cpw-' . $counter, 'name' => 'cpw' . $suffix, 'type' => 'text', 'value' => self::format_price($price), 'title' => self::get_price_input_label_text($product), 'class' => ['input-text', 'amount', 'cpw-input', 'text'], 'aria-describedby' => ['cpw-minimum-price-' . $counter, 'cpw-error-' . $counter]];
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
        $attributes = \apply_filters('wc_cpw_price_input_attributes', $attributes, $product, $suffix);
        $input = '';
        // Build the input element.
        foreach ($attributes as $key => $attribute) {
            $attribute = \is_array($attribute) ? \implode(' ', $attribute) : $attribute;
            $input .= \sprintf('%s="%s" ', \esc_attr($key), \esc_attr($attribute));
        }
        $input = \sprintf('<input %s/>', $input);
        // Prepend label.
        $label_text = self::get_price_input_label_text($product);
        if ($label_text || self::has_cpw($product)) {
            $label_html = '<label id="cpw-label-' . self::get_counter() . '" for="' . $attributes['id'] . '">' . $label_text . '</label>';
            $input = $label_html . $input;
        }
        // Append hidden input for updating price.
        if (isset($_GET['update-price'])) {
            $updating_cart_key = \wc_clean(\wp_unslash($_GET['update-price']));
            if (isset(\WC()->cart->cart_contents[$updating_cart_key])) {
                $input .= '<input type="hidden" name="update-price" value="' . $updating_cart_key . '" />';
            }
        }
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
        return $input;
    }
    /**
     * @param string $input
     * @param mixed obj|int $product
     * @param string $suffix - needed for composites and bundles
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_subscription_period_input($input, $product, $suffix)
    {
        // Get product object.
        $product = self::maybe_get_product_instance($product);
        // Create the dropdown select element.
        $period = self::get_period_value_attr($product, $suffix);
        // The pre-selected value.
        $selected = $period ? $period : 'month';
        // Get list of available periods from Subscriptions plugin.
        $periods = self::get_subscription_period_strings();
        if ($periods) {
            $period_input = \sprintf('<span class="per">/ </span><select id="cpw-period%s" name="cpw-period%s" class="cpw-period">', self::get_counter(), $suffix);
            foreach ($periods as $i => $period) {
                $period_input .= \sprintf('<option value="%s" %s>%s</option>', $i, \selected($i, $selected, \false), $period);
            }
            $period_input .= '</select>';
            $period_input = '<span class="cpw-billing-period"> ' . $period_input . '</span>';
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
                \wc_doing_it_wrong(__FUNCTION__, 'wc_cpw_subscription_period_input filter has been removed for security reasons!', '3.0');
            }
            $input .= $period_input;
        }
        return $input;
    }
    /**
     * @param float $price              Raw price.
     * @param array $args               @since 1.0.0 - Arguments to format a price {
     *                                  Array of arguments.
     *                                  Defaults to empty array.
     *
     * @type string $decimal_separator  Decimal separator.
     *                                      Defaults the result of wc_get_price_decimal_separator().
     * @type string $thousand_separator Thousand separator.
     *                                      Defaults the result of wc_get_price_thousand_separator().
     * @type string $decimals           Number of decimals.
     *                                      Defaults the result of wc_get_price_decimals().
     * }
     * @return string
     * @since 1.0.0
     */
    public static function format_price($price, $args = [])
    {
        $args = \apply_filters('wc_price_args', \wp_parse_args($args, ['decimal_separator' => \wc_get_price_decimal_separator(), 'thousand_separator' => \wc_get_price_thousand_separator(), 'decimals' => \wc_get_price_decimals()]));
        if ('' !== $price) {
            $price = \apply_filters('raw_woocommerce_price', \floatval($price));
            $price = \apply_filters('formatted_woocommerce_price', \number_format($price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator']), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator']);
            if (\apply_filters('woocommerce_price_trim_zeros', \false) && $args['decimals'] > 0) {
                $price = \wc_trim_zeros($price);
            }
        }
        return $price;
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 1.0
     */
    public static function get_price_terms_html($product)
    {
        // Get product object.
        $product = self::maybe_get_product_instance($product);
        $minimum_price_html = self::get_minimum_price_html($product);
        $subscription_terms = self::get_subscription_terms_html($product);
        if ($minimum_price_html && $subscription_terms) {
            // Translators: %1$s is minimum price html. %2$s subscription terms.
            $terms = \sprintf(\__('%1$s %2$s', 'custom-price-for-woocommerce'), $minimum_price_html, $subscription_terms);
        } elseif ($minimum_price_html) {
            $terms = $minimum_price_html;
        } else {
            $terms = $subscription_terms;
        }
        return $terms;
    }
    /**
     * @param mixed obj|int $product
     *
     * @return  string
     * @since 1.0
     */
    public static function get_subscription_terms_html($product)
    {
        // Get product object.
        $product = self::maybe_get_product_instance($product);
        $terms = '';
        // Parent variable subscriptions don't have a billing period, so we get a array to string notice. therefore only apply to simple subs and sub variations.
        if ($product->is_type('subscription') || $product->is_type('subscription_variation')) {
            $minimum = self::get_minimum_price($product);
            $includes = [];
            $billing_interval = \intval(\WC_Subscriptions_Product::get_interval($product));
            $subscription_length = \intval(\WC_Subscriptions_Product::get_length($product));
            if (1 === $billing_interval && $billing_interval !== $subscription_length) {
                if (self::is_billing_period_variable($product)) {
                    $period = self::get_minimum_billing_period($product);
                } else {
                    $period = \WC_Subscriptions_Product::get_period($product);
                }
                // Get translated period.
                $period = wcs_get_subscription_period_strings($billing_interval, $period);
                if ($minimum && !self::is_minimum_hidden($product)) {
                    // Translators: %1$s is null string because minimum price is displayed elsewhere. %2$s is minimum billing period.
                    $price_string = \sprintf(\__('%1$s every %2$s', 'custom-price-for-woocommerce'), '', $period);
                } else {
                    // Translators: %s is minimum billing period.
                    $price_string = \sprintf(\__('Due every %s', 'custom-price-for-woocommerce'), $period);
                }
                $includes['subscription_period'] = \false;
            } else {
                if ($minimum && !self::is_minimum_hidden($product)) {
                    $price_string = '';
                } else {
                    $price_string = \__('Due', 'custom-price-for-woocommerce');
                }
            }
            $includes['price'] = $price_string;
            $terms = \WC_Subscriptions_Product::get_price_string($product, $includes);
        }
        return \apply_filters('wc_cpw_subscriptions_terms_html', $terms, $product);
    }
    /**
     * @param string $input
     * @param mixed obj|int $product
     *
     * @return  string
     * @deprecated 3.0.0
     * @since      2.0.0
     */
    public static function get_subscription_terms($input = '', $product = \false)
    {
        \wc_deprecated_function('get_subscription_terms', '3.0', 'Replaced by get_subscription_terms_html()');
        $terms = '&nbsp;';
        // Get product object.
        $product = self::maybe_get_product_instance($product);
        // Parent variable subscriptions don't have a billing period, so we get a array to string notice. therefore only apply to simple subs and sub variations.
        if ($product && $product->is_type('subscription') || $product->is_type('subscription_variation')) {
            if (self::is_billing_period_variable($product)) {
                // Don't display the subscription price, period or length.
                $include = ['price' => '', 'subscription_price' => \false, 'subscription_period' => \false];
            } else {
                $include = ['price' => '', 'subscription_price' => \false];
                // If we don't show the price we don't get the "per" backslash so add it back.
                if (\WC_Subscriptions_Product::get_interval($product) === 1) {
                    $terms .= '<span class="per">/ </span>';
                }
            }
            $terms .= \WC_Subscriptions_Product::get_price_string($product, $include);
        }
        // Piece it all together - JS needs a span with this class to change terms on variation found event.
        // Use details class to mimic Subscriptions plugin, leave terms class for backcompat.
        if ('wc_cpw_get_price_input' === \current_filter()) {
            $terms = '<span class="subscription-details subscription-terms">' . $terms . '</span>';
        }
        return $input . $terms;
    }
    /**
     * @param mixed obj|int $product
     * @param string $suffix - needed for composites and bundles
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_data_attributes($product, $suffix = null)
    {
        // Get product object.
        $product = self::maybe_get_product_instance($product);
        $price = (float) self::get_price_value_attr($product, $suffix);
        $minimum = self::get_minimum_price($product);
        $attributes = ['minimum-error' => self::error_message('minimum_js'), 'hide-minimum' => self::is_minimum_hidden($product), 'hide-minimum-error' => self::error_message('hide_minimum_js'), 'max-price' => self::get_maximum_price($product), 'maximum-error' => self::error_message('maximum_js'), 'empty-error' => self::error_message('empty'), 'initial-price' => self::get_initial_price($product)];
        if (self::is_subscription($product) && self::is_billing_period_variable($product)) {
            $period = self::get_period_value_attr($product, $suffix);
            $minimum_period = self::get_minimum_billing_period($product);
            $annualized_minimum = self::annualize_price($minimum, $minimum_period);
            $attributes['period'] = \esc_attr($period) ? \esc_attr($period) : 'month';
            $attributes['annual-minimum'] = $annualized_minimum > 0 ? (float) $annualized_minimum : 0;
        } else {
            $attributes['min-price'] = $minimum && $minimum > 0 ? (float) $minimum : 0;
        }
        /**
         * Filter wc_cpw_data_attributes
         *
         * @param array  $attributes The array of attributes for the NYP div
         * @param        $product    WC_Product The product object
         * @param string $suffix     - needed for grouped, composites, bundles, etc.
         *
         * @return string
         * @since 1.0.0
         */
        $attributes = \apply_filters('wc_cpw_data_attributes', $attributes, $product, $suffix);
        $data_string = '';
        foreach ($attributes as $key => $attribute) {
            $data_string .= \sprintf('data-%s="%s" ', \esc_attr($key), \esc_attr($attribute));
        }
        return $data_string;
    }
    /**
     * @param string $id      - selects which message to use
     * @param string $context - optionally varies the template $id by the validation context.
     *
     * @return  string
     * @since 1.0.0
     */
    public static function get_error_message_template($id = null, $context = '')
    {
        $errors = \apply_filters('wc_cpw_error_message_templates', ['invalid-product' => \__('This is not a valid product.', 'custom-price-for-woocommerce'), 'invalid' => \__('&quot;%%TITLE%%&quot; could not be added to the cart. Please enter a valid, positive number.', 'custom-price-for-woocommerce'), 'minimum' => \__('&quot;%%TITLE%%&quot; could not be added to the cart. Please enter at least %%MINIMUM%%.', 'custom-price-for-woocommerce'), 'hide_minimum' => \__('&quot;%%TITLE%%&quot; could not be added to the cart. Please enter a higher amount.', 'custom-price-for-woocommerce'), 'minimum_js' => \__('Please enter at least %%MINIMUM%%.', 'custom-price-for-woocommerce'), 'hide_minimum_js' => \__('Please enter a higher amount.', 'custom-price-for-woocommerce'), 'maximum' => \__('&quot;%%TITLE%%&quot; could not be added to the cart. Please enter less than or equal to %%MAXIMUM%%.', 'custom-price-for-woocommerce'), 'maximum_js' => \__('Please enter less than or equal to %%MAXIMUM%%.', 'custom-price-for-woocommerce'), 'empty' => \__('Please enter an amount.', 'custom-price-for-woocommerce'), 'minimum-cart' => \__('&quot;%%TITLE%%&quot; cannot be purchased. Please enter at least %%MINIMUM%%.', 'custom-price-for-woocommerce'), 'maximum-cart' => \__('&quot;%%TITLE%%&quot; cannot be purchased. Please enter less than or equal to %%MAXIMUM%%.', 'custom-price-for-woocommerce')]);
        if (isset($errors[$id . '-' . $context])) {
            $template = $errors[$id . '-' . $context];
        } elseif (isset($errors[$id])) {
            $template = $errors[$id];
        } else {
            $template = '';
        }
        return $template;
    }
    /**
     * @param string $id      - the error template to use
     * @param array  $tags    - array of tags and their respective replacement values
     * @param        $product - the relevant product object
     * @param string $context - the validation context
     *
     * @return  string
     * @since 1.0.0
     */
    public static function error_message($id, $tags = [], $product = null, $context = '')
    {
        $message = self::get_error_message_template($id, $context);
        foreach ($tags as $tag => $value) {
            $message = \str_replace($tag, $value, $message);
        }
        return \apply_filters('wc_cpw_error_message', $message, $id, $tags, $product);
    }
    /**
     * @since 1.0.0
     */
    public static function get_subscription_period_strings($number = 1, $period = '')
    {
        if (\function_exists('CPWFreeVendor\\wcs_get_subscription_period_strings')) {
            $strings = wcs_get_subscription_period_strings($number, $period);
        } else {
            $strings = \CPWFreeVendor\WPDesk\Library\CustomPrice\WC_Subscriptions_Manager::get_subscription_period_strings($number, $period);
        }
        return \apply_filters('wc_cpw_subscription_strings', $strings, $number, $period);
    }
    /**
     * @param int|\WC_Product $product_id A WC_Product object or product ID
     *
     * @return \WC_Product
     * @since 1.0.0
     */
    public static function maybe_get_product_instance($product)
    {
        if (!\is_object($product) || !\is_a($product, 'WC_Product')) {
            $product = \wc_get_product($product);
        }
        return $product;
    }
    /**
     * @return int
     * @since 1.0.0
     */
    public static function get_counter()
    {
        return self::$counter;
    }
    /**
     * @return int
     * @since 1.0.0
     */
    public static function increase_counter()
    {
        self::$counter++;
    }
    /**
     * @param int $cpw_id - A product|variation ID.
     *
     * @return string
     * @since 1.0.0
     */
    public static function get_suffix($cpw_id)
    {
        return \apply_filters('wc_cpw_field_suffix', '', $cpw_id);
    }
}
