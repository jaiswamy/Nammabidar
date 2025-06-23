(function( $ ) {
	var pointers_array = [],
		number_of_pointers = null,
		button = null;

	$( document ).ready( function() {

		if ( typeof listing_page_pointers === "undefined" ) {
			return;
		}

		// get an array of pointers
		pointers_array = $.map( listing_page_pointers.pointers, function( value, index ) {
			return [value];
		} );

		number_of_pointers = Object.keys( listing_page_pointers.pointers ).length;
		if ( typeof listing_page_pointers.pointers !== "undefined" ) {

			// show the first pointer
			show_pointer( pointers_array[0] );
		}
	} );

	var show_pointer = function( pointer ) {

		// no target, no fun
		if ( typeof pointer.target === "undefined" ) {
			return;
		}

		pointer.content = '<h3>' + pointer.title + '</h3>' + pointer.content;

		// create an extended pointer object which will show our tutorial step with direction buttons
		pointer = $.extend( pointer, {
			buttons: function( event, t ) {
				// get the current position of the pointer in array
				var current_position = pointers_array.map( function( obj, index ) {
					if ( obj == pointer ) {
						return index;
					}
				} ).filter( isFinite );
				current_position = parseInt( current_position ) + 1;

				var $buttons = $( '<div class="buttons"></div>' );

				// first let's create the counter
				if ( listing_page_pointers.counter ) {
					var counter_element = $( '<span id="pointer-counter">' + listing_page_pointers.step_label + ' ' + current_position + '/' + pointers_array.length + '</span>' );

					$buttons.append( counter_element );
				}

				var dismiss_button = $( '<a id="dismiss-pointer">' + listing_page_pointers.dismiss_label + '</a>' );
				dismiss_button.bind( 'click.pointer', function( i ) {

					$.post( ajaxurl, {
						pointer: listing_page_pointers.id, // pointer ID
						action: 'dismiss-wp-pointer'
					} );

					t.element.pointer( 'close' );
				} );

				$buttons.append( dismiss_button );

				// if this is the last step add the finish code and quit the other steps
				if ( typeof pointer.next_pointer === "undefined" || current_position === pointers_array.length ) {
					var finish_button = $( '<a id="pointer-next" class="button-primary">' + listing_page_pointers.finish_button_label + '</a>' );

					if ( pointers_array.length > 1 ) {
						// it doesn't mater anymore
						$.post( ajaxurl, {
							pointer: listing_page_pointers.id, // pointer ID
							action: 'dismiss-wp-pointer'
						} );
					}

					finish_button.bind( 'click.pointer', function( i ) {
						t.element.pointer( 'close' );
						$.post( ajaxurl, {
							pointer: listing_page_pointers.id, // pointer ID
							action: 'dismiss-wp-pointer'
						} );
					} );

					$buttons.append( finish_button );
					return $buttons;
				}

				var next_button = $( '<a id="pointer-next" class="button-primary">' + listing_page_pointers.next_button_label + '</a>' );
				next_button.bind( 'click.pointer', function( i ) {
					t.element.pointer( 'close' );
					show_pointer( listing_page_pointers.pointers[pointer.next_pointer] );
				} );

				$buttons.append( next_button );

				return $buttons;
			}
		} );

		$( pointer.target ).pointer( pointer ).pointer( 'open' );
	};

})( jQuery );

