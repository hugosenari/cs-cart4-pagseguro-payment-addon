<?php

require_once('helper.php');

use Addons\PagSeguro\Helper as PSH;


// test if cs-cart was installed
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION'))
{
    // this will never happen :(
    // see ../../../app/controllers/common/payment_notification.php
    
    // Otherwise we can create a post controller for payment_notification :)
    // see ../controllers/common/payment_notification.post.php
    // and http://docs.cs-cart.com/precontrollers-postcontrollers
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
        
    // create a redirect (form that posts) data to PagSeguro payment
    PSH\retirect_to_payment($submit_url);
}
exit;