<?php

class SQLConverter
{
    public static function create_table(string $class)
    {
        $reflection = new ReflectionClass($class);

        $table = "CREATE TABLE $class (";
        $columns = [];

        // Construction of normal columns
        foreach ($reflection->getProperties() as $property)
            if ( SQLConverter::get_constraint($property, "@hasMany") == null )
                array_push($columns, SQLConverter::construct_column($property));

        $script = $table . implode(", ", $columns);

        // Constructing referencing columns
        $foreign = [];
        foreach (SQLConverter::get_foreign_keys($class) as $column => $referenced)
        {
            $referenced_key = SQLConverter::get_primary_property($referenced);

            if (is_null($referenced_key))
                throw new Exception("<u>$referenced</u> has no primary key.");

            // Get the name of the referenced key
            $key_name = SQLConverter::get_constraint($referenced_key, "@name");
            $key_name = is_null($key_name) ? $referenced_key->getName() : $key_name;

            array_push($foreign, "FOREIGN KEY($column) REFERENCES $referenced($key_name)");
        }

        if (count($foreign) > 0)
            $script .= ", ";

        return $script . implode(", ", $foreign) . ")";
    }

    /**
     * Gets all annotations that concerns a certain property
     */
    public static function get_constraints(ReflectionProperty $property)
    {
        $constraints = [];
        
        $content = explode("*", $property->getDocComment());

        foreach($content as $line)
            if (strpos($line, "@") !== FALSE)
            {
                // Split line to key and a value
                $pair = explode("=", $line);
                // Retrieve the key (constraint)
                $constraint = count($pair) == 0 ? trim($line) : trim($pair[0]);
                // Push the pair to the constraints array
                $constraints[$constraint] = count($pair) > 1 ? trim($pair[1]) : true;
            }

        return $constraints;
    }

    // Gets all the foreign keys within a class
    public static function get_foreign_keys(string $className)
    {
        $keys = [];

        $reflection = new ReflectionClass($className);

        // Search for the columns who present foreign keys
        foreach ($reflection->getProperties() as $property)
            foreach (SQLConverter::get_constraints($property) as $constraint => $value)
                if (strcmp($constraint, "@references") == 0)
                {
                    $name = SQLConverter::get_constraint($property, "@name");
                    $name = is_null($name) ? $property->getName() : $name;

                    $keys[$name] = $value;
                }

        return $keys;
    }

    // Get all containers of a record's children
    // Returns all properties that have 'hasMany' annotation
    public static function get_children_containers(string $class)
    {
        $reflecter = new ReflectionClass($class);
        $containers = [];

        foreach ($reflecter->getProperties() as $property)
            if ( SQLConverter::get_constraint($property, "@hasMany") != null )
                array_push ($containers, $property);
            
        return $containers;
    }

    // To produce the query of a column with most of its constraints
    public static function construct_column(ReflectionProperty $property)
    {
        $column = "";
        $constraints = SQLConverter::get_constraints($property);

        $mainConstraints = ["@name", "@type"];
        $otherConstraints = [
            "@primary" => "PRIMARY KEY",
            "@auto" => "AUTO_INCREMENT",
            "@unique" => "UNIQUE"
        ];

        // Search for primary constraints
        foreach ($mainConstraints as $main)
        {
            // If the column doesn't have a name annotation
            // The name of the column should be the name of the property
            if (strcmp($main, "@name") == 0)
                if (!isset($constraints[$main]))
                    $constraints[$main] = $property->name;

            if (isset($constraints[$main]))
                $column .= $constraints[$main] . " ";

            unset($constraints[$main]);
        }

        // Search for other constraints
        foreach ($constraints as $key => $value)
            if (isset($otherConstraints[$key]))
                $column .= $otherConstraints[$key] . " ";

        return trim($column);
    }

    // Search for the primary key(s)
    public static function get_primary_property(string $class)
    {   
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getProperties() as $property)
            if (array_key_exists("@primary", SQLConverter::get_constraints($property)))
                return $property;

