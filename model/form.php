<?php
/**
 * Kill me now.
 *
 * Will in theory make a form based on a model=
 */
class model_form
{
	private $model;
	private $extra_models = array();
	private $form_data;
	private $form_output = array();
	/**
	 * Current names and fields.
	 */
	private $c_name;
	private $c_field;
	
	public function __construct( $model, $action, $id = -1, $custom = 0 )
	{
		include( SITE_PATH . DIRSEP . "res" . DIRSEP . "form.php" );
		$this->model = $model;
		$this->form_output = array(
			"title" => $model->nice_name,
			"name" => $model->name,
			"method" => "POST",
			"action" => $action,
		);
		
		if( is_array( $custom ))
		{
			$this->layout = $custom;
		}
		else
		{
			$this->layout = $model->default_form();
		}
		
		$this->populate();
	}
	
	public function output()
	{
		return $this->form_output;
	}

	/**
	 * Recursive function to process form layout arrays.
	 */
	private function traverse_layout( $v, $k = "", $parent = "noparent" )
	{
		$m = $this->model;
		
		# If we are an array, recurse into and operate on each node.
		if( is_array( $v ) )
		{
			foreach( $v as $n => $node )
			{
				# recursive bit
				$return[ $nn ] = $this->traverse_layout( $node, $n, $k );
			}
		}
		# If we are a node, do the thing
		else
		{
			$t = 0;
			
			if( $v == "title" )
			{
				$v = $k;
				$t = 1;
			}
			
			$v = explode( ".", $v );

			# Discern which table/field it is based on whether it is blah.blah or just blah
			$table = count( $v ) > 1 ? $v[ 0 ] : $m->primary_table;
			$name = count( $v ) > 1 ? $v[ 1 ] : $v[ 0 ];
			$tablename = count( $v ) > 1 ? $table . "." . $name : $name;
			$field = $m->definition[ "tables" ][ $table ][ $name ];
			$field[ "form_title" ] = $t;

			# because PHP is a retarded piece of shit and converts . in GET/POST to _ FFS.
			$php_broken_name =  str_replace( ".", "_", $tablename );
			
			
			if( strlen( $this->new_values[ $php_broken_name ] ) > 0 )
			{
				$field[ "value" ] = $this->new_values[ $php_broken_name ];
			}
			else
			{
				$field[ "value" ] = $m->values[ $tablename ];
			}	
					
			$this->form_output[ "fieldsets" ][ $parent ][ "fields" ][ $name ] = $this->element_get( $tablename, $field );
			$this->form_output[ "fieldsets" ][ $parent ][ "title" ] = $parent;
		}
		
		return $return;
	}
	
	/**
	 * Populates the form with data
	 */
	public function populate( $data = 0 )
	{
		$m = $this->model;
		$l = $this->layout;
		if( is_array( $data ) )
		{
			$this->new_values = $data;
		}
		$this->traverse_layout( $l );
	}
	
	/**
	 * Makes form go through and save all the models based on current data.
	 */
	public function save_all()
	{
		$this->model->set_fields_to_save( $this->layout );
		$this->model->save();

		foreach( $this->extra_models as $extra_model )
		{			
			$extra_model->set_fields_to_save( $this->layout );
			$extra_model->save();
		}
	}
	
	
	/**
	 * Decides which type of form element to get the string of, and calls the relevant function. Lots of CASE
	 */
	private function element_get( $name, $field )
	{
		$this->c_name = $name;
		$this->c_field = $field;
		switch( $field[ "type" ] )
		{
			case "varchar":
				if( isset( $field[ "choices" ] ))
					return $this->select_field();
				return $this->char_field();
				break;
			case "int":
				if( isset( $field[ "primary_key" ] ) || isset( $field[ "link_key" ] ) )
				{
					return $this->hidden_field();
				}
				else if( isset( $field[ "foreign_key" ] ))
				{
					if( $this->c_field[ "field_type" ] == "search" )
					{
						return $this->ajaj_search_field();
					}
					else
					{
						$foreign = MODEL::create( $field[ "foreign_key" ] );
						$this->c_field[ "choices" ] = $foreign->get_list();
						return $this->select_field();
					}
				}
				else
				{
					return $this->number_field();
				}
				break;
			case "tinyint":
				if( $field[ "boolean" ] == 1 )
					return $this->tick_field();
				return $this->number_field();
				break;
			case "smallint":
				return $this->number_field();
				break;
			case "datetime":
				return $this->datetime_field();
				break;
			case "decimal":
				return $this->number_field();
				break;
			case "float":
				return $this->number_field();
				break;
			case "text":
				return $this->text_field();
				break;
			default:
				return "UNKNOWN FIELD TYPE\n";
				break;
		}
	}
	
