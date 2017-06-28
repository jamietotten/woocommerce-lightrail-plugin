<?php
if ( ! class_exists( 'WC_Lightrail_Transactions' ) ) {
	class WC_Lightrail_Transactions {
		public static function get_lightrail_api_key() {
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( ! isset( $available_gateways [WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME] ) ) {
				write_log( 'Error while looking for lightrail API key: lightrail payment gateway cannot be found.' );
				throw new Exception( __( 'Cannot find Lightrail gateway.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
			}

			return $available_gateways [WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME]->get_option( 'api_key' );
		}

		private static function get_transaction_server_side_metadata_note( $order ) {
			$metadata_string = sprintf( '{"giftbit-note": {"note":"WooCommerce Order %s"}}', $order->get_id() );

			return json_decode( $metadata_string, true );
		}

		private static function link_to_card_page_on_lightrail_webapp( $display_text, $card_id ) {
			$link = sprintf( WC_Lightrail_API_Configs::WEB_APP_CARD_DETAILS_URL, $card_id );

			return sprintf( '<a href="%s">%s</a>', $link, $display_text );
		}

		public static function get_gift_code_balance( $code, $order_currency ) {
			$lightrail_api_key = self::get_lightrail_api_key();
			$available_credit_object = WC_LightrailEngine::get_available_credit( $code, $lightrail_api_key );
			$code_currency = $available_credit_object[WC_Lightrail_API_Constants::CODE_CURRENCY];
			if ( $order_currency !== $code_currency ) {
				throw new Exception( sprintf(
					__( 'Currency mismatch. Attempting to pay %s value with %s.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					$order_currency,
					$code_currency ) );
			}

			$code_principal = $available_credit_object[WC_Lightrail_API_Constants::CODE_PRINCIPAL]?? array();
			$code_state = $code_principal [WC_Lightrail_API_Constants::CODE_STATE] ?? '';
			if ( WC_Lightrail_API_Constants::CODE_STATE_ACTIVE !== $code_state ) {
				throw new Exception( __( 'This gift code is not active.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
			}

			$code_principal_balance = $code_principal [WC_Lightrail_API_Constants::CODE_CURRENT_VALUE] ?? 0;

			$value_attachments = $available_credit_object [WC_Lightrail_API_Constants::CODE_ATTACHED] ?? array();

			$active_value_attachments = array_filter( $value_attachments, function ( $attachment ) {
				return $attachment[WC_Lightrail_API_Constants::CODE_STATE] === WC_Lightrail_API_Constants::CODE_STATE_ACTIVE;
			} );
			$code_available_balance = array_reduce( $active_value_attachments,
				function ( $carry, $attachment ) {
					return $carry + $attachment[WC_Lightrail_API_Constants::CODE_CURRENT_VALUE];
				},
				$code_principal_balance );

			$code_available_balance = WC_Lightrail_Currency::lightrail_currency_minor_to_major( $code_available_balance, $code_currency );
			if ( 0 == $code_available_balance ) {
				throw new Exception( sprintf(
					__( 'The gift code does not have any value available.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE )
				) );
			}

			return $code_available_balance;
		}

		public static function post_pending_payment_transaction_by_code( $order, $code, $amount, $currency ) {
			$lightrail_api_key = self::get_lightrail_api_key();
			$transaction_result_object = WC_LightrailEngine::post_transaction_by_code(
				$code,
				( 0 - WC_Lightrail_Currency::lightrail_currency_major_to_minor( $amount, $currency ) ),
				$currency,
				uniqid( 'woo_' ),
				$lightrail_api_key,
				self::get_transaction_server_side_metadata_note( $order ) );
			$transaction_id = $transaction_result_object[WC_Lightrail_API_Constants::TRANSACTION_ID];
			$card_id = $transaction_result_object[WC_Lightrail_API_Constants::TRANSACTION_CARD_ID];

			$amount_paid = 0 - WC_Lightrail_Currency::lightrail_currency_minor_to_major( $transaction_result_object[WC_Lightrail_API_Constants::TRANSACTION_VALUE], $currency );
			$code_rep = '****' . substr( $code, -4 );

			//create a pending payment record object and add it to the order
			$payment_transaction_object = array(
				WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD => WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME,
				WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE          => $amount_paid,
				WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE           => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				WC_Lightrail_Metadata_Constants::TRANSACTION_NOTE           => 'gift code ' . $code_rep,
				WC_Lightrail_Metadata_Constants::TRANSACTION_ID             => $transaction_id,
				WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT     => $transaction_result_object,
				WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS         => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING,
			);
			$order->add_order_note( sprintf( __( 'Pending charge of %s on gift code %s - Transaction ID: %s',
				WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
				wc_price( $amount_paid ),
				$code_rep,
				self::link_to_card_page_on_lightrail_webapp( $transaction_id, $card_id ) ) );
			WC_Lightrail_Metadata::add_order_transaction( $order, $payment_transaction_object );
			$order->save();

			return $transaction_result_object;
		}

		public static function capture_pending_payment_transaction( $order, $pending_transaction_object ) {
			$lightrail_api_key = self::get_lightrail_api_key();
			$original_transaction_object = $pending_transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT];
			$capture_result = WC_LightrailEngine::capture_pending_transaction( $original_transaction_object, uniqid( 'woo_' ), $lightrail_api_key );


			$card_id = $capture_result[WC_Lightrail_API_Constants::TRANSACTION_CARD_ID];

			$pending_transaction_object [WC_Lightrail_Metadata_Constants::TRANSACTION_ORIGINAL_TRANSACTION] = $original_transaction_object;
			$pending_transaction_object [WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT] = $capture_result;
			$pending_transaction_object [WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED;
			WC_Lightrail_Metadata::add_order_transaction( $order, $pending_transaction_object );
			$order->add_order_note( sprintf( __( 'Finalized payment of %s on gift code ****%s - Transaction ID: %s - Original Pending Transaction ID: %s', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					wc_price( $pending_transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE] ),
					$original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_CODE_LAST_FOUR],
					self::link_to_card_page_on_lightrail_webapp( $capture_result[WC_Lightrail_API_Constants::TRANSACTION_ID], $card_id ),
					self::link_to_card_page_on_lightrail_webapp( $original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_ID], $card_id ) )
			);

			return $capture_result;
		}

		public static function void_pending_payment_transaction( $order, $pending_transaction_object ) {

			$lightrail_api_key = self::get_lightrail_api_key();

			$original_transaction_object = $pending_transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT];
			$void_result = WC_LightrailEngine::void_pending_transaction( $original_transaction_object, uniqid( 'woo_' ), $lightrail_api_key );
			$card_id = $void_result[WC_Lightrail_API_Constants::TRANSACTION_CARD_ID];

			$pending_transaction_object [WC_Lightrail_Metadata_Constants::TRANSACTION_ORIGINAL_TRANSACTION] = $original_transaction_object;
			$pending_transaction_object [WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT] = $void_result;
			$pending_transaction_object [WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_VOIDED;
			WC_Lightrail_Metadata::add_order_transaction( $order, $pending_transaction_object );
			$order->add_order_note( sprintf( __( 'Cancelled payment of %s on gift code ****%s - Transaction ID: %s - Original Pending Transaction ID: %s', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					wc_price( $pending_transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE] ),
					$original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_CODE_LAST_FOUR],
					self::link_to_card_page_on_lightrail_webapp( $void_result[WC_Lightrail_API_Constants::TRANSACTION_ID], $card_id ),
					self::link_to_card_page_on_lightrail_webapp( $original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_ID], $card_id )
				)
			);

			return $void_result;
		}

		public static function refund_captured_transaction( $order, $transaction_object ) {
			$lightrail_api_key = self::get_lightrail_api_key();

			$original_transaction_object = $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT];
			$refund_result_object = WC_LightrailEngine::refund_transaction( $original_transaction_object, uniqid( 'woo_' ), $lightrail_api_key );
			$refund_value = WC_Lightrail_Currency::lightrail_currency_minor_to_major( $refund_result_object[WC_Lightrail_API_Constants::TRANSACTION_VALUE], get_option( 'woocommerce_currency' ) ); //todo: fix this. currency should come from the transaction object
			$card_id = $refund_result_object[WC_Lightrail_API_Constants::TRANSACTION_CARD_ID];
			$refund_object = array(
				WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD          => WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME,
				WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE                   => 0 - $refund_value,
				WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE                    => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_REFUND,
				WC_Lightrail_Metadata_Constants::TRANSACTION_NOTE                    => sprintf( __( 'gift code ****%s', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $refund_result_object['codeLastFour'] ),
				WC_Lightrail_Metadata_Constants::TRANSACTION_ID                      => $refund_result_object['transactionId'],
				WC_Lightrail_Metadata_Constants::TRANSACTION_ORIGINAL_TRANSACTION_ID => $original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_ID],
				WC_Lightrail_Metadata_Constants::TRANSACTION_RAW_OBJECT              => $refund_result_object,
				WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS                  => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED,
			);
			WC_Lightrail_Metadata::add_order_transaction( $order, $refund_object );
			$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_REFUNDED;
			WC_Lightrail_Metadata::add_order_transaction( $order, $transaction_object );

			$order->add_order_note( sprintf( __( 'Refunded %s to gift code ****%s - Refund Transaction ID: %s - Original Transaction ID: %s', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					wc_price( $refund_value ),
					$original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_CODE_LAST_FOUR],
					self::link_to_card_page_on_lightrail_webapp( $refund_result_object[WC_Lightrail_API_Constants::TRANSACTION_ID], $card_id ),
					self::link_to_card_page_on_lightrail_webapp( $original_transaction_object[WC_Lightrail_API_Constants::TRANSACTION_ID], $card_id )
				)
			);

			return $refund_result_object;
		}

		public static function refund_third_party_transaction( $order, $transaction_object ) {
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			$payment_gateway = $available_gateways [$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD]];
			if ( isset( $payment_gateway ) ) //todo: test whether the gateway supports refund
			{
				write_log( sprintf( 'attempting a third-party gateway refund via %s', $payment_gateway->get_method_title() ) );
				$refund_value = $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE];
				$result = $payment_gateway->process_refund( $order->get_id(), $refund_value, __( 'Full refund requested via Lightrail.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
				if ( ! is_wp_error( $result ) ) {
					write_log( sprintf( 'Refund via %s result: %s', $payment_gateway->get_method_title(), $result ) );
					$refund_object = array(
						WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD          => $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD],
						WC_Lightrail_Metadata_Constants::TRANSACTION_ID                      => 'refund_of_' . $order->get_transaction_id(),
						WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD          => $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD],
						WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE                   => 0 - $refund_value,
						WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE                    => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_REFUND,
						WC_Lightrail_Metadata_Constants::TRANSACTION_ORIGINAL_TRANSACTION_ID => $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_ID],
						WC_Lightrail_Metadata_Constants::TRANSACTION_NOTE                    => sprintf( __( 'Refund for %s ', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $order->get_transaction_id() ),
						WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS                  => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED,
					);
					WC_Lightrail_Metadata::add_order_transaction( $order, $refund_object );
					$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_REFUNDED;
					WC_Lightrail_Metadata::add_order_transaction( $order, $transaction_object );


				} else {
					write_log( implode( " ", array(
						'WP_Error codes:',
						implode( ' ', $result->get_error_codes() ),
						'WP_Error messages:',
						implode( ' ', $result->get_error_messages() ),
					) ) );
					throw new Exception( implode( ' ', $result->get_error_messages() ) );
				}
			} else {
				throw new Exception( sprintf( __( 'Cannot find the original payment gateway %s for refund.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD] ) );
			}
		}

		public static function post_third_party_captured_transaction( $order, $payment_method, $amount, $transaction_id ) {
			$partial_payment_object = array(
				WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD => $payment_method,
				WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE          => $amount,
				WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE           => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				WC_Lightrail_Metadata_Constants::TRANSACTION_NOTE           => '',
				WC_Lightrail_Metadata_Constants::TRANSACTION_ID             => $transaction_id,
				WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS         => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED,
			);

			WC_Lightrail_Metadata::add_order_transaction( $order, $partial_payment_object );

		}

		public static function capture_all_pending_transactions( $order ) {
			$all_pending_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );
			$total_captured_transactions = 0;
			foreach ( $all_pending_transactions as $transaction_id => $transaction_object ) {
				//first save that we are going to capture. this help us recover if this fails.
				$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE;
				WC_Lightrail_Metadata::add_order_transaction( $order, $transaction_object );
			}

			//reload all the PENDING_TO_CAPTURE transactions to make sure we include anything remaining from past attempts.
			$all_pending_to_capture_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			try {
				foreach ( $all_pending_to_capture_transactions as $transaction_id => $transaction_object ) {
					self::capture_pending_payment_transaction( $order, $transaction_object );
					$total_captured_transactions++;
				}
			} catch ( Throwable $exception ) {
				write_log( 'Error in capturing some codes: ' . $exception->getMessage() );
				throw new Exception( 'Error occurred in finalizing some gift code payments. Please contact the store.' );
			}

			return $total_captured_transactions;
		}

		public static function void_all_pending_transactions( $order ) {

			$all_pending_payment_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );
			$total_voided_transactions = 0;

			//first save that we are going to capture. this help us recover if this fails.
			foreach ( $all_pending_payment_transactions as $transaction_id => $transaction_object ) {
				$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_VOID;
				WC_Lightrail_Metadata::add_order_transaction( $order, $transaction_object );
			}

			//reload the list of PENDING_TO_VOID to include the ones that might have failed previously.
			$all_pending_to_void_payment_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_VOID,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			try {
				foreach ( $all_pending_to_void_payment_transactions as $transaction_id => $transaction_object ) {
					self::void_pending_payment_transaction( $order, $transaction_object );
					$total_voided_transactions++;
				}
			} catch ( Throwable $exception ) {
				write_log( 'Error in voiding some codes: ' . $exception->getMessage() );
				throw new Exception( __( 'Error occurred in canceling some pending gift code payments. Please contact the store.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
			}

			return $total_voided_transactions;
		}

		public static function refund_all_transactions( $order ) {

			//if there has been any pending transaction that has failed to capture, we should just void it...
			$all_failed_to_capture_lightrail_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			//... so we change their statuses to PENDING so that they'll get voided in the next block.
			foreach ( $all_failed_to_capture_lightrail_transactions as $transaction_id => $transaction_object ) {
				$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING;
				WC_Lightrail_Metadata::add_order_transaction( $order, $transaction_object );
			}

			$total_number_of_voids_and_refunds = self::void_all_pending_transactions( $order );

			$all_captured_payment_lightrail_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			foreach ( $all_captured_payment_lightrail_transactions as $transaction_id => $transaction_object ) {
				$transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS] = WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED_TO_REFUND;
				WC_Lightrail_Metadata::add_order_transaction( $order, $transaction_object );
			}

			//reloading the list of payments to be refunded ensures that anything failed before will also be considered
			$all_captured_payments_to_be_refunded = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED_TO_REFUND,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			try {
				foreach ( $all_captured_payments_to_be_refunded as $transaction_id => $transaction_object ) {
					$payment_method = $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD];
					if ( $payment_method === WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME ) {
						self::refund_captured_transaction( $order, $transaction_object );
					} else {
						self::refund_third_party_transaction( $order, $transaction_object );
					}
					$total_number_of_voids_and_refunds++;
				}
			} catch ( Throwable $exception ) {
				write_log( 'Error in refunding some codes: ' . $exception->getMessage() );
				throw new Exception( __( 'Error occurred in refunding some gift code payments. Please contact the store.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
			}

			return $total_number_of_voids_and_refunds;
		}

		public static function retry_all_failed_transactions( $order ) {
			$number_of_fixes = 0;

			$pending_to_void_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_VOID,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			foreach ( $pending_to_void_transactions as $transaction_id => $transaction_object ) {
				self::void_pending_payment_transaction( $order, $transaction_object );
				$number_of_fixes++;
				$order->add_order_note( sprintf( __( 'Fixed transaction %s which had originally failed at cancelling.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $transaction_id ) );
			}

			$pending_to_capture_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			foreach ( $pending_to_capture_transactions as $transaction_id => $transaction_object ) {
				self::capture_pending_payment_transaction( $order, $transaction_object );
				$number_of_fixes++;
				$order->add_order_note( sprintf( __( 'Fixed transaction %s which had originally failed at finalizing.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $transaction_id ) );
			}

			$captured_to_refund_transactions = WC_Lightrail_Metadata::get_order_transactions_in_which( $order,
				array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED_TO_REFUND,
					WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE   => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT,
				) );

			foreach ( $captured_to_refund_transactions as $transaction_id => $transaction_object ) {
				if ( $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD] === WC_Lightrail_Plugin_Constants::LIGHTRAIL_PAYMENT_METHOD_NAME ) {
					self::refund_captured_transaction( $order, $transaction_object );
				} else {
					self::refund_third_party_transaction( $order, $transaction_object );
				}
				$number_of_fixes++;
				$order->add_order_note( sprintf( __( 'Fixed transaction %s which had originally failed at refund.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $transaction_id ) );
			}
			$order->save();//this is required for persisting the order notes; otherwise they will be lost.

			return $number_of_fixes;
		}

	}
}




