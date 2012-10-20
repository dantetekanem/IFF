<?php

/**
 * IFF - Imagine Form For (For FuelPHP v 1.3)
 *
 * @version 	1.0 alpha
 * @author 		Leonardo Pereira (www.imagineware.com.br)
 */

class IFF 
{
	private $obj 		= null;
	private $fname 		= "";
	private $fid 		= "";
	private $columns	= array();
	private $reserved 	= array('as', 'collection', 'has_blank', 'suffix');
	private $is_field 	= false;
	private $_suffix 	= "_attributes";

	public function __construct($model, $options = array())
	{
		// defaults
		$attrs 			= array(
			'method'	=> is_null($options['method']) ? 'post' : $options['method'],
			'action'	=> is_null($options['action']) ? \Uri::base() : \Uri::create($options['action']),
			'enctype'	=> 'multipart/form-data',
		);
		unset($options['method'], $options['action']);

		$attrs 			= array_merge($attrs, $options);

		$this->obj 		= $model;
		if(is_object($this->obj))
		{
			$this->fname 	= strtolower(get_class($model));
			$this->fid 		= strtolower(get_class($model));
			$this->columns 	= DB::list_columns($this->obj->table());
		}
		else
		{
			$this->fname 	= (string) $model;
			$this->fid 		= (string) $model;
			$this->columns 	= array();
		}

		// form
		echo '<form ', array_to_attr($attrs), '>';

		// default hiddens (for security)
		$this->forge_default_hiddens($attrs);

		// return object
		return $this;
	}

	private function forge_default_hiddens($attrs = array())
	{
		echo html_tag('input', array('type' => 'hidden', 'name' => '_method', 'value' => strtoupper($attrs['method'])));
		echo html_tag('input', array('type' => 'hidden', 'name' => '_csrf_token', 'value' => Security::fetch_token()));
	}

	public function end()
	{
		if($this->is_field)
		{
			unset($this);
			return;
		}

		return '</form>';
	}

	public function input($field, $options = array())
	{
		$type 			= 'default';
		if(isset($options['as']))
			$type 		= $options['as'];

		if( ! method_exists($this, '_'.$type.'_field'))
		{
			throw new Exception("Type field {$type} do not exists.");
		}
		else
		{
			return $this->{"_{$type}_field"}($field, $options);
		}
	}

	public function label($field, $label, $options = array())
	{
		if( ! isset($options['for']) )
			$options['for'] 	= $this->fid.'_'.$field;

		//
		return html_tag('label', $options, $label);
	}

	public function submit($label, $options = array())
	{
		$options['type'] 		= 'submit';
		$options['value']		= $label;
		$options['name']		= ! isset($options['name']) ? $this->fname.'[commit]' : $this->fname.'['.$options['name'].']';
		//
		return html_tag('input', $options);
	}

	/*---------------------------*/
	/** *** *** ALIASES *** *** **--------------------------------------------------------------------------*/
	/*---------------------------*/

	/**
	 * Alias to input :as => select
	 */
	public function select($f, $v = array(), $o = array()) { $o['as'] = 'select'; $o['collection'] = $v; return $this->input($f, $o); }

	/**
	 * Alias to input :as => file
	 */
	public function file($f, $o = array()) { $o['as'] = 'file'; return $this->input($f, $o); }

	/**
	 * Alias to input :as => hidden
	 */
	public function hidden($f, $v, $o = array()) { $o['as'] = 'hidden'; $o['value'] = $v; return $this->input($f, $o); }

	/**
	 * Alias to input :as => checkbox
	 */
	public function check($f, $v, $o = array()) { $o['as'] = 'check'; $o['value'] = $v; return $this->input($f, $o); }

	/**
	 * Alias to input :as => radiobox
	 */
	public function radio($f, $v, $l = '', $o = array()) { $o['as'] = 'radio'; $o['value'] = $v; if($l != "") { $o['label_title'] = $l; } return $this->input($f, $o); }

	/*---------------------------*/
	/** *** *** FIELDS FOR *** ***--------------------------------------------------------------------------*/
	/*---------------------------*/

