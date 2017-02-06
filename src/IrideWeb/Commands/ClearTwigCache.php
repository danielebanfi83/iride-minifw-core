<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 06/06/16
 * Time: 11:30
 */

namespace IrideWeb\Commands;


use IrideWeb\Core\IWCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

class ClearTwigCache extends IWCommand
{

    public function execute()
    {
        $dir = __DIR__."/../../../../../../templates/cache";
        try{
            $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($dir);
            echo "Twig Cache Cleared\n";
        }
        catch (UnexpectedValueException $e){
            echo "Failed to open dir: No such file or directory\n";
        }

    }
}