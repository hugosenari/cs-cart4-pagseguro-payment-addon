<?php

require_once('helper.php');

use Addons\PagSeguro\Helper as Please; // we will be polite


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
    
    $of_payment_request = Please\create_payment_request($order_info);
    $to_payment = Please\get_url($of_payment_request);

    Please\redirect_user($to_payment);
}