<?php

class Pay_Api {

    const REQUEST_TYPE_POST = 1;
    const REQUEST_TYPE_GET = 0;

    protected $_apiUrl = 'http://rest-api.pay.nl';
    protected $_version = 'v3';
    protected $_controller = '';
    protected $_action = '';
    protected $_serviceId = '';
    protected $_apiToken = '';
    protected $_requestType = self::REQUEST_TYPE_GET;
    protected $_postData = array();

    
    /**
     * Set the serviceid
     * The serviceid always starts with SL- and can be found on: https://admin.pay.nl/programs/programs
     * 
     * @param string $serviceId
     */
    public function setServiceId($serviceId) {
        $this->_serviceId = $serviceId;
    }

    /**
     * Set the API token
     * The API token is used to identify your company.
     * The API token can be found on: https://admin.pay.nl/my_merchant on the bottom
     * 
     * @param string $apiToken
     */
    public function setApiToken($apiToken) {
        $this->_apiToken = $apiToken;
    }

    protected function _getPostData() {

        return $this->_postData;
    }

    protected function _processResult($data) {
        return $data;
    }

    private function _getApiUrl() {
        if ($this->_version == '') {
            throw new Pay_Exception('version not set', 1);
        }
        if ($this->_controller == '') {
            throw new Pay_Exception('controller not set', 1);
        }
        if ($this->_action == '') {
            throw new Pay_Exception('action not set', 1);
        }

        return $this->_apiUrl . '/' . $this->_version . '/' . $this->_controller . '/' . $this->_action . '/json/';
    }

    public function getPostData(){
        return $this->_getPostData();
    }
    public function doRequest() {
        if ($this->_getPostData()) {

            $url = $this->_getApiUrl();
            $data = $this->_getPostData();

            $strData = http_build_query($data);

            $apiUrl = $url;

            $ch = curl_init();
            if ($this->_requestType == self::REQUEST_TYPE_GET) {
                $apiUrl .= '?' . $strData;
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $strData);
            }
           
          
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);


            if ($result == false) {
                $error = curl_error($ch);
            }
            curl_close($ch);

            $arrResult = json_decode($result, true);

            if ($this->validateResult($arrResult)) {
                return $this->_processResult($arrResult);
            }
        }
    }

    protected function validateResult($arrResult) {
        if ($arrResult['request']['result'] == 1) {
            return true;
        } else {
            if(isset($arrResult['request']['errorId']) && isset($arrResult['request']['errorMessage']) ){
                throw new Pay_Api_Exception($arrResult['request']['errorId'] . ' - ' . $arrResult['request']['errorMessage']);
            } elseif(isset($arrResult['error'])){
                throw new Pay_Api_Exception($arrResult['error']);
            } else {   
                throw new Pay_Api_Exception('Unexpected api result');
            }
        }
    }
}
