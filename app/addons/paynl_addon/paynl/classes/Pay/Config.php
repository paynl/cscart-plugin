<?php

class Pay_Config{
    /**
     *
     * @var SimpleXMLElement
     */
   protected $_objXml;
   public function __construct(){
       $dir = realpath(dirname(__FILE__));
       $this->_objXml = simplexml_load_file($dir.'/../../../config.xml');       
   } 

   public function __get($name) {
       return (string) $this->_objXml->$name;
   }
}