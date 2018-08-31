<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 29/12/17
 * Time: 11.50
 */

namespace IrideWeb\Oauth2\Middlewares;



use Interop\Container\Exception\ContainerException;
use \Exception;
use IrideWeb\OAuth2\Base\IWRestResponse;
use Slim\Http\Request;
use Slim\Http\Response;

class OAuthInitMiddleware extends BaseMiddleware
{

	public function __invoke(Request $request, Response $response, callable $next)
	{
		try {
			if ($this->container->get("exceptions") != null) {
				/**
				 * @var $handler \IWApi\Exceptions\ErrorHandler
				 * @var $exc Exception
				 */
				$exc = $this->container->get("exceptions");
				$handler = $this->container->get('errorHandler');
				return $handler($request, $response, $exc);
			}
		} catch (ContainerException $e) {
			return $handler($request, $response, $e);
		}

		return $next($request, $response);

	}

}