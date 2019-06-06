<?php
/**
 * Plugin Name:       Custom Payment Gateway
 * Plugin URI:        https://github.com/Shirkit/custom-payment-gayeway
 * Description:       A plugin to automatically update GitHub, Bitbucket or GitLab hosted plugins and themes. It also allows for remote installation of plugins or themes into WordPress.
 * Version:           1.0.0
 * Author:            Shirkit
 * License:           MIT License
 * License URI:       https://raw.githubusercontent.com/Shirkit/custom-payment-gayeway/master/LICENSE
 * GitHub Plugin URI: https://github.com/Shirkit/custom-payment-gayeway
 */

add_action( 'plugins_loaded', 'init_your_gateway_class' );
function init_your_gateway_class() {
    class WC_Gateway_Your_Gateway extends WC_Payment_Gateway {
		
		public function __construct() {
			$this->id = 'orquidario_cheque';
			//$this->icon
			$this->has_fields = false;
			$this->method_title = 'Cheque Próprio';
			$this->method_description = 'Forma de pagamento em Cheque';
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable Cheque Payment', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'woocommerce' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default' => __( 'Cheque Payment', 'woocommerce' ),
					'desc_tip'      => true,
				),
				'description' => array(
					'title' => __( 'Customer Message', 'woocommerce' ),
					'type' => 'textarea',
					'default' => ''
				)
			);
		}
		
		public function process_payment( $order_id ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );

			// Mark as on-hold (we're awaiting the cheque)
			$order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
	}
}

function add_your_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Your_Gateway'; 
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_your_gateway_class' );

?>

