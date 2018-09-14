<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 27/12/17
 * Time: 10.19
 */

namespace IrideWeb\OAuth2\Controllers;
use Slim\Container;

class BaseController
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