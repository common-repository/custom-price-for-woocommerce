<?php

namespace CPWFreeVendor;

/**
 * Status Report data for NYP.
 *
 * @package  WooCommerce Custom Price
 * @since  3.0.0
 */
// Exit if accessed directly.
if (!\defined('ABSPATH')) {
    exit;
}
?><table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Custom Price"><h2><?php 
\esc_html_e('Custom Price', 'custom-price-for-woocommerce');
?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Version" colspan="2"><?php 
\esc_html_e('Version', 'custom-price-for-woocommerce');
?>:</td>
			<td id="name-your-price-version" colspan="3">1.0.0</td>
		</tr>
		<tr>
			<td data-export-label="Template overrides"><?php 
\esc_html_e('Template overrides', 'custom-price-for-woocommerce');
?>:</td>
			<td class="help"><?php 
echo \wc_help_tip(\esc_html__('Shows any files overriding the default Custom Price templates.', 'custom-price-for-woocommerce'));
?></td>
			<td>
			<?php 
if (!empty($debug_data['overrides'])) {
    $total_overrides = \count($debug_data['overrides']);
    for ($i = 0; $i < $total_overrides; $i++) {
        $override = $debug_data['overrides'][$i];
        if ($override['core_version'] && (empty($override['version']) || \version_compare($override['version'], $override['core_version'], '<'))) {
            $current_version = $override['version'] ? $override['version'] : '-';
            \printf(
                /* Translators: %1$s: Template name, %2$s: Template version, %3$s: Core version. */
                \esc_html__('%1$s version %2$s (out of date)', 'custom-price-for-woocommerce'),
                '<code>' . \esc_html($override['file']) . '</code>',
                '<strong style="color:red">' . \esc_html($current_version) . '</strong>',
                \esc_html($override['core_version'])
            );
        } else {
            echo '<code>' . \esc_html($override['file']) . '</code>';
        }
        if (\count($debug_data['overrides']) - 1 !== $i) {
            echo ', ';
        }
        echo '<br />';
    }
} else {
    ?>
					&ndash;
					<?php 
}
?>
			</td>
		</tr>
	</tbody>
</table>
<?php 
