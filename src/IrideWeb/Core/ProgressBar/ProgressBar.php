<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 31/07/13
 * Time: 15:22
 */
namespace IrideWeb\Core\ProgressBar;

class ProgressBar {
    /**
     * @var ProgressBarInterface
     */
    protected $progress;

    public function __construct(){
        /*if (php_sapi_name() == "cli")*/ $this->progress = new ProgressBarCLI();//TODO da decidere come implementare a video
        /*else $this->progress = new ProgressBarHtml();*/
    }

    public function initializeFile(){
        $this->progress->init();
    }

    public function read(){
        return $this->progress->read();
    }

    public function write($value){
        $this->progress->advance($value, 100);
    }

    public function writePerc($i,$tot){
        $this->progress->advance($i, $tot);
    }

    public function delete(){
        $this->progress->stop();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->progress->getLabel();
    }

    /**
     * @param string $label
     * @return ProgressBar
     */
    public function setLabel($label)
    {
        $this->progress->setLabel($label);

        return $this;
    }
}