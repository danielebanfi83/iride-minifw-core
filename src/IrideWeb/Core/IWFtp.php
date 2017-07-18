<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 18/07/17
 * Time: 16:30
 */

namespace IrideWeb\Core;

class IWFtp{
    private $url,$port,$timeout;
    private $username,$password;

    private $conn_id;

    public function __construct($url,$username,$password,$port=21,$timeout=90){
        $this->url=$url;
        $this->username=$username;
        $this->password=$password;
        $this->port=$port;
        $this->timeout=$timeout;
    }

    public function connect(){
        $this->conn_id=ftp_connect($this->url,$this->port,$this->timeout);
        if($this->conn_id===false) throw new FtpException(t("connessione fallita"));
        if(!ftp_login($this->conn_id,$this->username,$this->password)) throw new FtpException(t("autenticazione fallita"));
    }

    public function close(){
        if(!ftp_close($this->conn_id)) throw new FtpException(t("chiusura della connessione fallita"));
    }

    public function download($file,$dir_destination,$dir="/"){
        if($file=="*") $this->downloadAllFiles($dir);
        else $this->downloadSingleFile($file,$dir,$dir_destination);
    }

    public function downloadAllFiles($dir,$dir_destination=""){
        $files=ftp_nlist($this->conn_id,$dir);
        foreach($files as $file){
            if($this->ftp_is_dir($file)) continue;
            $this->downloadSingleFile($file,$dir,$dir_destination);
        }
        return count($files);
    }

    public function getElencoFiles($dir){
        $files=ftp_nlist($this->conn_id,$dir);
        $my=array();
        foreach($files as $file){
            if($this->ftp_is_dir($file)) continue;
            $file=str_replace($dir."/","",$file);
            if(substr($file,0,1)==".") continue;
            $my[]=$file;
        }
        return $my;
    }

    public function downloadSingleFile($file,$dir,$dir_destination){
        $local_file=str_replace($dir."/","",$file);
        if(!is_dir($dir_destination)) mkdir($dir_destination);
        if(!ftp_get($this->conn_id,$dir_destination.$local_file,$file,$this::get_ftp_mode($file)))
            throw new FtpException(t("impossibile recuperare il file")." ".$file);
    }

    public function moveFilesToDir($array_files,$dir_source,$dir_destination){
        if(!$this->ftp_is_dir($dir_destination)) {
            $dir=ftp_mkdir($this->conn_id,$dir_destination);
            if($dir===false) throw new FtpException(t("impossibile creare la directory nella posizione specificata"));
        }
        foreach($array_files as $file){
            if(substr($file,0,1)==".") continue;
            if(!ftp_rename($this->conn_id,$dir_source."/".$file,$dir_destination."/".$file))
                throw new FtpException(t("errore nello spostamento del file:")." ".$dir_destination."/".$file);
        }
    }

    public function deleteFilesFromDir($array_files,$dir_source){
        foreach($array_files as $file){
            if(substr($file,0,1)==".") continue;
            if(!ftp_delete($this->conn_id,$dir_source."/".$file))
                throw new FtpException(t("errore nella cancellazione del file:")." ".$file);
        }
    }

    public function sendFileToServer($remote_file,$local_file){
        if(!ftp_put($this->conn_id,$remote_file,$local_file,$this::get_ftp_mode($local_file)))
            throw new FtpException(t("errore nel trasferimento del file:")." ".$local_file);
    }

    private static function get_ftp_mode($file){
        $path_parts = pathinfo($file);

        if (!isset($path_parts['extension'])) return FTP_BINARY;
        switch (strtolower($path_parts['extension'])) {
            case 'am':case 'asp':case 'bat':case 'c':case 'cfm':case 'cgi':case 'conf':
            case 'cpp':case 'css':case 'dhtml':case 'diz':case 'h':case 'hpp':case 'htm':
            case 'html':case 'in':case 'inc':case 'js':case 'm4':case 'mak':case 'nfs':
            case 'nsi':case 'pas':case 'patch':case 'php':case 'php3':case 'php4':case 'php5':
            case 'phtml':case 'pl':case 'po':case 'py':case 'qmail':case 'sh':case 'shtml':
            case 'sql':case 'tcl':case 'tpl':case 'txt':case 'vbs':case 'xml':case 'xrc':
            return FTP_ASCII;
        }
        return FTP_BINARY;
    }

    private function ftp_is_dir($dir) {
        if (ftp_chdir($this->conn_id, $dir)) {
            ftp_chdir($this->conn_id, '..');
            return true;
        } else {
            return false;
        }
    }
}

class FtpException extends \Exception{}

