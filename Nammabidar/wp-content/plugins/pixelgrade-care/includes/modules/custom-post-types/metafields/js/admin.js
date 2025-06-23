(function ( $ ) {
	'use strict';

	$( document ).ready( function () {

		/**
		 * Modal
		 **/

		// click on Manage button
		$( document ).on( 'click', '.open_pixcare_cpt_fields_modal', function ( ev ) {
			ev.preventDefault();
			open_modal();
		} );

		// click on close button
		$( document ).on( 'click', '.media-modal-close', function ( ev ) {
			ev.preventDefault();
			close_modal();
		} );

		// add field
		$( document ).on( 'click', '.pixcare_cpt_fields_add_new_field .add_field', function ( ev ) {
			ev.preventDefault();
			const label = $( this ).siblings( '.label' ).find( 'input' )[0];
			// do not allow fields without  a label
			// first force the label field to be required
			$( label ).attr( 'required', 'required' );
			if ( !label.checkValidity() ) {
				label.reportValidity();
				return false;
			}
			$( label ).attr( 'required', false );

			const $list = $( '.pixcare_cpt_fields_list' ),
				$new_field = $( this ).parent( '.pixcare_cpt_field' ),
				post_type = $new_field.data( 'post_type' ),
				filter = $( $new_field ).find( '.filterable input' )[0].checked;

			let order = 0;

			if ( typeof $list.get( 0 ) !== 'undefined' ) {
				order = ($list.get( 0 ).childElementCount >= 0) ? $list.get( 0 ).childElementCount : 1;
			}

			// we only need the value
			const label_val = $( label ).val();

			// do not allow an empty label input
			$list.append( get_field_template( {
				post_type: post_type,
				order: order,
				label: label_val,
				filter: filter
			} ) );

			// keep showing the last added field
			$list.parent().animate( { scrollTop: $list.parent().height() }, 300 );

			// clear after append
			$( label ).val( '' );
			$( $new_field ).find( '.filterable input' ).prop( 'checked', false );
		} );

		// delete field
		$( document ).on( 'click', '.pixcare_cpt_fields_box .delete_field', function ( ev, el ) {
			ev.preventDefault();

			const field = $( this ).siblings( '.label' ).children( 'input' ).val();

			const response = confirm( 'Do you really want to delete the field ' + field + '?' );
			if ( !response ) {
				return;
			}
			$( this ).parents( '.pixcare_cpt_field' ).remove();
		} );

		$( '.ui-sortable' ).sortable( {
			connectToSortable: '.ui-sortable',
			revert: true,
			placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true,
			dropOnEmpty: false,
			helper: 'clone',
			handle: '.drag',
			scroll: false,
		} );
		$( 'ul.ui-sortable, .ui-sortable li' ).disableSelection();

		// update pixcare_cpt_fields list
		$( document ).on( 'click', '.update_pixcare_cpt_fields', function ( ev ) {
			ev.preventDefault();

			const $pixcare_cpt_fields_container = $( '#pixcare_cpt_fields .inside' ),
				pixcare_cpt_fields = $( this ).parents( '.pixcare_cpt_fields_manager_form' ).find( '.pixcare_cpt_fields_list input' );

			let to_break = false;
			// Stop on invalid fields.
			$( pixcare_cpt_fields ).each( function ( ui, el ) {

				if ( $( el ).attr( 'type' ) === 'text' ) {
					$( el ).attr( 'required', true );
				}

				if ( !el.checkValidity() ) {
					el.reportValidity();
					to_break = true;
				}
			} );

			if ( to_break ) {
				return false;
			}

			$pixcare_cpt_fields_container.addClass( 'ajax_running' );
			const serialized_data = serialize_form( pixcare_cpt_fields );

			$.ajax( {
				url: pixcare_cpt_fields_l10n.ajax_url,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'save_pixcare_cpt_fields_list',
					nonce: pixcare_cpt_fields_l10n.nonce,
					post_id: $( '#post_ID' ).val(),
					fields: serialized_data
				},
				success: function ( result ) {
					if ( typeof result !== 'undefined' || result !== '' ) {
						if ( result.success ) {
							$( '#pixcare_cpt_fields .inside' ).html( result.data );
						}
					}
					$pixcare_cpt_fields_container.removeClass( 'ajax_running' );
				}
			} );

			close_modal();
		} );

		// Meta fields
		$( '.pixcare_cpt_field_value' ).each( function () {

			const post_type = $( this ).parents( '.pixcare_cpt_fields' ).data( 'post_type' ),
				field_key = $( this ).parents( '.pixcare_cpt_field' ).data( 'field_key' );

			$( this ).autocomplete( {
				source: function ( request, response ) {
					$.ajax( {
						url: pixcare_cpt_fields_l10n.ajax_url,
						dataType: 'json',
						data: {
							action: 'pixcare_cpt_field_autocomplete',
							nonce: pixcare_cpt_fields_l10n.nonce,
							post_type: post_type,
							field_key: field_key,
							term: request.term
						},
						success: function ( result ) {
							if ( typeof result !== 'undefined' && typeof result.data !== 'undefined') {
								response( $.map( result.data, function ( value ) {
									return {
										label: value,
										value: value
									};
								} ) );
							}
						}
					} );
				},
				minLength: 2
			} );
		} );
	} );

	const close_modal = function () {
		// clear our classes
		$( '#pixcare_cpt_fields_manager' ).removeClass( 'active' );

		// remove our atts before exit
		remove_required_atts();

		$( 'body' ).removeClass( 'pixcare_cpt_fields_modal_visible' );
	};

	const open_modal = function () {
		$( '#pixcare_cpt_fields_manager' ).addClass( 'active' );

		// remove our atts before exit
		remove_required_atts();

		// let the body know about our modal
		$( 'body' ).addClass( 'pixcare_cpt_fields_modal_visible' );
	};

	const remove_required_atts = function () {
		$( '.pixcare_cpt_fields_wrapper' ).find( 'input' ).attr( 'required', false );
	};

	const get_field_template = function ( args ) {

		const post_type = args.post_type,
			order = args.order,
			label = args.label;

		let filter = args.filter;

		// if the filter field was checked then we should put it in the new template too
		if ( filter ) {
			filter = 'checked="checked"';
		} else {
			filter = '';
		}
		return '' +
			'<li class="pixcare_cpt_field" data-order="' + order + '">' +
			'<span class="drag"><span class="dashicons dashicons-move"></span></span>' +
			'<span class="label">' +
			'<input type="text" name="pixcare_cpt_fields_list[' + post_type + '][' + order + '][label]" value="' + label + '" />' +
			'</span>' +
			//'<span class="default_value">' +
			//	'<input type="text" name="pixcare_cpt_fields_list['+post_type+']['+ order +'][default]" />' +
			//'</span>' +
			'<span class="filterable">' +
			'<input type="checkbox" name="pixcare_cpt_fields_list[' + post_type + '][' + order + '][filter]" ' + filter + '/>' +
			'</span>' +
			'<a href="#" class="delete_field">Delete</a>' +
			'</li>';
	};

	const serialize_form = function ( form ) {
		if ( form.length > 0 ) {
			var serialized_data = $( form ).serialize();
			return decodeURIComponent( serialized_data );
		}
		return false;
	};

	// @TODO this is a fail
	//$.fn.serializePixFields = function() {
	//	var data = {};
	//
	//	$(this).each( function( key, element ) {
	//
	//		var name = $(this ).attr('name').replace('pixcare_cpt_fields_list[', '');
	//		name = name.substring(0, name.length - 1 );
	//
	//		var keys = name.split(']['),
	//			post_type = keys.shift(),
	//			counter = keys.shift(),
	//			field = keys.shift();
	//
	//		if ( typeof data.post_type === 'undefined' ) {
	//			data.post_type = {};
	//		}
	//
	//
	//		if ( typeof  data.post_type.counter === 'undefined' ) {
	//			data.post_type.counter = {};
	//		}
	//		//if ( typeof data.post_type.counter.field === 'undefined' ) {
	//		//	data.post_type.counter.field = {};
	//		//}
	//
	//		data.post_type.counter.field = $(this).val();
	//		console.log(post_type);
	//	});
	//
	//	return data;
	//};

	$( function () {

		/**
		 *  Checkbox value switcher
		 *  Any checkbox should switch between value 1 and 0
		 *  Also test if the checkbox needs to hide or show something under it.
		 */
		//$('#pixtypes_form input:checkbox').each(function(i,e){
		//	check_checkbox_checked(e);
		//	$(e).check_for_extended_options();
		//});
		//$('#pixtypes_form').on('click', 'input:checkbox', function(){
		//	check_checkbox_checked(this);
		//	$(this).check_for_extended_options();
		//});
		/** End Checkbox value switcher **/

		/* Ensure groups visibility */
		$( '.switch input[type=checkbox]' ).each( function () {

			if ( $( this ).data( 'show_group' ) ) {

				let show = false;
				if ( $( this ).attr( 'checked' ) ) {
					show = true;
				}

				toggleGroup( $( this ).data( 'show_group' ), show );
			}
		} );

		$( '.switch ' ).on( 'change', 'input[type=checkbox]', function () {
			if ( $( this ).data( 'show_group' ) ) {
				var show = false;
				if ( $( this ).attr( 'checked' ) ) {
					show = true;
				}
				toggleGroup( $( this ).data( 'show_group' ), show );
			}
		} );
	} );

	const toggleGroup = function ( name, show ) {
		const $group = $( '#' + name );

		if ( show ) {
			$group.show();
		} else {
			$group.hide();
		}
	};

	/*
	 * Useful functions
	 */

	function check_checkbox_checked ( input ) { // yes the name is an ironic
		if ( $( input ).attr( 'checked' ) === 'checked' ) {
			$( input ).siblings( 'input:hidden' ).val( 'on' );
		} else {
			$( input ).siblings( 'input:hidden' ).val( 'off' );
		}
	}

	$.fn.check_for_extended_options = function () {
		const extended_options = $( this ).siblings( 'fieldset.group' );
		if ( $( this ).data( 'show-next' ) ) {
			if ( extended_options.data( 'extended' ) === true ) {
				extended_options
					.data( 'extended', false )
					.css( 'height', '0' );
			} else if ( (typeof extended_options.data( 'extended' ) === 'undefined' && $( this ).attr( 'checked' ) === 'checked') || extended_options.data( 'extended' ) === false ) {
				extended_options
					.data( 'extended', true )
					.css( 'height', 'auto' );
			}
		}
	};

}( jQuery ));
