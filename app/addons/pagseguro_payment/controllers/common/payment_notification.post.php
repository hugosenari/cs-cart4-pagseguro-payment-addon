<?php

use Addons\PagSeguro\Helper as Please;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// here comes the code for notifications
// params to be here: dispatch[payment_notification.pagseguro]=bar

$this_notification = $_REQUEST['notificationCode'];

if (
    $mode == 'pagseguro' &&
    $_REQUEST['notificationType'] == 'transaction'  &&
    !empty($this_notification)
) {
    define('PAYMENT_NOTIFICATION', true);
    Please\receive($this_notification);
}