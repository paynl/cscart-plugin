<?php

use Tygh\Registry;

require_once(dirname(__FILE__) . '/paynl/classes/Autoload.php');

function fn_getCredential($var)
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    return array('token_api' => $paynl_setting['token_api'],
        'service_id' => $paynl_setting['service_id']);
}

function fn_get_ideal_banks($processor_data)
{

    $service = new Pay_Api_Getservice();
    $service->setApiToken($processor_data['processor_params']['token_api']);
    $service->setServiceId($processor_data['processor_params']['service_id']);
    try {
        $result = $service->doRequest();
        $banks = $result['paymentOptions'][$processor_data['processor_params']['optionId']]['paymentOptionSubList'];

        return $banks;
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());

    }
}

function fn_getPaynlOptionId($var)
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    $service = new Pay_Api_Getservice();
    $service->setApiToken($paynl_setting['token_api']);
    $service->setServiceId($paynl_setting['service_id']);
    try {
        $result = $service->doRequest();
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
        fn_redirect('/index.php?dispatch=checkout.checkout');
    }

    return $result['paymentOptions'];
}

function fn_paynl_getInfo($payNLTransactionID, $processor_data)
{
    $payApiInfo = new Pay_Api_Info();
    $payApiInfo->setApiToken($processor_data['processor_params']['token_api']);
    $payApiInfo->setServiceId($processor_data['processor_params']['service_id']);
    $payApiInfo->setTransactionId($payNLTransactionID);
    try {
        $result = $payApiInfo->doRequest();
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
        fn_redirect('/index.php?dispatch=checkout.checkout');
    }
    return $result;
}

function fn_paynl_getState($payNLTransactionID, $processor_data)
{
    $payApiInfo = new Pay_Api_Info();
    $payApiInfo->setApiToken($processor_data['processor_params']['token_api']);
    $payApiInfo->setServiceId($processor_data['processor_params']['service_id']);
    $payApiInfo->setTransactionId($payNLTransactionID);
    try {
        $result = $payApiInfo->doRequest();
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
        fn_redirect('/index.php?dispatch=checkout.checkout');
    }
    $state = Pay_Helper::getStateText($result['paymentDetails']['state']);
    return $state;
}

