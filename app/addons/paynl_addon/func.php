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
        return $result['paymentOptions'][$processor_data['processor_params']['optionId']]['paymentOptionSubList'];
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
    }
}


function fn_paynl_getStatus($payNLTransactionID, $processor_data)
{
    $payApiInfo = new Pay_Api_Status();
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

function getObjectData()
{
    $phpVersion = substr(phpversion(), 0, 3);
    $cscartVersion = defined('PRODUCT_VERSION') ? PRODUCT_VERSION : '-';
    $payPlugin = '1.1.7';

    return substr('cscart ' . $payPlugin . ' | ' . $cscartVersion . ' | ' . $phpVersion, 0, 64);
}

function fn_paynl_startTransaction($order_id, $order_info, $processor_data, $exchangeUrl, $finishUrl, $paymentOptionSubId = null)
{
    $currency = CART_PRIMARY_CURRENCY;
    $payNL = new Pay_Api_Start();
    $payNL->setApiToken($processor_data['processor_params']['token_api']);
    $payNL->setServiceId($processor_data['processor_params']['service_id']);
    $payNL->setAmount(floatval($order_info['total']) * 100);
    $payNL->setPaymentOptionId($processor_data['processor_params']['optionId']);
    $payNL->setObject(getObjectData());

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

    $payNL->setIpAddress($order_info['ip_address']);

    foreach ($order_info['products'] as $key => $product) {
        $prices = paynl_getTaxForItem($order_info, $key);

        $taxPercent = $prices['tax_amount'] / $prices['price_excl'] * 100;

        $taxClass = paynl_getTaxClass($taxPercent);

        $payNL->addProduct(
            $product['product_id'],
            $product['product'],
            round($prices['price_incl'] * 100),
            $product['amount'],
            $taxClass
        );
    }


    $payment_surcharge = paynl_getTaxForSurcharge($order_info);
    if ($payment_surcharge['price_incl'] > 0) {

        $item_name = $order_info['payment_method']['surcharge_title'];
        if (empty($item_name) && strtolower($order_info['lang_code']) == 'nl'){
            $item_name = 'Toeslag';
        } elseif (empty($item_name)){
            $item_name = 'Surcharge';
        }

        $taxPercent = $payment_surcharge['tax_amount'] / $payment_surcharge['price_excl'] * 100;

        $taxClass = paynl_getTaxClass($taxPercent);

        $payNL->addProduct(substr($item_name, 0, 24), $item_name, round($payment_surcharge['price_incl'] * 100), 1, $taxClass);
    }

    // Shipping
    $shipping_cost = paynl_getTaxForShipping($order_info);
    if ($shipping_cost['price_incl'] > 0) {
        $taxPercent = $shipping_cost['tax_amount'] / $shipping_cost['price_excl'] * 100;

        $taxClass = paynl_getTaxClass($taxPercent);

        $payNL->addProduct('shipping_cost', __('shipping_cost'), round($shipping_cost['price_incl'] * 100), 1, $taxClass);
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
        return $payNL->doRequest();
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

//calculate incl and excl tax for a product
function paynl_getTaxForItem($order_info, $item_id)
{
    $price = floatval($order_info['products'][$item_id]['subtotal']) / $order_info['products'][$item_id]['amount'];
    $price_excl = $price;
    $price_incl = $price;

    if (array_key_exists('tax_value', $order_info['products'][$item_id]) &&
        $order_info['products'][$item_id]['tax_value'] > 0
    ) {
        // tax setting is set to unit_price
        $tax_amount = $order_info['products'][$item_id]['tax_value'] / $order_info['products'][$item_id]['amount'];
        $price_excl -= $tax_amount;
        return array(
            'price_excl' => $price_excl,
            'price_incl' => $price_incl,
            'tax_amount' => $tax_amount
        );
    }

    // tax setting is set to subtotal
    foreach ($order_info['taxes'] as $tax_rule) {
        if (
            array_key_exists($item_id, $tax_rule['applies']['items']['P']) &&
            $tax_rule['applies']['items']['P'][$item_id] === true
        ) {
            if ($tax_rule['rate_type'] == 'P') {
                // tax is a percentage
                $tax_percent = (floatval($tax_rule['rate_value']) / 100);
                if ($tax_rule['price_includes_tax'] == 'N') {
                    // tax not inculded
                    $tax_amount = $price * $tax_percent;
                    $price_incl += $tax_amount;
                } else {
                    // tax included
                    $tax_amount = $price / (1 + $tax_percent) * $tax_percent;
                    $price_excl -= $tax_amount;
                }
            } elseif ($tax_rule['rate_type'] == 'F') {
                // tax is fixed
                $tax_amount = floatval($tax_rule['rate_value']);
                // for some reason a fixed tax is shared between all products in the order
                // so we divide the amount by the number of products this tax applies to
                $tax_amount = $tax_amount / count($tax_rule['applies']['items']['P']);
                if ($tax_rule['price_includes_tax'] == 'N') {
                    $price_incl += $tax_amount;
                } else {
                    $price_excl -= $tax_amount;
                }
            }
        }
    }

    return array(
        'price_excl' => $price_excl,
        'price_incl' => $price_incl,
        'tax_amount' => $price_incl - $price_excl
    );
}

//calculate incl and excl tax for a product
function paynl_getTaxForShipping($order_info)
{
    if (array_key_exists('shipping', $order_info)) {
        $price_incl = 0;
        $price_excl = 0;

        $tax_amount = 0;
        foreach ($order_info['shipping'] as $shipping) {
            $price_incl += $shipping['rate'];
            $price_excl += $shipping['rate'];
            if (array_key_exists('taxes', $shipping) && $shipping['taxes'] != false) {
                foreach ($shipping['taxes'] as $tax_rule) {
                    $tax_amount += $tax_rule['tax_subtotal'];
                    if ($tax_rule['price_includes_tax'] == 'Y') {
                        $price_excl -= $tax_rule['tax_subtotal'];
                    } else {
                        $price_incl += $tax_rule['tax_subtotal'];
                    }
                }
            }
        }

        return array(
            'price_excl' => $price_excl,
            'price_incl' => $price_incl,
            'tax_amount' => $tax_amount
        );
    }
}

//calculate incl and excl tax for the payment surcharge
function paynl_getTaxForSurcharge($order_info)
{
    $price_excl = $price_incl = floatval($order_info['payment_surcharge']);
    $tax_amount = 0;

    foreach ($order_info['taxes'] as $tax_rule) {
        // tax setting is set to subtotal
        if (
            array_key_exists('PS', $tax_rule['applies']) &&
            $tax_rule['applies']['PS'] > 0
        ) {
            $tax_amount = $tax_rule['applies']['PS'];
            if ($tax_rule['price_includes_tax'] == 'N') {
                // tax not included
                $price_incl += $tax_amount;
            } else {
                // tax included
                $price_excl -= $tax_amount;
            }
        } else {
            // tax setting is set to unit price
            foreach ($tax_rule['applies'] as $key => $applies) {
                if (substr($key, 0, 2) == "PS" && is_float($applies)) {
                    $tax_amount += $applies;
                    if ($tax_rule['price_includes_tax'] == 'N') {
                        $price_incl += $applies;
                    } else {
                        $price_excl -= $applies;
                    }
                }
            }
        }
    }

    return array(
        'price_excl' => $price_excl,
        'price_incl' => $price_incl,
        'tax_amount' => $price_incl - $price_excl
    );
}

function paynl_getTaxClass($percentage)
{
    $taxClasses = array(
        0 => 'N',
        9 => 'L',
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
