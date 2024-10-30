<?php

namespace CPWFreeVendor;

/**
 * Single Product Price Input
 *
 * @package     Templates
 * @version     3.3.4
 */
if (!\defined('ABSPATH')) {
    exit;
    // Exit if accessed directly.
}
?>
<div
	class="cpw" <?php 
echo \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_data_attributes($cpw_product, $suffix);
?> > <?php 
// phpcs:ignore WordPress.Security.EscapeOutput
?>

	<?php 
\do_action('wc_cpw_before_price_input', $cpw_product, $suffix);
?>
	<p class="cwp-input-wrapper">
		<?php 
\do_action('wc_cpw_before_price_label', $cpw_product, $suffix);
?>
		<label for="<?php 
echo \esc_attr($input_id);
?>"><?php 
echo \wp_kses_post($input_label);
?></label>
		<?php 
\do_action('wc_cpw_after_price_label', $cpw_product, $suffix);
?>
		<input
			type="text"
			id="<?php 
echo \esc_attr($input_id);
?>"
			class="<?php 
echo \esc_attr(\implode(' ', (array) $classes));
?>"
			name="<?php 
echo \esc_attr($input_name);
?>"
			value="<?php 
echo \esc_attr($input_value);
?>"
			title="<?php 
echo \esc_attr(\strip_tags($input_label));
?>"
			placeholder="<?php 
echo \esc_attr($placeholder);
?>"

			<?php 
if (!empty($custom_attributes) && \is_array($custom_attributes)) {
    foreach ($custom_attributes as $key => $value) {
        \printf('%s="%s" ', \esc_attr($key), \esc_attr($value));
    }
}
?>
		/>

		<input type="hidden" name="update-price" value="<?php 
echo \esc_attr($updating_cart_key);
?>"/>
		<input type="hidden" name="_cpwnonce" value="<?php 
echo \esc_attr($_cpwnonce);
?>"/>
	</p>
	<?php 
\do_action('wc_cpw_after_price_input', $cpw_product, $suffix);
?>

</div>


<?php 