	public function fields_for($field, $options = array())
	{
		$nobj 					= clone $this;
		$suffix 				= $options['suffix'] != "" ? $options['suffix'] : $this->_suffix;
		//
		$nobj->fname 			= $this->fname.'['.strtolower($field).$suffix.']';
		$nobj->fid 				= $this->fid.'_'.strtolower($field).$suffix;
		$nobj->columns 			= array();
		$nobj->is_field 		= true;
		// build object
		// it has an id?
		if($this->obj->{"{$field}_id"} != "")
		{
			// attach object
			$nobj->obj 			= $this->obj->{$field};
		}
		else
		{
			// create object (extending the original)
			$obj_name 			= get_class($this->obj)."_".ucfirst($field);
			// if not exists we extend it as a simple model
			if( ! class_exists($obj_name) )
			{
				$obj_name 		= 'Model_'.ucfirst($field);
			}
			// if event that model dont exists, we just set usual names and an empty object
			if( ! class_exists($obj_name) )
			{
				$nobj->columns 	= array();
				$nobj->obj 		= null;
			}
			else
			{
				$nobj->obj 		= new $obj_name;
				$nobj->columns 	= DB::list_columns($nobj->obj->table());
			}
		}
		//
		return $nobj;
	}

	/*---------------------------*/
	/** *** TYPE FIELDS FUNCTIONS -------------------------------------------------------------------*/
	/*---------------------------*/

	// default type (select other type)
	private function _default_field($field, $options = array())
	{
		// check input type
		$type 		= count($this->columns) > 0 ? $this->columns[$field]['data_type'] : "string";
		$type_field = "";

		switch($type)
		{
			default: $type_field = "string"; break;
			case "integer": $type_field = "integer"; break;
			case "text": $type_field = "text"; break;
			case "boolean": $type_field = "boolean"; break;
			case "array": $type_field = "select"; break;
			case "object": $type_field = "select"; break;
		}

		return $this->{"_{$type_field}_field"}($field, $options);
	}