function fn_paynl_startTransaction($order_id, $order_info, $processor_data, $exchangeUrl, $finishUrl, $paymentOptionSubId = null)
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    $currency = CART_PRIMARY_CURRENCY;
    $payNL = new Pay_Api_Start();
    $payNL->setApiToken($processor_data['processor_params']['token_api']);
    $payNL->setServiceId($processor_data['processor_params']['service_id']);
    $payNL->setAmount(floatval($order_info['total']) * 100);
    $payNL->setPaymentOptionId($processor_data['processor_params']['optionId']);

    if (!empty($paymentOptionSubId)) {
        $payNL->setPaymentOptionSubId($paymentOptionSubId);
    }

    $payNL->setExchangeUrl($exchangeUrl);
    $payNL->setCurrency($currency);
    $payNL->setFinishUrl($finishUrl);
    $payNL->setDescription($order_info['order_id']);

    $s_address = splitAddress(trim($order_info['s_address'] . ' ' . $order_info['s_address_2']));
    $b_address = splitAddress(trim($order_info['b_address'] . ' ' . $order_info['b_address_2']));
    $payNL->setEnduser(array('accessCode' => $order_info['user_id'],
            'language' => $order_info['lang_code'],
            'initials' => $order_info['s_firstname'],
            'lastName' => $order_info['s_lastname'],
            'phoneNumber' => $order_info['s_phone'],
            'dob' => $order_info['birthday'],
            'emailAddress' => $order_info['email'],
            'address' => array('streetName' => $s_address[0],
                'streetNumber' => substr($s_address[1], 0, 4),
                'zipCode' => $order_info['s_zipcode'],
                'city' => $order_info['s_city'],
                'countryCode' => $order_info['s_country']),
            'invoiceAddress' => array('initials' => $order_info['b_firstname'],
                'lastName' => $order_info['b_lastname'],
                'streetName' => $b_address[0],
                'streetNumber' => substr($b_address[1], 0, 4),
                'zipCode' => $order_info['b_zipcode'],
                'city' => $order_info['b_city'],
                'countryCode' => $order_info['b_country']))
    );
    $payNL->setExtra1($order_id);

    foreach ($order_info['products'] as $key => $product) {
        $payNL->addProduct(
            $product['product_id'],
            $product['product'],
            floatval($product['price']) * 100,
            $product['amount'],
            paynl_getTaxClassForItem($order_info, $key)
        );
    }


    $surcharge = floatval($order_info['payment_surcharge']);
    $ship = fn_order_shipping_cost($order_info);

    if (floatval($order_info['payment_surcharge'])) {
        $item_name = $order_info['payment_method']['surcharge_title'];
        $payNL->addProduct(substr($item_name, 0, 24), $item_name, floatval($order_info['payment_surcharge']) * 100, 1, 'H');
    }

    // Shipping
    $shipping_cost = floatval($order_info['shipping_cost']) * 100;
    if (isset($shipping_cost) && $shipping_cost > 0) {
        $payNL->addProduct('shipping_cost', __('shipping_cost'), $shipping_cost, 1, 'H');
    }
    //gift
    if (!empty($order_info['use_gift_certificates'])) {
        foreach ($order_info['use_gift_certificates'] as $k => $v) {
            $payNL->addProduct($v['gift_cert_id'], $k, floatval($v['cost']) * (-100), 1, 'N');
        }
    }

    if (isset($order_info['subtotal_discount']) && $order_info['subtotal_discount'] > 0)
        $payNL->addProduct(__('discount'), __('discount'), $order_info['subtotal_discount'] * -100, 1, 'N');
    if (!empty($order_info['gift_certificates'])) {
        foreach ($order_info['gift_certificates'] as $k => $v) {
            $v['amount'] = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : $v['amount'];
            $payNL->addProduct($v['gift_cert_id'], $v['gift_cert_code'], (-100) * $v['amount'], 1, 'N');
        }
    }

    try {
        $result = $payNL->doRequest();
        return $result;
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
        fn_redirect('/index.php?dispatch=checkout.checkout');
    }
}

function splitAddress($strAddress)
{
    $strAddress = trim($strAddress);
    $a = preg_split('/([0-9]+)/', $strAddress, 2, PREG_SPLIT_DELIM_CAPTURE);
    $strStreetName = trim(array_shift($a));
    $strStreetNumber = trim(implode('', $a));

    if (empty($strStreetName)) { // American address notation
        $a = preg_split('/([a-zA-Z]{2,})/', $strAddress, 2, PREG_SPLIT_DELIM_CAPTURE);

        $strStreetNumber = trim(implode('', $a));
        $strStreetName = trim(array_shift($a));
    }

    return array($strStreetName, $strStreetNumber);
}

function paynl_getTaxClassForItem($order_info, $item_id)
{
    foreach ($order_info['taxes'] as $tax_rule) {
        if (
            array_key_exists($item_id, $tax_rule['applies']['items']['P']) &&
            $tax_rule['applies']['items']['P'][$item_id] === true
        ) {
            $percentage = floatval($tax_rule['rate_value']);
            return paynl_getTaxClass($percentage);
        }
        return null;
    }
}

function paynl_getTaxClass($percentage)
{
    $taxClasses = array(
        0 => 'N',
        6 => 'L',
        21 => 'H'
    );
    $nearestTaxRate = paynl_nearest($percentage, array_keys($taxClasses));
    return ($taxClasses[$nearestTaxRate]);
}

/**
 * Get the nearest number
 *
 * @param int $number
 * @param array $numbers
 * @return int|bool nearest number false on error
 */
function paynl_nearest($number, $numbers)
{
    $output = FALSE;
    $number = intval($number);
    if (is_array($numbers) && count($numbers) >= 1) {
        $NDat = array();
        foreach ($numbers as $n) {
            $NDat[abs($number - $n)] = $n;
        }
        ksort($NDat);
        $NDat = array_values($NDat);
        $output = $NDat[0];
    }
    return $output;
}