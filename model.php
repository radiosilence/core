<?php
/**
 * Brapp
 */
abstract class model
{
	# Database
	private static $sdb;
	# Array of loaded classes (to avoid double-include)
	private static $loaded_classes = array();
	
	public $definition;
	public $values;
	public $model_name;
	public $nice_name;
	public $primary_table;
	public $primary_key;
	public $id;
	public $fields_to_save = array();
	public $db;

	abstract public function __construct();	
	abstract public function default_form();
	/**
	 * The define function is where all we need to know about the model
	 * is defined. This is like metadata and allows the model class to
	 * seamlessly interact with the database and "know" the model.
	 * Things that are pretty much necessary include:
	 *	* name - the model's name
	 *	* nice_name - a nice version (capitalised, etc) of name
	 * 	* primary_table - the main table that the model is based
	 *		around
	 *	* primary_key - The field that holds the primary key.
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
	public function __set( $member, $value )
	{
		$this->values[ $member ] = $value;
	}

	/**
	 * Get overloader.
	 */	
	public function __get( $member )
	{
		return $this->values[ $member ];
	}

	/**
	 * Sets the static MODEL::$sdb.
	 */
	public static function set_db( $db )
	{
		self::$sdb = $db;
	}
	
	/**
	 * Function to save the model to the db.
	 */
	public function save()
	{
		$t = $this;
		if(DEBUG) FB::send( $this, "Saving model" );
		$db = self::$sdb;
		
		# Check to see if id exists..
		$sth = $db->prepare( "
			SELECT 	" . $t->primary_key . "
			FROM	" . $t->primary_table . "
			WHERE	" . $t->primary_key . " = :id
			LIMIT	1
		" );
		
		$binds = array( 
			":id"	=> $t->id
		);
		
		$sth->execute( $binds );

		# If more than 0 rows, we're updating.
		$updating = $sth->rowCount() > 0 ? 1 : 0;
		
		if( $updating )
		{
			foreach( $t->definition[ "tables" ] as $table_name => $table )
			{
				# Update query
				$sql = "UPDATE " . $table_name . " SET ";

				foreach( $table as $field => $date )
				{
					$fields[]	= $field . " = :" . $field;
					$binds[ ":" . $field ]	= $t->values[ $field ];
				}
				
				$sql .= implode( ", ", $fields );
				$sql .= " LIMIT 1";
				
				$sth = $db->prepare( $sql );
				$sth->execute( $binds );
						
			}
		}
		else
		{
			foreach( $t->definition[ "tables" ] as $table_name => $table )
			{
				# Update query
				$sql = "INSERT INTO " . $table_name . " VALUES ( ";

				foreach( $table as $field => $date )
				{
					$fields[]		= ":" . $field;
					$binds[ ":" . $field ]	= $t->values[ $field ] ? $t->values[ $field ] : "NULL";
				}
				
				$sql .= implode( ", ", $fields );
				$sql .= ")";
				
				$sth = $db->prepare( $sql );
				$sth->execute( $binds );
			}

		}		

	}
	
	/**
	 * Load the model from the db.
	 */
	public function load( $id )
	{
		# Shortcuts
		$t = $this;
		$db = $this->db;
		# Cycle through the fields, pulling them into a flat array $fields
		foreach( $t->definition[ "tables" ] as $table_name => $table )
		{
			foreach( $table as $field_name => $field )
			{
				# Making fieldnames in the format blah.blah or blah if it
				# is part of the primary table.
				$fields[] = $table_name
					. "." . $field_name
					. " as "
					. (
						$table_name != $t->primary_table ?
						$table_name . "." :
						null
					)
					. $field_name . "";
			}
		}
		
		# Get the data for this id we're loading
		if( is_array( $joins ) )
		{
			foreach( $t->definition[ "joins" ] as $join )
			{
				$b = explode( ".", $join[ 1 ] );
				$joins .= "LEFT JOIN " . $b[ 0 ] . "
					ON " . $join[ 0 ] . " = " . $join[ 1 ] . "\n";
			}
		}
		
		$binds = array(
			":id"	=> $id
		);
		
		$sth = $db->prepare( "
			SELECT 	" . implode( ", ", $fields ) . "
			FROM	" . $t->primary_table . "
			" . $joins . "
			WHERE " . $t->primary_key . " = :id
			LIMIT 1
		" );
		
		$sth->execute( $binds );
		$t->values 	= $sth->fetch( PDO::FETCH_ASSOC );
		$t->id 		= $id;
	}
	
	public function set_fields_to_save( $input )
	{
		if( is_array( $input ))
		{
			foreach( $input as $node )
			{
				$this->set_fields_to_save( $node );
			}
		}
		else
		{
			$inputs = explode( ".", $input );
			$table = count( $inputs ) == 2 ? $inputs[ 0 ] : $this->primary_table;
			$field = count( $inputs ) == 2 ? $inputs[ 1 ] : $inputs[ 0 ];
			if( is_array( $this->definition[ "tables" ][ $table ][ $field ] ))
			{
				$this->fields_to_save[] = $input;
			}
		}
	}
	
	/**
	 * Get the sql for a model.
	 */
	public function schema()
	{
		
	}
	
	/* Table definitions */
	
	protected function table( $name, $data )
	{
		$this->definition[ "tables" ][ $name ] = $data;
	}
	
	protected function join( $a, $b )
	{
		$this->definition[ "joins" ][] = array( $a, $b );
	}
	
	/* Field definitions */
	
	protected function primary_key()
	{
		return array(
			"type" => "int",
			"arg" => 11,
			"primary_key" => 1,
			"auto_increment" => 1,
		);
	}
	
	protected function foreign_key( $title, $model, $type, $args = array() )
	{
		$return = array(
			"type" => "int",
			"title" => $title,
			"arg" => 11,
			"foreign_key" => $model,
			"field_type" => $type,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;
	}
	
	/**
	 * A one to one link on a key. Key will be the same as present table's.
	 * Kind of stupid for new systems, designed to work with existing schema.
	 */
	protected function link_key( $field )
	{
		return array(
			"type" => "int",
			"title" => $title,
			"link_key" => $field,
			"arg" => isset( $extra[ "length" ] ) ? $extra[ "length" ] : 11,
		);
	}
	protected function char_field( $title, $args = array() )
	{
		$return = array(
			"type" => "varchar",
			"title" => $title,
			"arg" => isset( $extra[ "length" ] ) ? $extra[ "length" ] : 255,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;
	}
	
	protected function text_field( $title, $args = array() )
	{
		$return = array(
			"type" => "text",
			"title" => $title,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		return $return;
	}
	
	protected function integer_field( $title, $args = array() )
	{
		$return = array(
			"type" => "int",
			"title" => $title,
			"val_class" => "numeric",
			"arg" => isset( $args[ "length" ] ) ? $args[ "length" ] : 11,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;		
	}
	
	protected function tinyinteger_field( $title, $args = array() )
	{
		$return = array(
			"type" => "tinyint",
			"title" => $title,
			"arg" => isset( $args[ "length" ] ) ? $args[ "length" ] : 4,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;	
		
	}
	
	protected function smallinteger_field( $title, $args = array() )
	{
		$return = array(
			"type" => "smallint",
			"title" => $title,
			"arg" => isset( $extra[ "length" ] ) ? $extra[ "length" ] : 6,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;	
	}
	
	protected function boolean_field( $title, $args = array() )
	{
		return array(
			"type" => "tinyint",
			"title" => $title,
			"arg" => 1,
			"boolean" => 1,
		);
	}
	
	protected function decimal_field( $title, $args = array() )
	{
		$return = array(
			"type" => "decimal",
			"title" => $title,
			"arg" => ( isset( $extra[ "precision" ] ) ? $extra[ "precision" ] : 30 ) . "," 
				. ( isset( $extra[ "scale" ] ) ? $extra[ "scale" ] : 2 ),
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;	
	}
	
	protected function datetime_field( $title, $args = array() )
	{
		$return = array(
			"type" => "datetime",
			"title" => $title,
		);
		
		foreach( $args as $a => $v )
		{
			$return[ $a ] = $v;
		}
		
		return $return;	
	}
}
?>