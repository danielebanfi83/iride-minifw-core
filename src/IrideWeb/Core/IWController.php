<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 01/06/16
 * Time: 12:54
 */

namespace IrideWeb\Core;


use IrideWeb\Database\IWDb;
use IrideWeb\Database\IWUsersInterface;
use IrideWeb\IrideWeb\Core\IWNoAccess;
use IrideWeb\IWDb\Entities\UsersTable;
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
     * Internal Parameters loaded by parametri.yml
     * @var array
     */
    protected $parameters;
    
    public static function factory($object, $twig, $psrVars, $args, $role, $session){
        $index = "IrideWeb\\IrideWeb\\Controllers\\IWIndex";
        if($object == "") $object = $index;
        if(!class_exists($object)) $object = $index;

        /**
         * @var $object IWController
         */
        $object = new $object();

        if(!$object instanceof IWController)
            $object = new $index();

        $object->setSession($session);
        $object->setParameters();
        $object->setArgs($args);
        $perm = $object->checkPermission($role);
        if(!$perm) {
            $object = new IWNoAccess();
            $object->setSession($session);
            $object->setParameters();
            $object->setArgs($args);
        }

        $object->setTwig($twig);
        $object->setRequest($psrVars[0]);
        $object->setResponse($psrVars[1]);
        $object->setResponseFormat();
        $object->setRole($role);

        return $object;
    }

    private function setResponseFormat(){
        $this->responseFormat = $this->getResponseFormat();
    }

    public function getResponseFormat(){
        return "twig";
    }
    
    public function getTwigPage(){
        return "";
    }
    
    public function setParameters(){
        $this->parameters = \Spyc::YAMLLoad(__DIR__."/../../../../config/config.yml");
        IWGlobal::set("config", $this->parameters);
        $db_params = $this->parameters["db_parameters"];
        $this->iwdb = new IWDb($db_params["dbhost"],$db_params["dbuser"],$db_params["dbpwd"]);
        $this->session->set("db",$db_params["dbname"]);
        $this->iwdb->setSession($this->session);
        $this->iwdb->DBOpen();
        IWGlobal::setDbInstance($this->iwdb);

        $pwds = $this->parameters["pwd_passepartout"];
        IWGlobal::set("pwd1", $pwds["pwd1"]);
        IWGlobal::set("pwd2", $pwds["pwd2"]);
    }
    
    public function getDb(){
        return $this->iwdb;
    }

    public function checkPermission($role){
        if($role == "anon") return true;

        $usersClass = $this->parameters["db_parameters"]["users_class"];
        /**
         * @var $user IWUsersInterface
         */
        $user = new $usersClass($this->session->get("userLogged"));
        if(!($user instanceof IWUsersInterface)) return false;

        $perms = [
            "is_admin" => $user->getAdmin() == 1 ? true : false,
            "is_superadmin" => $user->getSuperadmin() == 1 ? true : false,
            "is_supersuperadmin" => $user->getSuperSuperadmin() == 1 ? true : false
        ];
        $this->args = array_merge($this->args, $perms);
        if($user->getId() == 0) return false;

        IWGlobal::setUser($user);
        if($user->getSuperSuperadmin() == 1) return true;

        $perm = 1;
        if($role == "admin") $perm = 11;
        if($role == "superadmin") $perm = 111;

        $user_perm = intval(intval($user->getSuperadmin()).intval($user->getAdmin())."1");

        if($user_perm >= $perm) return true;

        return false;
    }
    
    public function run(){
        if(intval($this->request->getParsedBody()["OP_FROM_AJAX"]) == 1) $this->responseFormat = "json";
        switch($this->responseFormat){
            case "json" :
                 $response = $this->response->withJson($this->getTotalContext()); break;
            case "xml":
                $response = $this->response->getBody()->write(generaXML($this->getTotalContext())); break;
            case "twig":
                $response = $this->twig->render($this->response, $this->getTwigPage(), $this->getTotalContext()); break;
            default:
                $response = $this->response->getBody()->write($this->getTotalContext()); break;
                break;
        }
        return $response;
    }
    
    private function getTotalContext(){
        if($this->request->getAttribute("csrf_result") == "FAILED") return["csrf_result" => "FAILED"];
        if($this->responseFormat == "json" && intval($this->request->getParsedBody()["OP_FROM_AJAX"]) == 1){
            unset($this->args["is_admin"], $this->args["is_superadmin"], $this->args["is_supersuperadmin"]);
            return array_merge($this->args,$this->saveInDb());
        }
        if(in_array($this->responseFormat, ["json","xml","twig"]))
        {
            return array_merge($this->args, $this->getContext());
        }

        return $this->getContext();
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
}