<?php
/**
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
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include_once __DIR__ . '/api/Yoopay.php';


/**
 * Extends the WC_Logger class to allow debug and error modes
 *
 * @class Logger
 * @extends WC_Logger
 * @version 1.0
 * @author Dario Martini <dario@it-consultis.com>
 */
class Logger extends WC_Logger {

    protected $_enabled;
    protected $_id;

    public function __construct($id, $enabled) {
        parent::__construct();
        $this->_id = $id;
        $this->_enabled = $enabled;
    }

    public function error($message) {
        $this->add( 'error_' . $this->_id, $message );
    }

    public function debug($message) {
        if( $this->_enabled ) {
            $this->add( 'debug_' . $this->_id, $message );
        }
    }
}

/**
 * Extends the WC_Payment_Gateway to add Yoopay gateway
 *
 * @class WC_Gateway_Yoopay
 * @extends WC_Payment_Gateway
 * @version 1.0
 * @author Dario Martini <dario@it-consultis.com>
 */
class WC_Gateway_Yoopay extends WC_Payment_Gateway {

    const TRANSLATION_ID = 'woocommerce_yoopay';
    const GATEWAY_ID = 'woocommerce_yoopay';

    /**
     * @var Yoopay
     */
    protected $_api;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var array
     */  
    public $supports = array( 'products', 'refunds' );

    public function __construct() {

        $this->id = self::GATEWAY_ID;
        $this->method_title = __( "Yoopay", self::TRANSLATION_ID );
        $this->method_description = __( "Yoopay Payment Gateway Plug-in for WooCommerce.", self::TRANSLATION_ID );
        $this->method_description .= "\n" . sprintf( __( "Default Notification Url: %s", self::TRANSLATION_ID ), $this->get_notification_url() );
        $this->method_title = __( "Yoopay", self::TRANSLATION_ID );
        $this->icon = null;
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }

        $this->invoice = (int) ("yes" === $this->invoice);
        $this->sandbox = (int) ("yes" === $this->sandbox);
        $this->auto_submit_form = (bool) ("yes" === $this->auto_submit_form);

        $additional = array();

        $this->_logger = new Logger( self::GATEWAY_ID, $this->debug );
        $this->_api = new Yoopay( $this->api_key, $this->seller_email, $additional );

