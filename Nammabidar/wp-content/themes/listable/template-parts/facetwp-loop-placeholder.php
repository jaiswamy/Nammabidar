<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div style="display: none;">
	<?php echo facetwp_display( 'template', 'listings' ); ?>
</div>

<script>
	(function($) {

		$( document ).ready( function() {
			// prevent the facets from disappearing
			FWP.loading_handler = function() {
			}
		} );

		$( document ).on( 'keyup', '.facet-wrapper input[type="text"]', function( e ) {
			if ( e.which === 13 ) {
				//wait a little bit
				setTimeout(
					function() {
						//if the user presses ENTER/RETURN in a text field then redirect
						facetwp_redirect_to_listings();
						return false;
					}, 500 );
			}
		} );

	})(jQuery);

	function facetwp_redirect_to_listings() {
		FWP.parse_facets();
		FWP.set_hash();
		var query_string = FWP.build_query_string();
		if ('' != query_string) {
			query_string = '?' + query_string;
		}
		var url = query_string;
		window.location.href = '<?php echo listable_get_listings_page_url(); ?>' + url;
	}
</script>
