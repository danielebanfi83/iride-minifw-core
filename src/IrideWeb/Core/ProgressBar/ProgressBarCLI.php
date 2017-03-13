<?php

/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 25/11/16
 * Time: 14:42
 */
namespace IrideWeb\Core\ProgressBar;

use ProgressBar\Manager;

class ProgressBarCLI implements ProgressBarInterface
{
    /**
     * @var Manager
     */
    protected $progress;

    protected $label;

    public function init()
    {
        $this->progress = new Manager(0, 0);
    }

    public function advance($i, $tot)
    {
        if($this->progress->getRegistry()->getValue("max") == 0)
            $this->progress->getRegistry()->setValue("max", $tot);

        $this->progress->update($i);
    }

    public function read(){
        return $this->progress->getRegistry()->getValue("current");
    }

    public function stop()
    {
        return;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     * @return ProgressBarCLI
     */
    public function setLabel($label)
    {
        $this->label = $label;
        echo $this->label."\n";

        return $this;
    }
}