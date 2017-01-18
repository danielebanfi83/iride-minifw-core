<?php

namespace IrideWeb\Cache;

/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 24/08/15
 * Time: 12:50
 */
class IWCache{
    /**
     * @var IWCacheInterface
     */
    private $tipoCache;

    public function __construct(){
        $tipoCache = "";
        switch($tipoCache){
            case "XCache" : $this->tipoCache = new IWCacheXCache(); break;
            case "ZendCache" : $this->tipoCache = new IWCacheZend(); break;
            default : $this->tipoCache = new IWCacheGlobal(); break;
        }
    }

    public function getTipoCache(){
        return $this->tipoCache;
    }

    public static function set($name,$value,$timelife=60){
        $cache = new IWCache();
        return $cache->getTipoCache()->set($name,$value,$timelife);
    }

    public static function get($name){
        $cache = new IWCache();
        return $cache->getTipoCache()->get($name);
    }

    public static function un_set($name){
        $cache = new IWCache();
        return $cache->getTipoCache()->un_set($name);
    }

    public static function init(){
        $cache = new IWCache();
        return $cache->getTipoCache()->init();
    }

    public static function have($name){
        $cache = new IWCache();
        return $cache->getTipoCache()->have($name);
    }
}