<?php

use Tygh\Registry;

require_once(dirname(__FILE__) . '/paynl/classes/Pay/vendor/autoload.php');


function getConfig($tokenCode = null, $apiToken = null, $useCore = false, $core = false)
{
    $config = new \PayNL\Sdk\Config\Config();
    $config->setUsername($tokenCode);
    $config->setPassword($apiToken);

    if (!empty($core) && $useCore === true) {
        $config->setCore($core);
    }

    return $config;
}

/**
 * @return array
 */
function fn_getPaymentMethods()
{
    try {
        $serviceId = getServiceId();
        $tokenCode = getTokencode();
        $apiToken = getApiToken();

        $config = getConfig($tokenCode, $apiToken);

        $serviceConfig = (new \PayNL\Sdk\Model\Request\ServiceGetConfigRequest($serviceId))
            ->setConfig($config)
            ->start();

        $paymentMethods = $serviceConfig->getPaymentMethods();
        $formattedMethods = array();

        foreach ($paymentMethods as $method)
        {
            $formattedMethods[] = array(
                'id' => $method->getId(),
                'name' => $method->getName()
            );
        }

        return $formattedMethods;

    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
        return array();
    }
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

/**
 * @param $payNLTransactionID
 * @param $processor_data
 * @return array[]|void
 */
function fn_paynl_getStatus($payNLTransactionID, $processor_data)
{
    try {
        $tokenCode = getTokencode();
        $apiToken = getApiToken();
        $config = getConfig($tokenCode, $apiToken);

        $request = new \PayNL\Sdk\Model\Request\TransactionStatusRequest($payNLTransactionID);
        $request->setConfig($config);
        $payOrder = $request->start();
        
        return array(
            'paymentDetails' => array(
                'state' => $payOrder->getStatus(),
                'amountOriginal' => array(
                    'value' => $payOrder->getAmount()
                ),
                'identifierName' => $payOrder->getPaymentMethod(),
                'identifierPublic' => $payOrder->getReference()
            )
        );
    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), $ex->getMessage());
        fn_redirect('/index.php?dispatch=checkout.checkout');
    }
}

function getObjectData()
{
    $phpVersion = substr(phpversion(), 0, 3);
    $cscartVersion = defined('PRODUCT_VERSION') ? PRODUCT_VERSION : '-';
    $payPlugin = '2.0.0';

    return substr('cscart ' . $payPlugin . ' | ' . $cscartVersion . ' | ' . $phpVersion, 0, 64);
}

/**
 * @param $order_id
 * @param $order_info
 * @param $processor_data
 * @param $exchangeUrl
 * @param $finishUrl
 * @return array|void
 * @throws Exception
 */
