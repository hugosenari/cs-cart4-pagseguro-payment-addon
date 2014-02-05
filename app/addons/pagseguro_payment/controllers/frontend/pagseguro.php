<?php

use Addons\PagSeguro\Helper as Please;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// here comes the code for user payment success (this not mean that they pay)
// params to be here: dispatch[pagseguro.complete]=bar&order_id=baz

$order = $_REQUEST['order_id'];
$with_this_transaction = $_REQUEST['transaction_id'];
if (
    $mode == 'complete'
    && !empty($order)
    && !empty($with_this_transaction)
) {
    Please\confirm($order, $with_this_transaction);
}