<?php

/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 25/11/16
 * Time: 14:42
 */

namespace IrideWeb\Core\ProgressBar;

interface ProgressBarInterface
{
    public function init();

    public function advance($i, $tot);

    public function read();

    public function stop();

    public function setLabel($label);

    public function getLabel();
}