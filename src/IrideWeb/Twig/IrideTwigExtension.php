<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 01/06/16
 * Time: 12:59
 */

namespace IrideWeb\Twig;

use IrideWeb\Core\Date;
use Slim\Views\TwigExtension;
use Twig_SimpleFunction;

class IrideTwigExtension extends TwigExtension
{
    protected $environment;

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return "iride_extensions";
    }
    
    public function getFunctions()
    {
        $func = parent::getFunctions();
        $func_iride = [
            new Twig_SimpleFunction("getmicrotime",function(){ return getmicrotime(); }),
            new Twig_SimpleFunction("t", function($word){ return t($word); }),
            new Twig_SimpleFunction("toEuro", function($number, $decimals = 2, $se_zero_stringa_vuota = false){ return toEuro($number, $decimals, $se_zero_stringa_vuota); }),
            new Twig_SimpleFunction("date_it", function($data){ return Date::it($data); }),
            new Twig_SimpleFunction("getCsrfForm", [$this, "getCsrfForm"],["is_safe" => ["all"], "needs_environment" => true]),
            new Twig_SimpleFunction("path",[$this, "path"])
        ];
        
        return array_merge($func, $func_iride);
    }
    
    public function getCsrfForm(\Twig_Environment $twig,$csrfNameKey,$csrfName,$csrfValueKey,$csrfValue){
        return $twig->render("Csrf/csrf.html.twig",[
            "csrfNameKey" => $csrfNameKey,
            "csrfName" => $csrfName,
            "csrfValueKey" => $csrfValueKey,
            "csrfValue" => $csrfValue
        ]);
    }

    public function path($route){
        return $this->environment == "dev" ? "/dev.php".$route : $route;
    }

    /**
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param mixed $environment
     * @return IrideTwigExtension
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }
}