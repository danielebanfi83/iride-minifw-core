<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 01/06/16
 * Time: 16:20
 */

namespace IrideWeb\Controllers;


use IrideWeb\Core\IWController;

class IWIndex extends IWController
{

    public function getTwigPage()
    {
        return "index.html.twig";
    }

    /**
     * @return string|array
     */
    public function getContext()
    {
        return [""];
    }
}