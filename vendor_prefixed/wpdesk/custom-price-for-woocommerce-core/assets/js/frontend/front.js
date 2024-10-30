/**
 * Script for validating the prices before adding to cart.
 *
 * @package WooCommerce Custom Price/Scripts
 */

/* global woocommerce_cpw_params */

/**----------------------------------------------------------------*/
/*  Global utility variables + functions.                          */
/*-----------------------------------------------------------------*/

// Format the price with accounting.js.
function woocommerce_cpw_format_price( price, currency_symbol, format ) {

	if ( 'undefined' === typeof currency_symbol ) {
		currency_symbol = '';
	}

	if ( 'undefined' === typeof format ) {
		format = false;
	}

	var currency_format = format ? woocommerce_cpw_params.currency_format : '%v';

	var formatted_price = accounting.formatMoney(
		price,
		{
			symbol : currency_symbol,
			decimal : woocommerce_cpw_params.currency_format_decimal_sep,
			thousand: woocommerce_cpw_params.currency_format_thousand_sep,
			precision : woocommerce_cpw_params.currency_format_num_decimals,
			format: currency_format
		}
	).trim();

	// Trim trailing zeros.
	if ( woocommerce_cpw_params.trim_zeros ) {
		var regex       = new RegExp( '\\' + woocommerce_cpw_params.currency_format_decimal_sep + '0+$', 'i' );
		formatted_price = formatted_price.replace( regex, '' );
	}

	return formatted_price;

}

// Get absolute value of price and turn price into float decimal.
function woocommerce_cpw_unformat_price( price ) {
	return Math.abs( parseFloat( accounting.unformat( price, woocommerce_cpw_params.currency_format_decimal_sep ) ) );
}

/**
 * Container script object getter.
 */
jQuery.fn.wc_cpw_get_script_object = function() {

	var $el = jQuery( this );

	if ( typeof( $el.data( 'wc_cpw_script_obj' ) ) !== 'undefined' ) {
		return $el.data( 'wc_cpw_script_obj' );
	}

	return false;
};

/*-----------------------------------------------------------------*/
/*  Encapsulation.                                                 */
/*-----------------------------------------------------------------*/

