<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 29/12/17
 * Time: 12.38
 */

namespace IrideWeb\OAuth2\Base;


use OAuth2\Response;

class IWRestResponse extends Response
{

	protected $message = "", $data = null;

	/**
	 * IWRestResponse constructor.
	 * @param int $code
	 * @param string $message
	 * @param null $data
	 */
	public function __construct($code = 200, $message = "", $data = null)
	{
		$this->setStatusCode($code);
		parent::__construct([], $code, []);
		if(!$this->isSuccessful()) {
			$this->setError($code, $this->getStatusText(), $message);
			$this->setMessage($this->getStatusText() . ": " . $message);
		}
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @param string $message
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	public function getResponse()
	{
		return [
			"success" => $this->isSuccessful(),
			"message" => $this->getMessage(),
			"code" => $this->getStatusCode(),
			"data" => $this->getData()
		];
	}

}