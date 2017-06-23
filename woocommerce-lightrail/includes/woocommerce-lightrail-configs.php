<?php
if ( ! class_exists( 'WC_Lightrail_API_Configs' ) ) {
	class WC_Lightrail_API_Configs {
		const API_BASE_URL = 'https://dev.lightrail.com/v1';
		const WEB_APP_CARD_DETAILS_URL = 'https://preprod.lightrail.com/app/#/cards/details/%s';

		const API_VERSION = '1.0';
		const PLUGIN_VERSION = '1.0';

		const WE_ARE_TESTING = false;
		const FAILURE_RATE = 20; //percent

	}
}
