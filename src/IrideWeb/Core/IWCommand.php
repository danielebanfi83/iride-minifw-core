<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 06/06/16
 * Time: 11:12
 */

namespace IrideWeb\Core;


use IrideWeb\Database\IWDb;
use IrideWeb\Core\IWGlobal;

abstract class IWCommand
{
    protected $args;
    
    private $iwdb;
    
    abstract public function execute();
    
    public static function run($args){

        $parameters = \Spyc::YAMLLoad(__DIR__."/../../../../../../config/config.yml");
        $db_params = $parameters["db_parameters"];
        $iwdb = new IWDb($db_params["dbhost"],$db_params["dbuser"],$db_params["dbpwd"]);
        $iwdb->setDb($db_params["dbname"]);
        $iwdb->DBUse();
        IWGlobal::setDbInstance($iwdb);
        
        $my_command = $args[0];
        $commands_list = \Spyc::YAMLLoad(__DIR__."/../../../../../../config/commands.yml");
        $obj = "";
        foreach ($commands_list["commands"] as $command) {
            if($command["name"] != $my_command) continue; 
            
            $obj = $command["obj"];
            break;
        }
        if($obj == "") {
            echo "Command not found";
            die();
        }
        
        $obj = new $obj();
        if(!($obj instanceof IWCommand)) {
            echo "Command not found";
            die();
        }
        
        $obj->setArgs($args);
        $obj->setIwdb($iwdb);
        $obj->execute();
    }

    /**
     * @param mixed $iwdb
     * @return IWCommand
     */
    public function setIwdb($iwdb)
    {
        $this->iwdb = $iwdb;

        return $this;
    }

    /**
     * @param mixed $args
     * @return IWCommand
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }
    
    public function getDb(){
        return $this->iwdb;
    }
}