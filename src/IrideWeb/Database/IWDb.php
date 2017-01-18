<?php
namespace IrideWeb\Database;

use IrideWeb\Cache\IWCache;
use IrideWeb\Core\IWGlobal;
use IrideWeb\Core\IWModule;
use PDO;
use PDOStatement;
use PDOException;

class IWDb{
	private $host;
	private $user;
	private $pwd;
	private $db;
    /**
     * @var \SlimSession\Helper
     */
    private $session;

	/**
	 * @var PDO
	 */
	private $pdo;

    /**
     * @var array
     */
    private $modules;

	public function __construct($host="",$user="",$pwd="")
	{
		$this->host = $host != "" ? $host : IWGlobal::get("dbhost");
		$this->user = $user !="" ? $user : IWGlobal::get("dbuser");
		$this->pwd  = $pwd !="" ? $pwd : IWGlobal::get("dbpwd");
		$this->DBConnect();
	}

	/**
	 * @return PDO
	 */
	private function db_connect()
	{
		$this->pdo = new PDO("mysql:host=".$this->host.";charset=utf8",$this->user,$this->pwd);
		return $this->pdo;
	}

    public function getPDO(){
        return $this->pdo;
    }

	/**
	 * @param IWQuery $sql
	 * @return bool|PDOStatement
	 */
	public function db_query($sql)
	{
        $sth = $this->pdo->prepare($sql);
        $params = $sql->getParams();
        foreach ($params as $param => $values) {
            $value = $values[0];
            $type = $values[1] == "int" ? PDO::PARAM_INT : PDO::PARAM_STR;
            //if(strtoupper($value) == "NULL")
              //  $type = PDO::PARAM_NULL;

            $sth->bindValue(":".$param, $value, $type);
        }
        $sth->execute();
        return $sth;
	}

	/**
	 * @param PDOStatement $rs
	 * @return int
	 */
	public function db_num_rows($rs)
	{
		return $rs->rowCount();
	}

	public function db_last_id()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * @param PDOStatement $rs
	 * @param int $tipo
	 * @return array|null|string
	 */
	public function db_fetch_array($rs,$tipo=0)
	{
        switch($tipo){
            case 1: $tipo = PDO::FETCH_ASSOC; break;
            default: $tipo = PDO::FETCH_BOTH; break;
        }
		return $rs->fetch($tipo);
	}
	public function db_error()
	{
		return "[".$this->pdo->errorCode()."] ".print_r($this->pdo->errorInfo(),true);
	}
	public function db_escape($string)
	{
		//return $this->pdo->quote($string);
        return $string;
	}


//*****************************************************************************************************************

	function db_list()
	{
		$sql="SHOW DATABASES";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();
        $dbs = [];
        while($r = $sth->fetch()){
            $dbs[] = $r[0];
        }
        return $dbs;
	}

//esegue la query e ritorna $rs e il numero di pagine totali della query
//1) $rs
//2) $npages
//nota: si comincia a contare le pagine da 1
    /**
     * @param IWQuery $sql
     * @return array
     */
	public function db_paging_query($sql)
	{
		$sql_limit = clone $sql;
        $iStart = intval(IWGlobal::getRequest()->post->get("iDisplayStart"));//TODO sistemare
        $iLength = intval(IWGlobal::getRequest()->post->get("iDisplayLength"));
        if(isset($iStart) && isset($iLength)) {
            $sql_limit->addLimit($iStart,$iLength);
		}

		$rs_tot=$this->db_query($sql);
		$nrighe=$this->db_num_rows($rs_tot);

		$rs=$this->db_query($sql_limit);
		return array($rs,$nrighe);

	}

//*****FUNZIONI PER IL COLLEGAMENTO E LA GESTIONE TRANSAZIONI

//ritorna true se la tabella E il campo nella tabelle esiste, false se non esiste
	public function DBConnect()
	{
		try{
			$this->db_connect();
		}
		catch(PDOException $e){
			die($e->getMessage());
		}
	}

//apre il database dell'utente: quindi tutte le future query saranno riferite
//                                 al DB del cliente
//se codcliente=="" prende il cliente che ha fatto il IWLogin
	public function DBOpen()
	{
        $db = $this->session->get("db");//IWSession::r("db");
        try{
            $this->db_connect();
            $this->DBUse($db);
        }
        catch(PDOException $e){
            die($e->getMessage());
        }
        IWGlobal::setDbTransaction("");
        return;
	}

//usa il db passato nei parametri. Se non è passato nulla apre il db di sessione
	public function DBUse($db="")
	{
		$this->db = $db == "" ? $this->session->get("db")/*IWSession::r("db")*/ : $db;
        try{
            $this->pdo->exec("USE ".$db);
        }
        catch(PDOException $e){
            die($e->getMessage());
        }
	}

