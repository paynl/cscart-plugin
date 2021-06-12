<?php

class Pay_Api_Status extends Pay_Api
{

    protected $_version = 'v15';
    protected $_controller = 'transaction';
    protected $_action = 'status';

    public function setTransactionId($transactionId)
    {
        $this->_postData['transactionId'] = $transactionId;
    }

    protected function _getPostData()
    {
        $data = parent::_getPostData();
        if ($this->_apiToken == '') {
            throw new Pay_Exception('apiToken not set', 1);
        } else {
            $data['token'] = $this->_apiToken;
        }
        if (!isset($this->_postData['transactionId'])) {
            throw new Pay_Exception('transactionId is not set', 1);
        }
        return $data;
    }
}
