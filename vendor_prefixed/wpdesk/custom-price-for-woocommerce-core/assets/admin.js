( function ( $ ) {
	"use strict";
	let filters_html = jQuery( '#invoice-filters-html' );
	if ( filters_html.length ) {
		jQuery( '.subsubsub' ).before( filters_html.html() );
	}

	var select2_translations = {
		language: {
			inputTooShort: function ( args ) {
				var remainingChars = args.minimum - args.input.length;
				return inspire_invoice_params.select2_min_chars.replace( '%', remainingChars );
			},
			loadingMore: function () {
				return inspire_invoice_params.select2_loading_more;
			},
			noResults: function () {
				return inspire_invoice_params.select2_no_results;
			},
			searching: function () {
				return inspire_invoice_params.select2_searching;
			},
			errorLoading: function () {
				return inspire_invoice_params.select2_error_loading;
			},
		},
	};

	var select2_single = jQuery('.select2-single');
	if (select2_single.length) {
		select2_single.select2({
			...select2_translations,
			width: '100%',
		});
	}

	var select2_multiple = jQuery('.select2-multiple');
	if (select2_multiple.length) {
		select2_multiple.select2({
			multiple: true,
			...select2_translations,
			width: '100%',
		});
	}

	jQuery( 'select#filter-by-date' ).parent().hide();

	var select2_ajax = jQuery('.select2-ajax');
	if (select2_ajax.length) {
		select2_ajax.select2({
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				delay: 300,
				type: 'POST',
				data: function (params) {
					return {
						action: 'fifa_find_user',
						name: params.term,
						security: fifa_localize.nonce
					};
				},
				processResults: function (data) {
					return {
						results: data.items
					};
				},
				cache: true,
			},
			minimumInputLength: 3,
			...select2_translations,
			width: '100%',
		});
	}

} )( jQuery );
