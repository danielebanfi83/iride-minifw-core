<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 15/05/15
 * Time: 13:19
 */

namespace IrideWeb\Database;

use IrideWeb\Cache\IWCache;
use IrideWeb\Core\IWGlobal;

abstract class DBTable implements \Iterator {
    protected $id;

    protected $table;

    protected $columns;

    abstract public function setColumns();

    protected function setFields($table,$id,$r){
        $this->table = $table;
        $this->id = intval($id);
        $cols = $this->getObjectCols();
        if($this->id>0 && empty($r)) {
            $sql = \IWSelect()->addField(implode(", ",$cols))->setTable($this->table)->addWhere("id = :id")->bindParam("id",$this->id,"int");
            if(IWCache::have($this->table."_constructor_".$this->id))
                $r = IWCache::get($this->table."_constructor_".$this->id);
            else {
                $r = $this->getDb()->DBRead($sql,1);
                IWCache::set($this->table."_constructor_".$this->id,$r);
            }
        }
        //Se la query non ha prodotto risultati oppure l'array di riempimento è ancora vuoto,
        //resetto l'id, dato che non esiste a database il record selezionato nei parametri
        if(empty($r)) {
            $this->id = 0;
            return;
        }
        foreach ($r as $key => $value) {
            if(!in_array($key,$cols)) continue;
            $this->$key = $value;
        }
    }

    public function getDb(){
        return IWGlobal::getDbInstance();
    }

    public function clearCacheObject(){
        IWCache::un_set($this->table."_constructor_".$this->id);
    }

    public function checkIfSave(){
        return true;
    }

    public function dbSave($forza_insert=false){
        $this->setColumns();

        $is_insert = $this->id==0 || $forza_insert;
        $sql = $is_insert ? \IWInsert() : \IWUpdate();
        $sql->setTable($this->table);
        /**
         * @var $column IWDbColumn
         */
        foreach ($this->columns as $column) {
            $name = $column->getName();
            $value = $this->$name;
            if($column->haveForeignKey() && intval($value) == 0)
                $value = null;
            $sql->addColumn($name);

            $sql->bindParam($name,$value,$column->getType() == "int" ? "int" : "string");
        }
        if(!$is_insert) $sql->addWhere("id = :id")->bindParam("id",$this->id, "int");
        if($is_insert && $this->id > 0) $sql->addColumn("id")->bindParam("id", $this->id, "int");

        $this->getDb()->db_query($sql);
        if($is_insert) $this->id = $this->getDb()->db_last_id();
        $this->clearCacheObject();
        return "";
    }

    public function insertDefaultRecords(){
        return "";
    }

    public function deleteRows($where="1=1"){
        $this->getDb()->DBDelete($this->table,$where);
        return "";
    }

    public function checkIfDelete(){
        return "";
    }

    public function deleteSingleRow(){
        $this->getDb()->DBDelete($this->table,"id=".$this->id);
        return "";
    }

    public function getObjectCols()
    {
        $vars = get_object_vars($this);
        unset($vars["table"],$vars["columns"]);
        $cols = array_filter_nulls(array_keys($vars));
        return $cols;
    }

    public function getObjectVars(){
        $vars = get_object_vars($this);
        unset($vars["table"],$vars["columns"]);
        return $vars;
    }

    public function getElenco($where = [],$orderby = "IWT.descrizione", $params = [], $key = ""){
        if($key != "" && IWCache::have("getElenco_".$key)) return IWCache::get($key);

        $cols=$this->getObjectCols();
        $sql=$this->getSqlElenco($cols,$where,$orderby, $params);
        $rs=$this->getDb()->db_query($sql);
        $class = get_called_class();
        $rows=[];
        while($r=$this->getDb()->db_fetch_array($rs,1)){
            $rows[] = new $class(0,$r);
        }
        if($key != "") IWCache::set("getElenco_".$key,$rows);
        return $rows;
    }

    public function getElencoPaginato($where=[],$orderby="IWT.descrizione", $params = [], $key = ""){
        if($key != "" && IWCache::have("getElencoPaginato_".$key)) return IWCache::get($key);
        $cols=$this->getObjectCols();
        $sql=$this->getSqlElenco($cols,$where,$orderby, $params);
        list($rs,$righe_totali)=$this->getDb()->db_paging_query3($sql);
        $class = get_called_class();
        $rows=[];
        while($r=$this->getDb()->db_fetch_array($rs,1)){
            $rows[] = new $class(0,$r);
        }
        $res = [$rows,$righe_totali];
        if($key != "") IWCache::set("getElencoPaginato_".$key,$res);
        return $res;
    }

    protected function getSqlElenco($cols,$where,$orderby, $params = []){
        $sql=new IWQuery("SELECT");
        $sql->addField("IWT.".implode(",IWT.",$cols))->setTable($this->table." AS IWT")->addOrderby($orderby);
        if(is_array($where)) {
            $sql->addWhere("1=1");
            $i = 0;
            foreach ($where as $col=>$value){
                if(is_numeric($col)) continue;
                $sql->addWhere("AND IWT.".$col."= :value".$i)->bindParam("value".$i, $value);
                $i++;
            }
        }
        else {
            $sql->addWhere($where);
            foreach ($params as $key => $value) {
                $sql->bindParam($key,$value);
            }
        }
        return $sql;
    }

    /**
     * @return array
     */
    public function getAjaxInfoTable(){
        unset($this->table,$this->columns);
        $ret = get_object_vars($this);
        unset($ret[0]);
        return $ret;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return IWDbColumn
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->columns);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->columns);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->columns);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return (current($this->columns) !== false);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->columns);
    }

    /**
     * @return Repository|null
     */
    public function getRepository(){
        $dbtable = get_class($this);
        $cache_identifier = str_replace("\\","",$dbtable);
        if(IWCache::have("repo_".$cache_identifier)) return IWCache::get("repo_".$cache_identifier);
        $pos = strpos($dbtable,"Entities") + 9;
        $namespace = substr($dbtable,0,$pos)."Repository\\";
        $class = $namespace.substr($dbtable,$pos)."Repository";
        if(!class_exists($class)) return null;
        /**
         * @var $repo Repository
         */
        $repo = new $class();
        $repo->setDbObject($this);
        IWCache::set("repo_".$cache_identifier, $repo, 86400);
        return $repo;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return intval($this->id);
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


}