<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listable_options = get_theme_mod( 'listable_options', array() );
if ( empty( $listable_options ) || ! is_array( $listable_options ) ) {
	return;
}

/**
 * Migrate the site_title_font.
 *
 * These are the separate fields:
 * site_title_font
 * site_title_font_size
 * site_title_text_transform
 * site_title_letter-spacing
 */
if ( ! empty( $listable_options['site_title_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['site_title_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	if ( isset( $listable_options['site_title_font_size'] ) ) {
		$current_value['font_size'] = $listable_options['site_title_font_size'];
		unset( $listable_options['site_title_font_size'] );
	}

	if ( ! empty( $listable_options['site_title_text_transform'] ) ) {
		$current_value['text_transform'] = $listable_options['site_title_text_transform'];
		unset( $listable_options['site_title_text_transform'] );
	}

	if ( isset( $listable_options['site_title_letter-spacing'] ) ) {
		$current_value['letter_spacing'] = (float) $listable_options['site_title_letter-spacing'] / 16;
		unset( $listable_options['site_title_letter-spacing'] );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['site_title_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the navigation_font.
 *
 * These are the separate fields:
 * site_title_font
 * site_title_font_size
 * site_title_text_transform
 * site_title_letter-spacing
 */
if ( ! empty( $listable_options['navigation_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['navigation_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	if ( isset( $listable_options['navigation_font_size'] ) ) {
		$current_value['font_size'] = $listable_options['navigation_font_size'];
		unset( $listable_options['navigation_font_size'] );
	}

	if ( ! empty( $listable_options['navigation_text_transform'] ) ) {
		$current_value['text_transform'] = $listable_options['navigation_text_transform'];
		unset( $listable_options['navigation_text_transform'] );
	}

	if ( isset( $listable_options['navigation_letter-spacing'] ) ) {
		$current_value['letter_spacing'] = (float) $listable_options['navigation_letter-spacing'] / 16;
		unset( $listable_options['navigation_letter-spacing'] );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['navigation_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the body_font.
 */
if ( ! empty( $listable_options['body_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['body_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['body_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the page_titles_font.
 */
if ( ! empty( $listable_options['page_titles_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['page_titles_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['page_titles_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the page_subtitles_font.
 */
if ( ! empty( $listable_options['page_subtitles_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['page_subtitles_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['page_subtitles_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the card_title_font.
 *
 * These are the separate fields:
 * card_title_font
 * card_title_font_size
 * card_title_text_transform
 * card_title_letter-spacing
 */
if ( ! empty( $listable_options['card_title_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['card_title_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	if ( isset( $listable_options['card_title_font_size'] ) ) {
		$current_value['font_size'] = $listable_options['card_title_font_size'];
		unset( $listable_options['card_title_font_size'] );
	}

	if ( ! empty( $listable_options['card_title_text_transform'] ) ) {
		$current_value['text_transform'] = $listable_options['card_title_text_transform'];
		unset( $listable_options['card_title_text_transform'] );
	}

	if ( isset( $listable_options['card_title_letter-spacing'] ) ) {
		$current_value['letter_spacing'] = (float) $listable_options['card_title_letter-spacing'] / 16;
		unset( $listable_options['card_title_letter-spacing'] );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['card_title_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the card_font.
 */
if ( ! empty( $listable_options['card_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['card_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['card_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

/**
 * Migrate the card_font.
 */
if ( ! empty( $listable_options['meta_font'] ) ) {
	//maybe we need to decode it
	$current_value = PixCustomifyPlugin::decodeURIComponent( $listable_options['meta_font'] );

	// If we've got a string then it is clear we need to decode it
	if ( ! is_array( $current_value ) ) {
		$current_value = json_decode( $current_value, true );
	}

	// Make sure it is an object from here going forward
	$current_value = (object) $current_value;

	// Save the new value.
	$listable_options['meta_font'] = PixCustomifyPlugin::encodeURIComponent( json_encode( $current_value ) );
}

// Finally save the new options values.
set_theme_mod( 'listable_options', $listable_options );
