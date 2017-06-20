<?php
if ( ! class_exists( 'WC_Gateway_Lightrail' ) && class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_Lightrail extends WC_Payment_Gateway {

		public function __construct() {
			$this->id = WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME;
			//$this->icon               = plugins_url( 'img/lightrail.png', __FILE__ );
			$this->has_fields = true;
			//$this->method_title       = __( 'Lightrail', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE );
			//$this->method_description = __( 'Pay using a Lightrail gift code.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE );

			$this->init_form_fields();

			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			//$this->instructions = $this->get_option( 'instructions' );

			//add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}

		public function init_form_fields() {

			$this->form_fields = array(
				'enabled'         => array(
					'title'   => __( 'Enable/Disable', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Lightrail gift code payments', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'default' => 'yes',
				),
				'title'           => array(
					'title'       => __( 'Title', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'default'     => __( 'Gift Code', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'desc_tip'    => true,
				),
				'description'     => array(
					'title'       => __( 'Description', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'default'     => __( 'Pay using a gift code.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'desc_tip'    => true,
				),
//				'instructions'    => array(
//					'title'       => __( 'Instructions', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
//					'type'        => 'textarea',
//					'description' => __( 'Instructions that will be added to the thank you page and emails.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
//					'default'     => '',
//					'desc_tip'    => true,
//				),

				// API CREDENTIALS
				'api_credentials' => array(
					'title'       => __( 'API credentials', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'type'        => 'title',
					'description' => sprintf( __( 'Enter your Lightrail API key to handle Lightrail credit. Access your <a href="%s">Lightrail API Credentials</a> under Account Settings -> API.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), 'https://www.lightrail.com/app/#/login' ),
				),
				'api_key'         => array(
					'title'       => __( 'API key', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'type'        => 'textarea',
					'description' => __( 'Enter your API key from Lightrail.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'default'     => '',
					'desc_tip'    => true,
				),
			);
		}

		public function process_admin_options() {
			parent::process_admin_options();
			WC_Lightrail_Admin::lightrail_validate_api_key();
		}

//		/**
//		 * Output for the order received page.
//		 */
//		public function thankyou_page() {
//			if ( $this->instructions ) {
//				echo wpautop( wptexturize( $this->instructions ) );
//			}
//		}


		/*
		 *  customer-facing checkout fields.
		 */
		public function payment_fields() {
			if ( $description = $this->get_description() ) {
				echo wpautop( wptexturize( $description ) );
			}

			woocommerce_form_field( 'lightrail_gift_code', array(
					'title'       => __( 'Gift Code', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => 'GFBT-xxxxx-xxxxx-xxxxx-xxxxx',
					'label'       => __( 'Enter your Gift code here', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					'default'     => '',
				)
			);
		}

		public function process_payment( $order_id ) {

			try {

				$order = wc_get_order( $order_id );

				//record the original total if it hasn't been set yet.
				// we need to record a copy of the original total because we may have to change the total in order to
				// get third-party gateway to charge for the remainder
				if ( ! WC_Lightrail_Metadata::is_order_original_total_set( $order ) ) {
					WC_Lightrail_Metadata::set_order_original_total( $order, $order->get_total() );
				}

				$total = WC_Lightrail_Metadata::get_order_balance( $order );

				//the gift code is passed on as a post request param
				$code = $_POST['lightrail_gift_code'];

				$available_credit_object = WC_LightrailEngine::get_available_credit( $code, $this->get_option( 'api_key' ) );

				$code_currency  = $available_credit_object[ WC_Lightrail_API_Constants::TRANSACTION_CURRENCY ];
				$order_currency = get_option( 'woocommerce_currency' );
				if ( $code_currency !== $order_currency ) {
					$error_msg = sprintf( __( 'Currency mismatch. Attempting to pay %s value with %s.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
						$order_currency, $code_currency );
					throw new Exception( $error_msg );
				}

				$code_current_value = $available_credit_object[ WC_Lightrail_API_Constants::TRANSACTION_CURRENT_VALUE ];
				$available_credit   = WC_Lightrail_Currency::lightrail_currency_minor_to_major( $code_current_value, $order_currency );
				if ( ! ( $available_credit > 0 ) ) {
					$error_msg = __( 'Insufficient value on the gift code.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE );
					write_log( 'Insufficient value on gift code. ' . $available_credit );
					wc_add_notice( $error_msg, 'error' );

					return;
				}

				//if there is not enough credit we will pay what we can with the code and go back to the payment page for paying the remainder
				$amount_to_charge = ( $available_credit < $total ) ? $available_credit : $total;

				WC_Lightrail_Transactions::post_pending_payment_transaction_by_code( $order, $code, $amount_to_charge, $order_currency );
				$new_balance = WC_Lightrail_Metadata::get_order_balance( $order );


				if ( $new_balance == 0 ) { //we're done paying; adjust total and go to success page.
					try {
						WC_Lightrail_Transactions::capture_all_pending_transactions( $order );

					} catch ( Throwable $exception ) {
						$order->set_status( 'failed', 'Failed to capture some gift code payments: ' . $exception->getMessage() );
						$order->save();

						return array(
							'result'   => 'success', //so that the user can navigate away from the page
							'redirect' => $this->get_return_url( $order ),
						);
					} finally {
						$order->set_total( WC_Lightrail_Metadata::get_order_original_total( $order ) );
						$order->save();
						WC()->cart->empty_cart();
					}
					$order->payment_complete();

					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);
				} else { //we're not done paying. go back to the payment page to pay the remaining balance.
					write_log( sprintf( 'remaining balance on order #%s: %s', $order->get_id(), WC_Lightrail_Metadata::get_order_balance( $order ) ) );
					$order->set_total( WC_Lightrail_Metadata::get_order_balance( $order ) );
					$order->save();

					return array(
						'result'   => 'success',
						'redirect' => $order->get_checkout_payment_url()
					);
				}

			} catch ( Throwable $e ) {
				wc_add_notice( __( 'Error occurred in processing this payment. Please try again or choose another payment method.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), 'error' );
				write_log( $e->getMessage() );

				return; //stay on the page
			}
		}

//		enable when partial refund is supported
//		public function process_refund( $order_id, $amount = null, $reason = '' ) {
//			//we don't support automatic partial refunds
//		}
	}
}