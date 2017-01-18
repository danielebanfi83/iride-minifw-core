<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 14/01/17
 * Time: 12:34
 */

namespace IrideWeb\Controllers;


use IrideWeb\Core\IWController;

class IWNoAccess extends IWController
{
    public function getResponseFormat(){
        return "string";
    }

    /**
     * @return string|array
     */
    public function getContext()
    {
        return "You don't have the permission to see that page. Click <a href='/'>HERE</a> to come back to the home page.";
    }
}