( function( $ ) {

	/**
	 * Main form object.
	 */
	var cpwForm = function( $cart ) {

		var cpw_script_object = $cart.wc_cpw_get_script_object();

		if ( 'object' === typeof cpw_script_object ) {
			return cpw_script_object;
		}

		this.$el          = $cart;
		this.$add_to_cart = $cart.find( '.single_add_to_cart_button' );

		// If the button isn't found by class, find it by type=submit.
		if ( ! this.$add_to_cart.length ) {
			this.$add_to_cart = $cart.find( ':submit' );
		}

		this.$addons_totals = this.$el.find( '#product-addons-total' );

		this.show_addons_totals = false;
		this.cpwProducts        = [];
		this.update_cpw_timer   = false;

		this.$el.trigger( 'wc-cpw-initializing', [ this ] );

		// Methods.
		this.updateForm = this.updateForm.bind( this );

		// Events.
		this.$add_to_cart.on( 'click', { cpwForm: this }, this.onSubmit );
		this.$el.on( 'wc-cpw-initialized', { cpwForm: this }, this.updateForm );
		this.$el.on( 'wc-cpw-updated', { cpwForm: this }, this.updateForm );

		this.initIntegrations();

		this.$el.data( 'wc_cpw_script_obj', this );

		// Initialize an update immediately.
		this.$el.trigger( 'wc-cpw-initialized', [ this ] );

	};

	/**
	 * Get all child item objects.
	 */
	cpwForm.prototype.getProducts = function() {

		var form = this;

		this.$el.find( '.cpw' ).each(
			function( index ) {
				var $cpw          = $( this ),
				cpw_script_object = $cpw.wc_cpw_get_script_object();

				// Initialize any objects that don't yet exist.
				if ( 'object' !== typeof cpw_script_object ) {
					  cpw_script_object = new cpwProduct( $cpw );
				}
				form.cpwProducts[ index ] = cpw_script_object;
			}
		);

		return form.cpwProducts;

	};

	/**
	 * Initialize integrations.
	 */
	cpwForm.prototype.initIntegrations = function() {

		if ( this.$el.hasClass( 'variations_form' ) ) {
			new WC_NYP_Variations_Integration( this );
		}

		if ( this.$el.hasClass( 'grouped_form' ) ) {
			new WC_NYP_Grouped_Integration( this );
		}

		if ( $( '#woo_pp_ec_button_product' ).length ) {
			new WC_NYP_PPEC_Integration( this );
		}

	};

	/**
	 * Update the form.
	 */
	cpwForm.prototype.updateForm = function( e, triggeredBy ) {

		var current_price = false;
		var attr_name     = false;
		var cpwProducts   = this.getProducts();

		// If triggered by form update, only get a single instance. Unsure how this will work with Bundles/Grouped.
		if ( 'undefined' === typeof triggeredBy && 'undefined' !== typeof cpwProducts && cpwProducts.length ) {
			triggeredBy = cpwProducts.shift();
		}

		if ( 'undefined' !== typeof triggeredBy && 'undefined' !== typeof triggeredBy.$price_input ) {
			attr_name     = triggeredBy.$price_input.attr( 'name' );
			current_price = triggeredBy.user_price;

			// Always add the price to the button as data for AJAX add to cart.
			this.$add_to_cart.data( attr_name, current_price );

			// Update Addons.
			this.$addons_totals.data( 'price', current_price );
			this.$el.trigger( 'woocommerce-product-addons-update' );

		}

		// Change button status.
		if ( this.isValid() ) {
			this.$add_to_cart.removeClass( 'cpw-disabled' );
			this.$el.trigger( 'wc-cpw-valid', [ this ] );
		} else {
			this.$add_to_cart.addClass( 'cpw-disabled' );
			this.$el.trigger( 'wc-cpw-invalid', [ this ] );
		}

	};

	/**
	 * Scheduled update.
	 *
	 * @deprecated 3.2.0
	 */
	cpwForm.prototype.updateFormTask = function( triggeredBy ) {
		this.updateForm( false, triggeredBy );
	};

	/**
	 * Validate on submit.
	 */
	cpwForm.prototype.onSubmit = function( e ) {
		var form = e.data.cpwForm;

		if ( ! form.isValid( 'submit' ) ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}

	};

	/**
	 * Are all NYP fields valid?
	 */
	cpwForm.prototype.isValid = function( event_type ) {

		var valid = true;

		this.getProducts().forEach(
			function (cpwProduct) {

				// Revalidate on submit.
				if ( 'submit' === event_type ) {
					cpwProduct.$el.trigger( 'wc-cpw-update' );
				}

				if ( ! cpwProduct.isValid() ) {
					valid = false;
					return true;
				}

			}
		);

		return valid;
	};

	/**
	 * Shuts down events, actions and filters managed by this script object.
	 */
	cpwForm.prototype.shutdown = function() {
		this.$el.find( '*' ).off();
	};

	/*-----------------------------------------------------------------*/
	/*  cpwProduct object                                              */
	/*-----------------------------------------------------------------*/

	var cpwProduct = function( $cpw ) {

		var cpw_script_object = $cpw.wc_cpw_get_script_object();

		if ( cpw_script_object ) {
			return cpw_script_object;
		}

		var self = this;

		// Objects.
		self.$el                 = $cpw;
		self.$cart               = $cpw.closest( '.cart' );
		self.$form               = $cpw.closest( '.cart' ).not( '.product, [data-bundled_item_id]' );
		self.$error              = $cpw.find( '.woocommerce-cpw-message' );
		self.$error_content      = self.$error.find( 'ul.woocommerce-error' );
		self.$label              = $cpw.find( 'label' );
		self.$screen_reader      = $cpw.find( '.screen-reader-text' );
		self.$price_input        = $cpw.find( '.cpw-input' );
		self.$period_input       = $cpw.find( '.cpw-period' );
		self.$minimum 			 = $cpw.find( '.minimum-price' );
		self.$subscription_terms = $cpw.find( '.subscription-details' );

		// Variables.
		self.form           = self.$form.wc_cpw_get_script_object();
		self.min_price      = parseFloat( $cpw.data( 'min-price' ) );
		self.max_price      = parseFloat( $cpw.data( 'max-price' ) );
		self.annual_minimum = parseFloat( $cpw.data( 'annual-minimum' ) );
		self.raw_price      = self.$price_input.val();
		self.user_price     = woocommerce_cpw_unformat_price( self.raw_price );
		self.user_period    = self.$period_input.val();
		self.error_messages = [];
		self.optional       = false;
		self.initialized    = false;

		// Methods.
		self.onUpdate = self.onUpdate.bind( self );
		self.validate = self.validate.bind( self );

		// Events.
		this.$el.on( 'change', '.cpw-input, .cpw-period', { cpwProduct: this }, this.onChange );
		this.$el.on( 'keypress', '.cpw-input, .cpw-period', { cpwProduct: this }, this.onKeypress );
		this.$el.on( 'woocommerce-cpw-update', { cpwProduct: this }, this.onUpdate ); // For backcompat only, please use wc-cpw-update instead.
		this.$el.on( 'wc-cpw-update', { cpwProduct: this }, this.onUpdate );

		// Store reference in the DOM.
		self.$el.data( 'wc_cpw_script_obj', self );

		// Trigger immediately.
		self.$el.trigger( 'wc-cpw-update', [ self ] );

	};

	/**
	 * Relay change event to the custom update event.
	 */
	cpwProduct.prototype.onChange = function( e ) {
		e.data.cpwProduct.$el.trigger( 'wc-cpw-update', [ e.data.cpwProduct ] );
	};

	/**
	 * Prevent submit on pressing Enter key.
	 */
	cpwProduct.prototype.onKeypress = function( e ) {
		if ( 'Enter' === e.key ) {
			e.preventDefault();
			e.data.cpwProduct.$el.trigger( 'wc-cpw-update', [ e.data.cpwProduct ] );
		}
	};

	/**
	 * Handle update.
	 */
	cpwProduct.prototype.onUpdate = function( e, args ) {

		var self = this;

		// Force revalidation.
		if ( 'undefined' !== typeof args && args.hasOwnProperty( 'force' ) && true === args.force ) {
			this.initialized = false;
		}

		// Current values.
		this.raw_price   = this.$price_input.val().trim() ? this.$price_input.val().trim() : '';
		this.user_price  = woocommerce_cpw_unformat_price( this.raw_price );
		this.user_period = this.$period_input.val();

		// Maybe auto-format the input.
		if ( '' !== this.raw_price ) {
			this.$price_input.val( woocommerce_cpw_format_price( this.user_price ) );
		}

		// Validate this!
		this.validate();

		// Always add price to NYP div for compatibility.
		this.$el.data( 'price', this.user_price );
		this.$el.data( 'period', this.user_period );

		if ( this.isValid() ) {

			// Remove error state class.
			this.$el.removeClass( 'cpw-error' );

			// Remove error messages.
			this.$error.slideUp();

			this.$el.trigger( 'wc-cpw-valid-item', [ this ] );

		} else {

			var $messages = $( '<ul/>' );
			var messages  = this.getErrorMessages();

			if ( messages.length > 0 ) {
				$.each(
					messages,
					function( i, message ) {
						$messages.append( $( '<li/>' ).html( message ) );
					}
				);
			}

			this.$error_content.html( $messages.html() );

			this.$el.trigger( 'wc-cpw-invalid-item', [ this ] );

		}

		if ( this.isInitialized() && ! this.isValid() ) {

			this.$el.addClass( 'cpw-error' );

			this.$error.slideDown(
				function() {
					self.$price_input.trigger( 'focus' ).trigger( 'select' );
				}
			);

		}

		// Backcompat triggers.
		this.$cart.trigger( 'woocommerce-cpw-updated-item' ); // Used by Product Bundles.
		$( 'body' ).trigger( 'woocommerce-cpw-updated' );

		// New trigger.
		this.$el.trigger( 'wc-cpw-updated', [ this ] );

		// Mark the product as initialized.
		this.initialized = true;

	};

	/**
	 * Validate all the prices.
	 */
	cpwProduct.prototype.validate = function() {

		// Skip validate if the price has not changed.
		if ( ! this.priceChanged() ) {
			return true;
		}

		// Reset validation messages.
		this.resetMessages();
		this.$el.data( 'cpw-valid', true );

		// Skip validation for optional products, ex: grouped/bundled.
		if ( this.isOptional() ) {
			return true;
		}

		// Not optional, so let's check the prices.

		// Begin building the error message.
		var error_message = this.$el.data( 'hide-minimum' ) ? this.$el.data( 'hide-minimum-error' ) : this.$el.data( 'minimum-error' );
		var error_tag     = '%%MINIMUM%%';
		var error_price   = ''; // This will hold the formatted price for the error message.

		// If has variable billing period AND a minimum then we need to annulalize min price for comparison.
		if ( this.annual_minimum > 0 ) {

			// Calculate the price over the course of a year for comparison.
			var form_annulualized_price = this.user_price * woocommerce_cpw_params.annual_price_factors[this.user_period];

			// If the calculated annual price is less than the annual minimum.
			if ( form_annulualized_price < this.annual_minimum ) {

				var min_price     = this.annual_minimum / woocommerce_cpw_params.annual_price_factors[this.user_period];
				var period_string = this.$period_input.find( 'option[value="' + this.user_period + '"]' ).text();

				error_price = woocommerce_cpw_params.i18n_subscription_string.replace( '%price', woocommerce_cpw_format_price( min_price, woocommerce_cpw_params.currency_format_symbol, true ) ).replace( '%period', period_string );
				this.addErrorMessage( error_message.replace( error_tag, error_price ) );

			}

			// Otherwise a regular product or subscription with non-variable periods, compare price directly.
		} else if ( this.min_price && this.user_price < this.min_price ) {

			error_price = woocommerce_cpw_format_price( this.min_price, woocommerce_cpw_params.currency_format_symbol, true );
			this.addErrorMessage( error_message.replace( error_tag, error_price ) );

			// Check maximum price.
		} else if ( this.max_price && this.user_price > this.max_price ) {

			error_message = this.$el.data( 'maximum-error' );
			error_tag     = '%%MAXIMUM%%';
			error_price   = woocommerce_cpw_format_price( this.max_price, woocommerce_cpw_params.currency_format_symbol, true );
			this.addErrorMessage( error_message.replace( error_tag, error_price ) );

			// Check empty input.
		} else if ( '' === this.raw_price ) {

			error_message = this.$el.data( 'empty-error' );
			this.addErrorMessage( error_message.replace( error_tag, error_price ) );

		}

		if ( ! this.isValid() ) {
			this.$el.data( 'cpw-valid', false );
		}

	};

	/**
	 * Has this price changed?
	 */
	cpwProduct.prototype.priceChanged = function() {
		var $changed = true;

		if ( ! this.$el.is( ':visible' ) ) {
			$changed = false;
		} else if ( this.isInitialized() && this.raw_price === this.user_price && this.user_price === this.$el.data( 'price' ) && this.user_period === this.$el.data( 'period' ) ) {
			$changed = false;
		}

		return $changed;
	};

	/**
	 * Is this price valid?
	 */
	cpwProduct.prototype.isValid = function() {
		return ! this.$el.is( ':visible' ) || this.isOptional() || ! this.error_messages.length;
	};

	/**
	 * Is this product optional?
	 */
	cpwProduct.prototype.isOptional = function() {
		return this.$el.data( 'optional' ) === 'yes' && this.$el.data( 'optional_status' ) !== true;
	};

	/**
	 * Is this product initialized?
	 */
	cpwProduct.prototype.isInitialized = function() {
		return this.initialized;
	};

	/**
	 * Add validation message.
	 */
	cpwProduct.prototype.addErrorMessage = function( message ) {
		this.error_messages.push( message.toString() );
	};

	/**
	 * Get validation messages.
	 */
	cpwProduct.prototype.getErrorMessages = function() {
		return this.error_messages;

	};

	/**
	 * Reset messages on update start.
	 */
	cpwProduct.prototype.resetMessages = function() {
		this.error_messages = [];
	};

	/**
	 * Reset messages on update start.
	 */
	cpwProduct.prototype.resetMessages = function() {
		this.error_messages = [];
	};

	/**
	 * Get the user price.
	 */
	cpwProduct.prototype.getPrice = function() {
		return this.user_price;
	};

	/**
	 * Get the user period.
	 */
	cpwProduct.prototype.getPeriod = function() {
		return this.user_period;
	};

	/*-----------------------------------------------------------------*/
	/*  Integrations .                                                 */
	/*-----------------------------------------------------------------*/

	/**
	 * Variable Product Integration.
	 */
	function WC_NYP_Variations_Integration( form ) {

		var self = this;

		// Assume in a variable product there's only 1 NYP field.
		var cpw = form.getProducts().shift();

		// Sanity check, make sure we have something to work with in case the script is loaded where it shouldn't be.
		if ( 'undefined' === typeof cpw ) {
			return;
		}

		// The add to cart text.
		var default_add_to_cart_text = form.$add_to_cart.html();

		// Init.
		this.integrate = function() {
			form.$el.on( 'found_variation', self.onFoundVariation );
			form.$el.on( 'reset_image', self.resetVariations );
			form.$el.on( 'click', '.reset_variations', self.resetVariations );
		};

		// When variation is found, decide if it is NYP or not.
		this.onFoundVariation = function( event, variation ) {

			// Hide any existing error message.
			cpw.$error.slideUp();

			// If NYP show the price input and tweak the data attributes.
			if ( 'undefined' !== typeof variation.is_cpw && true === variation.is_cpw ) {

				// Switch add to cart button text if variation is NYP.
				form.$add_to_cart.html( variation.add_to_cart_text );

				// Get the prices out of data attributes.
				var display_price = typeof variation.display_price !== 'undefined' && variation.display_price ? variation.display_price : '';

				// Set the NYP attributes for JS validation.
				cpw.min_price = typeof variation.minimum_price !== 'undefined' && variation.minimum_price ? parseFloat( variation.minimum_price ) : '';
				cpw.max_price = typeof variation.maximum_price !== 'undefined' && variation.maximum_price ? parseFloat( variation.maximum_price ) : '';

				// Maybe auto-format the input.
				if ( '' !== display_price.trim() ) {
					cpw.$price_input.val( woocommerce_cpw_format_price( display_price ) );
				} else {
					cpw.$price_input.val( '' );
				}

				// Maybe switch the label.
				if ( cpw.$label.length ) {

					var label = 'undefined' !== variation.price_label ? variation.price_label : '';

					if ( label ) {
						cpw.$label.html( label ).show();
					} else {
						cpw.$label.empty().hide();
					}
				}

				// Maybe show minimum price html.
				if ( cpw.$minimum.length ) {

					var minimum_price_html = 'undefined' !== variation.minimum_price_html ? variation.minimum_price_html : '';

					if ( minimum_price_html ) {
						cpw.$minimum.html( minimum_price_html ).show();
					} else {
						cpw.$minimum.empty().hide();
					}
				}

				// Show the input.
				cpw.$el.slideDown();

				// Toggle minimum error message between explicit and obscure.
				cpw.$el.data( 'hide-minimum', variation.hide_minimum );

				// Trigger update.
				cpw.initialized = false;
				cpw.$el.trigger( 'wc-cpw-update' );

				// If not NYP, hide the price input.
			} else {

				self.resetVariations();

			}

		};

		// Hide NYP errors when attributes are reset.
		this.resetVariations = function() {
			form.$add_to_cart.html( default_add_to_cart_text ).removeClass( 'cpw-disabled' );
			cpw.$el.slideUp().removeClass( 'cpw-error' );
			cpw.initialized = false;
			cpw.$error_content.empty();
			cpw.$price_input.val( '' );
		};

		// Lights on.
		this.integrate();

	}

	/**
	 * Grouped Product Integration.
	 */
	function WC_NYP_Grouped_Integration( form ) {

		var self = this;

		// Init.
		this.integrate = function() {

			// Handle status of optional grouped products.
			form.$el.on( 'change', '.qty, .wc-grouped-product-add-to-cart-checkbox', self.onStatusChange );
			form.$el.find( '.qty, .wc-grouped-product-add-to-cart-checkbox' ).change();

		};

		// Handle optional status changes.
		this.onStatusChange = function() {

			var $cpw = $( this ).closest( 'tr' ).find( '.cpw' );

			if ( $cpw.length ) {

				var selected = $( this ).is( ':checkbox' ) ? $( this ).is( ':checked' ) : $( this ).val() > 0;

				if ( selected ) {
					$cpw.data( 'optional_status', true );
				} else {
					$cpw.data( 'optional_status', false );
				}
				$cpw.trigger( 'wc-cpw-update', [ { 'force': true } ] );
			}

		};

		// Lights on.
		this.integrate();

	}

	/**
	 * PayPal Express Checkout Integration.
	 */
	function WC_NYP_PPEC_Integration( form ) {

		var self = this;

		// Init.
		this.integrate = function() {
			form.$el.on( 'wc-cpw-valid', self.enable );
			form.$el.on( 'wc-cpw-invalid', self.disable );
			$( document ).on( 'wc_ppec_validate_product_form', self.validate );
		};

		// Enable PayPal buttons.
		this.enable = function() {
			$( '#woo_pp_ec_button_product' ).trigger( 'enable' );
		};

		// Disable PayPal buttons.
		this.disable = function() {
			$( '#woo_pp_ec_button_product' ).trigger( 'disable' );
		};

		// Extra validation for NYP items.
		this.validate = function( e, is_valid, $form ) {

			var cpw_script_object = $form.wc_cpw_get_script_object();

			if ( 'object' === typeof cpw_script_object ) {
				is_valid = cpw_script_object.isValid();
			}

			return is_valid;

		};

		// Lights on.
		this.integrate();

	}

	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/

	jQuery(
		function( $ ) {

			/**
			 * Script initialization on '.cart' elements.
			 */
			$.fn.wc_cpw_form = function() {

				  var $cart         = $( this ),
				  cpw_script_object = $cart.wc_cpw_get_script_object();

				if ( ! $cart.hasClass( 'cart' ) ) {
					return false;
				}

				// If the script object already exists, then we need to shut it down first before re-initializing.
				if ( cpw_script_object) {
					$cart.data( 'wc_cpw_script_obj' ).shutdown();
				}

				// Launch the form object.
				new cpwForm( $cart );

				return this;

			};

			/**
			* Initialize NYP scripts.
			*/
			$( 'form.cart' ).each(
				function() {
					$( this ).wc_cpw_form();
				}
			);

			new cpwForm( $( 'form.cart' ) );

			/*-----------------------------------------------------------------*/
			/*  Compatibility .                                                */
			/*-----------------------------------------------------------------*/

			/**
			 * QuickView compatibility.
			 */
			$( 'body' ).on(
				'quick-view-displayed',
				function() {

					$( 'form.cart' ).each(
						function() {
							$( this ).wc_cpw_form();
						}
					);

				}
			);

			/*
				* One Page Checkout compatibility.
				*/
			$( '.wcopc .cart' ).each(
				function() {
					$( this ).wc_cpw_form();
				}
			);

			$( 'body' ).on(
				'opc_add_remove_product',
				function ( event, data, e ) {

					if ( 'undefined' !== typeof e ) {

						var $triggeredBy = $( e.currentTarget );

						var cpw_script_object = $triggeredBy.closest( '.cart' ).find( '.cpw' ).wc_cpw_get_script_object();

						if ( cpw_script_object ) {

							cpw_script_object.$el.trigger( 'wc-cpw-update' );

							var qty = parseFloat( data.quantity );

							if ( qty > 0 && ! cpw_script_object.isValid() ) {

								  // Reset input quantity to quantity in cart.
								if ( $triggeredBy.prop( 'type' ) === 'number' ) {
									$triggeredBy.val( $triggeredBy.data( 'cart_quantity' ) );
								}
								// Prevent OPC from firing AJAX.
								data.invalid = true;

							} else if ( qty === 0 ) {

								 // Remove error state class.
								 cpw_script_object.$el.removeClass( 'cpw-error' );

								 // Remove error messages.
								 cpw_script_object.$error.slideUp();

								 // Reset input to original value.
								 var original_price = cpw_script_object.$el.data( 'initial-price' );
								if ( $.trim( original_price ) !== '' ) {
									cpw_script_object.$price_input.val( woocommerce_cpw_format_price( original_price ) );
								} else {
									cpw_script_object.$price_input.val( '' );
								}

							}

						}
					}

					return data;

				}
			);

			/**
			 * Run when a Composite component is re-loaded.
			 */
			$( 'body .component' ).on(
				'wc-composite-component-loaded',
				function() {

					var $cpw = $( this ).find( '.cpw' );

					if ( $cpw.length ) {
						  cpwProduct( $cpw );
					} else {
						// Update the form.
						$( this ).trigger( 'wc-cpw-updated' );
					}

				}
			);

		}
	);

} )( jQuery );
