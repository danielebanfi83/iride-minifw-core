<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 17/01/17
 * Time: 16:41
 */

namespace IrideWeb\Database;


interface IWUsersInterface
{
    public function getId();

    public function getActive();

    public function getUsername();

    public function getLang();

    public function getAdmin();

    public function getSuperadmin();

    public function getSuperSuperadmin();
}