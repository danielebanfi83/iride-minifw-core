<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 18/01/17
 * Time: 18:04
 */

namespace IrideWeb\Core;


use Interop\Container\ContainerInterface;
use IrideWeb\Database\IWDb;
use IrideWeb\Database\IWQuery;
use IrideWeb\Oauth2\IWOauth2;
use IrideWeb\Twig\IrideTwigExtension;
use MultilingualSlim\LanguageMiddleware;
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

    /**
     * @var IWDb
     */
    protected $iwdb;

    protected $settings;

    private $environment;

    public function __construct($parameters)
    {
        $this->parameters = $parameters;
        $this->iwdb = null;
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

        if(!array_key_exists("db_parameters", $this->parameters)) return;

        $db_params = $this->parameters["db_parameters"];
        $this->iwdb = new IWDb($db_params["dbhost"],$db_params["dbuser"],$db_params["dbpwd"]);
        $this->iwdb->setDb($db_params["dbname"]);
        $this->iwdb->DBOpen();
        IWGlobal::setDbInstance($this->iwdb);
        $iwdb = $this->iwdb;
        $this->container["db"] = function () use ($iwdb){
            return $iwdb;
        };

    }

    /**
     * Application Middleware
     */
    public function setMiddleware(){
        //Session management
        $this->app->add(new \Slim\Middleware\Session([
            'name' => 'iwsession',
            'autorefresh' => true,
            'lifetime' => '8 hour',
            "csrf" => true
        ]));
        $this->container["helper"] = function (){
            return new Helper();
        };

        //CSRF Protection
        $this->container['csrf'] = function () {
            $guard = new Guard();
            $guard->setFailureCallable(function ($request, $response, $next) {
                $request = $request->withAttribute("csrf_result", 'FAILED');

                return $next($request, $response);
            });
            return $guard;
        };

        $this->app->add($this->container->get('csrf'));

        $dictionary = $this->parameters["dictionary"];
        $translator = $dictionary["class"];

        /**
         * @var $translator IWTranslator
         */
        $translator = new $translator();
        $translator->setDb($this->iwdb);
        $translator->setConfig($dictionary);
        $this->container["translator"] = $translator;
        $this->app->add(new LanguageMiddleware($translator->getAvailableLanguages(),$translator->getDefaultLanguage(),$this->container));
    }

    public function setRoutes(){
        $routes = \Spyc::YAMLLoad( __DIR__."/../../../../../../config/routes.yml");

        foreach ($routes["routes"] as $route) {
            $role = $route["role"];
            $obj = array_key_exists("obj", $route) ? $route["obj"] : "";
            $method = $route["method"];
            $parameters = $this->parameters;
            $iwdb = $this->iwdb;
            $this->app->$method($route["path"], function ($request, $response, $args) use ($role, $obj, $parameters, $iwdb){
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
                    ->setIwdb($iwdb)
                    ->setArgs($args);

                if(!$obj->checkPermission($role)) {
                    $obj = $obj->noAccessFactory();
                    $obj->setSession($this->helper)
                        ->setParameters($parameters)
                        ->setIwdb($iwdb)
                        ->setArgs($args);
                }


                $this->translator->setLang($this->language);
                $this->view->getEnvironment()->getExtension("iride_extensions")->setTranslator($this->translator);
                $obj->setTwig($this->view)
                    ->setTranslator($this->translator)
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
        $request_uri = str_replace("/dev.php","",$_SERVER["REQUEST_URI"]);
        if(startsWith("/api",$request_uri)) return new \IrideWeb\Oauth2\IWOauth2($parameters);

        if(!array_key_exists("factory", $parameters)) return new IWKernel($parameters);

        if(!array_key_exists("kernel",$parameters["factory"])) return new IWKernel($parameters);

        $altKernel = $parameters["factory"]["kernel"];
        $altKernel = new $altKernel($parameters);
        if(!($altKernel instanceof IWKernel)) return new IWKernel($parameters);

        return $altKernel;
    }
}