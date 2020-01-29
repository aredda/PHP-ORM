<?php

class Table extends ArrayList
{
    public $class;

    // Queues
    public $insertQueue, $deleteQueue, $updateQueue;

    function __construct($class)
    {
        parent::__construct();

        $this->class = $class;
        $this->clearQueues ();
    }

    // Verifiying object's type
    private function compareType($object)
    {
        return is_a($object, $this->class);
    }
    
    // Resetting queues
    public function clearQueues ()
    {
        $this->insertQueue = $this->deleteQueue = $this->updateQueue = [];
    }

    // Overriding methods
    public function add($item)
    {
        if(!$this->compareType($item))
            throw new Exception("Expected {" . $this->class . "} instance");

        // Add the item to the array normally
        parent::add($item);
        // Add it to the insertion queue as well
        array_push ($this->insertQueue, $item);
    }
    
    public function remove($item)
    {
        // If what's sent isn't an object, but rather the identifier of the object
        // Try to find an object with such identifier value
        if (!is_a ($item, $this->class))
            if ($this->find ($item) == null)
                throw new Exception ("Couldn't find any '{$this->class}' with such identifier '{$item}'");

        $item = $this->find ($item);

        if(!$this->compareType($item))
            throw new Exception ("Expected {" . $this->class . "} instance");
        
        // Remove item from the table's list
        parent::remove($item);
        // Add the deleted item to the queue
        array_push ($this->deleteQueue, $item);
    }

    // Update method
    public function update($item)
    {
        if(!$this->compareType($item))
            throw new Exception("Expected {" . $this->class . "} instance");

        // Get the primary property
        $pk = SQLConverter::get_primary_property($this->class);

        $original = $this->find ($pk->getValue($item));

        if ($original == null)
            throw new Exception ("Can't find original instance when updating");

        $original = $item;

        // add it to the update queue
        array_push ($this->updateQueue, $item);
    }

    // Find method
    public function find($id)
    {
        // Get the primary property for this class
        $pk = SQLConverter::get_primary_property($this->class);

        foreach ($this as $record)
            if (strcmp($pk->getValue($record), $id) == 0)
                return $record;

        return null;
    }

    // Getting items by index
    public function get($index)
    {
        if (count($this->array) <= $index)
            throw new Exception("Index is out of range");

        return $this->array[$index];
    }
}