<?php

interface IList extends Iterator
{
    function add($item);
    function remove($item);
    function removeAt($index);
    function count ();
}

class ArrayList implements IList
{
    protected $position = 0;
    protected $array;

    public function __construct()
    {
        $this->array = array();
    }

    // IList methods
    public function add($item)
    {
        array_push($this->array, $item);
    }
    public function remove($item)
    {}
    public function removeAt($index)
    {
        array_splice($this->array, $index, 1);
    }
    public function count()
    {
        return count ($this->array);
    }

    // Iterator methods
    public function current()
    {
        return $this->array[$this->position];
    }
    public function key()
    {
        return $this->position;
    }
    public function next()
    {
        $this->position++;
    }
    public function rewind()
    {
        $this->position = 0;
    }
    public function valid() 
    {
        return isset($this->array[$this->position]);
    }
}