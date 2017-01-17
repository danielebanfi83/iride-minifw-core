<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 25/01/16
 * Time: 18:10
 */

namespace IrideWeb\Core;

use IrideWeb\Database\IWDb;
use IrideWeb\Database\IWUsersInterface;

class IWGlobal
{
    private function __construct(){}

    public static function set($var,$value){
        $GLOBALS[$var] = $value;
    }

    public static function get($var){
        if(is_null($GLOBALS)) return "";
        if(!array_key_exists($var, $GLOBALS)) return "";
        return $GLOBALS[$var];
    }

    public static function setCodcliente($codcliente){
        self::set("codcliente",$codcliente);
    }

    public static function getCodcliente(){
        return self::get("codcliente");
    }

    /**
     * @param IWDb $instance
     */
    public static function setDbInstance($instance){
        self::set("db_instance", $instance);
    }

    /**
     * @return IWdb
     */
    public static function getDbInstance(){
        return self::get("db_instance");
    }

    public static function setDbTransaction($transaction){
        self::set("_DB_TRANSACTION_",$transaction);
    }

    public static function getDbTransaction(){
        return self::get("_DB_TRANSACTION_");
    }

    public static function setDbTransactionSemaphore($semaphore){
        self::set("_DB_TRANSACTION_SEMAPHORE_NAME_",$semaphore);
    }

    public static function getDbTransactionSemaphore(){
        return self::get("_DB_TRANSACTION_SEMAPHORE_NAME_");
    }

    public static function getPwdPassepartout1(){
        return self::get("pwd1");
    }

    public static function getPwdPassepartout2(){
        return self::get("pwd2");
    }

    /**
     * @param IWUsersInterface $user
     */
    public static function setUser($user){
        self::set("IWUSER",$user);
    }

    /**
     * @return IWUsersInterface
     */
    public static function getUser(){
        return self::get("IWUSER");
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public static function setRequest($request){
        self::set("iwrequest", $request);
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function getRequest(){
        return self::get("iwrequest");
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public static function setResponse($response){
        self::set("iwresponse", $response);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function getResponse(){
        return self::get("iwresponse");
    }

}
