<?php

if ( ! class_exists( 'WC_Lightrail_Metadata' ) ) {
	class WC_Lightrail_Metadata {

		public static function get_order_transactions( WC_Order $order ) {
			if ( isset( $order ) ) {
				$partial_payment_string = $order->get_meta( WC_Lightrail_Metadata_Constants::TRANSACTIONS_METADATA_KEY, true );
				if ( isset( $partial_payment_string ) ) {
					$partial_payment_object_array = json_decode( $partial_payment_string, true );
				}
			}

			return $partial_payment_object_array ?? array();
		}

		public static function get_order_transactions_in_which( $order, $include ) {
			$filtered_objects_array = self::get_order_transactions( $order );
			foreach ( $include as $key => $value ) {
				$filtered_objects_array = array_filter( $filtered_objects_array, function ( $object ) use ( $key, $value ) {
					return isset( $object[$key] ) && $object[$key] === $value;
				} );
			}

			return $filtered_objects_array ?? array();
		}

		static function filter_transactions_that_count_towards_balance( $object ) {
			return ( ! in_array( $object[WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS],
				array( WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_VOIDED,
					WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE ) ) );
		}

		static function transaction_value_sum( $carry, $transaction_object ) {
			return $carry + $transaction_object[WC_Lightrail_Metadata_Constants::TRANSACTION_VALUE];
		}


		/**
		 * The structure of the $payment_object array should be as the following:
		 * payment_object = array(
		 * 'transaction_id'   => /transaction id for this payment/, this is a required key and the function will ignore arrays that don't have this key. the values must be unique
		 * 'payment_method'   => 'lightrail',
		 * 'value'            => /amount/,
		 * 'type'             => /'PAYMENT'|'REFUND'/,
		 * 'note'             => /some note/,
		 * 'raw'              => / the raw object of the transaction result or other metadata to be stored/,
		 * 'status'           => /'CAPTURED'/'PENDING'/'PENDING_TO_CAPTURE'/'PENDING_TO_VOID'/'CAPTURED_TO_REFUND'/'REFUNDED'/'VOIDED'
		 * );
		 */
		public static function add_order_transaction( $order, $payment_object ) {
			$payments_metadata_string = $order->get_meta( WC_Lightrail_Metadata_Constants::TRANSACTIONS_METADATA_KEY );

			if ( isset( $payments_metadata_string ) ) {
				$payments_array = json_decode( $payments_metadata_string, true );
			}

			if ( ! isset( $payments_array ) ) {
				$payments_array = array();
			}

			$transaction_id = $payment_object[WC_Lightrail_Metadata_Constants::TRANSACTION_ID];
			if ( isset( $transaction_id ) ) {
				$payments_array[$transaction_id] = $payment_object;
				$order->add_meta_data( WC_Lightrail_Metadata_Constants::TRANSACTIONS_METADATA_KEY, json_encode( $payments_array ), true );
				$order->save();
			}
		}


		public static function get_order_transactions_total( $order ) {
			$order_transactions_array = array_filter( self::get_order_transactions( $order ),
				'WC_Lightrail_Metadata::filter_transactions_that_count_towards_balance' );
			$total = array_reduce( $order_transactions_array, 'WC_Lightrail_Metadata::transaction_value_sum', 0 );

			return $total;
		}


		public static function get_order_payments_total( $order ) {
			$order_transactions_array = array_filter( self::get_order_transactions_in_which( $order,
				array( WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_PAYMENT ) ),
				'WC_Lightrail_Metadata::filter_transactions_that_count_towards_balance' );
			return array_reduce( $order_transactions_array, 'WC_Lightrail_Metadata::transaction_value_sum', 0 );
		}


		public static function get_order_refunds_total( $order ) {
			$order_transactions_array = array_filter( self::get_order_transactions_in_which( $order,
				array( WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE => WC_Lightrail_Metadata_Constants::TRANSACTION_TYPE_REFUND ) ),
				'WC_Lightrail_Metadata::filter_transactions_that_count_towards_balance' );

			return array_reduce( $order_transactions_array, 'WC_Lightrail_Metadata::transaction_value_sum', 0 );
		}


		public static function get_order_original_total( $order ) {
			if ( isset( $order ) ) {
				if ( $order->meta_exists( WC_Lightrail_Metadata_Constants::ORIGINAL_TOTAL_METADATA_KEY ) ) {
					return $order->get_meta( WC_Lightrail_Metadata_Constants::ORIGINAL_TOTAL_METADATA_KEY );
				} else {
					return $order->get_total();
				}
			}
		}

		public static function is_order_original_total_set( $order ) {
			if ( isset( $order ) ) {
				return $order->meta_exists( WC_Lightrail_Metadata_Constants::ORIGINAL_TOTAL_METADATA_KEY );
			}
		}

		public static function set_order_original_total( $order, $value ) {
			if ( isset( $order ) ) {
				$order->add_meta_data( WC_Lightrail_Metadata_Constants::ORIGINAL_TOTAL_METADATA_KEY, $value, true );
				$order->save();
			}
		}

		public static function set_order_original_status( $order, $status ) {
			if ( isset( $order ) ) {
				$order->add_meta_data( WC_Lightrail_Metadata_Constants::ORIGINAL_STATUS_METADATA_KEY, $status, true );
				$order->save();
			}
		}

		public static function get_order_original_status( $order ) {
			if ( isset( $order ) ) {
				return $order->get_meta( WC_Lightrail_Metadata_Constants::ORIGINAL_STATUS_METADATA_KEY ) ?? '';
			}
		}

		public static function get_order_balance( $order ) {
			if ( isset( $order ) ) {
				return self::get_order_original_total( $order ) - self::get_order_transactions_total( $order );
			}
		}

		public static function get_order_number_of_failed_transactions( $order ) {
			$pending_to_void_transactions = self::get_order_transactions_in_which( $order,
				array( WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_VOID ) );
			$pending_to_capture_transactions = self::get_order_transactions_in_which( $order,
				array( WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_PENDING_TO_CAPTURE ) );
			$captured_to_refund_transactions = self::get_order_transactions_in_which( $order,
				array( WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS => WC_Lightrail_Metadata_Constants::TRANSACTION_STATUS_CAPTURED_TO_REFUND ) );

			return count( $pending_to_capture_transactions ) + count( $pending_to_void_transactions ) + count( $captured_to_refund_transactions );
		}
	}
}


