<?php

/**
 *    Pay.nl
 *    Date: 8-7-2014
 *    Version: 1.0.7
 */
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {// callback
    $order_info = null;
    $orderId = intval($_REQUEST['csCartOrderId']);

    if ($mode == 'exchange' || $mode == 'finish') {
        if (fn_check_payment_script('paynl.php', $orderId)) {
            $order_info = fn_get_order_info($orderId, true);
            if ($order_info['status'] == 'N') {
                fn_change_order_status($orderId, 'O', '', false);
            }
        }

        $payNLTransactionID = $mode == 'exchange' ? $_REQUEST['order_id'] : $_REQUEST['orderId'];
        if (empty($processor_data)) {
            $processor_data = fn_get_processor_data($order_info['payment_id']);
        }

        $state = fn_paynl_getState($payNLTransactionID, $processor_data);

        $alreadyPaid = fn_isAlreadyPAID($payNLTransactionID);
        if ($alreadyPaid) {
            $message = 'Order  already PAID';
            if ($mode == 'exchange') {
                echo 'TRUE|' . $message;
                die;
            } else {
                fn_order_placement_routines('route', $orderId);
            }
        }
        // only update the state if not already paid
        if (!$alreadyPaid) {
            $statuses = $processor_data['processor_params']['statuses'];
            if (isset($statuses[strtolower($state)])) {
                fn_manageState($state, $statuses[strtolower($state)], $mode,
                    $orderId, $payNLTransactionID, $processor_data);
            }
        }

    }
} else {//create the transaction
    $paymentOptionSub = null;
    if (isset($_REQUEST['paymentOptionbSubId'])) {
        $paymentOptionSub = $_REQUEST['paymentOptionbSubId'];
    }

    $exchangeUrl = fn_url("payment_notification.exchange?payment=paynl&csCartOrderId=$order_id",
        AREA, 'current');
    $finishUrl = fn_url("payment_notification.finish?payment=paynl&csCartOrderId=$order_id",
        AREA, 'current');
    $result = fn_paynl_startTransaction($order_id, $order_info,
        $processor_data, $exchangeUrl, $finishUrl, $paymentOptionSub);
    $data = array(
        'transaction_id' => $result['transaction']['transactionId'],
        'option_id' => $processor_data['processor_params']['optionId'],
        'amount' => floatval($order_info['total']) * 100, //cents
        'order_id' => $order_id,
        'start_data' => date('Y-m-d H:i:s')
    );

    db_query("INSERT INTO ?:paynl_transactions  ?e", $data);

    //update table order
    fn_change_order_status($order_id, 'O', '', false);
    $url = $result['transaction']['paymentURL'];
    if (isset($url)) {
        fn_redirect($url, true);
        exit;
    } else {
        fn_set_notification('E',
            "There was an error while processing your transaction: ", "");
        fn_redirect(Registry::get('config.http_location') . "/?dispatch=checkout.cart");
    }

    die;
}

function fn_updatePayTransaction($transactionId, $status)
{
    $now = new DateTime();
    $data = array(
        'status' => $status,
        'last_update' => date('Y-m-d H:i:s')
    );
    db_query('UPDATE ?:paynl_transactions SET ?u WHERE transaction_id = ?i',
        $data, $transactionId);
}

function fn_isAlreadyPAID($transactionID)
{
    $orderID = db_get_field('SELECT order_id FROM ?:paynl_transactions WHERE transaction_id =?s',
        $transactionID);
    $arrTransactions = db_get_field('SELECT count(*) FROM ?:paynl_transactions WHERE order_id =?s AND status = "PAID" ',
        $orderID);

    if (intval($arrTransactions) > 0) {
        return true;
    } else return false;
}

function fn_manageState($state, $idstate, $mode, $orderId, $payNLTransactionID,
                        $processor_data)
{
    switch ($state) {
        case 'PENDING':
            if ($mode == 'exchange') {
                echo 'TRUE| state:PENDING, orderId:' . $orderId . ', transactionId:' . $payNLTransactionID .
                    ',idState:' . $idstate . ', service_id:' . $processor_data['processor_params']['service_id'] .
                    ',token_api:' . $processor_data['processor_params']['token_api'] .
                    ',statuses:' . print_r($processor_data['processor_params']['statuses'],
                        true);
            } else {
                fn_order_placement_routines('route', $orderId);
            }
            die;
            break;
        case 'PAID':

            $payData = fn_paynl_getInfo($payNLTransactionID, $processor_data);

            $pp_response = array(
                'order_status' => $idstate,
                'naam' => $payData['paymentDetails']['identifierName'],
                'rekening' => $payData['paymentDetails']['identifierPublic']
            );

            if ($mode == 'exchange') {
                echo 'TRUE| orderId=' . $orderId . ', transactionId=' . $payNLTransactionID .
                    ',idState:' . $idstate . ', service_id:' . $processor_data['processor_params']['service_id'] .
                    ',token_api:' . $processor_data['processor_params']['token_api'] .
                    ',statuses:' . print_r($processor_data['processor_params']['statuses'],
                        true);

                fn_finish_payment($orderId, $pp_response, true);
                fn_updatePayTransaction($payNLTransactionID, 'PAID');
                die;
            } else {
                fn_order_placement_routines('route', $orderId);
            }

            break;
        case 'CANCEL':

            if ($mode == 'exchange') {
                echo 'TRUE| CANCEL orderId=' . $orderId . ', transactionId=' . $payNLTransactionID .
                    ',idState:' . $idstate . ', service_id:' . $processor_data['processor_params']['service_id'] .
                    ',token_api:' . $processor_data['processor_params']['token_api'] .
                    ',statuses:' . print_r($processor_data['processor_params']['statuses'],
                        true);
                fn_updatePayTransaction($payNLTransactionID, 'CANCEL');
                die;
            } else {
                fn_updatePayTransaction($payNLTransactionID, 'CANCEL');
                fn_change_order_status($orderId, $idstate, '', false);
                fn_order_placement_routines('route', $orderId);
            }

            break;
        case 'CHECKAMOUNT':

            if ($mode == 'exchange') {
                echo 'TRUE| CHECKAMOUNT orderId=' . $orderId . ', transactionId=' . $payNLTransactionID .
                    ',idState:' . $idstate . ', service_id:' . $processor_data['processor_params']['service_id'] .
                    ',token_api:' . $processor_data['processor_params']['token_api'] .
                    ',statuses:' . print_r($processor_data['processor_params']['statuses'],
                        true);
                fn_updatePayTransaction($payNLTransactionID, 'CHECKAMOUNT');
                die;
            } else {
                fn_updatePayTransaction($payNLTransactionID, 'CHECKAMOUNT');
                fn_change_order_status($orderId, $idstate, '', false);
                fn_order_placement_routines('route', $orderId, false);
            }

            break;

        default:
            $pp_response['order_status'] = $processor_data['processor_params']['statuses'][$state];
            fn_updatePayTransaction($payNLTransactionID, 'PENDING');
            fn_change_order_status($orderId, $pp_response['order_status'], '',
                false);
            break;
    }
}

?>