	private function get_foreign_keys()
	{
		print_r( $this->c_field );
		print_r( $this->c_name );
	}
	
	/* Field html generators */
	private function hidden_field()
	{
		return sprintf( RES_FORM_FIELD_HIDDEN,
			$this->c_name,
			$this->c_field[ "value" ] );
	}
	private function char_field()
	{
		return sprintf( RES_FORM_FIELD_CHAR,
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "value" ],
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_CLASS_TITLE : null );
	}
	private function number_field()
	{
		return sprintf( RES_FORM_FIELD_NUMBER,
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "value" ],
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_CLASS_TITLE : null );
	}
	private function datetime_field()
	{
		return sprintf( RES_FORM_FIELD_DATETIME,
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "value" ] );
	}
	private function text_field()
	{	
		return sprintf( RES_FORM_FIELD_TEXT,
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "value" ],
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_CLASS_TITLE : null );
	}
	private function password_field()
	{
		return sprintf( RES_FORM_FIELD_PASSWORD,
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_CLASS_TITLE : null );
	}
	private function tick_field()
	{
		return sprintf( RES_FORM_FIELD_TICK,
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "value" ] == 1 ? RES_FORM_FIELD_TICK_CHECKED : RES_FORM_FIELD_TICK_UNCHECKED,
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_CLASS_TITLE : null );
	}
	private function select_field( $use_keys = 0 )
	{
		$options = null;
		if( is_array( $this->c_field[ "choices" ] ))
		{
			$options[] = RES_FORM_FIELD_SELECT_DEF;
			foreach( $this->c_field[ "choices" ] as $k => $v )
			{
				# It is hard to see what is going on here. Basically we are first selecting
				# whether we're using keys, and putting the key in if that is there, then,
				# for each of those cases, we're putting "selected" based on whether the
				# key or value (respectively) equal the value of the field.
				$string = RES_FORM_FIELD_SELECT_1;
				if( $use_keys )
				{
					$string .= sprintf( RES_FORM_FIELD_SELECT_2, $k );
					if( $k == $this->c_field[ "value" ] )
					{
						$string .= RES_FORM_FIELD_SELECT_SEL;
					}					
				}
				else if( $v == $this->c_field[ "value" ] )
				{
					$string .= RES_FORM_FIELD_SELECT_SEL;
				}
				$string .= sprintf( RES_FORM_FIELD_SELECT_3, $v );
				
				$options[] = $string;
			}
		}
		else
		{
			$options[] = RES_FORM_FIELD_SELECT_NO;;
		}
		return sprintf( RES_FORM_FIELD_SELECT,
			$this->c_name,
			$this->c_field[ "title" ],
			implode( "\n", $options ),
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_CLASS_TITLE : null );
	}
	private function ajaj_search_field()
	{
		return sprintf( RES_FORM_FIELD_AJAJ,		
			$this->c_name,
			$this->c_field[ "title" ],
			$this->c_field[ "foreign_key" ],
			$this->c_field[ "value" ],
			$this->c_field[ "string" ],
			$this->c_field[ "form_title" ] == 1 ? RES_FORM_FIELD_AJAJ_CLASS_TITLE : null,
			$this->c_field[ "store" ] );
	}
}
?>