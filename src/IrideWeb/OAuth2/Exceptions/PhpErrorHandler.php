<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 29/12/17
 * Time: 9.26
 */

namespace IrideWeb\Oauth2\Exceptions;


use IrideWeb\OAuth2\Base\IWRestResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class PhpErrorHandler extends BaseHandler
{

	/**
	 * @param $request Request
	 * @param $response Response
	 * @param $exception \Exception
	 * @return mixed
	 */
	public function __invoke($request, $response, $exception) {
		$res = new IWRestResponse(505, $exception->getMessage());
		return $response->withJson($res->getResponse(),500);
	}

}