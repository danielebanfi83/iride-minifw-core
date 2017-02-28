<?php
namespace IrideWeb\Cache;

use IrideWeb\Core\IWGlobal;

class IWCacheGlobal implements IWCacheInterface
{
	public function init()
	{
		$arr = [];
        IWGlobal::set("cache", $arr);
        IWGlobal::set("uso_cache",0);
	}
	
	public function set($chiave,$valore,$timelife=60)
	{
		$arr=IWGlobal::get("cache");
		$arr["$chiave"]=$valore;
        IWGlobal::set("cache", $arr);
		return true;
	}
	
	public function un_set($chiave)
	{
		$arr=IWGlobal::get("cache");
        if(!is_array($arr)) {
            $this->init();
            $arr = [];
        }
		if(array_key_exists($chiave,$arr)) unset($arr[$chiave]);
		IWGlobal::set("cache",$arr);
        return true;
	}

	public function get($chiave)
	{
        $arr = IWGlobal::get("cache");
        if(is_string($arr)) $this->init();
		$uso = IWGlobal::get("uso_cache");
        $uso++;
        IWGlobal::set("uso_cache", $uso);
		return $arr[$chiave];
	}

	public function have($chiave)
	{
		if($chiave=="") return false;
        $arr=IWGlobal::get("cache");
        if(!is_array($arr)) {
        	$this->init();
        	$arr = [];
        }
        return array_key_exists($chiave,$arr);
	}
}