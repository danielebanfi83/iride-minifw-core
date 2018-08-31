<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 29/12/17
 * Time: 9.16
 */

namespace IrideWeb\Oauth2\Exceptions;

use IrideWeb\OAuth2\Base\IWRestResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class NotFoundHandler extends BaseHandler
{

	/**
	 * @param $request Request
	 * @param $response Response
	 * @return mixed
	 */
	public function __invoke($request, $response) {
		$res = new IWRestResponse(404, t("not found"));
		return $response->withJson($res->getResponse(),404);
	}

}