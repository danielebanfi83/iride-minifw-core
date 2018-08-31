<?php
/**
 * Created by PhpStorm.
 * User: Daniele
 * Date: 30/08/18
 * Time: 16:50
 */

namespace IrideWeb\Core;

use Slim\Http\Response;
use IrideWeb\Database\IWDb;

abstract class IWOauth2Controller
{
    protected $parameters, $args;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var IWDb
     */
    protected $iwdb;
    
    public static function factory($object){
        if($object == "") return false;
        if(!class_exists($object)) return false;

        /**
         * @var $object IWOauth2Controller
         */
        $object = new $object();

        if(!$object instanceof IWOauth2Controller) return false;

        return $object;
    }
    
    public function run(){
        $this->response->withStatus(400)
            ->withHeader('Content-type', "application/json;charset=utf-8")
            ->write(json_encode($this->getData()));
    }
    
    abstract public function getData();

    /**
     * @return mixed
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param mixed $parameters
     * @return IWOauth2Controller
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
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
     * @return IWOauth2Controller
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
     * @return IWOauth2Controller
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return IWDb
     */
    public function getIwdb()
    {
        return $this->iwdb;
    }

    /**
     * @param IWDb $iwdb
     * @return IWOauth2Controller
     */
    public function setIwdb($iwdb)
    {
        $this->iwdb = $iwdb;
        return $this;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     * @return IWOauth2Controller
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }
}