<?php
/**
 * Created by PhpStorm.
 * User: thepasto
 * Date: 27/12/17
 * Time: 14.41
 */

namespace IrideWeb\OAuth2\Auth;

use OAuth2\Storage;
use PDO;
use PDOException;
use Slim\Container;
use IWUser;
use InvalidArgumentException;
use OAuth2\Storage\Pdo as PdoStorage;

class IWStorage extends Storage\Pdo
{

	protected $client_id;
	
	protected $db_parameters;
	
	protected $oauth2_parameters;
	/**
	 * IWStorage constructor.
	 * @param $container Container
     * @param $params array
	 * @throws InvalidArgumentException
	 * @throws \Interop\Container\Exception\ContainerException
	 */
	public function __construct($container, $params)
	{
	    $this->db_parameters = $params["db_parameters"]; 
	    $this->oauth2_parameters = $params["oauth2"];
		$conn = null;
		try {

			$conn = $this->getConnection();

		} catch (PDOException $e) {

			if ($e->getCode() == 1049) {
				$conn = $this->createDatabase();
			} else
				throw new PDOException($e, 500);
		}

		parent::__construct($conn);

		$this->client_id = $this->oauth2_parameters["client_id"];
		if ($this->client_id == "")
			throw new \InvalidArgumentException(t("Client Id non impostato"), 401);

		$secret = $this->oauth2_parameters["client_secret"];

		if ($secret == "")
			throw new InvalidArgumentException(t("utente non abilitato"), 403);

		$client = $this->getClientDetails($this->client_id);

		if (!$client) {
			$sql = 'INSERT INTO oauth_clients (client_id, client_secret, scope, redirect_uri) VALUES (?, ?, ?, ?)';
			$conn->prepare($sql)->execute([$this->client_id, $secret, null, null]);
		}

	}

	/**
	 * @return PDO
	 */
	protected function getConnection()
	{

		$conn = new PDO("mysql:host=" . $this->db_parameters["dbhost"] . ";dbname=slim_oauth2", $this->db_parameters['dbuser'], $this->db_parameters['dbpwd']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	}

	protected function createDatabase()
	{
		$conn = new PDO("mysql:host=" . $this->db_parameters['dbhost'], $this->db_parameters['dbuser'], $this->db_parameters['dbpwd']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "CREATE DATABASE slim_oauth2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
		$conn->exec($sql);

		$conn = $this->getConnection();

		$storage = new PdoStorage($conn);
		foreach (explode(';', $storage->getBuildSql()) as $statement) {
			$conn->exec($statement);
		}

		return $conn;
	}

	/**
	 * @return mixed
	 */
	public function getClientId()
	{
		return $this->client_id;
	}

}