<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// here comes the code for user payment success (this not mean that they pay)

if (
    $mode == 'complete' &&
    fn_check_payment_script('../addons/pagseguro_payment/payments/script.php', $_REQUEST["order_id"]))
{
    $order_id = $_REQUEST["order_id"];
    $order_short_info = fn_get_order_short_info($order_id);
    $cur_status = $order_short_info['status'];
    if($cur_status == 'N')
    {
        $pp_response = array();
        $pp_response['order_status'] = 'O';
        $pp_response['reason_text'] = __('order_id') . '-' . $order_id . '-Pedido registrado no pagseguro';
        
        fn_finish_payment($order_id, $pp_response);
    }
    fn_order_placement_routines('save', $order_id);
}