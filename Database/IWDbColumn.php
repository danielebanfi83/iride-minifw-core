<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 30/01/16
 * Time: 17:05
 */

namespace IrideWeb\Database;


class IWDbColumn
{
    protected $name;
    protected $type;
    protected $size;
    protected $default;
    protected $comment;
    /**
     * @var array
     */
    protected $foreign_key;

    public function __construct($name)
    {
        $this->name = $name;
        $this->default = "";
        $this->foreign_key = [];
        return $this;
    }

    public function setDefaultNULL(){
        $this->default = "NULL";
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return IWDbColumn
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return IWDbColumn
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setTypeVarchar(){
        $this->type = "varchar";

        return $this;
    }

    public function setTypeText(){
        $this->type = "text";

        return $this;
    }

    public function setTypeLongText(){
        $this->type = "longtext";

        return $this;
    }

    public function setTypeDecimal(){
        $this->type = "decimal";

        return $this;
    }

    public function setTypeInteger(){
        $this->type = "int";

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     * @return IWDbColumn
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     * @return IWDbColumn
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     * @return IWDbColumn
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return array
     */
    public function getForeignKey()
    {
        return $this->foreign_key;
    }

    /**
     * @param $table
     * @param $onDelete
     * @param $onUpdate
     * @return IWDbColumn
     */
    public function setForeignKey($table,$onDelete,$onUpdate="no action")
    {
        $this->foreign_key = ["table" => $table,"on_update"  => $onUpdate,"on_delete" => $onDelete];

        return $this;
    }

    public function haveForeignKey(){
        if(empty($this->foreign_key)) return false;

        return true;
    }
}