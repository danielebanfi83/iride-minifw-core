<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 27/12/17
 * Time: 10.18
 */

namespace IrideWeb\OAuth2\Controllers;

use Interop\Container\Exception\ContainerException;
use IrideWeb\OAuth2\Base\IWRestResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class ApiController extends BaseController
{

	/**
	 * @param $request Request
	 * @param $response Response
	 * @return mixed
	 * @throws \Exception
	 */
	public function getClientSecret($request, $response)
	{
		$secret = DBReadFirst("SELECT key_basic_auth FROM datiazienda");
		try {
			$cli = $this->container->get("client_id");
			return $response->withJson([$cli => $secret], 200);
		} catch (ContainerException $e) {
			throw new \Slim\Exception\ContainerException($e);
		}

	}

}