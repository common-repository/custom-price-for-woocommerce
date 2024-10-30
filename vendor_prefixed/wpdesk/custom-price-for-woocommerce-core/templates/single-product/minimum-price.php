<?php

namespace CPWFreeVendor;

/**
 * Minimum Price Template
 *
 * @package     Templates
 * @version     3.0.0
 */
if (!\defined('ABSPATH')) {
    exit;
    // Exit if accessed directly.
}
?>
<p id="cpw-minimum-price-<?php 
echo \esc_attr($counter);
?>" class="minimum-price cpw-terms">
	<?php 
echo \wp_kses_post(\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::get_minimum_price_html($cpw_product));
?>
</p>

<?php 
