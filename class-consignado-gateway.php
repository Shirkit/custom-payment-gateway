<?php

class WC_Gateway_Orquidario_Consignado extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'consignado_orquidario';
        $this->icon               = apply_filters( 'woocommerce_cheque_icon', '' );
        $this->has_fields         = false;
        $this->method_title       = _x( 'Consignado', 'Venda consignada.', 'woocommerce' );
        $this->method_description = __( 'Venda consignada.', 'woocommerce' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->instructions = $this->get_option( 'instructions' );
        $this->fee          = $this->get_option( 'fee' );

        // Actions.
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

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
                'label'   => __( 'Habilitar pagamentos consignados', 'woocommerce' ),
                'default' => 'no',
            ),
            'title'        => array(
                'title'       => __( 'Title', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'     => _x( 'Consignado', 'Pagar consignado.', 'woocommerce' ),
                'desc_tip'    => true,
            ),
            'fee'        => array(
                'title'       => __( 'Fee', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Enter a fixed amount or percentage to apply as a fee.', 'woocommerce' ),
                'default'     => '20%',
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

            if (!empty($this->fee) && !empty(trim($this->fee)) ) {

              $value = 0;

              if (strpos($this->fee, '%') !== false) {
                $value = (floatval( trim( $this->fee, '%' ) ) / 100) * ($order->get_subtotal() - $order->get_total_discount(false));
              } else {
                $value = floatval( $this->fee );
              }

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

            $order->update_status( 'on-hold', 'Aguardando pagamento consignado.' );
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
