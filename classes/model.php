<?php
/**
 * Brapp
 */
abstract class Model {
    /**
     * Model's database.
     */
    protected $db;
    
    /**
     * ID Of model currently being worked with.
     */
    public $_id;
    
    /**
     * Fields we're saving.
     */
    protected $_fields_to_save = array();
    
    
    /**
     * Whether we've loaded from db.
     */
    protected $_loaded = false;

    /**
     * Where model metadata is stored.
     */
    public $_definition;
    
    /**
     * Simple, flat array of fields and values.
     */
    public $_values;
    
    /**
     * Name of the model.
     */
    public $_model_name;
    
    /**
     * Capitalised or otherwise "nice" name for the model.
     */
    public $_nice_name;
    
    /**
     * Model's primary table.
     */
    public $_primary_table;
    
    /**
     * The primary key for the model.
     */
    public $_primary_key;

    /**
     * All models must have a constructor.
     */
     
    public function __construct($db, $id = false) {
        $this->db = $db;
        $this->define();
        
        if($id) {
            try {
                $this->load($id);
                $this->_loaded = true;
            } catch(Exception $e) {
                $this->_loaded = false;
                trigger_error("Tried to load object that was not in database.", E_USER_ERROR);
            }        
        }
    }
    
    /**
     * Default form layout as an array.
     */
    abstract public function default_form();
    
    /**
     * The define function is where all we need to know about the model
     * is defined. This is like metadata and allows the model class to
     * seamlessly interact with the database and "know" the model.
     * Things that are pretty much necessary include:
     * * _model_name - the model's name
     * * _nice_name - a nice version (capitalised, etc) of name
     * * _primary_table - the main table that the model is based
     * around
     * * _primary_key - The field that holds the primary key.
     *
     * Because the system is designed for working with existing schema,
     * models have to be defined on a table by table basis. This although
     * not the most elegant solution, is quite powerful. You use the
     * MODEL::table() method.
     *    
     */
    abstract protected function define();

    /**
     * Set overloader.
     */    
    public function __set($member, $value) {
        $this->_values[$member] = $value;
    }

    /**
     * Get overloader.
     */    
    public function __get($member) {
        return $this->_values[$member];
    }

    /**
     * Sets the static MODEL::$sdb.
     */
    public static function set_db($db) {
        self::$sdb = $db;
    }
    
