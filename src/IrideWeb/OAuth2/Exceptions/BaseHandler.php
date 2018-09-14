<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 29/12/17
 * Time: 9.14
 */

namespace IrideWeb\OAuth2\Exceptions;


use Slim\Container;

class BaseHandler
{

	/**
	 * @var $container Container
	 */
	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	public function __get($property)
	{
		if ($this->container->{$property}) {
			return $this->container->{$property};
		}

		return null;
	}
}