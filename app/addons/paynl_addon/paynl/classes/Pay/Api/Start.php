<?php
/**
 * Class to use for starting a transaction
 */
class Pay_Api_Start extends Pay_Api {

    protected $_version = 'v3';
    protected $_controller = 'transaction';
    protected $_action = 'start';
    private $_amount;
    private $_currency;
    private $_paymentOptionId;
    private $_paymentOptionSubId;
    private $_finishUrl;
    private $_ipAddress;

    private $_exchangeUrl;
    private $_description;
    private $_enduser;
    private $_extra1;
    private $_extra2;
    private $_extra3;
    
    private $_promotorId;
    private $_info;
    private $_tool;
    private $_object;
    private $_domainId;
    private $_transferData;
    
    private $_products = array();

    public function setCurrency($currency){
        $this->_currency = strtoupper($currency);
    }
    public function setPromotorId($promotorId){
        $this->_promotorId = $promotorId;
    }
    public function setInfo($info){
        $this->_info = $info;
    }
    public function setTool($tool){
        $this->_tool = $tool;
    }
    public function setObject($object){
        $this->_object = $object;
    }
 
    public function setTransferData($transferData){
        $this->_transferData = $transferData;
    }
    /**
     * Add a product to an order
     * Attention! This is purely an adminstrative option, the amount of the order is not modified.
     * 
     * @param string $id
     * @param string $description
     * @param int $price
     * @param int $quantity
     * @param int $vatPercentage
     * @throws Pay_Exception
     */
    public function addProduct($id, $description, $price, $quantity, $vatPercentage = 'H') {
        if (!is_numeric($price)) {
            throw new Pay_Exception('Price moet numeriek zijn', 1);
        }
        if (!is_numeric($quantity)) {
            throw new Pay_Exception('Quantity moet numeriek zijn', 1);
        }

        $quantity = $quantity * 1;

        //description mag maar 45 chars lang zijn
        $description = substr($description, 0, 45);

        $arrProduct = array(
            'productId' => $id,
            'description' => $description,
            'price' => $price,
            'quantity' => $quantity,
            'vatCode' => $vatPercentage,
        );
        $this->_products[] = $arrProduct;
    }

    /**
     * Set the enduser data in the following format
     * 
     * array(
     *  initals
     *  lastName
     *  language
     *  accessCode
     *  gender (M or F)
     *  dob (DD-MM-YYYY)
     *  phoneNumber
     *  emailAddress
     *  bankAccount
     *  iban
     *  bic
     *  sendConfirmMail
     *  confirmMailTemplate
     *  address => array(
     *      streetName
     *      streetNumber
     *      zipCode
     *      city
     *      countryCode
     *  )
     *  invoiceAddress => array(
     *      initials
     *      lastname
     *      streetName
     *      streetNumber
     *      zipCode
     *      city
     *      countryCode
     *  )
     * )
     * @param array $enduser
     */
    public function setEnduser($enduser) {
        $this->_enduser = $enduser;
    }

    /**
     * Set the amount(in cents) of the transaction
     * 
     * @param int $amount
     * @throws Pay_Exception
     */
    public function setAmount($amount) {
        if (is_numeric($amount)) {
            $this->_amount = $amount;
        } else {
            throw new Pay_Exception('Amount is niet numeriek', 1);
        }
    }

    public function setPaymentOptionId($paymentOptionId) {
        if (is_numeric($paymentOptionId)) {
            $this->_paymentOptionId = $paymentOptionId;
        } else {
            throw new Pay_Exception('PaymentOptionId is niet numeriek', 1);
        }
    }

    public function setPaymentOptionSubId($paymentOptionSubId) {
        if (is_numeric($paymentOptionSubId)) {
            $this->_paymentOptionSubId = $paymentOptionSubId;
        } else {
            throw new Pay_Exception('PaymentOptionSubId is niet numeriek', 1);
        }
    }

    /**
     * Set the url where the user will be redirected to after payment.
     * 
     * @param string $finishUrl
     */
    public function setFinishUrl($finishUrl) {
        $this->_finishUrl = $finishUrl;
    }

    /**
     * Set the comunication url, the pay.nl server will call this url when the status of the transaction changes
     * 
     * @param string $exchangeUrl
     */
    public function setExchangeUrl($exchangeUrl) {
        $this->_exchangeUrl = $exchangeUrl;
    }

