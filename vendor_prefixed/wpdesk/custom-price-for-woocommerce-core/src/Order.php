<?php

/**
 * Interact with WooCommerce orders
 *
 * @class   Order
 * @package WooCommerce Custom Price/Classes
 * @since  2.4.0
 */
namespace CPWFreeVendor\WPDesk\Library\CustomPrice;

use CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Order;
use WC_Order_Item_Product;
/**
 * Order class.
 */
class Order implements \CPWFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable
{
    public function hooks()
    {
        \add_filter('woocommerce_order_again_cart_item_data', [$this, 'order_again_cart_item_data'], 5, 3);
    }
    /**
     * Add cart session data from existing order.
     *
     * @param array $cart_item_data
     * @param WC_Order_Item_Product (supports array notation due to ArrayAccess)
     * @param WC_Order
     *
     * @return array
     * @since 1.0.0
     */
    public function order_again_cart_item_data($cart_item_data, $line_item, $order)
    {
        // Get the product/variation product object of this item.
        $cpw_product = $line_item->get_product();
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_cpw($cpw_product)) {
            $line_price = $line_item->get_subtotal();
            /**
             * If the order prices include tax then we need to add back the tax on this product.
             * Otherwise, the subtotal is the original price entered *minus* tax.
             * See: https://github.com/helgatheviking/woocommerce-name-your-price/issues/91
             */
            if ($cpw_product->is_taxable() && $order->get_prices_include_tax()) {
                /**
                 * If not rounding taxes at the subtotal we need to round the item's subtotal.
                 * We can assume that the original NYP price was entered with the number of price decimals from settings.
                 * Hopefully, we can remove this when https://github.com/woocommerce/woocommerce/issues/24184 is resolved.
                 * A workaround for rounding issues is to enable tax rounding at the subtotal level via settings
                 * see: https://share.getcloudapp.com/4gux6lve
                 */
                if ('yes' !== \get_option('woocommerce_tax_round_at_subtotal')) {
                    $line_price = \wc_format_decimal($line_price, \wc_get_price_decimals());
                }
                // Use the taxes array items here as they contain taxes to a more accurate number of decimals.
                $taxes = $line_item->get_taxes();
                $line_price += \array_sum($taxes['subtotal']);
            }
            $line_price = $line_price / $line_item->get_quantity();
            $cart_item_data['cpw'] = (float) \wc_format_decimal($line_price);
        }
        if (\CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_subscription($cpw_product) && \CPWFreeVendor\WPDesk\Library\CustomPrice\Helper::is_billing_period_variable($cpw_product)) {
            $subscription = $this->find_subscription($line_item, $order);
            if (\is_callable([$subscription, 'get_billing_period'])) {
                $cart_item_data['cpw_period'] = $subscription->get_billing_period();
            }
        }
        return $cart_item_data;
    }
    /**
     * Find the order item's related subscription.
     * Slightly hacky, matches product ID against product ID of subscription.
     * Will fail if multiple variable billing period subs exist in subscription.
     *
     * @since 1.0.0
     */
    public function find_subscription($order_item, $order)
    {
        $order_items_product_id = wcs_get_canonical_product_id($order_item);
        $subscription_for_item = null;
        foreach (wcs_get_subscriptions_for_order($order, ['order_type' => 'parent']) as $subscription) {
            foreach ($subscription->get_items() as $line_item) {
                if (wcs_get_canonical_product_id($line_item) === $order_items_product_id) {
                    $subscription_for_item = $subscription;
                    break 2;
                }
            }
        }
        return $subscription_for_item;
    }
}
