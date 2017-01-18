<?php
namespace IrideWeb\Core\Cache;

/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 24/08/15
 * Time: 16:19
 */
interface IWCacheInterface
{
    public function set($name,$value,$timelife=60);
    public function get($name);
    public function un_set($name);
    public function init();
    public function have($name);
}