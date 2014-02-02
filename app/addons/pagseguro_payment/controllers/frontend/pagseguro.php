<?php

require_once('../../payments/helper.php');
use Addons\PagSeguro\Helper as PSH;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// here comes the code for user payment success (this not mean that they pay)

if ($mode == 'complete')
{
    $order_id = $_REQEST['order_id'];
    $order_short_info = fn_get_order_short_info($order_id);
    $cur_status = $order_short_info['status'];
    print_r($order_short_info);
    //PSH\logger( true), __FILE__, __LINE__);
    if($cur_status == 'N')
    {
        $pp_response = array();
        $pp_response['order_status'] = 'O';
        $pp_response['reason_text'] = __('order_id') . '-' . $_REQUEST['order_number'] . 'Pedido registrado no pagseguro';
        
        fn_update_order_payment_info($order_id, $pp_response);
    }
    fn_order_placement_routines('save', $_REQUEST["order_id"]); 
}