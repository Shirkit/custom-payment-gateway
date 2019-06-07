<?php
/**
 * Plugin Name:       Custom Payment Gateway
 * Plugin URI:        https://github.com/Shirkit/custom-payment-gayeway
 * Description:       A plugin to automatically update GitHub, Bitbucket or GitLab hosted plugins and themes. It also allows for remote installation of plugins or themes into WordPress.
 * Version:           1.0.4
 * Author:            Shirkit
 * License:           MIT License
 * License URI:       https://raw.githubusercontent.com/Shirkit/custom-payment-gayeway/master/LICENSE
 * GitHub Plugin URI: https://github.com/Shirkit/custom-payment-gayeway
 */

add_action( 'plugins_loaded', 'init_your_gateway_class' );
function init_your_gateway_class() {
	include_once('class-transfer-gateway.php');
	include_once('class-cheque-gateway.php');
	include_once('class-consignado-gateway.php');
	include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
	include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
}

function add_your_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Orquidario_Cheque'; 
	$methods[] = 'WC_Gateway_Orquidario_Consignado'; 
	$methods[] = 'WC_Gateway_Orquidario_Transfer'; 
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_your_gateway_class' );

?>

