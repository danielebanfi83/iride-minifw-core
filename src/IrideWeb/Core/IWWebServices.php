<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 13/03/17
 * Time: 10:16
 */

namespace IrideWeb\Core;


abstract class IWWebServices extends IWController
{
    protected $token_not_valid = false;

    /**
     * @return string|array
     */
    abstract public function wsContext();

    public function getResponseFormat()
    {
        $token = $this->request->getMethod() == "GET" ? $this->args["token"] : $this->request->getParsedBody()["token"];
        $this->no_csrf_protection = true;

        if($token != $this->parameters["webservice"]["token"]){
            $this->token_not_valid = true;
            return "";
        }
        return "json";
    }

    public function getContext()
    {
        if($this->token_not_valid)
        {
            $this->response = $this->response->withStatus(401,"Unauthorized request, token not valid");
            return "";
        }

        $ret = $this->wsContext();
        if($this->request->getMethod() == "GET") unset($this->args["token"]);
        
        return $ret;
    }
}