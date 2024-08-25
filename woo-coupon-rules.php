<?php
/**
 * Plugin Name: Coupon Rules
 * Description: Add custom coupon code functionality to give discount based on coupon code
 * Version: 1.0.4
 * Text Domain: coupon-rules
 * Author: msanjay23
 * Author URI: https://profiles.wordpress.org/msanjay23/
 * License: GPLv3
 * 
 * @package Coupon Rules
 */

// Exit if accessed directly 
if( !defined( 'ABSPATH' ) ) exit; 

/**
 * Basic plugin definitions 
 * 
 * @package 
 * @since 1.0.3
 */
if( !defined( 'WOO_COUPON_RULES_VERSION' ) ) {
	define( 'WOO_COUPON_RULES_VERSION', '1.0.3' ); // version of plugin
}
if( !defined( 'WOO_COUPON_RULES_DIR' ) ) {
	define( 'WOO_COUPON_RULES_DIR', dirname(__FILE__) ); // plugin dir
}
if( !defined( 'WOO_COUPON_RULES_PLUGIN_BASENAME' ) ) {
	define( 'WOO_COUPON_RULES_PLUGIN_BASENAME', basename( WOO_COUPON_RULES_DIR ) ); //Plugin base name
}
if( !defined( 'WOO_COUPON_RULES_URL' ) ) {
	define( 'WOO_COUPON_RULES_URL', plugin_dir_url(__FILE__) ); // plugin url
}
if( !defined( 'WOO_COUPON_RULES_INCLUDE_DIR' ) ) {
	define( 'WOO_COUPON_RULES_INCLUDE_DIR', WOO_COUPON_RULES_DIR . '/includes/' ); 
}
if( !defined( 'WOO_COUPON_RULES_INCLUDE_URL' ) ) {
	define( 'WOO_COUPON_RULES_INCLUDE_URL', WOO_COUPON_RULES_URL . 'includes/' ); // plugin include url
}
if( !defined( 'WOO_COUPON_RULES_ADMIN_DIR' ) ) {
	define( 'WOO_COUPON_RULES_ADMIN_DIR', WOO_COUPON_RULES_DIR . '/includes/admin' ); // plugin admin dir 
}

/**
 * Load Text Domain
 * 
 * This gets the plugin ready for translation.
 */
function woo_coupon_rules_load_textdomain() {
	
	// Set filter for plugin's languages directory
	$lang_dir	= dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$lang_dir	= apply_filters( 'woo_coupon_rules_languages_directory', $lang_dir );
	
	// Traditional WordPress plugin locale filter
	$locale	= apply_filters( 'plugin_locale',  get_locale(), 'coupon-rules' );
	$mofile	= sprintf( '%1$s-%2$s.mo', 'coupon-rules', $locale );
	
	// Setup paths to current locale file
	$mofile_local	= $lang_dir . $mofile;
	$mofile_global	= WP_LANG_DIR . '/' . WOO_COUPON_RULES_PLUGIN_BASENAME . '/' . $mofile;
	
	if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/coupon-rules folder
		load_textdomain( 'coupon-rules', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) { // Look in local /wp-content/plugins/coupon-rules/languages/ folder
		load_textdomain( 'coupon-rules', $mofile_local );
	} else { // Load the default language files
		load_plugin_textdomain( 'coupon-rules', false, $lang_dir );
	}	
}

/**
 * Load Plugin
 */
function woo_coupon_rules_plugin_loaded() {
	woo_coupon_rules_load_textdomain();
}
add_action( 'plugins_loaded', 'woo_coupon_rules_plugin_loaded' );


/**
 * Declaration of global variable
 */ 
global $woo_coupon_rules;

include_once( WOO_COUPON_RULES_INCLUDE_DIR . '/class-woo-coupon-rules.php' );
$woo_coupon_rules = new Woo_Coupon_Rules();

include_once( WOO_COUPON_RULES_ADMIN_DIR . '/class-woo-coupon-rules-admin.php' );
$woo_coupon_rules_admin = new Woo_Coupon_Rules_Admin();

