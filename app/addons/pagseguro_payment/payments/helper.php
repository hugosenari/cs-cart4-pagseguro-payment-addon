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
    $paymentRequest->addParameter('redirectURL', "http://php-barzilay.rhcloud.com/index.php?dispatch=checkout.complete&order_id=$id");
    //$paymentRequest->addParameter('redirectURL', \fn_url("checkout.complete?order_id=$id"));
    $paymentRequest->addParameter('notificationURL', "http://php-barzilay.rhcloud.com/index.php?dispatch=payment_notification.foo&order_id=$id");
    //$paymentRequest->addParameter('notificationURL', \fn_url("payment_notification.foo?payment=pagseguro&order_id=$id"));
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


function save_order($id)
{
    \fn_order_placement_routines('save', $id);
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


function payment_notification($mode)
{
    //logger("Notificacao ($mode): " . print_r($_REQUEST, true), __FILE__, __LINE__);
    if ($_REQUEST['notificationType'] === 'transaction') {
        $transaction = \PagSeguroNotificationService::checkTransaction(  
            get_credentials(),
            $_REQUEST['notificationCode']
        );
        $order_id = $transaction->getReference();
        $status = $transaction->getStatus();
        switch($status->getTypeFromValue())
        {
            case 'WAITING_PAYMENT':
                break;
            case 'IN_ANALYSIS':
                break;
            case 'PAID':
                break;
            case 'AVAILABLE':
                break;
            case 'IN_DISPUTE':
                break;
            case 'REFUNDED':
                break;
            case 'CANCELLED':
                break;
        }
    }
}