<?php

/**
 *    PAY.
 *    Date: 11-06-2021
 *    Version: 1.0.7
 */
use Tygh\Registry;
use PayNL\Sdk\Util\Exchange;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {
    # Callback
    $order_info = null;
    $orderId = intval($_REQUEST['csCartOrderId']);

    if ($mode == 'finish') {
        fn_order_placement_routines('route', $orderId, false);
        die();
    }

    $action = $_REQUEST['object']['status']['action'] ?? '';
    $payNLTransactionID = $_REQUEST['object']['orderId'] ?? '';
    if (strtolower($action) == 'pending' || strtolower($action) == 'refund:received' || empty($action) || empty($payNLTransactionID)) {
        die('TRUE|Ignoring ' . (empty($payNLTransactionID) ? ', no order id' : $action));
    }

    $order_info = fn_get_order_info($orderId, true);
    $csCartOrderAmount = (int)str_replace('.', '', $order_info['total']) / 100;
    $processor_data = fn_get_processor_data($order_info['payment_id']);
    $statuses = $processor_data['processor_params']['statuses'];

    if ($mode == 'exchange') {
        try {
            $config = getConfig(getTokencode(), getApiToken());

            $exchange = new Exchange();
            $payOrder = $exchange->process($config);

            $alreadyPaid = fn_isAlreadyPAID($payOrder->getId()) || $order_info['status'] == 'P';
            
            if ($alreadyPaid) {
                $exchange->setResponse(true, 'Order already PAID');
            }
            
            $responseResult = true;
            $responseMessage = 'Processed';
            
            if ($payOrder->isPending()) {
                $responseResult = true;
                $responseMessage = 'Processed pending';
                $idstate = $statuses['pending'] ?? 'N';
                
            } elseif ($payOrder->isPaid() || $payOrder->isAuthorized()) {
                $responseMessage = 'Processed paid. Order: ' . $payOrder->getReference();
                $idstate = $statuses['paid'] ?? $statuses['authorize'] ?? 'P';
                
                if (fn_check_payment_script('paynl.php', $orderId)) {
                    fn_change_order_status($orderId, $idstate);
                }
                
                fn_updatePayTransaction($payOrder->getId(), $payOrder->isPaid() ? 'PAID' : 'AUTHORIZE');
                
                $pp_response = array(
                    'order_status' => $idstate, 
                    'naam' => $payOrder->getPaymentMethod(),
                    'rekening' => $payOrder->getReference()
                );
                fn_finish_payment($orderId, $pp_response);
                
            } elseif ($payOrder->isCancelled()) {
                $responseResult = true;
                $responseMessage = 'Processed cancelled';
                $idstate = $statuses['cancelled'] ?? 'I';
                
                if (fn_check_payment_script('paynl.php', $orderId)) {
                    fn_change_order_status($orderId, $idstate);
                }
                fn_updatePayTransaction($payOrder->getId(), 'CANCEL');
                
            } else {
                $responseResult = true;
                $responseMessage = 'No action defined for payment state ' . $payOrder->getStatusCode();
            }
            
            $exchange->setResponse($responseResult, $responseMessage);
            
        } catch (Throwable $exception) {
            $exchange = new Exchange();
            $exchange->setResponse(false, $exception->getMessage());
        }
    }
} else {
    # Create the transaction
    $paymentOptionSub = null;
    if (isset($_REQUEST['paymentOptionbSubId'])) {
        $paymentOptionSub = $_REQUEST['paymentOptionbSubId'];
    }

    $exchangeUrl = fn_url("payment_notification.exchange?payment=paynl&csCartOrderId=$order_id", AREA, 'current');
    $finishUrl = fn_url("payment_notification.finish?payment=paynl&csCartOrderId=$order_id", AREA, 'current');
    $result = fn_paynl_startTransaction($order_id, $order_info, $processor_data, $exchangeUrl, $finishUrl);
    $data = array(
        'transaction_id' => $result['transaction']['transactionId'],
        'option_id' => $processor_data['processor_params']['optionId'],
        'amount' => floatval($order_info['total']) * 100, //cents
        'order_id' => $order_id,
        'start_data' => date('Y-m-d H:i:s')
    );

    db_query("INSERT INTO ?:paynl_transactions  ?e", $data);

    # Update table order
    fn_change_order_status($order_id, 'O', '', false);
    $url = $result['transaction']['paymentURL'];
    if (isset($url)) {
        header("Location: $url");
        exit;
    } else {
        fn_set_notification('E', "There was an error while processing your transaction: ", "");
        fn_redirect(Registry::get('config.http_location') . "/?dispatch=checkout.cart");
    }

    die;
}

function fn_updatePayTransaction($transactionId, $status)
{
    $data = array('status' => $status, 'last_update' => date('Y-m-d H:i:s'));
    db_query('UPDATE ?:paynl_transactions SET ?u WHERE transaction_id = ?i', $data, $transactionId);
}

function fn_isAlreadyPAID($transactionID)
{
    $orderID = db_get_field('SELECT order_id FROM ?:paynl_transactions WHERE transaction_id =?s', $transactionID);

    $arrTransactions = db_get_field('SELECT count(*) FROM ?:paynl_transactions WHERE order_id =?s AND status = "PAID" ', $orderID);

    if (intval($arrTransactions) > 0) {
        return true;
    } else return false;
}