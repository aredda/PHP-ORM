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
        $this->map (Country::class, Band::class, Person::class);

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
    $db = new TestDatabase(new mysqli("localhost", "root", "123"));

    ?>
    <table width=50%>
        <thead>
            <th>Band's name</th>
            <th>Band's genre</th>
            <th>Band's origin</th>
        </thead>
        <tbody>
            <?php
                foreach ($db[Band::class] as $band)
                {
                    echo "<tr>";
                    echo "<td>" . $band->name . "</td>";
                    echo "<td>" . $band->genre . "</td>";
                    echo "<td>" . $band->country->name . "</td>";
                    echo "</tr>";
                }
            ?>
        </tbody>
    </table>
    <?php
    // Group bands by their country
    foreach ($db[Country::class] as $country)
    {
        echo "<p><u>$country->name</u></p>";
        
        foreach ($country->bands as $band)
            echo "<p>$band->name<p>";
    }

    echo "<hr>";

    $band = $db[Band::class]->get(0);
    $band->name = "Green Day";
    $band->genre = "Punk";

    $db[Band::class]->update($band);
    $db->refresh();
}
catch(Exception $e)
{
    echo "<b style='color: red'>" . $e->getMessage() . "</b>";
}