<?php

if ( ! function_exists( 'write_log' ) ) {
    function write_log( $log ) {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}

if ( ! class_exists( 'WC_LightrailEngine' ) ) {
    class WC_LightrailEngine {

        public static function api_ping( string $api_key ) {
            $response = self::call_lightrail_api_with_headers( WC_Lightrail_API_Constants::ENDPOINT_PING, WC_Lightrail_API_Constants::HTTP_GET, $api_key );
            return self::handle_api_response( $response, WC_Lightrail_API_Constants::API_RESPONSE_KEY_USER );
        }

        public static function get_available_credit( string $code, string $api_key ) {
            $response = self::call_lightrail_api_with_headers( sprintf( WC_Lightrail_API_Constants::ENDPOINT_BALANCE, $code ), WC_Lightrail_API_Constants::HTTP_GET, $api_key );
            return self::handle_api_response( $response, WC_Lightrail_API_Constants::API_RESPONSE_KEY_BALANCE );
        }

        //NOTE parameter $amount must be negative
        public static function post_transaction_by_code( string $code, int $amount, string $currency, string $userSuppliedId, string $api_key, bool $pending = false, array $metadata = [] ) {
            // Optional crash, for testing purposes
            self::please_crash( __FUNCTION__ );

            //Business as usual
            $post_transaction_body = array(
                WC_Lightrail_API_Constants::TRANSACTION_USER_SUPPLIED_ID => $userSuppliedId,
                WC_Lightrail_API_Constants::TRANSACTION_VALUE => $amount,
                WC_Lightrail_API_Constants::TRANSACTION_CURRENCY => $currency,
                WC_Lightrail_API_Constants::TRANSACTION_METADATA => $metadata,
                WC_Lightrail_API_Constants::TRANSACTION_PENDING => $pending
            );

            $response = self::call_lightrail_api_with_headers( sprintf( WC_Lightrail_API_Constants::ENDPOINT_CODE_TRANSACTION, $code ), WC_Lightrail_API_Constants::HTTP_POST, $api_key, $post_transaction_body );

            return self::handle_api_response( $response, WC_Lightrail_API_Constants::API_RESPONSE_KEY_TRANSACTION );

        }

//		public static function post_transaction_by_cardid( string $cardId, int $amount, string $currency, string $userSuppliedId, string $api_key, array $metadata = [], bool $pending = false ) {
//			// Optional crash, for testing purposes
//            self::please_crash( __FUNCTION__ );
//
//			//Business as usual
//			// NOTE this will need to be updated if using pending transactions
//
//			$post_transaction_by_cardid_body = array(
//				WC_Lightrail_API_Constants::TRANSACTION_USER_SUPPLIED_ID => $userSuppliedId,
//				WC_Lightrail_API_Constants::TRANSACTION_VALUE          => $amount,
//				WC_Lightrail_API_Constants::TRANSACTION_CURRENCY       => $currency,
//				WC_Lightrail_API_Constants::TRANSACTION_METADATA       => $metadata,
//				WC_Lightrail_API_Constants::TRANSACTION_PENDING        => $pending
//			);
//
//			$response = self::call_lightrail_api_with_headers( sprintf( '/cards/%s/code/transactions', $cardId ), 'post', $api_key, $post_transaction_by_cardid_body );
//
//			return self::handle_api_response( $response, 'transaction' );
//
//		}

        public static function refund_transaction( array $original_transaction_object_returned_from_post_method, string $userSuppliedId, string $api_key ) {
            // Optional crash, for testing purposes
            self::please_crash( __FUNCTION__ );

            //Business as usual
            $cardId = $original_transaction_object_returned_from_post_method[WC_Lightrail_API_Constants::TRANSACTION_CARD_ID] ?? null;
            $original_transaction_id = $original_transaction_object_returned_from_post_method[WC_Lightrail_API_Constants::TRANSACTION_ID] ?? null;

            if ( $cardId && $original_transaction_id ) {
                $refund_request_body = array(
                    WC_Lightrail_API_Constants::TRANSACTION_USER_SUPPLIED_ID => $userSuppliedId,
                );

                $response = self::call_lightrail_api_with_headers( sprintf( WC_Lightrail_API_Constants::ENDPOINT_REFUND, $cardId, $original_transaction_id ), WC_Lightrail_API_Constants::HTTP_POST, $api_key, $refund_request_body );

                return self::handle_api_response( $response, WC_Lightrail_API_Constants::API_RESPONSE_KEY_TRANSACTION );

            } else {
                $error_info = self::get_printable_error_info( __FUNCTION__, func_get_args(), [], $custom_message = sprintf( "The method 'refund_transaction()' was called on a transaction object that was missing either the cardId key ('%s') or the transactionId key ('%s')", $cardId, $original_transaction_id ) );
                write_log( $error_info );
                throw new Exception( $error_info );
            }
        }

        public static function void_pending_transaction( array $original_transaction_object_returned_from_post_method, string $userSuppliedId, string $api_key ) {
            // Optional crash, for testing purposes
            self::please_crash( __FUNCTION__ );

            //Business as usual
            return self::handle_pending_transaction( $original_transaction_object_returned_from_post_method, $userSuppliedId, WC_Lightrail_API_Constants::TRANSACTION_PENDING_VOID, $api_key );

        }

        public static function capture_pending_transaction( array $original_transaction_object_returned_from_post_method, string $userSuppliedId, string $api_key ) {
            // Optional crash, for testing purposes
            self::please_crash( __FUNCTION__ );

            //Business as usual
            return self::handle_pending_transaction( $original_transaction_object_returned_from_post_method, $userSuppliedId, WC_Lightrail_API_Constants::TRANSACTION_PENDING_CAPTURE, $api_key );
        }

        private static function handle_api_response( $response, string $json_response_object_name ) {
            //Check for happy path first
            if ( ! is_wp_error( $response ) && ( wp_remote_retrieve_response_code( $response ) == 200 ) ) {
                $decoded_response = json_decode( $response[WC_Lightrail_API_Constants::API_RESPONSE_KEY_BODY], true ) ?? [];
                if ( isset( $decoded_response[$json_response_object_name] ) ) {
                    return $decoded_response[$json_response_object_name];
                } else {
                    $error_message = self::get_printable_error_info( __FUNCTION__, func_get_args(), $response, sprintf( "A 200 OK was received, but the requested key '%s' was not found in the response body.", $json_response_object_name ) );
                    write_log( $error_message );
                    throw new Exception( $error_message );
                }
            } else {
                // Handle WP_Error or non-200 HTTP response
		 $calledByFunction = self::getCallerAndCallerArgs()[0];
		 $callerFunctionArgs = self::getCallerAndCallerArgs()[1];
		 $error_info = self::get_printable_error_info( $calledByFunction, $callerFunctionArgs, $response, "Triggered by the method 'handle_api_response()' - remainder of this message pertains to the method that called it: " );
                write_log( $error_info );
                throw new Exception( $error_info );
            }

        }

        private static function call_lightrail_api_with_headers( string $endpoint, string $method, string $api_key, array $body = [] ) {
            if ( $method == 'GET' ) {
                return wp_safe_remote_get( WC_Lightrail_API_Configs::API_BASE_URL . $endpoint,
                    array(
                        WC_Lightrail_API_Constants::HTTP_HEADERS => self::build_headers( $api_key ),
                    )
                );
            }

            if ( $method == 'POST' ) {
                return wp_safe_remote_post( WC_Lightrail_API_Configs::API_BASE_URL . $endpoint,
                    array(
                        WC_Lightrail_API_Constants::HTTP_HEADERS => self::build_headers( $api_key ),
                        'body' => json_encode( $body, JSON_FORCE_OBJECT ),
                    )
                );
            }

            // If something besides GET or POST was passed in, log it
            write_log( self::get_printable_error_info( __FUNCTION__, func_get_args() ) );

            return;
        }

        private static function build_headers( $api_key ) {
            return array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $api_key
            );
        }

        private static function handle_pending_transaction( array $original_transaction_object_returned_from_post_method, string $userSuppliedId, string $action, string $api_key ) {
            $cardId = $original_transaction_object_returned_from_post_method[WC_Lightrail_API_Constants::TRANSACTION_CARD_ID] ?? null;
            $original_transaction_id = $original_transaction_object_returned_from_post_method[WC_Lightrail_API_Constants::TRANSACTION_ID] ?? null;
            $original_metadata = $original_transaction_object_returned_from_post_method[WC_Lightrail_API_Constants::TRANSACTION_METADATA] ?? null;
            $body = array(
                WC_Lightrail_API_Constants::TRANSACTION_USER_SUPPLIED_ID => $userSuppliedId,
                WC_Lightrail_API_Constants::TRANSACTION_METADATA => $original_metadata
            );

            if ( $cardId && $original_transaction_id ) {

                $response = self::call_lightrail_api_with_headers( sprintf( WC_Lightrail_API_Constants::ENDPOINT_HANDLE_PENDING, $cardId, $original_transaction_id, $action ), WC_Lightrail_API_Constants::HTTP_POST, $api_key, $body );

                return self::handle_api_response( $response, WC_Lightrail_API_Constants::API_RESPONSE_KEY_TRANSACTION );

            } else {

                $error_info = self::get_printable_error_info( __FUNCTION__, func_get_args(), [], $custom_message = sprintf( "The method 'handle_pending_transaction()' was called on a transaction object that was missing either the cardId key ('%s') or the transactionId key ('%s')", $cardId, $original_transaction_id ) );
                write_log( $error_info );
                throw new Exception ( $error_info );

            }

        }

        private static function please_crash( string $method_name ) {
            if ( WC_Lightrail_API_Configs::WE_ARE_TESTING ) {
                if ( rand( 0, 100 ) <= WC_Lightrail_API_Configs::FAILURE_RATE ) {
                    write_log( "Here's the crash you ordered from " . $method_name );
                    throw new Exception( 'A crash? No problem. Served up fresh from ' . $method_name );
                }
            } else {
                return;
            }
        }


        private static function get_printable_error_info( string $method_name, array $parameters = [], $http_response_or_wp_error = [], string $custom_message = '' ) {
            $error_info = sprintf( 'ERRORS: %s ', $custom_message );

            if ( ! empty( $http_response_or_wp_error ) ) {
                if ( is_wp_error( $http_response_or_wp_error ) ) {
                    $error_info = $error_info . sprintf( 'WP_Error: %s', json_encode( $http_response_or_wp_error, true ) );
                } else {
                    $error_info = $error_info . sprintf( 'HTTP response: %s - %s',
                            wp_remote_retrieve_response_code( $http_response_or_wp_error ),
                            wp_remote_retrieve_response_message( $http_response_or_wp_error )
                        );
                }
            }

            $error_info = $error_info . sprintf( " | From method %s with parameters %s",
                    $method_name,
                    json_encode( $parameters, true )
                );

            return $error_info;
        }

	private static function getCallerAndCallerArgs() {
		$trace = debug_backtrace();
		$name = $trace[2]['function'] ?? 'global';
		$args = $trace[2]['args'] ?? [];
		return array( $name, $args );
	}

    }
}