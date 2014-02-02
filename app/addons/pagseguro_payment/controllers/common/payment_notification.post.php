<?php

require_once('../../payments/helper.php');
use Addons\PagSeguro\Helper as PSH;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


// here comes the code for notifications
// params to be here: dispatch[payment_notification.pagseguro]=bar&payment=pagseguro&order_id=baz

// Vars defined here
// $mode == 'dumb_payment' // whatever you set after payment_notification.

if (
    $mode == 'pagseguro' &&
    !empty($_REQUEST['notificationCode']) &&
    $_REQUEST['notificationType'] == 'transaction'
)
{
    define('PAYMENT_NOTIFICATION', true);
    PSH\payment_notification($_REQUEST['notificationCode']);
}