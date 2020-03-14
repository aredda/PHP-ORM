<?php

$root = $_SERVER["DOCUMENT_ROOT"] . "/PHP-ORM";

include "$root/utilities/collections/list.php";
include "$root/dao/table.php";
include "$root/dao/database.php";
include "$root/utilities/sql_converter.php";

class TestDatabase extends Database
{
    function setup()
    {
        // Specify the classes to map
        $this->map (Country::class, Band::class);

        // Create the database if not existed
        if (!$this->is_created())
        {
            $result = $this->create();

            if (!$result)
                die ('Failed to create the database!');

            header ('refresh: 0;');
        }  
        // If the database is created
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
    $db = new TestDatabase(new mysqli("localhost", "root", ""));

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