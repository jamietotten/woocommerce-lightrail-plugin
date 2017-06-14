<?php
if ( ! class_exists( 'WC_Lightrail_Metadata_Constants' ) ) {
	class WC_Lightrail_Metadata_Constants {
		const ORIGINAL_TOTAL_METADATA_KEY = '__lightrail_original_total';
		const TRANSACTIONS_METADATA_KEY = '__lightrail_payment';


		const TRANSACTION_ID = 'transaction_id';
		const TRANSACTION_STATUS = 'status';
		const TRANSACTION_TYPE = 'type';
		const TRANSACTION_VALUE = 'value';
		const TRANSACTION_NOTE = 'note';
		const TRANSACTION_PAYMENT_METHOD = 'payment_method';
		const TRANSACTION_RAW_OBJECT = 'raw';
		const TRANSACTION_ORIGINAL_TRANSACTION_ID = 'original_transaction_id';
		const TRANSACTION_ORIGINAL_TRANSACTION = 'original_transaction';

		//const TRANSACTION_PAYMENT_METHOD_LIGHTRAIL = 'lightrail';


		const TRANSACTION_STATUS_PENDING = 'PENDING';
		const TRANSACTION_STATUS_VOIDED = 'VOIDED';
		const TRANSACTION_STATUS_CAPTURED = 'CAPTURED';
		const TRANSACTION_STATUS_REFUNDED = 'REFUNDED';
		const TRANSACTION_STATUS_PENDING_TO_CAPTURE = 'PENDING_TO_CAPTURE';
		const TRANSACTION_STATUS_PENDING_TO_VOID = 'PENDING_TO_VOID';
		const TRANSACTION_STATUS_CAPTURED_TO_REFUND = 'CAPTURED_TO_REFUND';

		const TRANSACTION_TYPE_PAYMENT = 'PAYMENT';
		const TRANSACTION_TYPE_REFUND = 'REFUND';

	}
}

if ( ! class_exists( 'WC_Lightrail_Plugin_Constants' ) ) {
	class WC_Lightrail_Plugin_Constants {
		const LIGHTRAIL_PAYMENT_METHOD_NAME = 'lightrail';
		const LIGHTRAIL_NAMESPACE = 'lightrail';
	}
}

if (!class_exists('WC_Lightrail_API_Constants')) {
        class WC_Lightrail_API_Constants {
            const HTTP_HEADERS = 'headers';

            const HTTP_GET = 'GET';
            const HTTP_POST = 'POST';

            const TRANSACTION_USER_SUPPLIED_ID = 'userSuppliedId';
            const TRANSACTION_CARD_ID = 'cardId';
            const TRANSACTION_ID = 'transactionId';
            const TRANSACTION_CURRENCY = 'currency';
            const TRANSACTION_VALUE = 'value';
            const TRANSACTION_CURRENT_VALUE = 'currentValue';
            const TRANSACTION_METADATA = 'metadata';
            const TRANSACTION_PENDING = 'pending';
            const TRANSACTION_PENDING_CAPTURE = 'capture';
            const TRANSACTION_PENDING_VOID = 'void';
            const TRANSACTION_CODE_LAST_FOUR='codeLastFour';


            const ENDPOINT_PING= '/ping';
            const ENDPOINT_BALANCE = '/codes/%s/balance';
            const ENDPOINT_CODE_TRANSACTION = '/codes/%s/transactions';
            const ENDPOINT_REFUND = '/cards/%s/transactions/%s/refund';
            const ENDPOINT_HANDLE_PENDING = '/cards/%s/transactions/%s/%s';

            const API_RESPONSE_KEY_BODY = 'body';
            const API_RESPONSE_KEY_USER = 'user';
            const API_RESPONSE_KEY_BALANCE = 'balance';
            const API_RESPONSE_KEY_TRANSACTION = 'transaction';
        }
}
