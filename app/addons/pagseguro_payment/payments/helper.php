<?php
namespace Addons\PagSeguro\Helper;


require_once(
'PagSeguroLibrary/PagSeguroLibrary.php');
'PagSeguroLibrary/PagSeguroLibrary.php';


use Tygh\Registry;


function logger($msg, $f, $l)
{
    // Johny Logger, keep logging
    $logger = Tygh\Logger::instance();
    $logger->logfile = 'var/cache/pagseguro_payment' . date('Y-m-d') . '.log';
    $logger->write($msg, $f, $l);
}


function get_credentials()
{
    $email = Registry::get('addons.pagseguro_payment.account_id');
    $token = Registry::get('addons.pagseguro_payment.account_token');
    return new \PagSeguroAccountCredentials($email,$token);
}


function create_payment_request($order_info)
{
    $paymentRequest = new \PagSeguroPaymentRequest();

    set_order_info($paymentRequest, $order_info);
    set_sender($paymentRequest, $order_info);
    set_shipping($paymentRequest, $order_info);
    add_items($paymentRequest, $order_info['products']);
    return $paymentRequest;
}


function set_order_info($paymentRequest, $order_info)
{
    $id = $order_info['order_id'];
    $paymentRequest->addParameter('redirectURL', \fn_url("pagseguro.complete?order_id=$id"));
    $paymentRequest->addParameter('notificationURL', \fn_url("payment_notification.pagseguro?payment=pagseguro&order_id=$id"));
    $paymentRequest->setReference($id);
    $paymentRequest->setCurrency(\PagSeguroCurrencies::getIsoCodeByName('REAL'));
    return $paymentRequest;
}


function add_items($paymentRequest, $itens)
{
    foreach($itens as $product)
    {
        $paymentRequest->addItem(
            $product['product_id'],
            $product['product'],
            $product['amount'],
            $product['price']
        );
    }
    return $paymentRequest;
}


function set_sender($paymentRequest, $sender_info)
{
    $paymentRequest->setSender(
        $sender_info['firstname'] . ' ' . $sender_info['lastname'],
        $sender_info['email']
    );
    return $paymentRequest;
}


function set_shipping_type($paymentRequest, $shipping_info)
{
    $typeName = strtoupper($shipping_info['shipping']);
    $type = \PagSeguroShippingType::getCodeByType($typeName);
    $type = $type === false ? \PagSeguroShippingType::getCodeByType('NOT_SPECIFIED') : $type;
    $paymentRequest->setShippingType($type);
    return $paymentRequest;
}


function set_shipping($paymentRequest, $shipping_info)
{
    set_shipping_type($paymentRequest, $shipping_info['shipping'][0]);
    $paymentRequest->setShippingCost($shipping_info['shipping_cost']);
    $paymentRequest->setShippingAddress(
        $shipping_info['s_zipcode'],
        $shipping_info['s_address'],
        null,
        $shipping_info['s_address_2'],
        null,
        $shipping_info['s_city'],
        $shipping_info['s_state'],
        $shipping_info['s_country']
    );
    return $paymentRequest;
}


function get_payment_url($paymentRequest, $credentials)
{
    return $paymentRequest->register($credentials);
}


function retirect_to_payment($submit_url)
{
    // aditional data for cs-cart
    $data = array();
    $payment_name = 'UOL PagSeguro Payment';
    $exclude_empty_values = true;
    
    // create a redirect (form that posts) data to PagSeguro payment
    fn_create_payment_form($submit_url, $data, $payment_name, $exclude_empty_values);
}


function payment_notification($code)
{
    if ($_REQUEST['notificationType'] === 'transaction') {
        $transaction = \PagSeguroNotificationService::checkTransaction(  
            get_credentials(),
            $_REQUEST['notificationCode']
        );
        
        $order_id = $transaction->getReference();
        $status = $transaction->getStatus();
        $status_type = $status->getTypeFromValue();
        
        $pp_response = array();
        $pp_response['reason_text'] = __('order_id') . '-' . $status_type;
        $pp_response['order_status'] = translate_pagseguro_status($status_type, $order_id);

        fn_update_order_payment_info($order_id, $pp_response);
        fn_change_order_status($order_id, $pp_response['order_status'], '', array());
    }
}


function translate_pagseguro_status($type, $order_id)
{
    // http://www.cs-cart.com/documentation/reference_guide/index.htmld?orders_order_statuses.htm
    // B: Backordered       C: Complete         D: Declined         F: Failed
    // I: Canceled          N: None             O: Open             P: Processed
    $cur_status = fn_get_order_short_info($order_id)['status'];
    $result = $cur_status;
    switch($status_type)
    {
        case 'WAITING_PAYMENT':
        case 'IN_ANALYSIS':
            if(in_array($cur_status, array('N')))
            {
                $result = 'O';
            }
            break;
        case 'PAID':
        case 'AVAILABLE':
            if(in_array($cur_status, array('O', 'N')))
            {
                $result = 'P';
            }
            break;
        case 'REFUNDED':
        case 'IN_DISPUTE':
        case 'CANCELLED':
            if(in_array($cur_status, array('O', 'N')))
            {
                $result = 'I';
            }
            break;
    }
    return $result;
}