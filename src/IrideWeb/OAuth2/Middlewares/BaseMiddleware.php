<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 27/12/17
 * Time: 10.38
 */

namespace IrideWeb\OAuth2\Middlewares;
use Slim\Container;

class BaseMiddleware
{
	/**
	 * @var $container Container
	 */
	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

}