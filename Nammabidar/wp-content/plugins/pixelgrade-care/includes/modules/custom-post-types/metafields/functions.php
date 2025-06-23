<?php
/**
 * A series of helper functions for others to easily retrieve metafields data.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'pixcare_cpt_metafields_get_post_metafield_value' ) ) {

	/**
	 * Retrieve a metafield's value for a certain post.
	 *
	 * @since 1.12.2
	 *
	 * @param string           $meta_key The meta key name to retrieve the value of.
	 *                                   Do not include the prefix since we will automatically prefix it.
	 * @param int|WP_Post|null $post     Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                   return the current global post inside the loop. Defaults to global $post.
	 *
	 * @return mixed|false The post's meta value. False on error.
	 */
	function pixcare_cpt_metafields_get_post_metafield_value( $meta_key, $post = 0 ) {
		return PixelgradeCare_CPT_Metafields::get_post_metafield_value( $meta_key, $post );
	}
}

if ( ! function_exists( 'pixcare_cpt_metafields_get_meta_values' ) ) {

	/**
	 * Get all the values for a certain meta key and certain post type.
	 *
	 * @since 1.12.2
	 *
	 * @param string $meta_key The meta key name to retrieve values of.
	 * @param string $post_type Optional. The custom post type of posts to retrieve value of.
	 *                          Defaults to the `post` post type.
	 * @param string $post_status Optional. Restrict target posts by their status.
	 *                            Defaults to published posts.
	 *
	 * @return array List of unique metafields values. Empty array when none were found.
	 */
	function pixcare_cpt_metafields_get_meta_values( $meta_key = '', $post_type = 'post', $post_status = 'publish' ) {
		return PixelgradeCare_CPT_Metafields::get_meta_values( $meta_key, $post_type, $post_status );
	}
}

if ( ! function_exists( 'pixcare_cpt_metafields_get_post_metafields' ) ) {

	/**
	 * Retrieve all the metafields details for a certain post.
	 *
	 * @since 1.12.2
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                               return the current global post inside the loop. Defaults to global $post.
	 *
	 * @return array List of post metafields keyed by the meta_key.
	 *               Each entry includes `label`, `value`, and optionally, `filter`.
	 */
	function pixcare_cpt_metafields_get_post_metafields( $post = 0 ) {
		return PixelgradeCare_CPT_Metafields::get_post_metafields( $post );
	}
}

if ( ! function_exists( 'pixcare_cpt_metafields_get_filterable_metafields' ) ) {

	/**
	 * Get all the filterable keys.
	 *
	 * @since 1.12.2
	 *
	 * @param string $post_type Optional. Defaults to the post type of the current global post.
	 *
	 * @return array List of filterable metakeys as $key => $label. Empty list if none were found.
	 */
	function pixcare_cpt_metafields_get_filterable_metafields( $post_type = '' ) {
		return PixelgradeCare_CPT_Metafields::get_filterable_metafields( $post_type );
	}
}
