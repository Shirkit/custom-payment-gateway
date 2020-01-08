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
	$styles['custom_gayeways'] = plugin_dir_url( __FILE__ ) . '/assets/register.css';
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

				$fee->set_amount( $value );
				$fee->set_total( $value );

				$order->calculate_totals();
			}
		}
	}
}

add_action( 'woocommerce_order_before_calculate_taxes', 'calculate_taxes', 10, 2 );
function calculate_taxes($args, $order) {
	$gt = new WC_Gateway_Orquidario_Consignado();
	if ($order->get_payment_method() == $gt->id) {
		foreach (WC()->payment_gateways()->payment_gateways as $gateway) {
			if ($gateway->id == $gt->id) {
				if ($gateway->apply_on_edit == 'yes') {

					$found = false;
					$value = 0;

					if (strpos($gateway->fee, '%') !== false) {
						$value = (floatval( trim( $gateway->fee, '%' ) ) / 100) * ($order->get_subtotal() - $order->get_total_discount(false) - $order->get_total_refunded());
					} else {
						$value = floatval( $gateway->fee );
					}

					foreach ($order->get_fees() as $fee) {
						if (strcasecmp($fee->get_name(), 'consignado') == 0 ) {
							$found = true;

							$fee->set_amount( $value );
							$fee->set_total( $value );

							break;
						}
					}
					if (!$found) {
						// TODO: the code is duplicated here from the Class Consignado
						// This only triggers if it's Consignado and it doesn't have a Fee called Consignado

						// Get the customer country code
						$country_code = $order->get_shipping_country();

						// Set the array for tax calculations
						$calculate_tax_for = array(
							'country' => $country_code,
							'state' => '',
							'postcode' => '',
							'city' => ''
						);

						// Get a new instance of the WC_Order_Item_Fee Object
						$item_fee = new WC_Order_Item_Fee();

						$item_fee->set_name( "Consignado" ); // Generic fee name
						$item_fee->set_amount( $value ); // Fee amount
						$item_fee->set_tax_class( '' ); // default for ''
						$item_fee->set_tax_status( 'taxable' ); // or 'none'
						$item_fee->set_total( $value ); // Fee amount

						// Calculating Fee taxes
						$item_fee->calculate_taxes( $calculate_tax_for );

						// Add Fee item to the order
						$order->add_item( $item_fee );

						$order->calculate_totals();
					}
				}
				break;
			}
		}
	}
}
?>
