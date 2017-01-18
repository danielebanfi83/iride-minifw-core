<?php 


//funzione che carica un altra pagina con l'aggiunta opzionale di stringhe all'url
use IrideWeb\Cache\IWCache;
use IrideWeb\Core\IWGlobal;
use IrideWeb\Database\IWQuery;

//funzione che da una stringa converte in numero
// (sostituisce le virgole con i punti)
//rende scrivibile nel DB il numero
function tofloat($s,$decimali=999)
{
	if( is_array($s) ){ $s=array_map("tofloat",$s); return $s; }
	$s=str_replace(',','.',$s);
	$s=floatval($s);
	$s=str_replace(',','.',$s);

	if($decimali!=999)
		$s=number_format( round($s,$decimali),$decimali,".","") ;

	return $s;
}

//funzione che serve per stampare un valore EURO in modo che sia presentabile a schermo
//(ovvero 2 decimali e separatore delle migliaia)
function toEuro($e,$decimals=2,$se_zero_stringa_vuota=false)
{
	if( is_array($e) ){ $e=array_map("toEuro",$e); return $e; }
	$e=str_replace(' ','',$e);
	$e=floatval($e);
	$e=round($e,$decimals);	

    $e=number_format($e,$decimals,IWGlobal::get("DECIMAL_SEPARATOR"),IWGlobal::get("THOUSANDS_SEPARATOR"));
	
	if($se_zero_stringa_vuota && $e==toEuro(0,$decimals)) $e="";

	return $e;
}

//ritorna l'estensione del file in formato lowercase
function getExtension($fn)
{
	//$ext="";
	$fn=strtolower($fn);
	$i=strrpos($fn,".");
	if($i>0)
		$ext=substr($fn,$i+1);
	else
		$ext="";
	return $ext;
}

/**
 * @param $n
 * @return bool
 */
function isIntero($n)
{
	$n=round($n,12);
	$upper=ceil($n);
	$lower=floor($n);
	if( $upper==$lower)
		return true;
	else
		return false;
}

//data un array associativo, lo scrive a schermo con XML. Prima di scriverlo pulisce il buffer dell schermo
function generaXML($OUT)
{
    $doc = new DOMDocument("1.0", "UTF-8");
    $doc->formatOutput = true;
    $root=$doc->createElement("root");
    $root = $doc->appendChild($root);

    if( !is_array($OUT[0]) ){
        $keys=array_keys($OUT);
        $values=array_values($OUT);
        for($i=0;$i<count($OUT);$i++){
            $key=$doc->createElement($keys[$i]);
            $key=$root->appendChild($key);
            $value = $doc->createTextNode($values[$i]);
            $key->appendChild($value);
        }
    }
    if( is_array($OUT[0]) ) {
        for($i=0;$i<count($OUT);$i++){
            $row=$doc->createElement("row");
            $r=$root->appendChild($row);
            $ar_row=$OUT[$i];
            foreach($ar_row as $chiave=>$valore){
                $key=$doc->createElement($chiave);
                $key=$r->appendChild($key);
                $value = $doc->createTextNode($valore);
                $key->appendChild($value);
            }
        }
    }
    return $doc->saveXML();
}


//Stessa cosa del genera xml, solo che ritorna un oggetto json
function generaJSON($OUT){
    return json_encode($OUT);
}

/**
 * @param $str
 * @param bool $metti_accento
 * @return mixed
 */
function replace_lettere_accentate($str,$metti_accento=true)
{
	$x="";
	if( $metti_accento ) $x="'";
	$str=str_replace("à","a{$x}",$str);
	$str=str_replace("è","e{$x}",$str);
	$str=str_replace("é","e{$x}",$str);
	$str=str_replace("ì","i{$x}",$str);
	$str=str_replace("ò","o{$x}",$str);
	$str=str_replace("ù","u{$x}",$str);
    $str=str_replace("°","",$str);
	
	return $str;
}

/**
 * Funzione che ritorna un array contenente i file all'interno della cartella passata come parametro
 * Non considera eventuali sottodirectory
 * @param string $dir
 * @return array
 */
function elencafiles($dir){
	$array = [];
    if($directory = opendir($dir))
    {
        while(false !== ($file = readdir($directory)))
        {
			if(is_dir($directory."/".$file)) continue;
			$array[] = $file;
        }

        closedir($directory);
    }
    sort($array,SORT_STRING);
    return $array;
}

/**
 * Funzione che traduce le parole passate sulla base del dizionario presente (unico per ogni azienda)
 * Se non esiste la stringa passata ritorna la stringa stessa, di default mettendo in maiuscolo le prime lettere di ogni parola
 * @param $id
 * @param bool $no_ucwords
 * @return string
 */
function t($id,$no_ucwords=false){
    $lang=IWGlobal::get("LANG");
    $dizionario=IWGlobal::get("DIZIONARIO");
    $original_word=$id;
    $id=strtolower($id);
    if($dizionario[$id][$lang]=="") return $no_ucwords ? $original_word : ucwords($id);
    else return $dizionario[$id][$lang];
}

