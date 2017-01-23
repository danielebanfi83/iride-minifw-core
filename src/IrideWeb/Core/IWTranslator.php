<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 23/01/17
 * Time: 09:36
 */

namespace IrideWeb\Core;


use IrideWeb\Cache\IWCache;
use IrideWeb\Database\IWDb;

class IWTranslator
{
    /**
     * @var IWDb
     */
    protected $db;

    /**
     * @var array
     */
    protected $config;

    protected $lang;

    public function getDefaultLanguage(){
        return $this->config["default_language"];
    }

    public function getAvailableLanguages(){
        return $this->config["available_language"];
    }

    public function trans($key){
        if(IWCache::have("dictionary")) $keys = IWCache::get("dictionary");
        else{
            $files = elencafiles(__DIR__."/../../../../../../config/dictionary");
            $keys = [];
            foreach ($files as $file) {
                $extension = pathinfo($file)["extension"];
                if($extension != "yml") continue;
                $keys_file = \Spyc::YAMLLoad(file_get_contents($file));
                $keys = array_merge($keys,$keys_file);
            }
            IWCache::set("dictionary",$keys);
        }
        if(array_key_exists($key,$keys))
            return $keys[$key][$this->lang];

        return $key;
    }

    /**
     * @return IWDb
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param IWDb $db
     * @return IWTranslator
     */
    public function setDb($db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return IWTranslator
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param mixed $lang
     * @return IWTranslator
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }
}