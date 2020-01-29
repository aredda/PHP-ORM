<?php

include $_SERVER["DOCUMENT_ROOT"] . "/AredaORM/collection/list.php";
include $_SERVER["DOCUMENT_ROOT"] . "/AredaORM/table.php";
include $_SERVER["DOCUMENT_ROOT"] . "/AredaORM/database.php";
include $_SERVER["DOCUMENT_ROOT"] . "/AredaORM/sql_converter.php";

class TestDatabase extends Database
{
    function setup()
    {
        // Specify the classes to map
        $this->map (Country::class, Band::class);

        // Create the database if not existed
        if (!$this->is_created())
            if (!$this->create())
                echo "It is not created";
        
        if ($this->is_created())
            $this->refresh();
    }
}

class Country
{
    /**
     * @type=INT
     * @auto
     * @primary
     */
    public $id;
    /**
     * @type=VARCHAR(32)
     */
    public $name;
    /**
     * @hasMany=Band
     */
    public $bands;
}

class Band
{
    /**
     * @primary
     * @auto
     * @type=INT
     */
    public $id;
    /**
     * @type=VARCHAR(16)
     */
    public $name;
    /**
     * @type=VARCHAR(16)
     */
    public $genre;
    /**
     * @type=INT
     * @references=Country
    */
    public $country;
}

class Person
{
    /**
     * @primary
     * @auto
     * @type=INT
     */
    public $id;
    /**
     * @type=VARCHAR(16)
     */
    public $name;
    /**
     * @type=INT
     * @references=Band
     */
    public $band;
}

try
{
    $db = new TestDatabase(new mysqli("localhost", "areda", "123"));
    
    $band = new Band ();
    $band->id = 50;
    $band->name = "Blur";
    $band->genre = "Britpop";
    $band->country = 2;

    $db[Band::class]->add ($band);
    $db->refresh ();
    
    // Group bands by their country
    foreach ($db[Country::class] as $country)
    {
        echo "<p><u>$country->name</u></p>";
        
        foreach ($country->bands as $band)
            echo "<small>$band->name</small><br>";
    }
}
catch(Exception $e)
{
    echo "<b style='color: red'>" . $e->getMessage() . "</b>";
}