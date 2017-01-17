<?php
namespace IrideWeb\Core\Cache;

/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 24/08/15
 * Time: 16:31
 */
class IWCacheZend implements IWCacheInterface
{

    public function set($name, $value, $timelife = 60)
    {
        return zend_disk_cache_store($name,$value,$timelife);
    }

    public function get($name)
    {
        return zend_disk_cache_fetch($name);
    }

    public function un_set($name)
    {
        return zend_disk_cache_delete($name);
    }

    public function init()
    {
        return zend_disk_cache_clear();
    }

    public function have($name)
    {
        if(zend_disk_cache_fetch($name)!="") return true;
        return false;
    }
}