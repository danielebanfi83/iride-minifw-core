<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 01/06/16
 * Time: 12:54
 */

namespace IrideWeb\Core;


use IrideWeb\Controllers\IWIndex;
use IrideWeb\Controllers\IWNoAccess;
use IrideWeb\Database\IWDb;
use IrideWeb\Database\IWUsersInterface;
use IrideWeb\Twig\IrideTwigExtension;
use Slim\Http\Response;
use Twig_Environment;

abstract class IWController
{
    /**
     * @var \Slim\Views\Twig
     */
    protected $twig;
    
    protected $args;

    /**
     * @var \SlimSession\Helper
     */
    protected $session;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Response Format, defaults supported are "twig", "json" or "xml"
     * @var string
     */
    protected $responseFormat;

    /**
     * @var IWDb
     */
    private $iwdb;
    
    protected $role;

    /**
     * @var array
     */
    protected $modules;

    /**
     * Internal Parameters loaded by parametri.yml
     * @var array
     */
    protected $parameters;

    /**
     * @var IWTranslator
     */
    protected $translator;

    /**
     * @var IWUsersInterface
     */
    protected $user;

    /**
     * @var bool
     */
    protected $no_csrf_protection = false;

    /**
     * @var string
     */
    private $filename;
    
    public static function factory($object){
        $parameters = IWGlobal::get("config");
        if(!array_key_exists("factory", $parameters)) $index = new IWIndex();
        elseif(!array_key_exists("index", $parameters["factory"])) $index = new IWIndex();
        else{
            $index = $parameters["factory"]["index"];
            $index = new $index();
        }

        if($object == "") return $index;
        if(!class_exists($object)) return $index;

        /**
         * @var $object IWController
         */
        $object = new $object();

        if(!$object instanceof IWController) return $index;

        return $object;
    }

    /**
     * @return IWController
     */
    public function noAccessFactory(){
        if(!array_key_exists("factory", $this->parameters)) return new IWNoAccess();

        if(!array_key_exists("no_access", $this->parameters["factory"])) return new IWNoAccess();

        $no_access = $this->parameters["factory"]["no_access"];
        $no_access = new $no_access();
        if(!($no_access instanceof IWController)) return new IWNoAccess();

        return $no_access;
    }

    public function getResponseFormat(){
        return "twig";
    }
    
    public function getTwigPage(){
        return "";
    }
    
    public function setParameters($parameters){
        $this->parameters = $parameters;

        $this->modules = [];
        foreach ($this->parameters["modules"] as $module) {
            $this->modules[] = $module["name"];
        }

        $pwds = $this->parameters["pwd_passepartout"];
        IWGlobal::set("pwd1", $pwds["pwd1"]);
        IWGlobal::set("pwd2", $pwds["pwd2"]);

        return $this;
    }

    /**
     * @param IWDb $iwdb
     * @return IWController
     */
    public function setIwdb($iwdb)
    {
        $this->iwdb = $iwdb;
        if($this->iwdb !== null){
            $this->iwdb->setModules($this->modules);
            $this->iwdb->setSession($this->session);
        }

        return $this;
    }
    
    public function getDb(){
        return $this->iwdb;
    }

    public function checkPermission($role){
        if($role == "anon") return true;

        if(!array_key_exists("factory", $this->parameters)) return false;
        if(!array_key_exists("users", $this->parameters["factory"])) return false;

        $usersClass = $this->parameters["factory"]["users"];

        $this->user = new $usersClass($this->session->get("userLogged"));
        if(!($this->user instanceof IWUsersInterface)) return false;

        $perms = [
            "is_admin" => $this->user->getAdmin() == 1 ? true : false,
            "is_superadmin" => $this->user->getSuperadmin() == 1 ? true : false,
            "is_supersuperadmin" => $this->user->getSuperSuperadmin() == 1 ? true : false
        ];
        $this->args = array_merge($this->args, $perms);
        if($this->user->getId() == 0) return false;

        IWGlobal::setUser($this->user);
        if($this->user->getSuperSuperadmin() == 1) return true;

        $perm = 1;
        if($role == "admin") $perm = 11;
        if($role == "superadmin") $perm = 111;

        $user_perm = intval(intval($this->user->getSuperadmin()).intval($this->user->getAdmin())."1");

        if($user_perm >= $perm) return true;

        return false;
    }
    
