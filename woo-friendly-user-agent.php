<?php
/**
* Plugin Name: Friendly User Agent for WooCommerce
* Plugin URI:
* Description: Show the order user agent in a user friendly view on the orders page.
* Version: 1.3.0
* Tested up to: 6.0
* Requires PHP: 5.6
* WC requires at least: 3.0.0
* WC tested up to: 6.6.1
* Author: Blaze Concepts
* Author URI: https://www.blazeconcepts.co.uk/
*
* Text Domain: woo-friendly-user-agent
*
*/
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Enable Languages
 *
 * @return void
 */
add_action( 'init', 'blz_fua_load_plugin_textdomain' );

function blz_fua_load_plugin_textdomain() {
	$domain = 'woo-friendly-user-agent';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

include_once dirname( __FILE__ ) . '/parse-user-agent.php';

/**
 * Add Friendly User Agent to single order page
 *
 * @param [object] $order
 * @return void
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'blz_fua_show_user_agent_admin_order' );

function blz_fua_show_user_agent_admin_order($order) {

  $agent = $order->get_customer_user_agent();

  if ( !isset($agent) || empty($agent) ) {
    // Not set don't output
  } else {
    echo '<div class="single_order_ua"><strong>'.__('Customer User Agent','woo-friendly-user-agent').':</strong><br> '.blz_fua_getFriendlyAgent($agent).'</div>';
  }

}

/**
 * Add Friendly User Agent column to orders list page
 *
 * @param [array] $columns
 * @return $new_columns
 */
add_filter('manage_edit-shop_order_columns', 'blz_fua_add_order_agent_column_header', 20);

function blz_fua_add_order_agent_column_header( $columns ) {

  $new_columns = array();

  foreach ( $columns as $column_name => $column_info ) {

    $new_columns[ $column_name ] = $column_info;

    if ( 'order_total' === $column_name ) {
      $new_columns['order_agent'] = __( 'User Agent', 'woo-friendly-user-agent' );
    }
  }

  return $new_columns;
}

/**
 * Display Friendly User Agent in column on orders list page
 *
 * @param [string] $column
 * @return void
 */
add_action('manage_shop_order_posts_custom_column', 'blz_fua_add_order_agent_column_content');

function blz_fua_add_order_agent_column_content( $column ) {
  global $post;

  if ( 'order_agent' === $column ) {

    $order = wc_get_order( $post->ID );
    $agent = $order->get_customer_user_agent();

    if ( !isset($agent) || empty($agent) ) {
      $output = __('Not set','woo-friendly-user-agent');
    } else {
      $output = blz_fua_getFriendlyAgent($agent);
    }

    echo $output;
  }
}

/**
 * Get Friendly User Agent and return output
 *
 * @param [string] $agent
 * @return $output
 */
function blz_fua_getFriendlyAgent($agent) {
  $friendlyagent = blz_fua_parse_user_agent($agent);

    $output = '';

    if(isset($friendlyagent['platform']) && !empty($friendlyagent['platform'])){
        $output .= __('Platform','woo-friendly-user-agent').': '.$friendlyagent['platform'];
    } else {
        $output .= __('Platform: Not known','woo-friendly-user-agent');
    }

    if(isset($friendlyagent['imgplatform']) && !empty($friendlyagent['imgplatform'])){
        $output .= '<img src="'.$friendlyagent['imgplatform'].'" width="15" height="15" />';
    }
    $output .= '<br>';

    if(isset($friendlyagent['browser']) && !empty($friendlyagent['browser'])){
        $output .= __('Browser','woo-friendly-user-agent').': '.$friendlyagent['browser'];
    } else {
        $output .= __('Browser: Not known','woo-friendly-user-agent');
    }

    if(isset($friendlyagent['imgbrowser']) && !empty($friendlyagent['imgbrowser'])){
        $output .= '<img src="'.$friendlyagent['imgbrowser'].'" width="15" height="15" />';
    }
    $output .= '<br>';

    if(isset($friendlyagent['version']) && !empty($friendlyagent['version'])){
        $output .= __('Version','woo-friendly-user-agent').': '.$friendlyagent['version'];
    }

  return $output;
}

/**
 * Add admin CSS
 *
 * @return void
 */
add_action( 'admin_print_styles', 'blz_fua_add_order_agent_column_style' );

function blz_fua_add_order_agent_column_style() {
    $css = '.post-type-shop_order .wp-list-table td.column-order_agent { width: 9%; line-height: 20px; }
    .post-type-shop_order .wp-list-table td.column-order_agent img { vertical-align: middle; margin-left: 5px; }
    .post-type-shop_order .single_order_ua { line-height: 20px; }
    .post-type-shop_order .single_order_ua img { vertical-align: middle; margin-left: 5px; }';
    wp_add_inline_style( 'woocommerce_admin_styles', $css );
}