<?php
/**
 * Generic autoloader for classes
 */
class Pay_Autoload {

    public static function register(){
        spl_autoload_register(array(__CLASS__, 'spl_autoload_register'));
    }

    public static function spl_autoload_register($class_name) {
        $dir = realpath(dirname(__FILE__));
        $class_path = $dir . '/' . str_replace('_', '/', $class_name) . '.php';

        if (file_exists($class_path)){
            require_once $class_path;
        }
    }
}
Pay_Autoload::register();
