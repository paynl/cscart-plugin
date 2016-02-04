<?php

class Pay_Helper {

    /**
     * Bepaal de status aan de hand van het statusid.
     * Over het algemeen worden allen de statussen -90(CANCEL), 20(PENDING) en 100(PAID) gebruikt
     * 
     * @param int $statusId
     * @return string De status
     */
      public static function getStateText($stateId) {
        switch ($stateId) {
            case 80:
            case -51:
                return 'CHECKAMOUNT';
            case 100:
                return 'PAID';
            default:
                if ($stateId < 0) {
                    return 'CANCEL';
                } else {
                    return 'PENDING';
                }
        }
    }

    //remove all empty nodes in an array
    public static function filterArrayRecursive($array) {
        $newArray = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::filterArrayRecursive($value);
            }
            if (!empty($value)) {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

    /**
     * Find out if the connection is secure
     * 
     * @return boolean Secure
     */
    public static function isSecure() {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $isSecure = true;
        }
        return $isSecure;
    }
    public static function getUri(){        
        if(self::isSecure()){
            $uri='https://';
        } else {
            $uri='http://';
        }
        
        $uri .= $_SERVER['SERVER_NAME'];
        
        if(!empty($_SERVER['REQUEST_URI'])){
            $uri .= $_SERVER['REQUEST_URI'];
            $uriDir = $uri;
            if(substr($uri, -4) == '.php'){
                $uriDir = dirname($uri);
            }
            
            
            if($uriDir != 'http:' && $uriDir != 'https:'){
                $uri = $uriDir;
            }
        }
        
        return $uri.'/';
    }
    
    public static function sortPaymentOptions($paymentOptions){
        uasort($paymentOptions, 'sortPaymentOptions');
        return $paymentOptions;
    }   
}
function sortPaymentOptions($a,$b){
    return strcmp($a['name'], $b['name']);
}