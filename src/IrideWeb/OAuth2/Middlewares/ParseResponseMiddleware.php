<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 03/01/18
 * Time: 13.34
 */

namespace IrideWeb\Oauth2\Middlewares;

use IrideWeb\OAuth2\Base\IWRestResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class ParseResponseMiddleware extends BaseMiddleware
{

	public function __invoke(Request $request, Response $response, callable $next)
	{
		/**
		 * @var $resp Response
		 */
		$resp = $next($request, $response);
		$code = $resp->getStatusCode();
		$body = json_decode($resp->getBody()->__toString(), true);

		if(array_key_exists("success", $body) && array_key_exists("message", $body) && array_key_exists("data", $body)){
			return $response->withJson($body, $body["code"]);
		}
		$ct = $resp->getHeader("Content-Type");
		if(is_array($ct))
			$ct = $ct[0];

		if( stripos($ct, 'application/json') === false )
			return $resp;
		
		$res = new IWRestResponse($code);
		if (!$res->isSuccessful()) {
			$res->setMessage($body["error"] . ": " . $body["error_description"]);
			unset($body["error"]);
			unset($body["error_description"]);
		}
		$res->setData(count($body)>0 ? $body : null);

		return $response->withJson($res->getResponse(), $code);
	}

}