<?php
/**
 * Plugin Name:       Custom Payment Gateway
 * Plugin URI:        https://github.com/Shirkit/custom-payment-gateway
 * Description:       Adds more payment gateways for POS System.
 * Version:           1.0.5
 * Author:            Shirkit
 * License:           MIT License
 * License URI:       https://raw.githubusercontent.com/Shirkit/custom-payment-gateway/master/LICENSE
 * GitHub Plugin URI: https://github.com/Shirkit/custom-payment-gateway
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


add_filter('wc_pos_enqueue_styles', 'custom_payment_gateway_styles', 10, 1);

function custom_payment_gateway_styles($styles) {
	$styles['coupon_presets'] = plugin_dir_url( __FILE__ ) . '/assets/register.css';
	return $styles;
}

add_action( 'woocommerce_order_refunded', 'process_refunds', 10, 2 );
function process_refunds( $order_id, $refunds_id ) {
	$gcfee = '';
	$order = wc_get_order( $order_id );
	$refund = wc_get_order( $refunds_id );
	$fees = $order->get_fees();

	$gateways = WC()->payment_gateways()->payment_gateways();
	if (array_key_exists('consignado_orquidario', $gateways)) {
		$gcfee = WC()->payment_gateways()->payment_gateways()['consignado_orquidario']->get_option('fee');
	}

	foreach ($fees as $fee) {
		if (strcasecmp($fee->get_name(), 'consignado') == 0) {
			if (!empty($gcfee) && !empty(trim($gcfee)) ) {

				$value = 0;

				if (strpos($gcfee, '%') !== false) {
					$value = (floatval( trim( $gcfee, '%' ) ) / 100) * ($order->get_subtotal() - $order->get_total_discount(false) - abs($refund->get_total()));
				} else {
					$value = floatval( $gcfee );
				}
				error_log('');
				error_log('refund');
				error_log( floatval( trim( $gcfee, '%' ) ) / 100 );
				error_log( $order->get_subtotal() );
				error_log( $order->get_total_discount(false) );
				error_log( $refund->get_total() );
				error_log( $value );

				$fee->set_amount( $value );
				$fee->set_total( $value );

				$order->calculate_totals();
			}
		}
	}
}

?>