function checkEmail($email){
    $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
    if(getExtension($email)=="info") return true;
    if(getExtension($email)=="coop") return true;
	if(getExtension($email)=="name") return true;
    if (preg_match($regex, $email)) return true;
    else return false;
}

function formSerialize($array){
    $s="";
    foreach($array as $key=>$value) $s.="&".$key."=".$value;
    return $s;
}

function iwPad($input,$length,$pad_string=" ",$type=STR_PAD_LEFT){
    $l1=strlen($input);
    $l2=strlen(utf8_decode($input));
    $diff=$l1-$l2;
    if($diff>0) $length+=$diff;
    $input=substr(trim($input),0,$length);
    return str_pad($input,$length,$pad_string,$type);
}

function iwPadRight($input,$length,$pad_string=" "){
    return iwPad($input,$length,$pad_string,STR_PAD_RIGHT);
}

/**
 * Simile all'array_push ma non pusha sull'ultima posizione dell'array, ma sulla chiave che gli passo in parametro
 * Gli altri elementi saranno spostati di 1 posizione
 * @param $array
 * @param $element
 * @param $key
 * @return array
 */
function array_insert($array,$element,$key){
    $ar1=array_slice($array,0,$key);
    array_push($ar1,$element);
    $ar2=array_slice($array,$key);
    foreach($ar2 as $e) array_push($ar1,$e);
    return $ar1;
}

function isNotNull($var){ return $var!=""; }

function array_filter_nulls($array){
    return array_filter($array,"isNotNull");
}

/**
 * Funzione che controlla se la stringa passata come primo parametro è il prefisso iniziale della seconda stringa passata come parametro
 * @param $search
 * @param $subject
 * @return bool
 */
function startsWith($search,$subject){
    if(substr($subject,0,strlen($search))==$search) return true;
    return false;
}

/**
 * Funzione che contorlla se la stringa passata contenga e rimuova i due caratteri iniziali che ne indicano la nazionalità
 * @param $string
 * @return string
 */

function pivaCharsRemover($string){
	return preg_replace("/^\\D*/", "", $string);
}

function elencadir($dir,$array,$exclude_dirs=array()){
    if($directory = opendir($dir)){
        while(false !== ($file = readdir($directory))){
            if(array_search($file,$exclude_dirs)) continue;
            $real=realpath($dir."/".$file);
            if(is_dir($real)) {
                array_push($array,$real);
                $array=elencadir($real,$array,$exclude_dirs);
            }
        }
        closedir($directory);
    }
    sort($array,SORT_STRING);
    return $array;
}

function iwautoload($class_name) {
    $ar_classes = explode("\\",$class_name);
    $class_name = $ar_classes[count($ar_classes)-1];
    //include("/Users/Daniele/Documents/Iride/irideweb2/classi/core/".$class_name.".php");
    $exclude_dirs = array("", ".", "..","third-party");
    $ar=array();
    $fromCache = array_key_exists("dirList", $GLOBALS) ? $GLOBALS["dirList"] : null;
    if(is_null($fromCache)) {
        $ar_dir=elencadir(__DIR__."/../../../src",$ar,$exclude_dirs);
        $GLOBALS["dirList"] = $ar_dir;
    }
    else $ar_dir = $fromCache;
    foreach($ar_dir as $dir){
        if(!file_exists($dir."/".$class_name.".php")) continue;

        include_once($dir."/".$class_name.".php");
        if(IWCache::have($class_name."_namespace")) break;

        $content = file_get_contents($dir."/".$class_name.".php");
        $fullyQ = getNameSpaceFromContent($content);
        IWCache::set($class_name."_namespace", $fullyQ, 604800);

        break;
    }
}

function getNameSpaceFromContent($content){
    $pos1 = strpos($content,"namespace") + 10;
    $pos2 = strpos(substr($content,$pos1),";");
    return trim(substr($content,$pos1,$pos2));
}

function getNameSpace($classname){
    if(IWCache::get($classname."_namespace") != "") return IWCache::get($classname."_namespace");
    $ar = [];
    $exclude_dirs = array("", ".", "..","third-party");
    $ar_dir=elencadir(__DIR__."/../../src",$ar,$exclude_dirs);
    $content = "";
    foreach ($ar_dir as $dir) {
        if(!file_exists($dir."/".$classname.".php")) continue;

        $content = file_get_contents($dir."/".$classname.".php");
        break;
    }
    $fullyQ = getNameSpaceFromContent($content);
    IWCache::set($classname."_namespace", $fullyQ, 604800);
    return $fullyQ;
}

function getCurrentClass($class){
    $path = explode("\\",$class);
    $c = count($path) - 1;
    return $path[$c];
}

function IWSelect(){
    return new IWQuery("SELECT");
}

function IWInsert(){
    return new IWQuery("INSERT");
}

function IWUpdate(){
    return new IWQuery("UPDATE");
}

function IWDelete(){
    return new IWQuery("DELETE");
}

//ritorna l'attuale timestamp in secondi con precisione al microsecondo
function getmicrotime()
{
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}