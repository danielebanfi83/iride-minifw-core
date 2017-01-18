<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 05/09/13
 * Time: 11:38
 */

namespace IrideWeb\Database;

class IWQuery {
    private $tipo;
    private $table;
    private $fields=[];
    private $joins=[];
    private $leftjoins=[];
    private $alljoins = [];
    private $keyJoins = [];
    private $wheres=[];
    private $orderbys=[];
    private $groupbys=[];
    private $having=[];
    private $columns = [];
    private $params = [];
    private $limit="";
    private $queryString = "";

    public function __construct($tipo){
        $this->tipo=$tipo;
        return $this;
    }

    public function addField($fields){
        array_push($this->fields,$fields);
        return $this;
    }

    public function removeField($index){
        unset($this->fields[$index]);
        $this->fields=array_values($this->fields);
        return $this;
    }

    public function removeAllFields(){
        $this->fields=[];
        return $this;
    }

    public function getFields(){
        return $this->fields;
    }

    /**
     * @param $column
     * @return $this
     * @throws IWQueryException
     */
    public function addColumn($column){
        $this->columns[] = $column;
        if($this->tipo == "SELECT" || $this->tipo == "DELETE")
            throw new IWQueryException("IWQuery: Integrity Violation: usage of method addColumn on INSERT or UPDATE queries");
        return $this;
    }

    public function setTable($table){
        $this->table=$table;
        return $this;
    }

    public function addJoin($table,$on){
        $this->joins[$table]=$on;
        $this->alljoins[] = ["table" => $table, "on" => $on, "type" => "inner" ];
        $this->keyJoins[] = $table;
        return $this;
    }

    public function removeJoin($table){
        unset($this->joins[$table]);
        $key = array_search($table, $this->keyJoins);
        unset($this->alljoins[$key]);
        return $this;
    }

    public function addLeftJoin($table,$on){
        $this->leftjoins[$table]=$on;
        $this->alljoins[] = ["table" => $table, "on" => $on, "type" => "left" ];
        $this->keyJoins[] = $table;
        return $this;
    }

    /**
     * @return array
     */
    public function getAlljoins()
    {
        return $this->alljoins;
    }

    public function addWhere($where){
        array_push($this->wheres,$where);
        return $this;
    }

    public function addWhereIds($column,$value,$operator="AND"){
        $where=$operator."( ";
        $where.=$column." LIKE '".$value.",%' ";
        $where.=" OR ".$column." LIKE '%,".$value."' ";
        $where.=" OR ".$column." LIKE '%,".$value.",%' ";
        $where.=" OR ".$column."='".$value."' ";
        $where.=" ) ";
        $this->addWhere($where);
        return $this;
    }

    public function removeWhere($index){
        unset($this->wheres[$index]);
        $this->wheres=array_values($this->wheres);
        return $this;
    }

    public function removeAllWhere(){
        $this->wheres = [];
        $this->params = [];
        return $this;
    }

    public function bindParam($param,$value,$type = "string"){
        $this->params[$param] = [$value, $type];
        return $this;
    }

    /**
     * @param array $params
     * @return IWQuery
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    public function getParams(){
        return $this->params;
    }

    public function addGroupBy($groupby){
        array_push($this->groupbys,$groupby);
        return $this;
    }

    public function getGroupBy()
    {
        return $this->groupbys;
    }

    public function removeGroupBy(){
        $this->groupbys=[];
        return $this;
    }

    public function addHaving($having){
        array_push($this->having,$having);
        return $this;
    }

    public function addOrderby($orderby){
        array_push($this->orderbys,$orderby);
        return $this;
    }

    public function removeOrderby(){
        $this->orderbys=[];
        return $this;
    }

    public function addLimit($start,$stop=""){
        $this->limit="LIMIT ".( $stop == "" ? $start : $start.",".$stop);
        return $this;
    }

    public function getLimit(){
        return $this->limit;
    }

    public function __toString(){
        if($this->queryString != "") return $this->queryString;

        switch($this->tipo){
            case "UPDATE": $sql = $this->tipo." ".$this->table." SET "; break;
            case "INSERT": $sql = $this->tipo." INTO ".$this->table." "; break;
            case "DELETE": $sql = $this->tipo." FROM ".$this->table." "; break;
            default: $sql = $this->tipo." ".implode(", ",$this->fields)." FROM ".$this->table." "; break;
        }
        if(!empty($this->columns)){
            if($this->tipo == "INSERT") $sql .= " (".implode(", ",$this->columns).") VALUES ( :".implode(", :",$this->columns)." )";
            else {
                foreach ($this->columns as $column) {
                    $sql .= " ".$column." = :".$column.", ";
                }
                $sql = substr($sql,0,strlen($sql)-2);
            }
        }
        if(count($this->joins)>0){
            foreach($this->joins as $table=>$on) $sql.=" JOIN ".$table." ON ".$on." ";
        }
        if(count($this->leftjoins)>0){
            foreach($this->leftjoins as $table=>$on) $sql.=" LEFT JOIN ".$table." ON ".$on." ";
        }
        if(count($this->wheres)>0){
            $sql.=" WHERE ";
            foreach($this->wheres as $where) if($where != "") $sql.=$where." ";
        }
        if(count($this->groupbys)>0) {
            $sql.=" GROUP BY ";
            $sql.=implode(",",$this->groupbys);
        }
        if(count($this->having)>0){
            $sql.=" HAVING ";
            foreach($this->having as $having) $sql.=$having." ";
        }
        if(count($this->orderbys)>0) {
            $sql.=" ORDER BY ";
            $sql.=implode(",",$this->orderbys);
        }
        if($this->limit!=""){
            $sql.=" ".$this->limit;
        }

        return $sql;
    }

    /**
     * Metodo utile per cachare le query sostituendone i parametri
     * @return string
     */
    public function getQueryForCache(){
        $sql = $this->__toString();
        foreach ($this->params as $param => $values) {
            $value = $values[0];
            $sql = str_replace(":".$param,$value,$sql);
        }
        return $sql;
    }

    public function castAs() {
        $obj = new IWQuery("SELECT");
        foreach (get_object_vars($this) as $key => $name) {
            $obj->$key = $name;
        }
        return $obj;
    }

    /**
     * @return array
     */
    public function getWheres()
    {
        return $this->wheres;
    }

    /**
     * @param array $wheres
     * @return $this
     */
    public function setWheres($wheres)
    {
        $this->wheres = $wheres;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * @param string $queryString
     * @return IWQuery
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @return array
     */
    public function getLeftjoins()
    {
        return $this->leftjoins;
    }

    /**
     * @return array
     */
    public function getGroupbys()
    {
        return $this->groupbys;
    }

    /**
     * @return array
     */
    public function getOrderbys()
    {
        return $this->orderbys;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getHaving()
    {
        return $this->having;
    }

}

class IWQueryException extends \Exception{}