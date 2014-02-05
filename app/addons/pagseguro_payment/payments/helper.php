<?php

namespace Addons\PagSeguro\Helper;
use Tygh\Registry;


require_once('PagSeguroLibrary/PagSeguroLibrary.php');


// addon constants
class PAYMENT {
    const NAME = 'UOL PagSeguro Payment';
    const SCRIPT = '../addons/pagseguro_payment/payments/script.php';
    const LOG_PATH = 'var/cache/pagseguro_payment';
    const CFG_PREFIX = 'addons.pagseguro_payment.';
    const REDIR_URL = 'pagseguro.complete?';
    const NOFITY_URL = 'payment_notification.pagseguro?payment=pagseguro&';
}


/**
 * Registra em PAYMENT::LOG_PATH a mensagem de log
 * @param string $msg
 * @param string $file_name
 * @param string $file_line
 */
function log($msg, $file_name = __FILE__, $file_line = __LINE__) {
    // Johny Logger, keep logging
    $logger = Tygh\Logger::instance();
    $logger->logfile = PAYMENT::LOG_PATH . date('Y-m-d') . '.log';
    $logger->write($msg, $file_name, $file_line);
}


/**
 * Retorna as credênciais do pagseguro
 * 
 * @return PagSeguroAccountCredentials
 */
function get_credentials() {
    $email = Registry::get(PAYMENT::CFG_PREFIX . 'account_id');
    $token = Registry::get(PAYMENT::CFG_PREFIX . 'account_token');

    return new \PagSeguroAccountCredentials($email,$token);
}


/**
 * Cria uma requisição de pagamento para o pagseguro
 *
 * @param array $order_info
 * @return PagSeguroPaymentRequest
 */
function create_payment_request($order_info) {
    $in_payment_request = new \PagSeguroPaymentRequest();

    set_order_info($in_payment_request, $order_info);
    set_sender($in_payment_request, $order_info);
    set_shipping($in_payment_request, $order_info);
    add_items($in_payment_request, $order_info['products']);

    return $in_payment_request;
}


/**
 * Define na requisição de pagamento
 * os detalhes mínimos do pedido
 *
 * @param PagSeguroPaymentRequest $payment_request
 * @param array $order_info
 * @return PagSeguroPaymentRequest
 */
function set_order_info($payment_request, $order_info) {
    $order = $order_info['order_id'];

    //$payment_request->addParameter('redirectURL',        \fn_url(PAYMENT::REDIR_URL .     "order_id=$order"));
    //$payment_request->addParameter('notificationURL',    \fn_url(PAYMENT::NOFITY_URL .    "order_id=$order"));
    $payment_request->addParameter('redirectURL',        "http://54.201.120.65/index.php?dispatch=pagseguro.complete&order_id=$order");
    $payment_request->addParameter('notificationURL',    "http://54.201.120.65/index.php?dispatch=payment_notification.pagseguro&payment=pagseguro&order_id=$order");
    $payment_request->setCurrency(\PagSeguroCurrencies::getIsoCodeByName('REAL'));
    $payment_request->setReference($order);

    return $payment_request;
}


/**
 * Adiciona na requisição de pagamento os itens
 * do pedido
 *
 * @param PagSeguroPaymentRequest $payment_request
 * @param array $itens do pedido
 * @return PagSeguroPaymentRequest
 */
function add_items($payment_request, $itens) {
    foreach($itens as $product) {
        $payment_request->addItem(
            $product['product_id'],
            $product['product'],
            $product['amount'],
            $product['price']
        );
    }

    return $payment_request;
}


/**
 * Define na requisição de pagamento os dados
 * da pessoa que esta enviando o pedido
 *
 * @param PagSeguroPaymentRequest $payment_request
 * @param array $sender
 * @return PagSeguroPaymentRequest
 */
function set_sender($payment_request, $sender) {
    $payment_request->setSender(
        $sender['firstname'] . ' ' . $sender['lastname'],
        $sender['email']
    );

    return $payment_request;
}


/**
 * Define na requisição de pagamento
 * o tipo de envio
 *
 * @param PagSeguroPaymentRequest $payment_request
 * @param array $shipping
 * @return PagSeguroPaymentRequest
 */
function set_shipping_type($payment_request, $shipping) {
    $name = \strtoupper($shipping['shipping']);
    $type = \PagSeguroShippingType::getCodeByType($name);
    $type = $type ?  $type : \PagSeguroShippingType::getCodeByType('NOT_SPECIFIED');
    $payment_request->setShippingType($type);

    return $payment_request;
}


/**
 * Define na requisição de pagamento
 * as informações de entrega
 * @param PagSeguroPaymentRequest $payment_request
 * @param array $shipping
 * @return PagSeguroPaymentRequest
 */
function set_shipping($payment_request, $shipping) {
    set_shipping_type($payment_request, $shipping['shipping'][0]);
    $payment_request->setShippingCost($shipping['shipping_cost']);
    $payment_request->setShippingAddress(
        $shipping['s_zipcode'],
        $shipping['s_address'],
        null,
        $shipping['s_address_2'],
        null,
        $shipping['s_city'],
        $shipping['s_state'],
        $shipping['s_country']
    );

    return $payment_request;
}