  public function setIpAddress($ip)
  {
    $this->_ipAddress = $ip;
  }

    public function setExtra1($extra1) {
        $this->_extra1 = $extra1;
    }
    public function setExtra2($extra2) {
        $this->_extra2 = $extra2;
    }

    public function setExtra3($extra3) {
        $this->_extra3 = $extra3;
    }
    public function setDomainId($domainId) {
        $this->_domainId = $domainId;
    }

    /**
     * Set the description for the transaction
     * @param type $description
     */
    public function setDescription($description) {
        $this->_description = $description;
    }

    /**
     * Get the post data, if not all required variables are set, this wil rthrow an exception
     * 
     * @return array
     * @throws Pay_Exception
     */
    protected function _getPostData()
    {
        $data = parent::_getPostData();

        if ($this->_apiToken == '') {
            throw new Pay_Exception('apiToken not set', 1);
        } else {
            $data['token'] = $this->_apiToken;
        }
        if (empty($this->_serviceId)) {
            throw new Pay_Exception('apiToken not set', 1);
        } else {
            $data['serviceId'] = $this->_serviceId;
        }
        if (empty($this->_amount)) {
            throw new Pay_Exception('Amount is niet geset', 1);
        } else {
            $data['amount'] = $this->_amount;
        }
        if(!empty($this->_currency)){
            $data['transaction']['currency'] = $this->_currency;
        }
        if (!empty($this->_paymentOptionId)) {  
            $data['paymentOptionId'] = $this->_paymentOptionId;
        }
        if (empty($this->_finishUrl)) {
            throw new Pay_Exception('FinishUrl is niet geset', 1);
        } else {
            $data['finishUrl'] = $this->_finishUrl;
        }
        if (!empty($this->_exchangeUrl)) {    
            $data['transaction']['orderExchangeUrl'] = $this->_exchangeUrl;
        }

        if (!empty($this->_description)) {
            $data['transaction']['description'] = $this->_description;
        }

        if (!empty($this->_paymentOptionSubId)) {
            $data['paymentOptionSubId'] = $this->_paymentOptionSubId;
        }

        
        $data['ipAddress'] = empty($this->_ipAddress) ? $_SERVER['REMOTE_ADDR'] : $this->_ipAddress;
        
        // I set the browser data with dummydata, because most servers dont have the get_browser function available
        $data['browserData'] = array(
            'browser_name_regex' => '^mozilla/5\.0 (windows; .; windows nt 5\.1; .*rv:.*) gecko/.* firefox/0\.9.*$',
            'browser_name_pattern' => 'Mozilla/5.0 (Windows; ?; Windows NT 5.1; *rv:*) Gecko/* Firefox/0.9*',
            'parent' => 'Firefox 0.9',
            'platform' => 'WinXP',
            'browser' => 'Firefox',
            'version' => 0.9,
            'majorver' => 0,
            'minorver' => 9,
            'cssversion' => 2,
            'frames' => 1,
            'iframes' => 1,
            'tables' => 1,
            'cookies' => 1,
        );
        if (!empty($this->_products)) {
            $data['saleData']['invoiceDate'] = date('d-m-Y');
            $data['saleData']['deliveryDate'] = date('d-m-Y', strtotime('+1 day'));
            $data['saleData']['orderData'] = $this->_products;
        }

        if (!empty($this->_enduser)) {
            $data['enduser'] = $this->_enduser;
        }

         if (!empty($this->_extra1)) {
            $data['statsData']['extra1'] = $this->_extra1;
        }
        if (!empty($this->_extra2)) {
            $data['statsData']['extra2'] = $this->_extra2;
        }
        if (!empty($this->_extra3)) {
            $data['statsData']['extra3'] = $this->_extra3;
        }
        if(!empty($this->_promotorId)){
            $data['statsData']['promotorId'] = $this->_promotorId;
        }
        if(!empty($this->_info)){
            $data['statsData']['info'] = $this->_info;
        }
        if(!empty($this->_tool)){
            $data['statsData']['tool'] = $this->_tool;
        }
        if(!empty($this->_object)){
            $data['statsData']['object'] = $this->_object;
        }
        if(!empty($this->_domainId)){
            $data['statsData']['domain_id'] = $this->_domainId;
        }
        if(!empty($this->_transferData)){
            $data['statsData']['transferData'] = $this->_transferData;
        }
        
        return $data;
    }

}
