<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 IT Consultis (https://it-consultis.com)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package     Yoopay
 * @copyright   Copyright (c) 2016 IT Consultis (https://it-consultis.com)
 * @license     https://opensource.org/licenses/MIT MIT
 */

/**
 * Yoopay api php integration
 *
 * @class Yoopay
 * @version 1.0
 * @author Dario Martini <dario@it-consultis.com>
 */
class Yoopay
{
    const API_ADDRESS = 'https://yoopay.cn/yapi';

    const REQUEST_CHARGE = 'CHARGE';
    const REQUEST_REFUND = 'REFUND';

    public static $ALLOWED_LANGUAGES = array( 'en', 'zh' );
    public static $ALLOWED_CURRENCIES = array( 'USD', 'CNY' );

    private static $KEYS_ORDER_CHARGE = array( 'tid', 'item_price', 'item_currency', 'notify_url', 'sandbox', 'invoice' );
    private static $KEYS_ORDER_REFUND = array( 'tid', 'yapi_tid', 'refund_amount', 'full_refund', 'notify_url', 'sandbox' );

    private static $KEYS_ORDER_CHARGE_RESPONSE = array( 'yapi_tid', 'tid', 'item_price', 'item_currency', 'result_status', 'type', 'sandbox' ); 
    private static $KEYS_ORDER_REFUND_RESPONSE = array( 'tid', 'refund_yapi_tid', 'refund_amount', 'refund_currency', 'refund_customer_email', 'refund_status', 'type', 'sandbox' );

    private static $ATTRS_REQUIRED_ORDER_CHARGE = array( 'language' => 'Language' , 'type' => 'Required', 'tid' => 'Required', 'item_name' => 'Required', 'item_body' => 'Required', 'item_price' => 'Required', 'item_currency' => 'Currency', 'payment_method' => 'PaymentMethod', 'customer_name' => 'Required', 'customer_email' => 'Required', 'invoice' => 'Required', 'sandbox' => 'Required' );
    private static $ATTRS_OPTIONAL_ORDER_CHARGE = array( 'customer_mobile', 'sandbox_target_status', 'notify_url', 'return_url', 'retry_count' );

    private static $ATTRS_REQUIRED_ORDER_REFUND = array( 'type' => 'Required', 'yapi_tid' => 'Required', 'tid' => 'Required', 'full_refund' => 'Required' );
    private static $ATTRS_OPTIONAL_ORDER_REFUND = array( 'sandbox', 'sandbox_target_status', 'refund_amount', 'notify_url' );

    private static $ATTRS_INVOICE = array( 'inovice_title', 'invoice_recipient', 'invoice_phone', 'invoice_mailing_address', 'invoice_city', 'invoice_postal_code' );

    private static $DEFAULT_CONFIG = array( 
        'curl_timeout' => 60
    );

    /**
     * @var string
     */
    protected $_api_key;
    /**
     * @var string
     */
    protected $_seller_email;

    /**
     * @var array
     */
    protected $_config;


    /**
     * @param string $api_key
     * @param string $seller_email
     * @param array $additional
     */
    public function __construct($api_key, $seller_email, $additional = array()) {
        $this->_api_key = $api_key;
        $this->_seller_email = $seller_email;

        if(count($additional)) {
            $valid = array_keys(self::$DEFAULT_CONFIG);
            $this->_config = self::$DEFAULT_CONFIG;
            foreach($additional as $key => $value) {
                if(in_array($key, $valid)) {
                    $this->_config[$key] = $value;
                }
            }
        }
    }

    /**
     * Generates the sign to be used in the requests
     *
     * @param array $attrs
     * @return string
     */
    protected function _getSign($attrs) {
        return md5(strtoupper(implode('', $attrs)));
    }

    /**
     * Returns an array containing the values of the associative array provided ordered by the keys
     *
     * @param array $keys
     * @param array $assoc
     * @return array
     * @throws InvalidArgumentException
     */
    protected function _getOrderedValues($keys, $assoc) {
        $values = array();
        foreach($keys as $key) {
            if(!isset($assoc[$key])) {
                throw new InvalidArgumentException($key);
            }
            array_push($values, $assoc[$key]);
        }
        return $values;
    }

    /**
     * @return string
     */
    public function getChargeAction() {
        return self::API_ADDRESS;
    }

    /**
     * @return string
     */
    public function getRefundAction() {
        return self::API_ADDRESS;
    }

    /**
     * Generates the Charge sign
     *
     * Requires an array with: 
     * 
     * tid
     * item_price
     * item_currency
     * notify_url
     * sandbox
     * invoice
     *
     * @param array $attrs
     * @return string
     */
    public function getChargeRequestSign($attrs) {
        $values = $this->_getOrderedValues(self::$KEYS_ORDER_CHARGE, $attrs);
        array_unshift($values, $this->_api_key, $this->_seller_email);
        return $this->_getSign($values);
    }

    /**
     * Generates the Refund sign
     *
     * Requires an array with: 
     * 
     * app_key 
     * seller_email
     * tid
     * yapi_tid
     * refund_amount 
     * full_refund
     * notify_url
     * sandbox
     *
     * @param array $attrs
     * @return string
     */
    public function getRefundRequestSign($attrs) {
        $values = $this->_getOrderedValues(self::$KEYS_ORDER_REFUND, $attrs);
        array_unshift($values, $this->_api_key, $this->_seller_email);
        return $this->_getSign($values);
    }

    /**
     * @param array $attrs
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function _validateRequired($attrs, $key) {
        if( !isset( $attrs[$key] ) ) {
            throw new InvalidArgumentException('Missing required attribute: ' . $key);
        }
    }

    /**
     * @param array $attrs
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function _validateLanguage($attrs, $key) {
        $this->_validateRequired($attrs, $key);
        if(!in_array($attrs[$key], self::$ALLOWED_LANGUAGES)) {
            throw new InvalidArgumentException('Language is not supported: ' . $attrs[$key]);
        }
    }

    /**
     * @param array $attrs
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function _validateCurrency($attrs, $key) {
        $this->_validateRequired($attrs, $key);
        if(!in_array($attrs[$key], self::$ALLOWED_CURRENCIES)) {
            throw new InvalidArgumentException('Currency is not supported: ' . $attrs[$key]);
        }
    }

    /**
     * @param array $attrs
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function _validatePaymentMethod($attrs, $key) {
        $this->_validateRequired($attrs, $key);
        if( !( is_int($attrs[$key]) || is_string($attrs[$key]) ) ) {
            throw new InvalidArgumentException('Payment Method is not supported: ' . $attrs[$key]);
        }
    }

    /**
     * Returns an associative array containing all the fields that need to be submitted to the api
     *
     * @param array $attrs
     * @return array
     */
    public function getChargeRequestFields($attrs) {
        $return = array();
        $return['seller_email'] = $this->_seller_email;
        $return['language'] = $attrs['language'];
        $return['sign'] = $this->getChargeRequestSign($attrs);
        foreach( self::$ATTRS_REQUIRED_ORDER_CHARGE as $required => $validation ) {
            $this->{"_validate$validation"}($attrs, $required);
            $return[$required] = $attrs[$required];
        }
        foreach( self::$ATTRS_OPTIONAL_ORDER_CHARGE as $optional ) {
            if( isset( $attrs[$optional] ) && "" != $attrs[$optional] ) {
                $return[$optional] = $attrs[$optional];
            }
        }
        return $return;
    }

    /**
     * Returns an associative array containing all the fields that need to be submitted to the api
     *
     * @param array $attrs
     * @return array
     */
    public function getRefundReqestFields($attrs) {
        $return = array();
        $return['seller_email'] = $this->_seller_email;
        $return['sign'] = $this->getRefundRequestSign($attrs);
        foreach( self::$ATTRS_REQUIRED_ORDER_REFUND as $required => $validation ) {
            $this->{"_validate$validation"}($attrs, $required);
            $return[$required] = $attrs[$required];
        }
        foreach( self::$ATTRS_OPTIONAL_ORDER_REFUND as $optional ) {
            if( isset( $attrs[$optional] ) && "" != $attrs[$optional] ) {
                $return[$optional] = $attrs[$optional];
            }
        }
        return $return;
    }

    /**
     * @param array $parameters
     * @param array $request
     * @return bool
     * @throws UnexpectedValueException
     */
    protected function _validateResponseSign( $parameters, $request ) {
        $values = $this->_getOrderedValues($parameters, $request);
        array_unshift($values, $this->_api_key);
        if( !isset($request['sign']) || $request['sign'] !== $this->_getSign($values) ) {
            throw new UnexpectedValueException('Sign does not match');
        }
        return true;
    }

    /**
     * @param array $request
     * @return bool
     */
    public function validateChargeResponse( $request ) {
        return $this->_validateResponseSign( self::$KEYS_ORDER_CHARGE_RESPONSE, $request );
    }

    /**
     * @param array $request
     * @return bool
     */
    public function validateRefundResponse( $request ) {
        return $this->_validateResponseSign( self::$KEYS_ORDER_REFUND_RESPONSE, $request );
    }

    /**
     * @param array $request
     * @return bool
     */
    static function isSuccessRefund( $request ) {
        return ( (int) $request['refund_status'] ) === 1;
    }

    /**
     * @param array $request
     * @return bool
     */
    static function isSuccess( $request ) {
        return ( (int) $request['result_status'] ) === 1;
    }

    /**
     * @param array $request
     * @return bool
     */
    static function isPending( $request ) {
        return ( (int) $request['result_status'] ) === 0;
    }

    /**
     * @param array $request
     * @return string 
     */
    static function getYoopayId( $request ) {
        return $request['yapi_tid'];
    }

    /**
     * @param array $request
     * @return string 
     */
    static function getRefundTid( $request ) {
        return $request['refund_yapi_tid'];
    }

    /**
     * @param array $request
     * @return string 
     */
    static function getRefundAmount( $request ) {
        return $request['refund_amount'];
    }

    /**
     * @param array $request
     * @return string 
     */
    static function getRefundCurrency( $request ) {
        return $request['refund_currency'];
    }

    /**
     * @param array $request
     * @return string 
     */
    static function getPaymentMethod( $request ) {
        return $result['result_desc'];
    }

    /**
     * Returns an array containing the invoice attributes or false if no invoice is present
     *
     * @param array $request
     * @return array|false
     */
    static function getInvoice( $request ) {
        if( ( (int) $request['invoice'] ) === 1 ) {
            $return = array();
            foreach( self::$ATTRS_INVOICE as $attr ) {
                if( isset($request[$attr] ) ) {
                    $return[str_replace( 'invoice_', '', $attr )] = $request[$attr];
                }
            }
            return $return;
        }
        return false;
    }

    /**
     * Fires a curl request and returns the data contained in the response
     *
     * @param mixed $body
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function postCurl($body, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_config['curl_timeout']);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));

        $data = curl_exec($ch);

        if (empty($data)) //TODO: check
        {
            $error = curl_errno($ch);
            throw new Exception($error);
        }

        curl_close($ch);
        return $data;
    }
}
