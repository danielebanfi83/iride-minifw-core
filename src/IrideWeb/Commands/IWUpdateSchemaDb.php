<?php
/**
 * Created by Iride Staff.
 * User: mattia
 * Date: 02/02/17
 * Time: 17.16
 */

namespace IrideWeb\Commands;


use IrideWeb\Core\IWCommand;
use IrideWeb\Database\DBTable;
use IrideWeb\Database\IWDb;


class IWUpdateSchemaDb extends IWCommand
{

    public function execute()
    {
        $dir_entities = __DIR__."/../../../../../../src/AppModule/Entities";
        $files = elencafiles($dir_entities);
        list($msg, $fk) = $this->rigeneraDatabase($files);
        $msg .= "\n".$this->rigeneraForeignKey($fk);
        echo $msg."\n";
    }

    protected function rigeneraDatabase($files){
        $i=0;
        $foreign_keys = [];
        $entities = [];
        foreach ($files as $file) {
            if (getExtension($file) != "php") continue;
            $class = str_replace(".php", "", $file);
            $classname = "AppModule\\Entities\\" . $class;
            $i++;
            /**
             * @var $entity DBTable
             */
            $entity = new $classname();
            if (!($entity instanceof DBTable)) continue;
            $entity->setColumns();
            $entities[] = $entity;
            $sql = "CREATE TABLE " . $entity->getTable() . " (id int(11) NOT NULL auto_increment,PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            try {
                $ret = $this->getDb()->DBExec($sql);
                $fk = 1;
                foreach ($entity as $column) {
                    $size = strtoupper($column->getType()) == "TEXT" || strtoupper($column->getType()) == "LONGTEXT" ? "" : "(" . $column->getSize() . ")";

                    if (is_numeric($column->getDefault()) || $column->getDefault() == "NULL") $default = $column->getDefault();
                    else $default = $column->getDefault() == "" ? $default = "''" : "'" . $column->getDefault() . "'";

                    $sql = "ALTER TABLE " . $entity->getTable() . " ADD COLUMN " . $column->getName() . " " . strtoupper($column->getType()) . $size . " DEFAULT " . $default . " COMMENT '" . addslashes($column->getComment()) . "'";
                    $this->getDb()->DBExec($sql);

                    if (empty($column->getForeignKey())) continue;

                    $foreign_keys[] = ["fk" => $column->getForeignKey(), "entity" => $entity, "column" => $column->getName(), "n" => $fk];
                    $fk++;
                }
                $entity->insertDefaultRecords();
            }
            catch (\PDOException $e){
                echo $e->getMessage()."\n";
            }
        }
        return array("Rigenera Db: ".$i." tabelle rigenerate", $foreign_keys);
    }

    public function rigeneraForeignKey($foreign_keys){
        //Ora rigenero le foreign key
        $i = 0;
        foreach ($foreign_keys as $ar_fk) {
            /**
             * @var $entity DBTable
             */
            $foreignKey = $ar_fk["fk"];
            $entity = $ar_fk["entity"];
            $colname = $ar_fk["column"];
            $fk = $ar_fk["n"];

            $sql = "ALTER TABLE ".$entity->getTable()." ADD CONSTRAINT `".$entity->getTable()."_ibfk_".$fk."` FOREIGN KEY (`".$colname."`) REFERENCES `".$foreignKey["table"]."` (`id`) ON DELETE ".strtoupper($foreignKey["on_delete"])." ON UPDATE ".strtoupper($foreignKey["on_update"]);
            $this->getDb()->DBExec($sql);
            $i++;
        }

        return "Rigenerate ".$i. " foreign keys";
    }

}