    public function run(){
        if($this->user !== null){
            $this->translator->setLang($this->user->getLang());
            $this->twig->getEnvironment()->getExtension("iride_extensions")->setTranslator($this->translator);
        }

        $this->responseFormat = $this->getResponseFormat();

        if($this->request->getParsedBody()!==null)
            if(array_key_exists("OP_FROM_AJAX",$this->request->getParsedBody()))
                if(intval($this->request->getParsedBody()["OP_FROM_AJAX"]) == 1) $this->responseFormat = "json";
        switch($this->responseFormat){
            case "json" :
                 $response = $this->response->withJson($this->getTotalContext()); break;
            case "xml":
                $this->response = $this->response->withHeader("Content-Disposition","attachment; filename=\"stream.xml\"");
                $this->response->getBody()->write(generaXML($this->getTotalContext())); 
                $response = $this->response;
                break;
            case "twig":
                $response = $this->twig->render($this->response, $this->getTwigPage(), $this->getTotalContext()); break;
            case "file":
                $this->response = $this->response->withHeader("Content-Type", "application/".getExtension($this->filename));
                $this->response = $this->response->withAddedHeader("Content-Disposition", "attachment; filename=\"".$this->filename."\"");
                $this->response->getBody()->write($this->getTotalContext());
                $response = $this->response;
                break;
            default:
                $this->response->getBody()->write($this->getTotalContext());
                $response = $this->response;
                break;
        }
        return $response;
    }
    
    private function getTotalContext(){
        if($this->request->getAttribute("csrf_result") == "FAILED" && !$this->no_csrf_protection) return["csrf_result" => "FAILED"];
        if(array_key_exists("OP_FROM_AJAX", $this->request->getParsedBody())){
            if($this->responseFormat == "json" && intval($this->request->getParsedBody()["OP_FROM_AJAX"]) == 1){
                unset($this->args["is_admin"], $this->args["is_superadmin"], $this->args["is_supersuperadmin"]);
                return array_merge($this->args,$this->saveInDb());
            }    
        }
        if(in_array($this->responseFormat, ["json","xml","twig"]))
        {
            return array_merge($this->args, $this->getContext());
        }

        return $this->getContext();
    }
    
    public function setFilename($filename){
        $this->filename = $filename;
    }

    private function saveInDb(){
        $this->getDb()->DBOpen();
        $this->getDb()->DBStart();

        $OUT = [];
        $ret = $this->save();
        if(is_array($ret)) $OUT = $ret;
        else $OUT["msg"] = $ret;
        
        if($OUT["msg"]!="") $this->getDb()->DBRollBack();
        if($this->getDb()->DBCommit())
            $OUT["ret"] = true;
        else
            $OUT["ret"] = false;
        
        return $OUT;
    }

    public function path($route){
        $twig_env = $this->twig->getEnvironment();
        /**
         * @var $irideTwig IrideTwigExtension
         */
        $irideTwig = $twig_env->getExtension("iride_extensions");
        return $irideTwig->path($route);
    }

    /**
     * @return array|string
     */
    public function save(){
        return "";
    }

    /**
     * @return string|array
     */
    abstract public function getContext();

    /**
     * @return \Slim\Views\Twig
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * @param Twig_Environment $twig
     * @return IWController
     */
    public function setTwig($twig)
    {
        $this->twig = $twig;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $args
     * @return IWController
     */
    public function setArgs($args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return IWController
     */
    public function setRequest($request)
    {
        $this->request = $request;
        IWGlobal::setRequest($this->request);

        return $this;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return IWController
     */
    public function setResponse($response)
    {
        $this->response = $response;
        IWGlobal::setResponse($this->response);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     * @return IWController
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
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
     * @return IWController
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return IWTranslator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param IWTranslator $translator
     * @return IWController
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;

        return $this;
    }
}