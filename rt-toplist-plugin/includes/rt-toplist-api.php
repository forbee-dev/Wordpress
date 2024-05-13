<?php
/**
 * Toplist API functions
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function fetch_api_data( $toplist_id = null) {
	$api_url = get_field('api_url', 'option');
	$api_token = get_field( 'api_token', 'option' );

	// Append the toplist ID to the URL if provided
	if ( !is_null( $toplist_id ) ) {
		$api_url .= $toplist_id;
	}

	$response = wp_remote_get( $api_url, array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $api_token
		)
	) );

	if ( is_wp_error( $response ) ) {
		error_log( 'Failed to fetch API data: ' . $response->get_error_message() );
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body );

	return json_encode( $data, JSON_PRETTY_PRINT );

}

/**
 * Update ACF toplist_json field with API data
 */
function update_acf_field_with_api_data( $toplist_id = null) {

	// Fetch data for the selected toplist
	$toplist_data_json = fetch_api_data( $toplist_id );
	$toplist_data = json_decode($toplist_data_json, true);

	// Process each row to extract needed information
	$processed_data = array_map( function ($row) {
		$redirect_links_processed = array_map( function ($redirect_link) {
			return [
				'id' => $redirect_link['id'],
				'media_key' => $redirect_link['media_key'],
				'request_status' => $redirect_link['request_status'],
				'affiliate_link' => $redirect_link['affiliate_link']['link'] ?? '',
			];
		}, $row['redirect_links'] );

		return [
			'brand' => $row['brand'],
			'subscription_status' => $row['subscription_status'],
			'program_status' => $row['program_status'],
			'bonuses' => $row['bonuses'],
			'redirect_links' => $redirect_links_processed,
			'licenses' => $row['licenses'],
			'logo' => $row['logo'],
		];
	}, $toplist_data['rows'] ?? [] );

	// Encode the processed data with JSON_PRETTY_PRINT for better readability
	return json_encode( $processed_data, JSON_PRETTY_PRINT );

}

/**
 * On Page load populate ACF toplists_market select field with API data
 */
add_filter( 'acf/load_field/name=toplists_market', 'populate_toplists_market_field' );
function populate_toplists_market_field( $field ) {
	$data_json = fetch_api_data();
	$data = json_decode( $data_json, true );

	// Check if data is not empty and is an array
	if ( !empty( $data ) && is_array( $data ) ) {
		// Reset choices
		$field['choices'] = array();

		// Prepend the "Select Market" option with an empty value
		$field['choices'][''] = 'Select Market';

		// Loop through the data and add each name as a choice
		foreach ( $data as $item ) {
			if ( isset( $item['name'] ) ) {
				// Use the name both as the key and the value for simplicity
				$field['choices'][ $item['name'] ] = $item['name'];
			}
		}
	}

	// Return the modified field
	return $field;
}

/**
 * AJAX handler for updating `toplists` options based on `toplists_market`
 */
function ajax_update_toplists_options() {
	// Verify nonce for security
	check_ajax_referer( 'toplist-ajax-nonce', 'security' );

	$selected_market = sanitize_text_field( $_POST['toplists_market'] );

	$data_json = fetch_api_data();
	$data = json_decode( $data_json, true );

	$options = [];
	if ( ! empty( $data ) && is_array( $data ) ) {
		foreach ( $data as $item ) {
			if ( isset( $item['name'] ) && $item['name'] === $selected_market ) {
				foreach ( $item['top_lists'] as $list ) {
					$options[ $list['id'] ] = $list['id'];
				}
				break;
			}
		}
	}

	wp_send_json_success( $options );

}
add_action( 'wp_ajax_update_toplists_field', 'ajax_update_toplists_options' );

/**
 * AJAX handler for updating the `toplist_json` ACF field based on selected `toplist`
 */
function ajax_update_toplist_json() {
	// Verify nonce for security
	check_ajax_referer( 'toplist-ajax-nonce', 'security' );

	$toplist_id = isset( $_POST['toplist_id'] ) ? $_POST['toplist_id'] : false;

	if ( $toplist_id ) {
		$processed_data = update_acf_field_with_api_data( $toplist_id );

		if ( $processed_data ) {

			wp_send_json_success( [ 'toplist_data_json' => $processed_data ] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to process toplist data.' ] );
		}
	} else {
		wp_send_json_success ( ['toplist_data_json' => ''] );
		wp_send_json_error( [ 'message' => 'Invalid toplist ID.' ] );
	}
}
add_action( 'wp_ajax_update_toplist_json_field', 'ajax_update_toplist_json' );