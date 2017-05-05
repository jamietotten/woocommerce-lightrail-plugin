<?php
/**
 * Created by IntelliJ IDEA.
 * User: mohammad
 * Date: 2017-05-04
 * Time: 9:55 AM
 */


if (!class_exists('LightrailEngine'))
{
    class LightrailEngine
    {
        public $api_version= "1.0";
        public $plugin_version= "0.1";

        public $balanace = 50;
        public function __construct()
        {
        }

        public function get_available_credit($code)
        {
            return $this->balanace;
        }

        public function post_transaction($code, $amount)
        {
            $this->balanace = $this->balanace + $amount;

        }

    }
    $GLOBALS['wc_lightrail'] = new LightrailEngine();
}
?>