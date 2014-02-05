<?php

use Addons\PagSeguro\Helper as PSH;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// here comes the code for user payment success (this not mean that they pay)
// params to be here: dispatch[pagseguro.complete]=bar&order_id=baz

$order = $_REQUEST['order_id'];

if (
    $mode == 'complete' &&
    !empty($order)
) {
    PSH\confirm($order);
}