function fn_paynl_startTransaction($order_id, $order_info, $processor_data, $exchangeUrl, $finishUrl)
{
    $currency = CART_PRIMARY_CURRENCY;

    $config = getConfig(getTokencode(), getApiToken(), true, $processor_data['processor_params']['multicore']);
    
    // Create the order request
    $request = new \PayNL\Sdk\Model\Request\OrderCreateRequest();
    $request->setConfig($config);
    $request->setServiceId(getServiceId());
    $request->setAmount(floatval($order_info['total']));
    $request->setTestmode(getTestMode());
    $request->setCurrency($currency);
    $request->setReturnurl($finishUrl);
    $request->setExchangeUrl($exchangeUrl);
    $request->setDescription($order_info['order_id']);
    $request->setReference($order_info['order_id']);

    if (!empty($processor_data['processor_params']['optionId'])) {
        $request->setPaymentMethodId((int)$processor_data['processor_params']['optionId']);
    }
    
    // Create customer
    $customer = new \PayNL\Sdk\Model\Customer();
    $customer->setFirstName($order_info['s_firstname']);
    $customer->setLastName($order_info['s_lastname']);
    $customer->setEmail($order_info['email']);
    $customer->setPhone($order_info['s_phone']);
    $customer->setIpAddress($order_info['ip_address']);
    $customer->setLanguage(strtoupper($order_info['lang_code']));
    
    if (!empty($order_info['birthday'])) {
        $customer->setBirthDate($order_info['birthday']);
    }
    
    $request->setCustomer($customer);

    $order = new \PayNL\Sdk\Model\Order();
    $order->setCountryCode($order_info['s_country']);
    
    // Shipping address
    $s_address = splitAddress(trim($order_info['s_address'] . ' ' . $order_info['s_address_2']));
    $shippingAddress = new \PayNL\Sdk\Model\Address();
    $shippingAddress->setCode('delivery');
    $shippingAddress->setStreetName($s_address[0]);
    $shippingAddress->setStreetNumber(substr($s_address[1], 0, 4));
    $shippingAddress->setZipCode($order_info['s_zipcode']);
    $shippingAddress->setCity($order_info['s_city']);
    $shippingAddress->setCountryCode($order_info['s_country']);
    $order->setDeliveryAddress($shippingAddress);
    
    // Billing address
    $b_address = splitAddress(trim($order_info['b_address'] . ' ' . $order_info['b_address_2']));
    $billingAddress = new \PayNL\Sdk\Model\Address();
    $billingAddress->setCode('invoice');
    $billingAddress->setStreetName($b_address[0]);
    $billingAddress->setStreetNumber(substr($b_address[1], 0, 4));
    $billingAddress->setZipCode($order_info['b_zipcode']);
    $billingAddress->setCity($order_info['b_city']);
    $billingAddress->setCountryCode($order_info['b_country']);
    $order->setInvoiceAddress($billingAddress);
    
    // Products
    $products = new \PayNL\Sdk\Model\Products();
    
    foreach ($order_info['products'] as $key => $product) {
        $prices = paynl_getTaxForItem($order_info, $key);
        $taxPercent = empty($prices['price_excl']) ? 0 : ($prices['tax_amount'] / $prices['price_excl'] * 100);
        
        $orderProduct = new \PayNL\Sdk\Model\Product();
        $orderProduct->setId($product['product_id']);
        $orderProduct->setDescription($product['product']);
        $orderProduct->setType(\PayNL\Sdk\Model\Product::TYPE_ARTICLE);
        $orderProduct->setAmount($prices['price_incl']);
        $orderProduct->setCurrency($currency);
        $orderProduct->setQuantity($product['amount']);
        $orderProduct->setVatPercentage($taxPercent);
        
        $products->addProduct($orderProduct);
    }

    $payment_surcharge = paynl_getTaxForSurcharge($order_info);
    if ($payment_surcharge['price_incl'] > 0) {
        $item_name = $order_info['payment_method']['surcharge_title'];
        if (empty($item_name) && strtolower($order_info['lang_code']) == 'nl') {
            $item_name = 'Toeslag';
        } elseif (empty($item_name)) {
            $item_name = 'Surcharge';
        }

        $taxPercent = $payment_surcharge['tax_amount'] / $payment_surcharge['price_excl'] * 100;

        $surchargeProduct = new \PayNL\Sdk\Model\Product();
        $surchargeProduct->setId(substr($item_name, 0, 24));
        $surchargeProduct->setDescription($item_name);
        $surchargeProduct->setType(\PayNL\Sdk\Model\Product::TYPE_HANDLING);
        $surchargeProduct->setAmount($payment_surcharge['price_incl']);
        $surchargeProduct->setCurrency($currency);
        $surchargeProduct->setQuantity(1);
        $surchargeProduct->setVatPercentage($taxPercent);
        
        $products->addProduct($surchargeProduct);
    }

    // Shipping
    $shipping_cost = paynl_getTaxForShipping($order_info);
    if ($shipping_cost['price_incl'] > 0) {
        $taxPercent = $shipping_cost['tax_amount'] / $shipping_cost['price_excl'] * 100;

        $shippingProduct = new \PayNL\Sdk\Model\Product();
        $shippingProduct->setId('shipping_cost');
        $shippingProduct->setDescription(__('shipping_cost'));
        $shippingProduct->setType(\PayNL\Sdk\Model\Product::TYPE_SHIPPING);
        $shippingProduct->setAmount($shipping_cost['price_incl']);
        $shippingProduct->setCurrency($currency);
        $shippingProduct->setQuantity(1);
        $shippingProduct->setVatPercentage($taxPercent);
        
        $products->addProduct($shippingProduct);
    }

    if (!empty($order_info['use_gift_certificates'])) {
        foreach ($order_info['use_gift_certificates'] as $k => $v) {
            $giftProduct = new \PayNL\Sdk\Model\Product();
            $giftProduct->setId($v['gift_cert_id']);
            $giftProduct->setDescription($k);
            $giftProduct->setType(\PayNL\Sdk\Model\Product::TYPE_DISCOUNT);
            $giftProduct->setAmount(-floatval($v['cost']));
            $giftProduct->setCurrency($currency);
            $giftProduct->setQuantity(1);
            $giftProduct->setVatPercentage(0);
            
            $products->addProduct($giftProduct);
        }
    }

    if (isset($order_info['subtotal_discount']) && $order_info['subtotal_discount'] > 0) {
        $discountProduct = new \PayNL\Sdk\Model\Product();
        $discountProduct->setId('discount');
        $discountProduct->setDescription(__('discount'));
        $discountProduct->setType(\PayNL\Sdk\Model\Product::TYPE_DISCOUNT);
        $discountProduct->setAmount(-$order_info['subtotal_discount']);
        $discountProduct->setCurrency($currency);
        $discountProduct->setQuantity(1);
        $discountProduct->setVatPercentage(0);
        
        $products->addProduct($discountProduct);
    }

    $order->setProducts($products);
    $request->setOrder($order);
    
    // Set stats/object data
    $stats = new \PayNL\Sdk\Model\Stats();
    $stats->setObject(getObjectData());
    $stats->setExtra1($order_id);
    $request->setStats($stats);

    try {
        $payOrder = $request->start();
        
        // Return data in format expected by existing code
        return array(
            'transaction' => array(
                'transactionId' => $payOrder->getId(),
                'paymentURL' => $payOrder->getPaymentUrl(),
                'popupAllowed' => false,
                'popupHeight' => 0,
                'popupWidth' => 0
            ),
            'request' => array(
                'result' => '1',
                'errorId' => '',
                'errorMessage' => ''
            )
        );
    } catch (\PayNL\Sdk\Exception\PayException $ex) {
        fn_set_notification('E', __('error'), $ex->getFriendlyMessage());
        fn_redirect('/index.php?dispatch=checkout.checkout');
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

/**
 * @return string
 */
function getApiToken()
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    return $paynl_setting['token_api'];
}

/**
 * @return string
 */
function getTokencode()
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    return $paynl_setting['token_code'];
}

/**
 * @return string
 */
function getServiceId()
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    return $paynl_setting['service_id'];
}

/**
 * @return int
 */
function getTestMode()
{
    $paynl_setting = Registry::get('addons.paynl_addon');
    if (empty($paynl_setting['test_mode'])) {
        return 0;
    }
    return $paynl_setting['test_mode'] == 'Y' ? 1 : 0;
}

/**
 * @return array
 */
function fn_paynl_getMultiCore()
{
    try {
        $serviceId = getServiceId();
        $tokenCode = getTokencode();
        $apiToken = getApiToken();

        $config = getConfig($tokenCode, $apiToken);

        $serviceConfig = (new \PayNL\Sdk\Model\Request\ServiceGetConfigRequest($serviceId))
            ->setConfig($config)
            ->start();

        $cores = $serviceConfig->getCores();
        $formattedCores = array();

        foreach ($cores as $core) {
            if ($core['status'] === 'ACTIVE') {
                $formattedCores[] = array(
                    'domain' => $core['domain'],
                    'name' => $core['label']
                );
            }
        }

        return $formattedCores;

    } catch (Exception $ex) {
        fn_set_notification('E', __('error'), 'Could not fetch TGU cores: ' . $ex->getMessage());
        return array();
    }
}