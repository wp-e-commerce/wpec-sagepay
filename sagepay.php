<?php
/*
Plugin Name: WP eCommerce SagePay Gateway
Plugin URI: https://wpecommerce.org
Version: 1.0
Author: WP eCommerce
Description: A plugin that allows the store owner to process payments using SagePay
Author URI:  https://wpecommerce.org
*/

define( 'WPECSGP_VERSION', '1.0' );
define( 'WPECSGP_PRODUCT_ID', '' );

// Defines filter types used for a parameter in the cleanInput() function.
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC', 'clean_input_filter_alphabetic' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED', 'clean_input_filter_alphabetic_and_accented' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC', 'clean_input_filter_alphanumeric' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED', 'clean_input_filter_alphanumeric_and_accented' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_NUMERIC', 'clean_input_filter_numeric' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT', 'clean_input_filter_text' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_WIDEST_ALLOWABLE_CHARACTER_RANGE', 'clean_input_filter_text' );

if ( ! defined( 'WPECSGP_PLUGIN_DIR' ) ) {
	define( 'WPECSGP_PLUGIN_DIR', dirname( __FILE__ ) );
}
if ( ! defined( 'WPECSGP_PLUGIN_URL' ) ) {
	define( 'WPECSGP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

include_once( WPECSGP_PLUGIN_DIR . '/includes/functions.php' );

function wpec_sagepay_init() {
	include_once( WPECSGP_PLUGIN_DIR . '/class-sagepay.php');
}
add_action( 'wpsc_init', 'wpec_sagepay_init' );

if ( isset( $_GET['crypt'] ) && ( substr( $_GET['crypt'], 0, 1 ) === '@') ) {
  add_action('init', 'sagepay_process_gateway_info');
}

// register the gateway
function wpec_add_sagepay_gateway( $nzshpcrt_gateways ) {
	$num = count( $nzshpcrt_gateways ) + 1;
	
	$nzshpcrt_gateways[$num] = array(
		'name' => 'Sagepay',
		'api_version' => 2.0,
		'class_name' => 'wpec_merchant_sagepay',
		'has_recurring_billing' => false,
		'display_name' => 'Credit Card',	
		'wp_admin_cannot_cancel' => false,
		'requirements' => array(
			'php_version' => 5.0
		),
		'form' => 'wpec_sagepay_settings_form',
		'submit_function' => 'wpec_save_sagepay_settings',
		'internalname' => 'wpec_sagepay',
		'display_name' => "SagePay"
	);
	return $nzshpcrt_gateways; 
}
add_filter( 'wpsc_merchants_modules', 'wpec_add_sagepay_gateway', 100 );
?>