    /**
     * Metodo che esegue una query di inserimento/modifica/cancellazione
     * @param IWQuery $sql
     * @return bool|PDOStatement
     */
	public function DBExec($sql)
	{
        //Se eseguo una DBExec all'interno di una transazione attiva...
		if(	IWGlobal::getDbTransaction() == "STARTED" )
		{
            $this->db_query($sql);
            return true;
		}
        //Se invece la transazione è già fallita, non eseguo nulla
		if(	IWGlobal::getDbTransaction() == "ROLLBACK" )
		{
			return false;
		}

        //In condizioni normali, eseguo la query e ritorno un booleano in caso di fallimento o un oggetto PDOStatement
		return $this->db_query($sql);

	}

    /**
     * @param IWQuery $sql
     * @param bool $use_pagination
     * @return array
     */
    public function getResults($sql, $use_pagination = true){
        $entities = $sql->getFields();
        $sql2 = \IWSelect();
        $t = 0;
        $alias = [];
        $columns = [];
        $namespaces = [];
        foreach ($entities as $entity) {
            $dbTable = "";
            foreach ($this->modules as $namespace) {
                if(!class_exists($namespace."\\Entities\\".$entity)) continue;

                $dbTable = $namespace."\\Entities\\".$entity;
                break;
            }
            //$dbTable = getNameSpace($entity)."\\".$entity;
            $namespaces[$entity] = $dbTable;
            /**
             * @var $dbTable DBTable
             */
            $dbTable = new $dbTable();
            $cols = $dbTable->getObjectCols();
            foreach ($cols as $col) {
                $sql2->addField("T".$t.".".$col." AS ".$entity."_".$col);
            }

            $alias[$entity] = "T".$t;
            $columns[$entity] = $cols;

            $t++;
        }
        $table_entity = $namespaces[$sql->getTable()];
        /**
         * @var $dbTableEntity DBTable
         */
        $dbTableEntity = new $table_entity();
        $sql2->setTable($dbTableEntity->getTable()." AS T0");

        $t = 1;
        foreach ($sql->getAlljoins() as $join) {
            $table = $join["table"];
            $myAlias = $alias[$table];
            $on = $join["on"];
            $method = $join["type"] == "left" ? "addLeftJoin" : "addJoin";
            $entity = $namespaces[$table];
            $dbTable = new $entity();
            $on = str_replace(array_keys($alias), array_values($alias), $on);
            $sql2->$method($dbTable->getTable(). " AS ".$myAlias, $on);
            $t++;
        }
        $wheres = $sql->getWheres();
        foreach ($wheres as $where) {
            if($where == "1=1") {
                $sql2->addWhere($where);
                continue;
            }
            $where = str_replace(array_keys($alias), array_values($alias), $where);
            $sql2->addWhere($where);
            continue;
        }

        $sql2->setParams($sql->getParams());
        $orderbys = $sql->getOrderbys();
        foreach ($orderbys as $orderby) {
            $orderby = str_replace(array_keys($alias), array_values($alias), $orderby);
            $sql2->addOrderby($orderby);
        }

        $sql2->setParams($sql->getParams());

        $nrighe = 0;
        if($use_pagination)
            list($rs, $nrighe) = $this->db_paging_query3($sql2);
        else $rs = $this->db_query($sql2);

        $res = [];
        $tot_righe = 0;
        while($r = $this->db_fetch_array($rs,1)){
            $row = [];
            foreach ($entities as $entity) {
                $values = [];
                foreach ($r as $key => $value) {
                    if( !startsWith($entity, $key) ) continue;

                    $values[str_replace($entity."_","",$key)] = $value;
                    unset($r[$key]);
                }
                $dbTable = $namespaces[$entity];
                $dbTable = new $dbTable(0, $values);
                $row[] = $dbTable;
            }

            $tot_righe++;
            $res[] = $row;
        }

        if($nrighe == 0) $nrighe = $tot_righe;
        return [$res,$nrighe];
    }

    /**
     * Legge SOLO il primo record di una query e ritorna un array
     * @param IWQuery $sql
     * @param int $tipo
     * @param bool|false $use_cache
     * @return array|bool|null|string
     */
	function DBRead($sql,$tipo=0,$use_cache=false)
	{
		switch($tipo){
			case 1: $tipo = PDO::FETCH_ASSOC; break;
			default: $tipo = PDO::FETCH_BOTH; break;
		}
		if( $use_cache && IWCache::have($sql) )
			return IWCache::get($sql);

		$rs = $this->db_query($sql);
		if( $this->db_num_rows($rs)==0 )
		{
			IWCache::set($sql,false);
			return false;
		}

		$r = $this->db_fetch_array($rs,$tipo);
		if($r===false) echo "<u>MysqlError:</u> ".$this->db_error()." <u>sql:</u>$sql<hr>";
		IWCache::set($sql,$r);
		return $r;
	}