        if ( is_admin() ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        if( ! $this->is_valid_for_use() ) {
            $this->enabled = 'no';
        } else {
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ));
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'check_response' ));
            add_action( 'woocommerce_api_' . $this->id, array( $this, 'check_notification' ) );
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     * @return bool
     */
    public function is_valid_for_use() {
        return in_array( get_woocommerce_currency(), Yoopay::$ALLOWED_CURRENCIES );
    }

    /**
     * @return string
     */
    public function get_notification_url() {
        return get_site_url() . '/?wc-api=' . $this->id;
    }

    /**
     * Creates the fields that need to be displayed in the administration page
     *
     * @param void
     * @return void
     */
    public function init_form_fields() {

        $payment_methods = array(
            1 => __( 'Online Banking (China Union Pay)', self::TRANSLATION_ID ),
            2 => __( 'Alipay', self::TRANSLATION_ID ),
            3 => __( 'China Bank Transfer', self::TRANSLATION_ID ),
            4 => __( 'Oversea Bank Transfer', self::TRANSLATION_ID ),
            5 => __( 'Oversea Credit Card', self::TRANSLATION_ID ),
            6 => __( 'Paypal', self::TRANSLATION_ID ),
            7 => __( 'WeChat Pay', self::TRANSLATION_ID )
        );

        $sandbox_status = array(
            -1 => __( 'Expect a failed response', self::TRANSLATION_ID ),
            0 => __( 'Expect a pending response', self::TRANSLATION_ID ),
            1 => __( 'Expect a successful response', self::TRANSLATION_ID ),
        );

        $this->form_fields = array(
            'enabled' => array(
                'title'     => __( 'Enable / Disable', self::TRANSLATION_ID ),
                'label'     => __( 'Enable this payment gateway', self::TRANSLATION_ID ),
                'type'      => 'checkbox',
                'default'   => 'no',
            ),
            'title' => array(
                'title'     => __( 'Title', self::TRANSLATION_ID ),
                'type'      => 'text',
                'desc_tip'  => __( 'Payment title the customer will see during the checkout process.', self::TRANSLATION_ID ),
                'default'   => __( 'Yoopay', self::TRANSLATION_ID ),
            ),
            'description' => array(
                'title'     => __( 'Description', self::TRANSLATION_ID ),
                'type'      => 'textarea',
                'desc_tip'  => __( 'Payment description the customer will see during the checkout process.', self::TRANSLATION_ID ),
                'default'   => __( 'Pay securely using yoopay.', self::TRANSLATION_ID ),
                'css'       => 'max-width:350px;'
            ),
            'item_name' => array(
                'title'     => __( 'Item Name', self::TRANSLATION_ID ),
                'type'      => 'text',
                'desc_tip'  => __( "Item Name the customer will see in the yoopay payment window.\nAvailable variables:\n - {{site_name}}\n - {{date}}", self::TRANSLATION_ID ),
                'default'   => __( 'Purchase from {{site_name}} on {{date}}', self::TRANSLATION_ID ),
            ),
            'item_body' => array(
                'title'     => __( 'Item Body', self::TRANSLATION_ID ),
                'type'      => 'textarea',
                'desc_tip'  => __( "Item Body the customer will see in the yoopay payment window.\nAvailable variables:\n - {{items_in_cart}}", self::TRANSLATION_ID ),
                'default'   => __( "Item Bought:\n {{items_in_cart}}", self::TRANSLATION_ID ),
                'css'       => 'max-width:350px;'
            ),
            'api_key' => array(
                'title'     => __( 'Yoopay Merchant API key', self::TRANSLATION_ID ),
                'type'      => 'text',
                'desc_tip'  => __( '', self::TRANSLATION_ID ),
            ),
            'seller_email' => array(
                'title'     => __( 'Yoopay login email', self::TRANSLATION_ID ),
                'type'      => 'email',
                'desc_tip'  => __( '', self::TRANSLATION_ID ),
            ),
            'payment_method' => array(
                'title'             => __( 'Enabled payment methods', self::TRANSLATION_ID ),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'default'           => '',
                'description'       => __( 'Specify the payment methods should be displayed on the payment page.', self::TRANSLATION_ID ),
                'options'           => $payment_methods,
                'desc_tip'          => true,
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Payment methods', self::TRANSLATION_ID )
                )
            ),
            'invoice' => array(
                'title'     => __( 'Invoice on Yoopay', self::TRANSLATION_ID ),
                'label'     => __( 'Indicate whether to show the invoice collection form on the payment page.', self::TRANSLATION_ID ),
                'type'      => 'checkbox',
                'default'   => 'no',
            ),
            'auto_submit_form' => array(
                'title'     => __( 'Auto submit form', self::TRANSLATION_ID ),
                'label'     => __( 'Auto submit form', self::TRANSLATION_ID ),
                'type'      => 'checkbox',
                'description' => __( 'If checked the form in the receipt page will be authomatically submitted.', self::TRANSLATION_ID ),
                'default'   => 'no',
            ),
            'sandbox' => array(
                'title'     => __( 'Yoopay Sandbox Mode', self::TRANSLATION_ID ),
                'label'     => __( 'Enable Sandbox Mode', self::TRANSLATION_ID ),
                'type'      => 'checkbox',
                'description' => __( 'Place the payment gateway in sandbox mode.', self::TRANSLATION_ID ),
                'default'   => 'no',
            ),
            'sandbox_target_status' => array(
                'title'             => __( 'Sandbox target status', self::TRANSLATION_ID ),
                'type'              => 'select',
                'default'           => 1,
                'description'       => __( 'Select the sandbox target status.', self::TRANSLATION_ID ),
                'options'           => $sandbox_status
            ),
            'debug' => array(
                'title'     => __( 'Yoopay Debug Mode', self::TRANSLATION_ID ),
                'label'     => __( 'Enable Debug Mode', self::TRANSLATION_ID ),
                'type'      => 'checkbox',
                'description' => __( 'Place the payment gateway in debug mode. Will log to woocommerce/logs/.', self::TRANSLATION_ID ),
                'default'   => 'no',
            ),

        );
    }

    /**
     * Prints the html for the receipt page
     *
     * @param int $order_id
     * @return void
     */
    public function receipt_page( $order_id ) {

        echo "<p>" . __( '', self::TRANSLATION_ID ) . "</p>";

        try {
            echo $this->_get_payment_form( $order_id );
        } catch (InvalidArgumentException $e) {
            $this->_logger->error( $e->getMessage() );
            wc_add_notice( __('An error occured while processing the order, if the problem persist please contact us.', self::TRANSLATION_ID), 'error' );
        }

    }

    /**
     * Returns the current language code if allowed by the payment method, defaults to english
     *
     * @return string
     */
    public function get_language() {
        $language = 'en';
        if( defined(ICL_LANGUAGE_CODE) ) {
            $lang = ICL_LANGUAGE_CODE;
        } else {
            $lang = get_locale();
            $lang = substr( $lang, 0, 2 );
        }
        if( in_array($lang, Yoopay::$ALLOWED_LANGUAGES) ) {
            $language = $lang;
        }
        return $language;
    }

    /**
     * @return string
     */
    public function get_item_name() {
        $item_name = $this->item_name;
        $item_name = str_replace('{{site_name}}', get_bloginfo('name'), $item_name);
        $item_name = str_replace('{{date}}', date( __('d/m/Y', self::TRANSLATION_ID) ), $item_name);
        return $item_name;
    }

    /**
     * @param WC_Order $order
     * @return string
     */
    public function get_item_body( $order ) {
        $item_body = $this->item_body;
        if( strpos($this->item_body, '{{items_in_cart}}') !== false ) {
            $items = "";
            foreach( $order->get_items() as $line_item ) {
                $items .= $line_item['name'] . "\n";
            }
            $item_body = str_replace('{{items_in_cart}}', $items, $item_body);
        }
        return $item_body;
    }

    /**
     * Returns the form html in receipt page
     *
     * @param int $order_id
     * @return string
     */
    protected function _get_payment_form( $order_id ) {
        $order = wc_get_order( $order_id );

        $attrs = array(
            // Required fields
            'language'              => $this->get_language(),
            'type'                  => Yoopay::REQUEST_CHARGE,
            'tid'                   => $order_id,
            'item_name'             => $this->get_item_name(),
            'item_body'             => $this->get_item_body( $order ),
            'item_price'            => self::get_order_total(),
            'item_currency'         => get_woocommerce_currency(),
            'payment_method'        => implode( ';', $this->payment_method ),
            'customer_name'         => $order->billing_first_name . ' ' . $order->billing_last_name,
            'customer_email'        => $order->billing_email,
            'invoice'               => $this->invoice,
            // Optional fields
            'customer_mobile'       => $order->billing_phone,
            'sandbox'               => $this->sandbox,
            'sandbox_target_status' => $this->sandbox_target_status,
            'notify_url'            => $this->get_notification_url(),
            'return_url'            => self::get_return_url( $order ),
            'retry_count'           => ''
        );

        $fields = $this->_api->getChargeRequestFields($attrs);

        $html = '<form id="woocommerce_yoopay_form" method="post" action="' . $this->_api->getChargeAction() . '">';
        foreach( $fields as $key => $field ) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $field . '" style="display:none;">';
        }
        if( $this->auto_submit_form ) {
            wc_enqueue_js('document.getElementById("woocommerce_yoopay_form") && document.getElementById("woocommerce_yoopay_form").submit();');
            $html .= '<p>' . __( 'You will be redirected to Yoopay shortly.', self::TRANSLATION_ID ) . '</p>';
        } else {
            $html .= '<input type="submit" class="button-alt" value="' . __('Pay with Yoopay', self::TRANSLATION_ID ) . '" />';
            $html .= '<a class="button cancel" href="'.$order->get_cancel_order_url().'">' . __('Cancel', self::TRANSLATION_ID ) . '</a>';
        }
        $html .= '</form>';

        return $html;
    }

    /**
     * Called when the user returns from the payment gateway
     */
    public function check_response() {
        $request = wp_unslash( $_REQUEST );
        $this->_logger->debug(json_encode($request));

        try {
            $order = wc_get_order( $request['tid'] );

            if( $order->needs_payment() ) {

                if( $this->_api->validateChargeResponse($request) ) {

                    if( Yoopay::isSuccess( $request ) || Yoopay::isPending( $request ) ) {
                        $order->add_order_note(sprintf( __( 'User has correctly returned from Yoopay website. Yoopay id: %s', self::TRANSLATION_ID ), Yoopay::getYoopayId($request) ) );
                        WC()->cart->empty_cart();
                    } else {
                        $order->update_status('failed');
                        $order->add_order_note(sprintf( __( 'Payment failed for order number %s, yoopay id: %s', self::TRANSLATION_ID ), $order->id, Yoopay::getYoopayId($request) ) );
                    }

                } else {
                    $order->update_status('failed');
                    $order->add_order_note(sprintf( __( 'Sign error for order number %s, yoopay id: %s', self::TRANSLATION_ID ), $order->id, Yoopay::getYoopayId($request) ) );
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->debug($e->getTraceAsString());
        }
    }

    /**
     * Processes a charge notification
     *
     * @param array $request
     * @return void
     */
    protected function _process_charge_notification($request) {
        try {
            $order = wc_get_order( $request['tid'] );

            if( $order->needs_payment() ) {

                if( $this->_api->validateChargeResponse($request) ) {

                    if( Yoopay::isSuccess( $request ) ) {
                        $yoopay_id = Yoopay::getYoopayId($request);
                        $order->payment_complete( $yoopay_id );
                        $order->add_order_note(sprintf( __( 'Order has successfully been paid. Yoopay id: %s', self::TRANSLATION_ID ), $yoopay_id ) );
                        $invoice = Yoopay::getInvoice( $request );
                        if( $invoice ) {
                            $note = __( 'Invoice Information', self::TRANSLATION_ID ) . "\n";
                            foreach( $invoice as $key => $value ) {
                                $note .= __( ucfirst( str_replace( '_', '', $key ) ), self::TRANSLATION_ID ) . " : " . $value . "\n";
                            }
                            $order->add_order_note( $note, true );
                        }
                        WC()->cart->empty_cart();
                    } elseif ( Yoopay::isPending( $request ) ) {
                        $order->add_order_note(sprintf( __( 'Order number %s is pending, yoopay id: %s', self::TRANSLATION_ID ), $order->id, Yoopay::getYoopayId($request) ) );
                        WC()->cart->empty_cart();
                    } else {
                        $order->update_status('failed');
                        $order->add_order_note(sprintf( __( 'Payment failed for order number %s, yoopay id: %s', self::TRANSLATION_ID ), $order->id, Yoopay::getYoopayId($request) ) );
                    }
                    $payment_method = Yoopay::getPaymentMethod($request);
                    if( $payment_method != '' ) {
                        $order->add_order_note(sprintf( __( 'Yoopay payment completed using: %s', self::TRANSLATION_ID ), $payment_method ) );
                    }

                } else {
                    $order->update_status('failed');
                    $order->add_order_note(sprintf( __( 'Sign error for order number %s, yoopay id: %s', self::TRANSLATION_ID ), $order->id, Yoopay::getYoopayId($request) ) );
                }

                die( 'SUCCESS' );
            }
        } catch(Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->debug($e->getTraceAsString());
            wp_die( 'Notification validation error', 'Woocommerce Yoopay', array( 'response' => 500 ) );
        }
    }

    /**
     * Processes a charge notification
     *
     * @param array $request
     * @return void
     */
    protected function _process_refund_notification($request) {
        try {
            $order = wc_get_order( $request['tid'] );

            if( $this->_api->validateRefundResponse($request) && Yoopay::isSuccessRefund($result_array) ) {
                $order->add_order_note(sprintf( __( 'Order has successfully been refunded. Yoopay refund id: %s', self::TRANSLATION_ID ), Yoopay::getRefundTid($request) ) );
            } else {
                $order->add_order_note(sprintf( __( 'Sign error for refund number %s, yoopay id: %s', self::TRANSLATION_ID ), $order->id, Yoopay::getRefundTid($request) ) );
                $this->_logger->error(json_encode($request));
            }

            die( 'SUCCESS' );
        } catch(Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->debug($e->getTraceAsString());
            wp_die( 'Notification validation error', 'Woocommerce Yoopay', array( 'response' => 500 ) );
        }
    }

    /**
     * Called when the gateway sends the notification to the server
     */
    public function check_notification() {
        $request = wp_unslash( $_REQUEST );
        $this->_logger->debug(json_encode($request));

        if(!isset($request['type'])) {
            $this->_logger->error('The "type" field is not present:' . json_encode($request));
            wp_die('ERROR', array('response' => 500));
        }

        switch($request['type']) {
            case Yoopay::REQUEST_CHARGE :
                $this->_process_charge_notification($request);
                break;
            case Yoopay::REQUEST_REFUND :
                $this->_process_refund_notification($request);
                break;
            default:
                wp_die('ERROR', array('response' => 500));
        }
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        return array(
            'result'    => 'success',
            'redirect'  => $order->get_checkout_payment_url( true )
        );

    }

    /**
     * Process refund.
     *
     * If the gateway declares 'refunds' support, this will allow it to refund.
     * a passed in amount.
     *
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return bool|WP_Error True or false based on success, or a WP_Error object.
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );

        $is_full = (int) ($order->get_total() == $amount);

        $attrs = array(
            // Required fields
            'type'                  => Yoopay::REQUEST_REFUND,
            'tid'                   => $order_id,
            'yapi_tid'              => $order->get_transaction_id(), 
            'full_refund'           => $is_full,
            // Optional fields
            'sandbox'               => $this->sandbox,
            'sandbox_target_status' => $this->sandbox_target_status,
            'notify_url'            => $this->get_notification_url(),
            'refund_amount'         => $amount
        );

        $fields = $this->_api->getRefundReqestFields($attrs);
        $this->_logger->debug(json_encode($fields));

        try {
            $result = $this->_api->postCurl($fields, $this->_api->getRefundAction()); 
            $result_array = is_array($result) ? $result : json_decode($result, true);

            $this->_logger->debug(json_encode($result_array));
            if( $this->_api->validateRefundResponse($result_array) && Yoopay::isSuccessRefund($result_array) ) {
                $order->add_order_note(sprintf( __( 'Yoopay refund (%s %s) completed, Yoopay refund id: %s', self::TRANSLATION_ID ), Yoopay::getRefundAmount($result_array), Yoopay::getRefundCurrency($result_array), Yoopay::getRefundTid($result_array) ) );
                return true;
            } else {
                $this->_logger->error(json_encode($result_array));
            }
        } catch(Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->debug($e->getTraceAsString());
        }

        return false;
    }

    /**
     * @return bool
     */
    public function validate_fields() {
        return true;
    }

}