    /**
     * Function to save the model to the db.
     */
    public function save() {
        $t = $this;
        if(DEBUG) FB::send($this, "Saving model");
        $db = self::$sdb;
        
        # Check to see if id exists..
        $sth = $db->prepare("
            SELECT " . $t->_primary_key . "
            FROM " . $t->_primary_table . "
            WHERE " . $t->_primary_key . " = :id
            LIMIT 1
        ");
        
        $binds = array(
            ":id" => $t->_id
        );
        
        $sth->execute($binds);

        # If more than 0 rows, we're updating.
        $updating = $sth->rowCount() > 0 ? 1 : 0;
        
        if($updating) {
            foreach($t->_definition["tables"] as $table_name => $table) {
                # Update query
                $sql = "UPDATE " . $table_name . " SET ";

                foreach($table as $field => $date) {
                    $fields[] = "`" . $field . "` = :" . $field;
                    $binds[":" . $field] = $t->_values[$field];
                }
                
                $sql .= implode(", ", $fields);
                $sql .= " LIMIT 1";
                
                $sth = $db->prepare($sql);
                $sth->execute($binds);
                        
            }
        }
        else {
            foreach($t->_definition["tables"] as $table_name => $table) {
                # Update query
                $sql = "INSERT INTO " . $table_name . " VALUES (";

                foreach($table as $field => $date) {
                    $fields[] = ":" . $field;
                    $binds[":" . $field] = $t->_values[$field] ? $t->_values[$field] : "NULL";
                }
                
                $sql .= implode(", ", $fields);
                $sql .= ")";
                
                $sth = $db->prepare($sql);
                $sth->execute($binds);
            }

        }        

    }
    
    /**
     * Load the model from the db.
     */
    public function load($id) {
        # Shortcuts
        $t = $this;
        $db = $t->db;
        
        # Cycle through the fields, pulling them into a flat array $fields
        foreach($t->_definition["tables"] as $table_name => $table) {
            foreach($table as $field_name => $field) {
                # Making fieldnames in the format blah.blah or blah if it
                # is part of the primary table.
                $fields[] = "`" . $table_name . "`"
                    . ".`" . $field_name . "`"
                    . " as "
                    . (
                        $table_name != $t->_primary_table ?
                        "`" . $table_name . "`." :
                        null
                      )
                    . "`" . $field_name . "`";
            } }
        
        # Get the data for this id we're loading
        if(is_array($joins)) {
            foreach($t->_definition["joins"] as $join) {
                $b = explode(".", $join[1]);
                $joins .= "LEFT JOIN " . $b[0] . "
                    ON " . $join[0] . " = " . $join[1] . "\n";
            }
        }
        
        $binds = array(
            ":id" => $id
        );
        
        $sth = $db->prepare("
            SELECT " . implode(", ", $fields) . "
            FROM " . $t->_primary_table . "
            " . $joins . "
            WHERE " . $t->_primary_key . " = :id
            LIMIT 1
        ");
        
        $sth->execute($binds);
        
        if($sth->rowCount() < 1) {
            throw new Exception("Instance ID not found in database.");
        }
        
        $t->_values = $sth->fetch(PDO::FETCH_ASSOC);
        $t->_id = $id;
    }
    
    public function set_fields_to_save($input) {
        if(is_array($input)) {
            foreach($input as $node) {
                $this->set_fields_to_save($node);
            }
        } else {
            $inputs = explode(".", $input);
            $table = count($inputs) == 2 ? $inputs[0] : $this->_primary_table;
            $field = count($inputs) == 2 ? $inputs[1] : $inputs[0];
            
            if(is_array($this->_definition["tables"][$table][$field])) {
                $this->_fields_to_save[] = $input;
            }
        }
    }
    
    /**
     * Get the sql for a model.
     */
    public function schema()
     { }
    
    /* Table definitions */
    
    protected function table($name, $data) {
        $this->_definition["tables"][$name] = $data;
    }
    
    protected function join($a, $b) {
        $this->_definition["joins"][] = array($a, $b);
    }
    
    /* Field definitions */
    
    protected function primary_key() {
        return array(
            "type" => "int",
            "arg" => 11,
            "primary_key" => 1,
            "auto_inc" => 1,
       );
    }
    
    protected function foreign_key($title, $model, $type, $args = array()) {
        $return = array(
            "type" => "int",
            "title" => $title,
            "arg" => 11,
            "foreign_key" => $model,
            "field_type" => $type,
       );
        
        foreach($args as $a => $v) {
            $return[$a] = $v;
        }
        
        return $return;
    }
    
    /**
     * A one to one link on a key. Key will be the same as present table's.
     * Kind of stupid for new systems, designed to work with existing schema.
     */
    protected function link_key($field) {
        return array(
            "type" => "int",
            "title" => $title,
            "link_key" => $field,
            "arg" => isset($extra["length"]) ? $extra["length"] : 11,
       );
    }
    protected function char_field($title, $args = array()) {
        $return = array(
            "type" => "varchar",
            "title" => $title,
            "arg" => isset($extra["length"]) ? $extra["length"] : 255,
       );
        
        foreach($args as $a => $v)
        {
            $return[$a] = $v;
        }
        
        return $return;
    }
    
    protected function text_field($title, $args = array()) {
        $return = array(
            "type" => "text",
            "title" => $title,
       );
        
        foreach($args as $a => $v)
        {
            $return[$a] = $v;
        }
        return $return;
    }
    
    protected function integer_field($title, $args = array()) {
        $return = array(
            "type" => "int",
            "title" => $title,
            "val_class" => "numeric",
            "arg" => isset($args["length"]) ? $args["length"] : 11,
       );
        
        foreach($args as $a => $v)
        {
            $return[$a] = $v;
        }
        
        return $return;        
    }
    
    protected function tinyinteger_field($title, $args = array()) {
        $return = array(
            "type" => "tinyint",
            "title" => $title,
            "arg" => isset($args["length"]) ? $args["length"] : 4,
       );
        
        foreach($args as $a => $v)
        {
            $return[$a] = $v;
        }
        
        return $return;    
        
    }
    
    protected function smallinteger_field($title, $args = array()) {
        $return = array(
            "type" => "smallint",
            "title" => $title,
            "arg" => isset($extra["length"]) ? $extra["length"] : 6,
       );
        
        foreach($args as $a => $v)
        {
            $return[$a] = $v;
        }
        
        return $return;    
    }
    
    protected function boolean_field($title, $args = array()) {
        return array(
            "type" => "tinyint",
            "title" => $title,
            "arg" => 1,
            "boolean" => 1,
       );
    }
    
    protected function float_field($title, $args = array()) {
        $return = array(
            "type" => "decimal",
            "title" => $title,
            "arg" => (isset($extra["precision"]) ? $extra["precision"] : 30) . "," 
                . (isset($extra["scale"]) ? $extra["scale"] : 2),
       );
        
        foreach($args as $a => $v) {
            $return[$a] = $v;
        }
        
        return $return;    
    }
    
    protected function datetime_field($title, $args = array()) {
        $return = array(
            "type" => "datetime",
            "title" => $title,
       );
        
        foreach($args as $a => $v) {
            $return[$a] = $v;
        }
        
        return $return;    
    }
}
?>