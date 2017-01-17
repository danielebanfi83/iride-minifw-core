<?php

namespace IrideWeb\Core\Cache;

/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 24/08/15
 * Time: 16:21
 */
class IWCacheXCache implements IWCacheInterface
{
    public function set($name,$value,$timelife=60){
        if (is_null($value) || !is_scalar($value)) {
            return xcache_set($name, serialize($value), $timelife);
        } else {
            return xcache_set($name, $value, $timelife);
        }
    }

    public function get($name){
        $value = xcache_get($name);
        if (!is_null($value)) {
            $unserializedValue = @unserialize($value);
            if ($unserializedValue !== false) {
                $value = $unserializedValue;
            }
        }
        return $value;
    }

    public function un_set($name){
        return xcache_unset($name);
    }

    public function init(){
        for($i = 0, $max = xcache_count(XC_TYPE_VAR); $i< $max; $i++){
            if(false == xcache_clear_cache(XC_TYPE_VAR, $i)){
                break;
            }
        }
    }

    public function have($name){
        return xcache_isset($name);
    }
}