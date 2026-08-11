<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode == 'get_payment_methods') {
    $tokenCode = !empty($_REQUEST['tokenCode']) ? $_REQUEST['tokenCode'] : null;
    $apiToken = !empty($_REQUEST['apiToken']) ? $_REQUEST['apiToken'] : null;
    $serviceId = !empty($_REQUEST['serviceId']) ? $_REQUEST['serviceId'] : null;

    $paymentMethods = fn_getPaymentMethods($tokenCode, $apiToken, $serviceId);

    header('Content-Type: application/json');
    echo json_encode(array(
        'paymentMethods' => $paymentMethods,
    ));
    exit;
}