	// string type
	private function _string_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		if(isset($this->obj->$field)) $options['value']	= $this->obj->$field;
		//
		return html_tag('input', $this->_clear_reserved($options));
	}

	// text type
	private function _text_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		//
		return html_tag('textarea', $this->_clear_reserved($options), !$this->obj->$field ? '' : $this->obj->$field);
	}

	// file type
	private function _file_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		$options['type']	= 'file';

		return html_tag('input', $this->_clear_reserved($options));
	}

	// select type
	private function _select_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		//
		$results 			= $options['collection'] or array();
		//
		$opts 				= '';

		if(isset($options['has_blank']))
		{
			$opts 			.= '<option value="">'.(is_string($options['has_blank']) ? $options['has_blank'] : '').'</option>'."\n";
		}
		// mount options
		if(count($results) > 0)
		{
			foreach($results as $v => $l)
			{
				$opts 		.= '<option';
				// selected?
				if(isset($this->obj->$field))
				{
					if($this->obj->$field == $v) $opts .= ' selected="selected"';
				}
				// value
				$opts 		.= ' value="'.$v.'"';
				// label
				$opts 		.= '>'.$l;
				// close
				$opts 		.= '</option>'."\n";
			}
		}
		//
		return html_tag('select', $this->_clear_reserved($options), $opts);
	}

	private function _check_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		$options['type']	= "checkbox";

		// label options
		if( ! isset($options['label_options']) )
		{
			$label_options 	= array();
		}
		else
		{
			$label_options 	= $options['label_options'];
			unset($options['label_options']);
		}

		// label title
		if( isset($options['label_title']) and $options['label_title'] != '' )
		{
			$label_title 	= $options['label_title'];
		}
		else
		{
			$label_title 	= '';
		}

		// if is a collection
		if(isset($options['collection']) and is_array($options['collection']) and count($options['collection']) > 0)
		{
			// not fully supported yet, make a recursively single input
			$checks = array();
			$os 	= $options;
			unset($os['collection']);
			$pos 	= 0;

			foreach($options['collection'] as $v => $l) { 
				$os['id'] 			= $options['id'].'_'.$pos; 
				$os['label_options']['for'] = $os['id'];
				$os['name']			= $options['name'].'[]';
				$os['label_title'] 	= $l;
				$os['value']		= $v;
				$checks[] = $this->input($field, $os); 
				++$pos;
			}

			return join("\n", $checks);
		}
		else
		{
			// check if values is already checked
			if(isset($options['value']) and $options['value'] != "")
			{
				if(isset($this->obj->$field) and $this->obj->$field != "")
				{
					if($options['value'] == $this->obj->$field)
					{
						$options['checked'] = "checked";
					}	
				}
			}

			return html_tag('label', $label_options, html_tag('input', $this->_clear_reserved($options)) . $label_title);
		}
	}

	private function _radio_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		$options['type']	= "radio";

		// label options
		if( ! isset($options['label_options']) )
		{
			$label_options 	= array();
		}
		else
		{
			$label_options 	= $options['label_options'];
			unset($options['label_options']);
		}

		// label title
		if( isset($options['label_title']) and $options['label_title'] != '' )
		{
			$label_title 	= $options['label_title'];
		}
		else
		{
			$label_title 	= '';
		}

		#die(Debug::dump($field, $options));
		// if is a collection
		if(isset($options['collection']) and is_array($options['collection']) and count($options['collection']) > 0)
		{
			// not fully supported yet, make a recursively single input
			$radios = array();
			$os 	= $options;
			unset($os['collection']);
			$pos 	= 0;

			foreach($options['collection'] as $v => $l) { 
				$os['id'] 			= $options['id'].'_'.$pos; 
				$os['label_options']['for'] = $os['id'];
				$os['label_title'] 	= $l;
				$os['value']		= $v;
				$radios[] = $this->input($field, $os); 
				++$pos;
			}

			return join("\n", $radios);
		}
		else
		{
			// check if values is already checked
			if(isset($options['value']) and $options['value'] != "")
			{
				if(isset($this->obj->$field) and $this->obj->$field != "")
				{
					if($options['value'] == $this->obj->$field)
					{
						$options['checked'] = "checked";
					}	
				}
			}

			return html_tag('label', $label_options, html_tag('input', $this->_clear_reserved($options)) . $label_title);
		}
	}

	private function _hidden_field($field, $options = array())
	{
		$options 			= $this->_set_id_and_name($field, $options);
		$options['type']	= "hidden";

		// if already has a value
		if(isset($this->obj->$field) and $this->obj->$field != "")
		{
			$options['value'] = $this->obj->$field;
		} 
		//
		return html_tag('input', $this->_clear_reserved($options));
	}


	/*---------------------------*/
	/** *** *** PRIVATES *** *** ------------------------------------------------------------------------*/
	/*---------------------------*/


	// set id and name (its used to all fields)
	private function _set_id_and_name($field, $options, $is_multiple = false)
	{
		// if id already exists, we dont put our default
		if( ! isset($options['id']) )
		{
			$options['id'] 		= $this->fid.'_'.strtolower($field);
		}
		// if name already exists, we dont put our default (but is highly recommended to not set it)
		if( ! isset($options['name']) )
		{
			$options['name'] 	= $this->fname.'['.strtolower($field).']';

			// if it is a multiple field, we put [] at the end
			if($is_multiple)
			{
				$options['name'] .= '[]';
			}
		}

		return $options;
	}

	// remove reserved names from attributes
	private function _clear_reserved($options = array())
	{
		foreach($this->reserved as $r)
		{
			if( isset($options[$r]) ) unset($options[$r]);
		}

		return $options;
	}

}


/**
 * Helper Function (Object to Collection)
 * Useful to return an Model object to an array of return
 */
if( ! function_exists('object_to_collection'))
{
	function object_to_collection($object, $value, $label)
	{
		$return 	= array();
		foreach($object as $obj)
		{
			$return[$obj->{$value}] = $obj->{$label};
		}

		return $return;
	}
}