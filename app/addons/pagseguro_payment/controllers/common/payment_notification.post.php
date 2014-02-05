<?php

use Addons\PagSeguro\Helper as PSH;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// here comes the code for notifications
// params to be here: dispatch[payment_notification.pagseguro]=bar

$notification = $_REQUEST['notificationCode'];

if (
    $mode == 'pagseguro' &&
    $_REQUEST['notificationType'] == 'transaction'  &&
    !empty($notification)
) {
    define('PAYMENT_NOTIFICATION', true);
    PSH\receive($notification);
}