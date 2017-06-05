<?php
/**
 * Plugin Name: Yoopay - WooCommerce Gateway
 * Plugin URI: https://it-consultis.com/
 * Description: Extends WooCommerce by Adding the Yoopay.cn Gateway.
 * Version: 2.0
 * Author: IT Consultis
 * Author URI: http://it-consultis.com/
 * Requires at least: 4.1
 * Tested up to: 4.3
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Copyright: (C) 2016 IT Consultis
 *
 * Text Domain: woocommerce_yoopay
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
 * @package woocommerce-yoopay
 * @category WooCommerce
 * @author Dario Martini <dario@it-consultis.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
     exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', 'woocommerce_yoopay_init', 0 );
function woocommerce_yoopay_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    include_once __DIR__ . '/WC_Gateway_Yoopay.php';

    add_filter( 'woocommerce_payment_gateways', 'woocommerce_yoopay_gateway' );
    function woocommerce_yoopay_gateway( $methods ) {
        $methods[] = 'WC_Gateway_Yoopay';
        return $methods;
    }
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_yoopay_action_links' );
function woocommerce_yoopay_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woocommerce_yoopay' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}

add_action('plugins_loaded', 'woocommerce_yoopay_load_textdomain');

function woocommerce_yoopay_load_textdomain() {
    load_plugin_textdomain( 'woocommerce_yoopay', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
}
