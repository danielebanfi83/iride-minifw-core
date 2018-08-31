<?php
/**
 * Created by PhpStorm.
 * User: Daniele
 * Date: 30/08/18
 * Time: 16:02
 */
namespace IrideWeb\Oauth2;

use \IrideWeb\Core\IWKernel;
use IrideWeb\OAuth2\Base\IWRestResponse;
use IrideWeb\OAuth2\Controllers\ApiController;
use IrideWeb\Database\IWDb;
use IrideWeb\Core\IWGlobal;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\AuthorizationCode;
use Chadicus\Slim\OAuth2\Routes\Authorize;
use Chadicus\Slim\OAuth2\Routes\Token;
use Chadicus\Slim\OAuth2\Routes\ReceiveCode;
use IrideWeb\Oauth2\Exceptions\ErrorHandler;
use IrideWeb\Oauth2\Exceptions\NotFoundHandler;
use IrideWeb\Oauth2\Exceptions\NotAllowedHandler;
use IrideWeb\Oauth2\Exceptions\PhpErrorHandler;
use Chadicus\Slim\OAuth2\Middleware\Authorization;

class IWOauth2 extends IWKernel
{
    public function setDependencies()
    {
        $db_params = $this->parameters["db_parameters"];
        $this->iwdb = new IWDb($db_params["dbhost"],$db_params["dbuser"],$db_params["dbpwd"]);
        $this->iwdb->setDb($db_params["dbname"]);
        $this->iwdb->DBOpen();
        IWGlobal::setDbInstance($this->iwdb);
        $iwdb = $this->iwdb;
        $this->container["db"] = function () use ($iwdb){
            return $iwdb;
        };

        /**
         * @param $c \Slim\Container
         * @return \Monolog\Logger
         * @throws \Interop\Container\Exception\ContainerException
         */
        $this->container['logger'] = function ($c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
            return $logger;
        };

        $this->container['errorHandler'] = function ($c) {
            return new ErrorHandler($c);
        };

        $this->container['notFoundHandler'] =function ($c) {
            return new NotFoundHandler($c);
        };

        $this->container['notAllowedHandler'] = function ($c) {
            return new NotAllowedHandler($c);
        };

        if (version_compare(phpversion(), '7.0.0', '>')) {
            $this->container['phpErrorHandler'] = function ($c) {
                return new PhpErrorHandler($c);
            };
        }

        /**
         * @param $c \Slim\Container
         * @return Views\PhpRenderer
         * @throws \Interop\Container\Exception\ContainerException
         */
        $this->container['view'] = function ($c) {
            $settings = $c->get('settings')['renderer'];
            $view = new \Slim\Views\PhpRenderer($settings["template_path"]);
            return $view;
        };

        $this->container["client_id"] = function ($c){
            return $this->parameters["oauth2"]["client_id"];
        };

        $this->container["exceptions"] = function ($c) {
            return null;
        };

        try {
            $storage = new \IrideWeb\OAuth2\Auth\IWStorage($this->container, $this->parameters);
        } catch (\Exception $e){
            $this->container["exceptions"] = function ($c) use ($e) {
                return $e;
            };
        }

        /**
         * @param $c \Slim\Container
         * @return \OAuth2\Server
         * @throws \Interop\Container\Exception\ContainerException
         */
        if($storage) {
            $this->container["authserver"] = function ($c) use ($storage) {

                $server = new \OAuth2\Server(
                    $storage,
                    [
                        'access_lifetime' => 3600,
                    ],
                    [
                        new ClientCredentials($storage),
                        new AuthorizationCode($storage),
                    ]
                );
                return $server;
            };

            unset($this->container["client_id"]);
            $this->container["client_id"] = function ($c) use ($storage){
                return $storage->getClientId();
            };
        }

        $this->container["ApiController"] = function ($c) {
            return new \IrideWeb\OAuth2\Controllers\ApiController($c);
        };
    }

    public function setMiddleware()
    {
        $this->app->add(new \IrideWeb\Oauth2\Middlewares\OAuthInitMiddleware($this->container));
        $this->app->add(new \IrideWeb\Oauth2\Middlewares\ParseResponseMiddleware($this->container));
    }

    public function setRoutes()
    {
        if ($this->container->get("exceptions"))
            return;

        $auth = $this->container->get("authserver");
        $view = $this->container->get("view");

        $authmdwr = new Authorization($auth, $this->container);

        $this->app->get("/api/clientsecret", "ApiController:getClientSecret")->setName("clientsecret");

        $this->app->map(['GET', 'POST'], "/api".Authorize::ROUTE, new Authorize($auth, $view))->setName('authorize');
        $this->app->post("/api".Token::ROUTE, new Token($auth))->setName('token');
        $this->app->map(['GET', 'POST'], "/api".ReceiveCode::ROUTE, new ReceiveCode($view))->setName('receive-code');

        try {
            $routes = \Spyc::YAMLLoad(__DIR__ . "/../../../../../../config/routes.yml");

            foreach ($routes["routes"] as $route) {
                $obj = array_key_exists("obj", $route) ? $route["obj"] : "";
                $method = $route["method"];
                $parameters = $this->parameters;
                $iwdb = $this->iwdb;
                $this->app->$method($route["path"], function ($request, $response, $args) use ($obj, $parameters, $iwdb) {
                    IWGlobal::set("config", $parameters);
                    $obj = \IrideWeb\Core\IWOauth2Controller::factory($obj);

                    $obj->setParameters($parameters)
                        ->setIwdb($iwdb)
                        ->setArgs($args)
                        ->setRequest($request)
                        ->setResponse($response);

                    return $obj->run();
                })->add($authmdwr);
            }
        }
        catch (\Throwable $exception){
            $iwres = (new IWRestResponse(500, $exception->getMessage()))->getResponse();

            return (new Response())->withJson(
                $iwres,500
            );
        }
    }
}