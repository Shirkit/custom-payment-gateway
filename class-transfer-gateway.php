<?php

class WC_Gateway_Orquidario_Transfer extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'transfer_orquidario';
		$this->icon               = apply_filters( 'woocommerce_cheque_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = _x( 'Transferência', 'Pagamento via Transferência', 'woocommerce' );
		$this->method_description = __( 'Aceitar pagamentos via Transferência.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_cheque', array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilitar pagamentos via transferência', 'woocommerce' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => _x( 'Transferência', 'Pagamento via Transferência', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}


	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if(!$screen || $screen->id !== 'pos_page'){
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'cheque' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Mark as on-hold (we're awaiting the cheque).
			$order->update_status( 'on-hold', 'Aguardando confirmação da transferência bancária.' );
		} else {
			$order->payment_complete();
		}

		// Don't need to remove the cart since this gateway is POS only
		// WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => '',
		);
	}
}

?>
