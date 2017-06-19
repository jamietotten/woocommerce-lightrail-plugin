<?php

if ( ! class_exists( 'WC_Lightrail_Admin' ) ) {
	class WC_Lightrail_Admin {

		private static $LIGHTRAIL_TRANSACTION_STATUS_ADMIN_VIEW = null;

		private static function get_javascript_code_for_ajax_action( $action_name, $order_id, $ajax_call_url ) {
			$template = 'var data = {\'action\': \'%s\',\'order_id\': \'%s\'};
			jQuery.post(\'%s\', data, function(response) {
				if (response[\'data\'][\'reload\']) 
					location.reload(); 
				alert(response[\'data\'][\'message\']);
				});';

			return sprintf( $template, $action_name, $order_id, $ajax_call_url );
		}

		public static function init() {

			if ( WC_Lightrail_Admin::$LIGHTRAIL_TRANSACTION_STATUS_ADMIN_VIEW == null ) {
				WC_Lightrail_Admin::$LIGHTRAIL_TRANSACTION_STATUS_ADMIN_VIEW = array(
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_VOIDED             => __( '[Cancelled]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING            => __( '[Pending]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED           => __( '[Collected]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_REFUNDED           => __( '[Refunded]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_VOID    => __( '[Failed at Cancelling]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE => __( '[Failed at Collecting]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED_TO_REFUND => __( '[Failed at Refunding]', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ),
				);
			}


			add_action( 'woocommerce_admin_order_totals_after_total', 'WC_Lightrail_Admin::admin_order_totals_after_total_add_payments_and_refunds', 10, 1 );
			add_action( 'woocommerce_order_item_add_action_buttons', 'WC_Lightrail_Admin::order_item_add_action_buttons_frefund', 100, 1 );
			add_action( 'wp_ajax_frefund', 'WC_Lightrail_Admin::frefund_action' );
			add_action( 'woocommerce_order_item_add_action_buttons', 'WC_Lightrail_Admin::order_item_add_action_buttons_retry', 100, 1 );
			add_action( 'wp_ajax_retry', 'WC_Lightrail_Admin::retry_action' );


		}

		private static function get_admin_row_for_transaction_object( $order_transaction_object ) {
			$amount                    = $order_transaction_object[ WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE ];
			$transaction_status_string = WC_Lightrail_Admin::$LIGHTRAIL_TRANSACTION_STATUS_ADMIN_VIEW[ $order_transaction_object[ WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS ] ];
			$transaction_type_string   = ( $order_transaction_object[ WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE ] === WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_REFUND )
				? ' Refund via ' : ' ';
			$notes_string              = $order_transaction_object[ WC_Lightrail_Metadata_Constants::TRANSACTION_NOTE ] ?? '';
			$notes_string              = ( $notes_string !== '' ) ? ' (' . $notes_string . ')' : '';
			$payment_amount_string     = ( $order_transaction_object[ WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE ] === WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT )
				? '(' . wc_price( $amount ) . ')' : wc_price( 0 - $amount );

			echo '<tr>
						<td class="label">' . $transaction_status_string . $transaction_type_string . $order_transaction_object[ WC_Lightrail_Metadata_Constants::TRANSACTION_PAYMENT_METHOD ] . $notes_string . ':' . '</td><td width="1%"></td>
						<td class="total">' . $payment_amount_string . '</td>
				  </tr>';

		}

		public static function admin_order_totals_after_total_add_payments_and_refunds( $order_id ) {

			$order = wc_get_order( $order_id );

			$order_payments_array = WC_Lightrail_Metadata::get_order_transactions_in_which( $order, array( WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT ) );
			$order_refunds_array  = WC_Lightrail_Metadata::get_order_transactions_in_which( $order, array( WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_REFUND ) );

			write_log( sprintf( 'total of %d transactions on order %s', count( WC_Lightrail_Metadata::get_order_transactions( $order ) ), $order_id ) . json_encode( WC_Lightrail_Metadata::get_order_transactions( $order ) ) );

			foreach ( $order_payments_array as $order_transaction_object ) {
				WC_Lightrail_Admin::get_admin_row_for_transaction_object( $order_transaction_object );

			}
			if ( count( $order_payments_array ) != 0 ) {
				echo '<tr>
						<td class="label"><strong>' . __( 'Payments Total:', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) . '</strong></td><td width="1%"></td>
						<td class="total">' . '(' . wc_price( WC_Lightrail_Metadata::get_order_payments_total( $order ) ) . ')' . '</td>
			  	  </tr>';
			}
			foreach ( $order_refunds_array as $order_transaction_object ) {
				WC_Lightrail_Admin::get_admin_row_for_transaction_object( $order_transaction_object );
			}
			if ( count( $order_refunds_array ) != 0 ) {
				echo '<tr>
						<td class="label"><strong>' . __( 'Refunds Total:', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) . '</strong></td><td width="1%"></td>
						<td class="total">' . wc_price( 0 - WC_Lightrail_Metadata::get_order_refunds_total( $order ) ) . '</td>
		  		  </tr>';
			}

			if ( count( $order_refunds_array ) != 0 || count( $order_payments_array ) != 0 ) {
				echo '<tr>
				<td class="label"><strong>' . __( 'Balance:', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) . '</strong></td><td width="1%"></td>
				<td class="total">' . wc_price( WC_Lightrail_Metadata::get_order_balance( $order ) ) . '</td>
			  </tr>';
			}
		}

		public static function order_item_add_action_buttons_frefund( $order ) {
			$frefund_button_title = __( 'Full Refund via Lightrail', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE );
			$frefund_url          = wp_nonce_url( admin_url( 'admin-ajax.php' ), 'frefund_' . $order->get_id() );
			$onclick_java_script  = self::get_javascript_code_for_ajax_action( 'frefund', $order->get_id(), $frefund_url );
			echo '<button type="button" id= "lightrail-frefund-btn" class="button" onclick="' . $onclick_java_script . '">' . $frefund_button_title . '</button>';
		}

		public static function frefund_action() {
			$order_id = $_POST['order_id'];

			//verifying the nonce which is based on the action name and the order id to make sure
			check_admin_referer( 'frefund_' . $order_id );

			$order = wc_get_order( $order_id );

			$total_refunds_or_voids = 0;
			$should_be_reloaded     = false;

			try {
				$total_refunds_or_voids = WC_Lightrail_Transactions::refund_all_transactions( $order );
				$should_be_reloaded     = $should_be_reloaded || $total_refunds_or_voids > 0;

			} catch ( Throwable $exception ) {
				$order->add_order_note( sprintf( __( 'Could not refund some of the payments.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) ) );

				$response = array(
					'message' => sprintf( __( 'Error in refund process. You can safely retry a full refund. Error details: %s', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $exception->getMessage() ),
					'reload'  => true
				);
				write_log( 'Failed at refunding some transactions - Reason: ' . $exception->getMessage() );
				$order->set_status( 'failed', $note = 'Refund failed.' );
				$order->save();
				wp_send_json_error( $response );
			}

			if ( WC_Lightrail_Metadata::get_order_number_of_failed_transactions( $order ) === 0 && $order->get_status() === 'failed' ) {
				$order->set_status( 'processing', $note = 'Order refunded.' );
				$order->save();
				$should_be_reloaded = true;
			}

			$message  = ( $total_refunds_or_voids > 0 )
				? sprintf( __( '%s transactions refunded (or canceled if pending).', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $total_refunds_or_voids . '' )
				: sprintf( __( 'No new transactions refunded/canceled.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
			$response = array(
				'message' => $message,
				'refunds' => $total_refunds_or_voids . '',
				'reload'  => $should_be_reloaded
			);

			wp_send_json_success( $response, 200 );
		}

		public static function order_item_add_action_buttons_retry( $order ) {
			if ( WC_Lightrail_Metadata::get_order_number_of_failed_transactions( $order ) > 0 ) {
				$retry_button_title = __( 'Fix Failed Transctions', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE );
				$retry_url          = wp_nonce_url( admin_url( 'admin-ajax.php?action=retry&order_id=' . $order->get_id() ), 'retry_' . $order->get_id() );

				$onclick_java_script = self::get_javascript_code_for_ajax_action( 'retry', $order->get_id(), $retry_url );;
				echo '<button type="button" id= "lightrail-retry-btn" class="button" onclick="' . $onclick_java_script . '">' . $retry_button_title . '</button>';
			}
		}

		public static function retry_action() {
			//write_log( "lightrail_retry_action was called." );
			$order_id = $_POST['order_id'];

			check_admin_referer( 'retry_' . $order_id );

			$order = wc_get_order( $order_id );
			if ( WC_Lightrail_Metadata::get_order_number_of_failed_transactions( $order ) == 0 ) {
				$message  = __( 'There are no failed transactions on this order', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE );
				$response = array(
					'message' => $message,
					'fixes'   => 0 . '',
					'reload'  => false
				);
				wp_send_json_success( $response, 200 );
			}

			$total_transactions_fixed = 0;
			$should_be_reloaded       = false;

			try {
				$total_transactions_fixed = WC_Lightrail_Transactions::retry_all_failed_transactions( $order );
				$should_be_reloaded       = $should_be_reloaded || $total_transactions_fixed > 0;
			} catch ( Throwable $exception ) {
				$response = array(
					'message' => sprintf( __( 'Error occurred while trying to fix failed transactions. %s', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $exception->getMessage() ),
					'reload'  => true
				);
				write_log( 'Error occurred in the course of retrying.', $exception->getMessage() );
				wp_send_json_error( $response );
			}

			$message = ( $total_transactions_fixed > 0 )
				? sprintf( __( 'Fixed %s failed transaction(s).', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ), $total_transactions_fixed . '' )
				: sprintf( __( 'No new failed transactions were fixed.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );

			if ( WC_Lightrail_Metadata::get_order_number_of_failed_transactions( $order ) === 0 ) {
				$original_status = WC_Lightrail_Metadata::get_order_original_status( $order );
				$original_status = ( $original_status === '' ) ? 'processing' : $original_status;
				$order->set_status( $original_status, $note = __( 'Fixed all failed transactions.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );
				$order->save();
				$should_be_reloaded = true;
			}

			$response = array(
				'message' => $message,
				'fixes'   => $total_transactions_fixed . '',
				'reload'  => $should_be_reloaded
			);


			wp_send_json_success( $response, 200 );
		}

		public static function display_admin_error_notice( $message ) {
			$class           = 'notice notice-error';
			$display_message = $message;
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $display_message ) );
		}

		/** Check the API key's validity
		 * NOTE this function currently cannot be called with the 'woocommerce_payment_gateways' filter hook:
		 * it retrieves the API key via the OTB 'payment_gateways()' method which calls that hook when it is run (infinite loop)
		 */
		public static function lightrail_validate_api_key() {
			$api_key = WC_Lightrail_Transactions::get_lightrail_api_key();

			if ( ! isset( $api_key ) || $api_key === '' ) {
				WC_Lightrail_Admin::display_admin_error_notice( __( 'Lightrail is almost set up - please enter your API access token.', WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );

				return false;
			}

			try {
				$response = WC_LightrailEngine::api_ping( $api_key );
			} catch ( Throwable $e ) {
				write_log( 'API key is not okay: ' . $e->getMessage() );
				WC_Lightrail_Admin::display_admin_error_notice( __( "Lightrail: Check your API key - that one doesn't work", WC_Lightrail_Plugin_Constants::LIGHTRAIL_NAMESPACE ) );

				return false;
			}

			return true;

		}

	}
}

