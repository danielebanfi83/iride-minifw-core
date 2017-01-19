<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 18/01/17
 * Time: 18:04
 */

namespace IrideWeb\Core;


use Interop\Container\ContainerInterface;
use IrideWeb\Twig\IrideTwigExtension;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use SlimSession\Helper;

class IWKernel
{
    protected $parameters;
    /**
     * @var App
     */
    protected $app;

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $settings;

    private $environment;

    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    public function setDependencies(){
        $env = $this->environment;
        $this->container['view'] = function ($container) use ($env) {
            $settings = $env == "prod" ? ["cache" => __DIR__."/../../../../../../templates/cache"] : [];
            $view = new Twig(
                __DIR__."/../../../../../../templates",$settings);
            $view->addExtension(new TwigExtension(
                $container['router'],
                $container['request']->getUri()
            ));
            $iride_twig = new IrideTwigExtension(
                $container["router"],
                $container["request"]->getUri()
            );
            $iride_twig->setEnvironment($env);
            $view->addExtension($iride_twig);

            return $view;
        };
    }

    /**
     * Application Middleware
     */
    public function setMiddleware(){
        $this->app->add(new \Slim\Middleware\Session([
            'name' => 'iwsession',
            'autorefresh' => true,
            'lifetime' => '8 hour',
            "csrf" => true
        ]));
        // Register with container
        $this->container["helper"] = function (){
            return new Helper();
        };

        $this->container['csrf'] = function () {
            $guard = new Guard();
            $guard->setFailureCallable(function ($request, $response, $next) {
                $request = $request->withAttribute("csrf_result", 'FAILED');

                return $next($request, $response);
            });
            return $guard;
        };

        // Register middleware for all routes
        // If you are implementing per-route checks you must not add this
        $this->app->add($this->container->get('csrf'));
    }

    public function setRoutes(){
        $routes = \Spyc::YAMLLoad( __DIR__."/../../../../../../config/routes.yml");

        foreach ($routes["routes"] as $route) {
            $role = $route["role"];
            $obj = array_key_exists("obj", $route) ? $route["obj"] : "";
            $method = $route["method"];
            $parameters = $this->parameters;
            $this->app->$method($route["path"], function ($request, $response, $args) use ($role, $obj, $parameters){
                $csrf = [
                    "csrfNameKey" => $this->csrf->getTokenNameKey(),
                    "csrfValueKey" => $this->csrf->getTokenValueKey(),
                    "csrfName" => $request->getAttribute($this->csrf->getTokenNameKey()),
                    "csrfValue" => $request->getAttribute($this->csrf->getTokenValueKey())
                ];
                $args = array_merge($args, $csrf);
                IWGlobal::set("config", $parameters);
                $obj = IWController::factory($obj);
                $obj->setSession($this->helper)
                    ->setParameters($parameters)
                    ->setArgs($args);

                if(!$obj->checkPermission($role)) {
                    $obj = $obj->noAccessFactory();
                    $obj->setSession($this->helper)
                        ->setParameters($parameters)
                        ->setArgs($args);
                }


                $obj->setTwig($this->view)
                    ->setRequest($request)
                    ->setResponse($response)
                    ->setRole($role);

                return $obj->run();
            });
        }
    }

    public function getSettings(){
        return [
            'settings' => [
                'displayErrorDetails' => $this->environment == "dev", // false in production, true in testing
            ],
        ];
    }

    public function dispatch($environment){
        $this->environment = $environment;
        $this->app = new App($this->getSettings());
        $this->container = $this->app->getContainer();
        $this->setDependencies();
        $this->setMiddleware();
        $this->setRoutes();
        $this->app->run();
    }

    /**
     * @return IWKernel
     */
    public static function factory(){
        $parameters = \Spyc::YAMLLoad(__DIR__."/../../../../../../config/config.yml");

        if(!array_key_exists("factory", $parameters)) return new IWKernel($parameters);

        if(!array_key_exists("kernel",$parameters["factory"])) return new IWKernel($parameters);

        $altKernel = $parameters["factory"]["kernel"];
        $altKernel = new $altKernel($parameters);
        if(!($altKernel instanceof IWKernel)) return new IWKernel($parameters);

        return $altKernel;
    }
}