    /**
     * Legge e ritorna il primo campo del primo record
     * @param IWQuery $sql
     * @param bool|false $use_cache
     * @param string $key_cache
     * @return string
     */
	public function DBReadFirst($sql,$use_cache=false,$key_cache = "")
	{
		if( $sql->getLimit() == "") $sql->addLimit(1);
		if($key_cache=="") $key_cache = $sql->getQueryForCache();

		if( $use_cache && IWCache::have($key_cache) )
			return IWCache::get($key_cache);

        $rs = $this->db_query($sql);
        if( $this->db_num_rows($rs)==0 ) return "";
        $r = $this->db_fetch_array($rs);
        $s=$r[0];
        IWCache::set($key_cache,$s);

		return $s;
	}

    /**
     * Legge la prima colonna del recordset e restituisce un array con chiavi numeriche
     * @param IWQuery $sql
     * @param bool|false $use_cache
     * @param string $key_cache
     * @return array
     */
	public function DBReadArray($sql,$use_cache=false, $key_cache = "")
	{
		if($key_cache=="") $key_cache = $sql->getQueryForCache();
		$arr=array();
		if($use_cache && IWCache::have($key_cache))
			return IWCache::get($key_cache);

        $rs = $this->db_query($sql);
        if( $this->db_num_rows($rs)==0 ) $arr=array();
        while( $r=$this->db_fetch_array($rs) )
            array_push($arr,$r[0]);

		IWCache::set($key_cache,$arr);

		return $arr;
	}

    /**
     * Legge la seconda colonna del recordset e restituisce un array che ha le chiavi impostate dalla prima colonna
     * @param IWQuery $sql
     * @param bool|false $use_cache
     * @param string $key_cache
     * @return array
     */
	public function DBReadArrayWithKeys($sql,$use_cache=false, $key_cache = "")
	{
		if($key_cache=="") $key_cache = $sql->getQueryForCache();
		$arr=array();
		if($use_cache && IWCache::have($key_cache))
			return IWCache::get($key_cache);

		$rs = $this->db_query($sql);
		if( $this->db_num_rows($rs)==0 ) $arr=array();
		while( $r=$this->db_fetch_array($rs) )
			$arr[$r[0]]=$r[1];

		IWCache::set($key_cache,$arr);

		return $arr;
	}


    /**
     * Inizia una transazione
     * Facoltativamente acquisisce anche un semaforo che verrà liberato al commit
     * @param string $semaphore_name
     */
	public function DBStart($semaphore_name="")
	{
        IWGlobal::setDbTransactionSemaphore($semaphore_name);
		if(IWGlobal::getDbTransactionSemaphore() !="" ) IWSemaphore::sem_acq(IWGlobal::getDbTransactionSemaphore());

		//IWSession::w("queries_transaction","");

		IWGlobal::setDbTransaction("STARTED");

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->beginTransaction();
	}

    /**
     * Metodo per eseguire query di DELETE specificando il where
     * @param $table
     * @param $where
     * @return bool|PDOStatement
     */
    public function DBDelete($table,$where)
    {
        $sql = new IWQuery("DELETE");
        $sql->setTable($table)->addWhere($where);
        $ret=$this->DBExec($sql);
        return $ret;
    }

    /**
     * Esegue il commit finale di una transazione. Libera l'eventuale semaforo se impostato
     * @return bool
     */
	public function DBCommit()
	{
		$ret = false;
		if(	IWGlobal::getDbTransaction() == "STARTED" )
		{
			if($this->pdo->inTransaction()) $ret = $this->pdo->commit();
			IWGlobal::setDbTransaction("");
		}
		else if( IWGlobal::getDbTransaction() == "ROLLBACK" )
		{
            IWGlobal::setDbTransaction("");
			$ret=false;
		}

		if( IWGlobal::getDbTransactionSemaphore() != "" )
		{
			IWSemaphore::sem_rel(IWGlobal::getDbTransactionSemaphore());
			IWGlobal::setDbTransactionSemaphore("");
		}
		return $ret;
	}

    /**
     * Metodo per eseguire la Rollback all'interno di una transazione
     */
	public function DBRollBack()
	{
		if($this->pdo->inTransaction()) $this->pdo->rollBack();
		IWGlobal::setDbTransaction("ROLLBACK");
        if( IWGlobal::getDbTransactionSemaphore() != "" )
        {
            IWSemaphore::sem_rel(IWGlobal::getDbTransactionSemaphore());
            IWGlobal::setDbTransactionSemaphore("");
        }
	}

    /**
     * Metodo che ritorna il valore del campo della tabella passata come argomento sull'id passato come argomento
     * @param $table
     * @param $campo
     * @param $id
     * @return string
     */
	public function DBGet($table,$campo,$id){
		return $this->DBReadFirst(\IWSelect()->addField($campo)->setTable($table)->addWhere("id = :id")->bindParam("id",$id,"int"),true);
	}

    /**
     * @return \SlimSession\Helper
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param \SlimSession\Helper $session
     * @return IWDb
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param array $modules
     * @return IWDb
     */
    public function setModules($modules)
    {
        $this->modules = $modules;

        return $this;
    }
}