        return null;
    }

    // Search for property by the value of its constraint
    public static function search_property($className, $annotation, $value)
    {
        $reflection = new ReflectionClass($className);

        foreach ($reflection->getProperties() as $property)
            foreach (SQLConverter::get_constraints($property) as $constraint => $constraint_value)
                if (strcmp($annotation, $constraint) == 0)
                    if (strcmp($value, $constraint_value) == 0)
                        return $property;
                    
        return null;
    }

    // Get the value of the constraint
    public static function get_constraint(ReflectionProperty $property, $constraint)
    {
        $constraints = SQLConverter::get_constraints($property);

        return !array_key_exists($constraint, $constraints) ? null : $constraints[$constraint]; 
    }

    // Execute queries and return the result as an array
    public static function select_query(string $query, $connection) : array
    {
        // Empty array
        $data = [];
        // Query result
        $result = $connection->query($query);
        // Add to the empty array
        while ($row = $result->fetch_assoc())
            array_push($data, $row);
        // Return
        return $data;
    }

    // Execute commands
    public static function execute_command(string $command, $connection)
    {
        // Execute the command
        $result = $connection->multi_query($command);
        // Return the result of the execution
        return $result;
    } 

    // Insert command
    public static function get_insert($instance)
    {
        $class = get_class($instance);
        $reflecter = new ReflectionClass($class);

        $query = "INSERT INTO $class VALUES (";
        $values = [];

        foreach ($reflecter->getProperties() as $property)
        {
            $annotations = SQLConverter::get_constraints($property);

            if (array_key_exists("@auto", $annotations))
                array_push ($values, "null");

            if (array_key_exists("@auto", $annotations) || array_key_exists("@hasMany", $annotations))
                continue;

            $value = $property->getValue($instance);

            if ($value == null)
                continue;

            if (array_key_exists("@references", $annotations))
            {
                if (is_object ($value))
                {
                    // Get the primary key of the object
                    $primaryProperty = SQLConverter::get_primary_property($annotations["@references"]);
                    // Get the id value of that object
                    $value = $primaryProperty->getValue ($value); 
                }
            }

            array_push ($values, "'$value'");
        }

        return $query . implode(", ", $values) . ")";
    }

    // Delete command
    public static function get_delete($instance)
    {
        $class = get_class($instance);
        $reflecter = new ReflectionClass($class);

        $primaryProperty = SQLConverter::get_primary_property($class);

        $name = $primaryProperty->getName ();
        $value = $primaryProperty->getValue ($instance);

        if (array_key_exists("@name", SQLConverter::get_constraints($primaryProperty)))
            $name = SQLConverter::get_constraints($primaryProperty) ["@name"];

        return "DELETE FROM $class WHERE $name = '$value'";
    }

    // Update command
    public static function get_update($instance)
    {
        $class = get_class($instance);
        $reflecter = new ReflectionClass($class);

        $primaryProperty = SQLConverter::get_primary_property($class);
        $pkName = $primaryProperty->getName (); 

        $query = "UPDATE $class SET ";
        $pairs = [];

        foreach ($reflecter->getProperties() as $property)
        {
            $annotations = SQLConverter::get_constraints($property);

            $columnName = array_key_exists("@name", $annotations) ? $annotations["@name"] : $property->getName();

            if (array_key_exists("@primary", $annotations))
                $pkName = $columnName;

            if (array_key_exists("@hasMany", $annotations) || array_key_exists("@primary", $annotations))
                continue;

            $value = $property->getValue($instance);

            if (array_key_exists("@references", $annotations))
            {
                $pk = SQLConverter::get_primary_property($annotations["@references"]);

                $value = $pk->getValue ($value);
            }

            array_push ($pairs, $columnName . "='$value'");
        } 

        $query .= implode(', ', $pairs);

        return $query . " WHERE $pkName='". $primaryProperty->getValue ($instance) ."'";
    }
}