<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 20/10/14
 * Time: 11.27
 *
 * Creare un nuovo oggetto IWRestClient specificando la base url del Web Service
 * $rest_client = new IWRestClient("http://www.irideglobalservice.it/virtuemart/export/index.php/");
 * Aggiungere eventuali header o parametri
 * $rest_client->addHeader("Content-Type", "application/json");
 * $rest_client->addParam("Param1", "parametro 1");
 * Se il servizio risponde un un Json potete ottenere in output direttamente un array impostando:
 * $rest_client->convertJsonToArray(true);
 */
namespace IrideWeb\Core;

class IWRestClient
{
    public $base_url, $is_post, $params = array(), $headers = array(), $json_to_array;

    protected $useragent, $cookie, $reference, $returnheader, $redirect, $tmpdir;

    /**
     * Timeout dell'esecuzione in SECONDI
     * @var int
     */
    protected $timeout;

    /**
     * Timeout per la connessione in SECONDI
     * @var int
     */
    protected $connect_timeout;

    /**
     * Di default la chiamata è in GET
     * @param String $base_url Punto di accesso del servizio REST
     */
    public function __construct($base_url = "")
    {
        $this->base_url = $base_url;
        $this->is_post = false;
        $this->params = array();
        $this->headers = array();
        $this->json_to_array = false;

        $this->setUseragent($this->getRandomUserAgent());
        $this->returnheader = false;
        $this->reference = "";
        $this->cookie = "";
        $this->redirect = false;
        $this->returnheader = false;
        $this->timeout = 0;
        $this->connect_timeout = 0;

    }

    /**
     * @param boolean $is_post
     */
    public function setIsPost($is_post)
    {
        $this->is_post = $is_post;
    }

    /**
     * Pulisce i parametri della chiamata
     */
    public function resetParams()
    {
        $this->params = array();
    }

    /**
     * Aggiunge un parametro (da usare con metodo POST)
     * @param String $key Nome del parametro
     * @param String $value Valore del parametro
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * Pulisce gli headers della chiamata
     */
    public function resetHeaders()
    {
        $this->headers = array();
    }

    /**
     * @param String $key Nome dell'header (es. Content-Type)
     * @param String $value Valore dell'header (es. application/json)
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Impostare a true per ricevere un array
     * @param boolean $json_to_array
     */
    public function convertJsonToArray($json_to_array)
    {
        $this->json_to_array = $json_to_array;
    }

    /**
     * @param mixed $useragent
     */
    public function setUseragent($useragent)
    {
        $this->useragent = $useragent;
    }

    /**
     * @param mixed $cookie
     */
    public function setCookie($cookie)
    {
        $this->cookie = $this->tmpdir . "/" . $cookie;
    }

    /**
     * @param mixed $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @param mixed $returnheader
     */
    public function setReturnheader($returnheader)
    {
        $this->returnheader = $returnheader;
    }

    /**
     * @param mixed $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getRandomUserAgent()
    {
        $userAgents = array(
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36",
            "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1",
            "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko",
            "Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko",
            "Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16",
            "Mozilla/5.0 (Windows NT 6.0; rv:2.0) Gecko/20100101 Firefox/4.0 Opera 12.14",
            "Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/48 (like Gecko) Safari/48",
        );
        $random = rand(0, count($userAgents) - 1);

        return $userAgents[$random];
    }

    public function startWs($uri)
    {
        $headers = array();
        //Converto gli header da array chiave/valore in un array di stringhe
        foreach ($this->headers as $key => $value) {
            array_push($headers, $key . ": " . $value);
        }
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_POST, $this->is_post);
        if ($this->is_post) curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($this->params));
        else $uri .= !empty($this->params) ? "?" . http_build_query($this->params) : "";
        curl_setopt($handle, CURLOPT_URL, $this->base_url . $uri);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($handle, CURLOPT_HEADER, $this->returnheader);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, $this->redirect);
        if ($this->reference != "") curl_setopt($handle, CURLOPT_REFERER, $this->reference);

        if ($this->cookie != "") {
            curl_setopt($handle, CURLOPT_COOKIE, $this->cookie);
            curl_setopt($handle, CURLOPT_COOKIEJAR, $this->cookie);
            curl_setopt($handle, CURLOPT_COOKIEFILE, $this->cookie);
        }
        if($this->timeout != 0) curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        if($this->connect_timeout != 0) curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);

        $curl_response = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $code_error = curl_errno($handle);
        $error = curl_error($handle);
        if($error != "") throw new IWRestClientException("[".$code_error."] ".$error, $code_error);
        curl_close($handle);
        if ($this->json_to_array) return array(json_decode($curl_response), $status);
        else return array(utf8_decode($curl_response), $status);
    }

    /**
     * @return mixed
     */
    public function getTmpdir()
    {
        return $this->tmpdir;
    }

    /**
     * @param mixed $tmpdir
     */
    public function setTmpdir($tmpdir)
    {
        $this->tmpdir = $tmpdir;
    }

    /**
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param mixed $timeout
     * @return IWRestClient
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connect_timeout;
    }

    /**
     * @param int $connect_timeout
     * @return IWRestClient
     */
    public function setConnectTimeout($connect_timeout)
    {
        $this->connect_timeout = $connect_timeout;

        return $this;
    }
}

class IWRestClientException extends \Exception {}