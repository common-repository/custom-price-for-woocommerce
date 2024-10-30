/**
 * Script for editing the prices in the product data metabox.
 *
 * @package WooCommerce Custom Price/Admin/Scripts
 */

( function ( $ ) {

	$.extend(
		{
			moveNYPmetaFields: function () {
				$( '.options_group.show_if_cpw' ).insertAfter( '.options_group.pricing' );
				$( '.options_group.show_if_cpw2' ).insertAfter( '.options_group.pricing' );
			},
			addClasstoRegularPrice: function () {
				$( '.options_group.pricing' ).addClass( 'hide_if_cpw' );
			},
			toggleRegularPriceClass: function ( is_cpw ) {
				if ( is_cpw ) {
					$( '.options_group.pricing' ).removeClass( 'show_if_simple' );
				} else {
					$( '.options_group.pricing' ).addClass( 'show_if_simple' );
				}
			},
			showHideNYPelements: function () {
				var product_type = $( 'select#product-type' ).val();
				var is_cpw = $( '#_cpw' ).prop( 'checked' );
				$.toggleRegularPriceClass( is_cpw );
				var checkbox_cpw = $( '.show_if_cpw2' );

				switch ( true ) {
					case 'subscription' === product_type:
						$.showHideNYPprices( is_cpw, true );
						checkbox_cpw.show();
						$.showHideSugesstedPrice();
						$.enableDisableSubscriptionPrice( is_cpw );
						var is_variable_billing = $( '#_variable_billing' ).prop( 'checked' );
						$.showHideNYPvariablePeriods( is_variable_billing );
						$.enableDisableSubscriptionPeriod( is_cpw && is_variable_billing );
						$.enableDisableSubscriptionLength( is_cpw && is_variable_billing );
						break;
					case 'variable-subscription' === product_type:
						$.showHideNYPprices( false );
						checkbox_cpw.show();
						$.moveNYPvariationFields();
						$.showHideNYPmetaforVariableSubscriptions();
						break;
					case woocommerce_cpw_metabox.simple_types.indexOf( product_type ) > -1:
						$.showHideNYPprices( is_cpw, true );
						checkbox_cpw.show();
						$.showHideSugesstedPrice();
						$.showHideNYPvariablePeriods( false );
						break;
					case woocommerce_cpw_metabox.variable_types.indexOf( product_type ) > -1:
						$.showHideNYPprices( false );
						checkbox_cpw.hide();
						$.showHideSugesstedPrice();
						$.moveNYPvariationFields();
						$.showHideNYPmetaforVariableProducts();
						break;
					default:
						$.showHideNYPprices( false );
						checkbox_cpw.hide();
						break;
				}
			},
			showHideNYPprices: function ( show, restore ) {
				// For simple and sub types we'll want to restore the regular price inputs.
				restore = typeof restore !== 'undefined' ? restore : false;

				if ( show ) {
					$( '.show_if_cpw' ).show();
					$( '.hide_if_cpw' ).hide();
				} else {
					$( '.show_if_cpw' ).hide();
					if ( restore ) {
						$( '.hide_if_cpw' ).show();
					}
				}
			},
			showHideSugesstedPrice: function () {
				var show = $( 'select#_suggested_price_type' ).val();
				if ( show === '0' ) {
					$( '#_suggested_price' ).closest( 'p' ).hide();
				} else {
					$( '#_suggested_price' ).closest( 'p' ).show();
				}
			},
			enableDisableSubscriptionPrice: function ( enable ) {
				if ( enable ) {
					$( '#_subscription_price' ).prop( 'disabled', true ).css( 'background', '#CCC' );
				} else {
					$( '#_subscription_price' ).prop( 'disabled', false ).css( 'background', '#FFF' );
				}
			},
			showHideNYPvariablePeriods: function ( show ) {
				var $variable_periods = $( '._suggested_billing_period_field, ._minimum_billing_period_field' );
				if ( show ) {
					$variable_periods.show();
				} else {
					$variable_periods.hide();
				}
			},
			enableDisableSubscriptionPeriod: function ( disable ) {
				var $subscription_period = $( '#_subscription_period_interval, #_subscription_period' );
				if ( disable ) {
					$subscription_period.prop( 'disabled', true ).css( 'background', '#CCC' );
				} else {
					$subscription_period.prop( 'disabled', false ).css( 'background', '#FFF' );
				}
			},
			enableDisableSubscriptionLength: function ( disable ) {
				if ( disable ) {
					$( '#_subscription_length' ).prop( 'disabled', true ).css( 'background', '#CCC' );
				} else {
					$( '#_subscription_length' ).prop( 'disabled', false ).css( 'background', '#FFF' );
				}
			},
			addClasstoVariablePrice: function () {
				$( '.woocommerce_variation .variable_pricing' ).addClass( 'hide_if_variable_cpw' );
			},
			moveNYPvariationFields: function () {
				$( '#variable_product_options .variable_cpw_pricing' ).not( '.cpw_moved' ).each(
					function () {
						$( this ).insertAfter( $( this ).siblings( '.variable_pricing' ) ).addClass( 'cpw_moved' );
					}
				);
			},
			showHideNYPvariableMeta: function () {
				if ( 'variable-subscription' === $( '#product-type' ).val() ) {
					$.showHideNYPmetaforVariableSubscriptions();
				} else {
					$.showHideNYPmetaforVariableProducts();
				}
			},
			showHideNYPmetaforVariableProducts: function () {

				$( '.variation_is_cpw' ).each(
					function () {

						var $variable_pricing = $( this ).closest( '.woocommerce_variation' ).find( '.variable_pricing' );

						var $cpw_pricing = $( this ).closest( '.woocommerce_variation' ).find( '.variable_cpw_pricing' );

						// Hide or display on load.
						if ( $( this ).prop( 'checked' ) ) {
							$cpw_pricing.show();
							$variable_pricing.hide();

						} else {
							$cpw_pricing.hide();
							$variable_pricing.removeAttr( 'style' );

						}

					}
				);

			},
			showHideNYPmetaforVariableSubscriptions: function () {

				$( '.variation_is_cpw' ).each(
					function () {
						var $variable_pricing = $( this ).closest( '.woocommerce_variation' ).find( '.variable_pricing' );
						var $variable_subscription_price = $( this ).closest( '.woocommerce_variation' ).find( '.wc_input_subscription_price' );

						var $cpw_pricing = $( this ).closest( '.woocommerce_variation' ).find( '.variable_cpw_pricing' );

						if ( $( this ).prop( 'checked' ) ) {
							$cpw_pricing.show();
							$variable_subscription_price.prop( 'disabled', true ).css( 'background', '#CCC' );
							$variable_pricing.children().not( '.show_if_variable-subscription' ).hide();
						} else {
							$cpw_pricing.hide();
							$variable_subscription_price.prop( 'disabled', false ).css( 'background', '#FFF' );
							$variable_pricing.children().not( '.hide_if_variable-subscription' ).show();
						}

					}
				);

			}

		}
	); // End extend.

	// Magically move the simple inputs into the sample location as the normal pricing section.
	if ( $( '.options_group.pricing' ).length > 0 ) {
		$.moveNYPmetaFields();
		$.addClasstoRegularPrice();
		$.showHideNYPelements();
	}

	// Adjust fields when the product type is changed.
	$( 'body' ).on(
		'woocommerce-product-type-change',
		function () {
			$.showHideNYPelements();
		}
	);

	// Adjust the fields when NYP status is changed.
	$( 'select#_suggested_price_type' ).on(
		'change',
		function () {
			$.showHideSugesstedPrice();
		}
	);

	// Adjust the fields when NYP status is changed.
	$( 'input#_cpw' ).on(
		'change',
		function () {
			$.showHideNYPelements();
		}
	);

	// Adjust the fields when variable billing period status is changed.
	$( '#_variable_billing' ).on(
		'change',
		function () {
			$.showHideNYPvariablePeriods( this.checked );
			$.enableDisableSubscriptionPeriod( this.checked );
			$.enableDisableSubscriptionLength( this.checked );
		}
	);

	// WC 2.4 compat: handle variable products on load.
	$( '#woocommerce-product-data' ).on(
		'woocommerce_variations_loaded',
		function () {
			$.addClasstoVariablePrice();
			$.moveNYPvariationFields();
			$.showHideNYPvariableMeta();
			$.showHideSugesstedPrice();
		}
	);

	// When a variation is added.
	$( '#variable_product_options' ).on(
		'woocommerce_variations_added',
		function () {
			$.addClasstoVariablePrice();
			$.moveNYPvariationFields();
			$.showHideNYPvariableMeta();
			$.showHideSugesstedPrice();
		}
	);

	// Hide/display variable cpw prices on single cpw checkbox change.
	$( '#variable_product_options' ).on(
		'change',
		'.variation_is_cpw',
		function () {
			$.showHideNYPvariableMeta();
		}
	);

	// Hide/display variable cpw prices on bulk cpw checkbox change.
	$( 'select.variation_actions' ).on(
		'woocommerce_variable_bulk_cpw_toggle',
		function () {
			$.showHideNYPvariableMeta();
		}
	);

	/*
	 * Bulk Edit callbacks
	 */
	// WC 2.4+ variation bulk edit handling.
	$( 'select.variation_actions' ).on(
		'variation_suggested_price_ajax_data variation_suggested_price_increase_ajax_data variation_suggested_price_decrease_ajax_data variation_min_price_ajax_data variation_min_price_increase_ajax_data variation_min_price_decrease_ajax_data variation_maximum_price_ajax_data variation_maximum_price_increase_ajax_data variation_maximum_price_decrease_ajax_data',
		function ( event, data ) {

			var variation_action = $( this ).val();
			var value;

			switch ( variation_action ) {
				case 'variation_suggested_price':
				case 'variation_min_price':
				case 'variation_maximum_price':
					value = window.prompt( woocommerce_cpw_metabox.enter_value );
					// Unformat.
					value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );
					break;
				case 'variation_suggested_price_increase':
				case 'variation_suggested_price_decrease':
				case 'variation_min_price_increase':
				case 'variation_min_price_decrease':
				case 'variation_maximum_price_increase':
				case 'variation_maximum_price_decrease':
					value = window.prompt( woocommerce_cpw_metabox.price_adjust );

					// Is it a percentage change?
					data.percentage = value.indexOf( '%' ) >= 0 ? 'yes' : 'no';

					// Unformat.
					value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );

			}

			if ( null !== value ) {
				data.value = value;
			}
			return data;
		}
	);

} )( jQuery ); // End.
