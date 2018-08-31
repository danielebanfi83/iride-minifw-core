<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 29/12/17
 * Time: 9.20
 */

namespace IrideWeb\Oauth2\Exceptions;

use IrideWeb\OAuth2\Base\IWRestResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class NotAllowedHandler extends BaseHandler
{

	/**
	 * @param $request Request
	 * @param $response Response
	 * @param array $methods
	 * @return mixed
	 */
	public function __invoke($request, $response, $methods) {
		$res = new IWRestResponse(405, t("not allowed"));
		return $response->withJson($res->getResponse(),405);
	}

}