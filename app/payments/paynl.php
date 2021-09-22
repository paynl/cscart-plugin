<?php

/**
 *    PAY.
 *    Date: 11-06-2021
 *    Version: 1.0.7
 */
use Tygh\Registry;

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

    $action = $_REQUEST['action'] ?? '';
    $payNLTransactionID = $_REQUEST['order_id'] ?? '';
    if ($action == 'pending' || $action == 'refund:received' || empty($action) || empty($payNLTransactionID)) {
        die('TRUE|Ignoring ' . (empty($payNLTransactionID) ? ', no order id' : $action));
    }

    $order_info = fn_get_order_info($orderId, true);
    $csCartOrderAmount = (int)str_replace('.', '', $order_info['total']);
    $processor_data = fn_get_processor_data($order_info['payment_id']);
    $statuses = $processor_data['processor_params']['statuses'];

    if ($mode == 'exchange') {
        # Retrieve PAY.-data
        $payData = fn_paynl_getStatus($payNLTransactionID, $processor_data);
        $alreadyPaid = fn_isAlreadyPAID($payNLTransactionID) || $order_info['status'] == 'P';

        if ($alreadyPaid) {
            die('TRUE|Order already PAID');
        }
        $payAmount = (int)$payData['paymentDetails']['amountOriginal']['value'];
        $state = Pay_Helper::getStateText($payData['paymentDetails']['state']);
        $bPaid = in_array($state, array(Pay_Helper::PAYMENT_AUTHORIZE, Pay_Helper::PAYMENT_PAID));

        if ($bPaid && $payAmount !== $csCartOrderAmount) {
            die('TRUE|Failed, invalid amounts: ' . $payAmount . ' vs ' . $csCartOrderAmount);
        }

        $idstate = $statuses[strtolower($state)];
        if (!empty($idstate)) {
            if (fn_check_payment_script('paynl.php', $orderId)) {
                fn_change_order_status($orderId, $idstate);
            }

            fn_updatePayTransaction($payNLTransactionID, $state);

            if ($bPaid) {
                $pp_response = array('order_status' => $idstate, 'naam' => $payData['paymentDetails']['identifierName'], 'rekening' => $payData['paymentDetails']['identifierPublic']);
                fn_finish_payment($orderId, $pp_response);
            }
            die('TRUE| Updated status to: ' . $state . ' state_id: ' . $idstate);
        }
        die('TRUE| unknown status ' . $state);
    }
} else {
    # Create the transaction
    $paymentOptionSub = null;
    if (isset($_REQUEST['paymentOptionbSubId'])) {
        $paymentOptionSub = $_REQUEST['paymentOptionbSubId'];
    }

    $exchangeUrl = fn_url("payment_notification.exchange?payment=paynl&csCartOrderId=$order_id", AREA, 'current');
    $finishUrl = fn_url("payment_notification.finish?payment=paynl&csCartOrderId=$order_id", AREA, 'current');
    $result = fn_paynl_startTransaction($order_id, $order_info, $processor_data, $exchangeUrl, $finishUrl, $paymentOptionSub);
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
        fn_redirect($url, true);
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