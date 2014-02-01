<?php

require_once('helper.php');

use Addons\PagSeguro\Helper as PSH;


// test if cs-cart was installed
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION'))
{
    // here comes the code for notifications
    // params to be here: dispatch[payment_notification.foo]=bar&payment=PagSeguro&order_id=baz
    
    // Vars defined here
    // $mode = 'foo' // whatever you set after payment_notification.
    
    PSH\payment_notification($mode);
}
else
{
    // here comes your ma... controller
    // here comes the code for payment selection
    
    // Vars defined here
    // $order_id = Integer
    // $payment_info = Array
    // $processor_data = Array
    // $order_info = Array
    // $mode = 'place_order'
    
    // create credentials
    $credentials = PSH\get_credentials();
    
    // create payment request
    $paymentRequest = PSH\create_payment_request($order_info);
    
    // gera a url de pagamento
    $submit_url = PSH\get_payment_url($paymentRequest, $credentials);
    
    // salva o pedido na nossa base
    PSH\save_order($order_id);
    
    // create a redirect (form that posts) data to PagSeguro payment
    PSH\retirect_to_payment($submit_url);
}