/**
 * Registra o pedido no pagseguro e retorna a url para
 * pagamento deste pedido
 * @param Object $payment_request
 * @return string url
 */
function get_url($paymentRequest) {
    return $paymentRequest->register(get_credentials());
}


/**
 * Redireciona o usuário para a página de pagamento
 *
 * @param string $to_payment url
 */
function redirect_user($to_payment) {
    // aditional data for cs-cart
    $data = array();
    $payment_name = PAYMENT::NAME;
    $exclude_empty_values = true;
    
    \fn_create_payment_form($to_payment, $data, $payment_name, $exclude_empty_values);
}


/**
 * Recebe a notificação do pagseguro
 *
 * @param string $notification do pagseguro
 */
function receive($notification) {
    $of_transaction = \PagSeguroNotificationService::checkTransaction(  
        get_credentials(),
        $notification
    );
    update_the_order($of_transaction);
}


/**
 * Confirma um pedido com uma trasação
 * 
 * @param int $order id
 * @param string $with_transaction code do pagseguro
 */
function confirm($order, $with_this_transaction) {
    $started_payment_with_pagseguro =\fn_check_payment_script(PAYMENT::SCRIPT, $order);
    if ($started_payment_with_pagseguro) {
        $of_transaction = \PagSeguroTransactionSearchService::searchByCode(
            get_credentials(),
            $with_this_transaction
        );
        $act = update_the_order($of_transaction);
        \fn_order_placement_routines($act, $order);
    }
}


/**
 * Atualiza o pedido com o status retornado pelo pagseguro
 *
 * @param PagSeguroTransaction $of_transaction
 */
function update_the_order($of_transaction) {
    $of_order = $of_transaction->getReference();
    $with_current = status($of_order);
    $pagseguro_status = $of_transaction->getStatus()->getTypeFromValue();
    $to_status = convert($pagseguro_status, $with_current);
    if ($to_status != $with_current) {
        $response = array();
        $response['order_status']   = $to_status;
        $response['reason_text']    = __('order_id') . '-' . $pagseguro_status;
        if ($from == ORDER_STATUS::NONE) {
           \fn_finish_payment($of_order, $response); 
        } else {
           \fn_update_order_payment_info($of_order, $response);
           \fn_change_order_status($of_order, $to_status, $with_current, array());
        }
    }
    return $to_status == ORDER_STATUS::NONE ? 'route' : 'save';
}


/**
 * Converte o status do pagseguro no status do cs-cart
 * @param string $pagseguro_status
 * @param string $current status
 * @return string cs-cart status
 */
function convert($pagseguro_status, $current) {
    $result = $current;

    switch($pagseguro_status) {
    case PAGSEGURO_STATUS::WAITING_PAYMENT:
    case PAGSEGURO_STATUS::IN_ANALYSIS:
        if (
           $current == ORDER_STATUS::NONE
        ) {
            $result = ORDER_STATUS::OPEN;
        }
        break;

    case PAGSEGURO_STATUS::PAID:
    case PAGSEGURO_STATUS::AVAILABLE:
        if (
            $current == ORDER_STATUS::OPEN
            || $current == ORDER_STATUS::NONE
        ) {
            $result = ORDER_STATUS::COMPLETE;
        }
        break;

    case PAGSEGURO_STATUS::REFUNDED:
    case PAGSEGURO_STATUS::IN_DISPUTE:
    case PAGSEGURO_STATUS::CANCELLED:
        if (
            $current == ORDER_STATUS::OPEN
        ) {
            $result = ORDER_STATUS::CANCELED;
        }
        break;
    }

    return $result;
}


/**
 * Retorna o status de um pedido
 * @param int $order id
 * @return string order status
*/
function status($of_order) {
    $order_short_info = \fn_get_order_short_info($of_order);
    return $order_short_info['status'];
}


class PAGSEGURO_STATUS {
    const WAITING_PAYMENT = 'WAITING_PAYMENT';
    const IN_ANALYSIS = 'IN_ANALYSIS';
    const PAID = 'PAID';
    const AVAILABLE = 'AVAILABLE';
    const REFUNDED = 'REFUNDED';
    const IN_DISPUTE = 'IN_DISPUTE';
    const CANCELLED = 'CANCELLED';
}


// http://www.cs-cart.com/documentation/reference_guide/index.htmld?orders_order_statuses.htm   
class ORDER_STATUS {
    const BACKORDERED = \STATUS_BACKORDERED_ORDER;
    const COMPLETE = 'C';
    const DECLINED = 'D';
    const FAILED = 'F';
    const CANCELED = \STATUS_CANCELED_ORDER;
    const NONE = \STATUS_INCOMPLETED_ORDER;
    const OPEN = \STATUSES_ORDER;
    const PROCESSED = 'P';
    const DARTH_VADER = \STATUS_PARENT_ORDER; // Luke, I'm your father...
}