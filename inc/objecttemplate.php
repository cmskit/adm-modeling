<?php
/**
*  cms-kit Object-Generator
* 
*  this Generator is based on PHP Object-Generator (http://phpobjectgenerator.com)
* 
*  Copyright notice
*
*  (c) 2014 Christoph Taubmann (info@cms-kit.org)
*  All rights reserved
*
*  This script is part of cms-kit Framework. 
*  This is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License Version 3 as published by
*  the Free Software Foundation, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/licenses/gpl.html

*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*/

$GLOBALS['cconfiguration'] = array(
	'author' => 'cms-kit Object-Generator ',
	'copyright' => 'MIT-License: Free for personal & commercial use. (http://opensource.org/licenses/mit-license.php) ',
	'link' => 'http://cms-kit.org'
);


class ObjectGenerator
{
	var $str; // php-code
	var $objectName; // Object Name
	var $elementList; // Fields
	var $savePath; // where to save
	
	
	function __construct($projectName, $objectName, $model, $types, $savepath, $version, $get)
	{
		
		// globalize Variables
		$this->projectName 	= $projectName;
		$this->objectName 	= $objectName;
		$this->model 		= $model;
		$this->elementList 	= $model[$objectName];
		$this->treeType 	= $model[$objectName]['ttype'];
		$this->db			= $model[$objectName]['db'];
		$this->manualId 	= ($model[$objectName]['inc']==1);
		$this->types 		= $types;
		$this->savePath 	= $savepath;
		$this->version = $version;
		
		$this->debug = isset($get['debug']);
		$this->nofilter = isset($get['nofilter']);
		
		//$this->manualId 	= ($this->confName::$DB_INCREMENT[$this->db] == 'manual') ? true : false; // manual/auto-increment
		
		$this->defaultFunctionCalls = array();
		
		// Function-calls
		$this->BeginObject();
		
		//$this->CreateConstructor();
		
		if(count($this->defaultFunctionCalls) > 0)
		{
			$this->CreateConstructor();
		}
		
		$this->CreateSingleton();
		
		$this->CreateGetFunction();
		$this->CreateGetListFunction();
		
		// Trees
		if($this->treeType=='Tree')
		{
			$this->CreateGetTreeFunction();
			$this->CreateAddTreeChildFunction();// creates multiple functions
		}
		
		// Graphs
		if ($this->treeType=='Graph')
		{
			$this->CreateGetGraphFunction();
			$this->CreateAddGraphChildFunction();// creates multiple functions
		}
		
		$this->CreateSaveFunction(true);
		$this->CreateSaveNewFunction(true);
		$this->CreateDeleteFunction(true);
		//$this->CreateDeleteListFunction(true);// buggy
		$this->CreateReferencesFunction();
		$this->EndObject();
		
		// save the class
		$path = $this->savePath . 'class.' . strtolower($this->objectName) . '.php';
		
		// reformat code-style according to PSR2
		if (isset($_GET['psr2']))
		{
			$this->str = strtr($this->str, array(
				//"\n\t{" => " {",
				"\n\t\t{" => " {",
				"\n\t\t\t{" => " {",
				"\n\t\t\t\t{" => " {",
				"\n\t\t\t\t\t{" => " {",
				"\n\t\t\t\t\t\t{" => " {",
				"\n\t\t\t\t\t\t\t{" => " {",
				"\t" => "    ",
			));
		}
		
		file_put_contents($path, $this->str);
		
		@chmod($path, 0776);
		
	}
	
	
	function MappingName($objectName1, $objectName2, $add='Map', $lower=true)
	{
		$array = array($objectName1, $objectName2);
		natcasesort($array);
		$str = array_shift($array) . $array[0];
		return  ($lower ? strtolower($str) : $str) . $add;
	}
	
	function CreateComments($description='', $parameterDescriptionArray='', $returnType='')
	{
		
		$this->str .= "/**\n\t* ".$description."\n\t*\n";
		
 		if ($parameterDescriptionArray != '')
 		{
	 		foreach ($parameterDescriptionArray as $parameter)
	 		{
	 			$this->str .= "\t* @param ".$parameter."\n";
	 		}
 		}
 		
 		if ($returnType != '')
 		{
			$this->str .= "\t* @return ".$returnType."\n";
		}
		
	    $this->str .= "\t*/\n";
	}
	
	function CreatePreface()
	{
		$this->str .= "/**\n*\tclass '" . $this->objectName . "' with integrated CRUD methods\n*\t";
		
		// write the object-comment as a description into the file preface
		if (isset($_GET['icomments']) && !empty($this->elementList['comment']))
		{
			$this->str .= "\n*\t" . str_replace("\n", "\n*\t", $this->elementList['comment']) . "\n*\t";
		}
		
		$this->str .= "\n*\t@author " . $GLOBALS['cconfiguration']['author'];
		$this->str .= "\n*\t@version " . $this->version;
		$this->str .= "\n*\t@copyright " . $GLOBALS['cconfiguration']['copyright'];
		$this->str .= "\n*\t@link " . $GLOBALS['cconfiguration']['link'];
		$this->str .= "\n*\t@package " . $this->projectName;
		$this->str .= "\n*/\n";
	}
	
	// -------------------------------------------------------------
	function BeginObject()
	{
		$this->str = "<?php\n";
		$this->str .= $this->CreatePreface();
		
		$this->str .= "\n\nnamespace ".$this->projectName.";";
		//
		$this->str .= "\n\ninclude_once(__DIR__ . '/__database.php');";
		
		//not needed anymore because of autoload
		/*
		// create include of Mapping-Classes
		if(isset($this->elementList['rel']))
		{
			foreach ($this->elementList['rel'] as $key => $attr)
			{
				// include the map-class 
				if ($attr == 's')
				{
					$this->str .= "\ninclude_once(__DIR__ . '/class.".strtolower($this->MappingName($this->objectName, $key)).".php');";
				}
			}
		}*/
		
		
		$this->str .= "\n\nclass ".$this->objectName."\n{\n\t";
		$x = 0;
		$tmp = array();
		$tmp2 = array();
		
		$conf = $this->projectName . '\\Configuration';
		
		$db_type = $conf::$DB_TYPE[$this->db];
		$this->str .="/**\n\t";
		$this->str .="* @const integer DB (Database-Type: $db_type)\n\t";
		$this->str .="*/\n\t";
		$this->str .="const DB = $this->db;";
		$this->str .="\n\t\n\t";
		
		$this->str .="/**\n\t";
		$this->str .="* @const integer INCREMENT\n\t";
		$this->str .="*/\n\t";
		$this->str .="const INCREMENT = ".$this->elementList['inc'].";";
		$this->str .="\n\t\n\t";
		
		$this->str .="/**\n\t";
		$this->str .="* @var object _INSTANCE_\n\t";
		$this->str .="* @access private\n\t";
		$this->str .="*/\n\t";
		$this->str.="static private \$_INSTANCE_ = null;";
		$this->str.="\n\t\n\t";
		
		
		
		foreach ($this->elementList['col'] as $key => $attr)
		{
			$unq = 0;
			$quote = intval($this->types[$attr['type']]['quote']);
			
			// if it is the Table-ID
			if($key=='id')
			{
				//$key = strtolower($this->objectName).'Id';
				$this->types[$attr['type']]['quote'] = 1;
				$unq = 1;
				if($this->manualId) $quote=1;
			}
			
			$tmp[] = "\n\t\t\t'".$key."'=>array('unique'=>".$unq.", 'quote'=>".$quote.")";
			
			$mod = $this->model[$this->objectName]['col'][$key];
			
			// write the Variable
			// create the comment block
			$this->str .= "/**\n\t";
			//$this->str .= (isset($mod['comment']) ? " * " . $mod['comment'] . "\n\t" : '');
			//$this->confName::$DB_TYPE[$this->db]
			if (isset($_GET['icomments']) && !empty($mod['comment']))
			{
				$this->str .= "* " . str_replace("\n", "\n\t* ", $mod['comment']) . "\n\t";
			}
			$this->str .= "* @var ". $this->types[$attr['type']]['php'] . ' $' . stripcslashes($key) . ' (Database: '.$this->types[$attr['type']][$db_type].")\n\t";
			$this->str .= "*/\n\t";
			
			$this->str .= "public $".$key." = ";
			$mod['default'] = urldecode($mod['default']);
			// set a default-value if defined
			if (!empty($mod['default']) && $mod['default'] != '__EMPTY_STRING_')
			{
				//$this->str .= $mod['default'];//"public $".$key." = ";
				
				if (substr($mod['default'], 0, 9) == 'function:')
				{
					$this->str .= "''";
					$this->defaultFunctionCalls[$key] = substr($mod['default'], 9);
				}
				else
				{
					$this->str .= 	is_numeric($mod['default']) 
									? floatval($mod['default']) 
									: "'".trim(urldecode($mod['default']))."'";
				}
			}
			else
			{
				$this->str .= "''";// no default, set empty String
			}
			
			$this->str .=";\n\t";
			// Variable END
			
			$this->str .="\n\t";
		
			$x++;
		}
		
		
		$tmp2[] = "\n\t\t\t'".$this->objectName."' => array('" . implode("','", array_keys($this->elementList['col'])) . "')";
		
		// define siblings + childs
		if(isset($this->elementList['rel']))
		{
			foreach ($this->elementList['rel'] as $key => $attr)
			{
				if ($attr == 's' || $attr == 'c')
				{
					$this->str .="/**\n\t";
					$this->str .="* @var array \$_".strtolower($key)."List List of $key-Objects\n\t";
					$this->str .="* @access private\n\t";
					$this->str .="*/\n\t";
					$this->str.="private \$_".strtolower($key)."List = array();\n\t";
					$this->str.="\n\t";
				}
				
				if ($this->model[$key]['col'])
				{
					$tmp2[] = "\n\t\t\t'".$key."' => array('" . implode("','", array_keys($this->model[$key]['col'])) . "')";
				}
			}
		}
		
		
		$this->str .="\n\t/**\n\t";
		$this->str .="* @var array \$__columns Definitions of Relation-Fields for '".$this->objectName."'\n\t";
		$this->str .="* @access private\n\t";
		$this->str .="*/";
		$this->str .= "\n\tprivate \$__columns = array(" . implode(', ', $tmp2) . " );";
		
	}
	
	
	function CreateSingleton()
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Singleton-Function to define the Object ".$this->objectName." only once",
											array(),
											"object \$".$this->objectName."");
		$this->str .= "\tpublic static function instance ()\n\t{";
		$this->str .= "\n\t\tif (!isset(self::\$_INSTANCE_))\n\t\t{";
		$this->str .= "\n\t\t\tself::\$_INSTANCE_ = new ".$this->objectName."();";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\treturn self::\$_INSTANCE_;";
		$this->str .= "\n\t}";
	}
	
	// -------------------------------------------------------------
	function CreateConstructor()
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Function to predefine the Object ".$this->objectName,
											array(),
											'');
		$this->str .= "\tpublic function __construct()\n\t{";
		
		foreach($this->defaultFunctionCalls as $k => $v)
		{
			$this->str .="\n\t\t\$this->".$k." = ".$v.";";
		}
		
		$this->str .= "\n\t}";
	}
	
	function CreateOutputFilter($el, $obj, $indent, $relObject=false)
	{
		// we have to check for f***ing magic_quotes
		$sls = '';
		$sle = '';
		if (get_magic_quotes_gpc())
		{
			$sls = 'stripslashes(';
			$sle = ')';
		}
		
		$cols = ($relObject) ? $relObject : $el->elementList['col'];
		
		foreach ($cols as $key => $attr)
		{
			if ($this->nofilter)
			{
				if ($attr['type']=='MODEL')
				{
					$this->str .= "\n".$indent."\$".$obj."->".$key." = json_decode(".$sls."\$result->".$key.$sle.", true) ?: array();";
				}
				continue;
			}
			
			if ($key=='id' && $this->manualId)
			{
				$attr['type'] = 'VARCHAR';
			}
			
			if (isset($attr['filter']))
			{
				foreach ($attr['filter'] as $filter => $add)
				{
					$f = "\n".$indent."\$".$obj."->".$key." = filter_var(".$sls."\$result->".$key.$sle.", ".$filter . (isset($add)?', '.$add:'') . ");";
				}
				$this->str .= $f;
			}
			else
			{
			
				switch ($attr['type'])
				{
					case 'MODEL':
						$this->str .= "\n".$indent;
						$this->str .= "\n".$indent."\$".$obj."->".$key." = json_decode(".$sls."\$result->".$key.$sle.", true) ?: array();";
						//$this->str .= "\n".$indent."print_r(\$".$obj."->".$key.");";
						if ($this->debug)
						{
							$this->str .= "\n".$indent."switch(json_last_error())\n".$indent."{";
							$this->str .= "\n".$indent."\tcase JSON_ERROR_DEPTH: trigger_error('JSON-Decode-Error in ".$this->objectName.", id '.$".$obj."->id.': Maximum stack depth exceeded', E_USER_ERROR); break;";
							$this->str .= "\n".$indent."\tcase JSON_ERROR_CTRL_CHAR: trigger_error('JSON-Decode-Error in ".$this->objectName.", id '.$".$obj."->id.': Unexpected control character found', E_USER_ERROR); break;";
							$this->str .= "\n".$indent."\tcase JSON_ERROR_SYNTAX: trigger_error('JSON-Decode-Error in ".$this->objectName.", id '.$".$obj."->id.': Syntax error, malformed JSON', E_USER_ERROR); break;";
							$this->str .= "\n".$indent."}";
						}
						$this->str .= "\n".$indent;
					break;
					
					/* if we need Output-Sanitation... 
					case 'BOOL':
					case 'INTEGER':
					case 'SELINTEGER':
					case 'HIDDENINTEGER':
					case 'TIMESTAMP':
						$this->str .= "\n".$indent."\$".$obj."->".$key." = intval(\$result->".$key.");";
					break;
					case 'FLOAT':
						$this->str .= "\n".$indent."\$".$obj."->".$key." = floatval(\$result->".$key.");";
					break;
					*/
					default:
						$this->str .= "\n".$indent."\$".$obj."->".$key." = ".$sls."\$result->".$key.$sle.";";
					break;
				}// switch end
				
			}
		}
		$this->str .= "\n".$indent;
	}
	
	//
	
	function CreateInputFilter($obj, $indent)
	{
		foreach ($this->elementList['col'] as $key => $attr)
		{
			if ($this->nofilter)
			{
				if ($attr['type']=='MODEL')
				{
						$this->str .= "\n".$indent."\$".$obj."->".$key." = json_encode(\$".$obj."->".$key.");";
						
				}
				continue;
			}
			
			if ($key == 'id' && $this->manualId)
			{
				$attr['type'] = 'VARCHAR';
			}
			
			// see http://www.php.net/manual/de/filter.filters.sanitize.php
			if (isset($attr['filter']))
			{
				foreach ($attr['filter'] as $filter => $add)
				{
					$f = "\n".$indent."\$".$obj."->".$key." = filter_var(\$".$obj."->".$key.", " . $filter . (isset($add)?', '.$add:'') . ");";
				}
				$this->str .= $f;
			}
			else
			{
				switch($attr['type'])
				{
					case 'MODEL':
						
						$this->str .= "\n".$indent;
						
						$this->str .= "\n".$indent."\$db_".$key." = @json_decode(\$result[0]->".$key.", true) ?: array();";
						//$this->str .= "\n".$indent."\$my_".$key." = array('name'=>\$db_".$key."['name'],'fields' => \$this->".$key.");";
						
						// Input-Sanitation for JSON-Encoded Values
						$this->str .= "\n".$indent."foreach (\$this->".$key." as \$k => \$v)\n".$indent."{";
						$this->str .= "\n".$indent."\t// Data-Sanitation for JSON-Encoded Values";
						$this->str .= "\n".$indent."\tswitch (@\$v['type'])\n".$indent."\t{";
						
						$this->str .= "\n".$indent."\t\tcase 'BOOL';";
						$this->str .= "\n".$indent."\t\tcase 'INTEGER';";
						//$this->str .= "\n".$indent."\t\tcase 'SELINTEGER';";
						//$this->str .= "\n".$indent."\t\tcase 'HIDDENINTEGER';";
						$this->str .= "\n".$indent."\t\tcase 'TIMESTAMP';";
						//$this->str .= "\n".$indent."\t\t\t\$".$obj."->".$key."['fields'][\$k]['value'] = intval(\$".$obj."->".$key."['fields'][\$k]['value']);";
						$this->str .= "\n".$indent."\t\tbreak;";
						
						$this->str .= "\n".$indent."\t\tcase 'FLOAT';";
						//$this->str .= "\n".$indent."\t\t\t\$".$obj."->".$key."['fields'][\$k]['value'] = floatval(\$".$obj."->".$key."['fields'][\$k]['value']);";
						$this->str .= "\n".$indent."\t\tbreak;";
						
						//$this->str .= "\n".$indent."\t\tcase 'CRON';";
						//$this->str .= "\n".$indent."\t\tcase 'DATE';";
						//$this->str .= "\n".$indent."\t\tcase 'DATETIME';";
						//$this->str .= "\n".$indent."\t\tcase 'VARCHAR';";
						//$this->str .= "\n".$indent."\t\tcase 'TEXT';";
						$this->str .= "\n".$indent."\t\tdefault:";
						//$this->str .= "\n".$indent."\t\t\t\$".$obj."->".$key."['fields'][\$k]['value'] = filter_var(\$".$obj."->".$key."['fields'][\$k]['value'], FILTER_SANITIZE_STRING);";
						$this->str .= "\n".$indent."\t\tbreak;";
						
						// how should we sanitize these?
						//$this->str .= "\n".$indent."\t\tcase 'TEXT';";
						//$this->str .= "\n".$indent."\t\tcase 'HIDDENTEXT';";
						//$this->str .= "\n".$indent."\t\tcase 'WIZARDTEXT';";
						//$this->str .= "\n".$indent."\t\tcase 'WIZARDBUTTON';";
						
						//$this->str .= "\n".$indent."\t\tbreak;";
						
						$this->str .= "\n".$indent."\t}";
						$this->str .= "\n".$indent."}";
						
						// MODEL-related
						$this->str .= "\n".$indent."if (is_array(\$".$obj."->".$key."))\n".$indent."{\n".$indent."\t\$".$key." = array_replace_recursive(\$db_".$key.", \$this->".$key.");\n".$indent."}";
						$this->str .= "\n".$indent."\$".$obj."->".$key." = json_encode(\$".$key.");";
						
						if ($this->debug)
						{
							$this->str .= "\n".$indent."switch(json_last_error())\n".$indent."{";
							$this->str .= "\n".$indent."\tcase JSON_ERROR_DEPTH: trigger_error('JSON-Encode-Error in ".$this->objectName.", id '.$".$obj."->id.': Maximum stack depth exceeded', E_USER_ERROR); break;";
							$this->str .= "\n".$indent."\tcase JSON_ERROR_CTRL_CHAR: trigger_error('JSON-Encode-Error in ".$this->objectName.", id '.$".$obj."->id.': Unexpected control character found', E_USER_ERROR); break;";
							$this->str .= "\n".$indent."\tcase JSON_ERROR_SYNTAX: trigger_error('JSON-Encode-Error in ".$this->objectName.", id '.$".$obj."->id.': Syntax error, malformed JSON', E_USER_ERROR); break;";
							$this->str .= "\n".$indent."}";
						}
						$this->str .= "\n".$indent;
					break;
					case 'BOOL':
					case 'INTEGER':
					case 'SELINTEGER':
					case 'HIDDENINTEGER':
					case 'EXCLUDEDINTEGER':
					case 'TIMESTAMP':
						$this->str .= "\n".$indent."\$".$obj."->".$key." = intval(\$".$obj."->".$key.");";
					break;
					case 'FLOAT':
						$this->str .= "\n".$indent."\$".$obj."->".$key." = floatval(\$".$obj."->".$key.");";
					break;
					case 'CRON';
					case 'DATE';
					case 'DATETIME';
					case 'VARCHAR';
					case 'WIZARDVARCHAR';
						$this->str .= "\n".$indent."\$".$obj."->".$key." = filter_var(\$".$obj."->".$key.", FILTER_SANITIZE_STRING);";
					break;
					default:
						$this->str .= "\n".$indent."\$".$obj."->".$key." = \$".$obj."->".$key.";";
					break;
				}// switch end

			}// else end
			
		}// foreach end
		
		$this->str .= "\n".$indent;
	}
	
	
	// -------------------------------------------------------------
	function CreateGetFunction()
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Gets object from database", 
						array( (($this->manualId)?'string':'integer') ." \$id"),
						"object \$".$this->objectName);
		
		$this->str .="\tpublic function Get (\$id = 0)\n\t{";
		$this->str .= "\n\t\t\$query = 'SELECT * FROM `".strtolower($this->objectName)."` WHERE `id`=:id LIMIT 1;';";
		$this->str .= "\n\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		
		
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		
		$this->str .= "\n\t\t\t\$prepare->execute(array(':id'=>\$id));";//
		$this->str .= "\n\t\t\t\$result = \$prepare->fetch();\n\t\t";
		
		// force to return an empty object if we call by $object->Get(0);
		$this->str .= "\n\t\t\tif (empty(\$id)) return \$this;";
		$this->str .= "\n\t\t\t";
		
		$this->CreateOutputFilter($this, 'this', "\t\t\t");
		
		$this->str .= "\n\t\n\t\t\treturn \$this;";
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		
		
		
		$this->str .= "\n\t}";
	}
	
	
	// -------------------------------------------------------------
	function CreateGetListFunction()
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Returns a sorted array of objects that match given conditions", 
						array(	"mixed \$fcv {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}",
								"array \$sortBy",
								"integer \$limit 0 means unlimited",
								"integer \$offset"
							),
						"array \$".strtolower($this->objectName)."List");
		
		$this->str .= "\tpublic function GetList (\$fcv = array(), \$sortBy = array(), \$limit = 0, \$offset = 0)\n\t{";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$".strtolower($this->objectName)."List = array();";
		$this->str .= "\n\t\t\$bindings = array();";
		$this->str .= "\n\t\t\$query = 'SELECT * FROM `".strtolower($this->objectName)."`';";
		
		$this->str .= "\n\t\tif (sizeof(\$fcv) > 0)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$whereArray = array();";
		
		$this->str .= "\n\t\t\tforeach (\$fcv as \$a)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tif (count(\$a) === 1 && is_string(\$a[0]))";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$whereArray[] = trim(DB::instance($this->db)->quote(\$a[0]), '\'');";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\telse";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\tif (is_array(\$a[0]))";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$orArray = array();";
		$this->str .= "\n\t\t\t\t\t\tforeach (\$a as \$o)";
		$this->str .= "\n\t\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$o[2], \$this->__columns['".$this->objectName."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '' . \$o[2] . '';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$o[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$orArray[] = '' . \$o[0] . ' ' . \$o[1] . ' ' . \$value;";
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = '(' . implode(' OR ', \$orArray) . ')';";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$a[2], \$this->__columns['".$this->objectName."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '' . \$a[2] . '';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$a[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = '' . \$a[0] . ' ' . \$a[1] . ' ' . \$value;";
		
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t\$query .= ' WHERE ' . implode(' AND ', \$whereArray);";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sortByArray = array();";
		$this->str .= "\n\t\tforeach (\$sortBy as \$field => \$direction)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (strtolower(\$direction) === 'asc' || strtolower(\$direction) === 'desc')";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$sortByArray[] = '`' . trim(DB::instance($this->db)->quote(\$field), '\'') . '` ' . \$direction;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tif (!isset(\$sortBy['id']))";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$sortByArray[] = '`id` ASC';";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sqlLimit = (intval(\$limit) > 0 ? ' LIMIT ' . \$limit : '');";
		$this->str .= "\n\t\t\$sqlOffset = (intval(\$offset) > 0 ? ' OFFSET ' . \$offset : '');";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$query .= ' ORDER BY ' . implode(', ', \$sortByArray) . \$sqlLimit . \$sqlOffset;";
		
		
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->str .= "\n\t\t\t\$prepare->execute(\$bindings);";
		$this->str .= "\n\t\t\t\$list = \$prepare->fetchAll();";
		
		$this->str .= "\n\t\t\t";
		//$this->str .= "\n\t\t\t\$thisObjectName = get_class(\$this);";
		$this->str .= "\n\t\t\tforeach (\$list as \$key => \$result)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$".$this->objectName." = new ".$this->objectName."();";// define new object
		//$this->str .= "\n\t\t\t\t\$".$this->objectName." = new \$thisObjectName();";// define new object
		//$this->str .= "\n\t\t\t\t\$".strtolower($this->objectName)." = \$thisObjectName::instance();";// define new object
		//$this->str .= "\n\t\t\t\tforeach (\$lst as \$key => \$val)";
		//$this->str .= "\n\t\t\t\t{";
		//$this->str .= "\n\t\t\t\t\t\$".$this->objectName."->\$key = \$val;";
		//$this->str .= "\n\t\t\t\t}";
		$this->CreateOutputFilter($this, $this->objectName, "\t\t\t\t");
		$this->str .= "\n\t\t\t\t\$".$this->objectName."List[] = \$".$this->objectName.";";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\treturn \$".$this->objectName."List;";
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		$this->str .= "\n\t}";
		
		
	}
	
	// not used atm
	/*function CreateGetTreeAsListFunction()
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Returns a sorted array of objects that match given conditions", 
											array("multidimensional array {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}","array \$sortBy","int \$limit","int \$offset"),
											"array \$".strtolower($this->objectName)."List");
		
		$this->str .= "\tpublic function GetList (\$fcv = array(), \$sortBy = array(), \$limit = 0, \$offset = 0)\n\t{";
		$this->str .= "\n\t\t";
		
		$this->str .= "\n\t\t\$list = \$this->GetTreeList(\$fcv);";
		$this->str .= "\n\t\t\$list2 = array();";
		$this->str .= "\n\t\t\$cnt = count(\$list);";
		$this->str .= "\n\t\tfor (\$i=\$offset; \$i<\$cnt; \$i++)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (\$i >= \$limit)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tbreak;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t\$list2[] = \$list[\$i];";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\treturn \$list2;";
		$this->str .= "\n\t}";
		
		
	}*/
	
	
	/*
	 * see 
	 * http://www.klempert.de/nested_sets (german)
	 * */
	// creates a Function to get the (Sub-)Tree from a Nested-Set Tree
	function CreateGetTreeFunction()
	{
		$n = strtolower($this->objectName);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Returns a hierachical tree-array of objects that match given conditions", 
											array(	"mixed \$fcv {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}",
													"array \$sort",
													"integer \$limit default unlimited",
													"integer \$offset default no Offset",
													(($this->manualId)?'string':'integer')." \$parentId 0 means top Level",
													"integer \$depth per default limited to 99 Levels", 
												),
											"array \$".$n."List");
		
		
		$this->str .= "\tpublic function GetTreeList (\$fcv = array(), \$sort = array(), \$limit = 0, \$offset = 0, \$parentId = 0, \$depth = 99)\n\t{";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$".$n."List = array();";
		$this->str .= "\n\t\t\$bindings = array(\$parentId);";
		//$this->str .= "\n\t\t\$query = 'SELECT a.*, count(a.*)-1 AS treelevel FROM `".strtolower($this->objectName)."` AS a JOIN `".strtolower($this->objectName)."` AS b ON a.treeleft BETWEEN b.treeleft AND b.treeright AND a.treeright BETWEEN b.treeleft AND b.treeright WHERE a.`treeparentid` = ?';";
		
		
		$this->str .= "\n\t\tif (\$depth == 1)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$query = 'SELECT c.*, 0 AS treelevel, ((c.treeright-c.treeleft-1)/2) AS treechilds FROM `".$n."` c WHERE c.treeparentid = ? ';";
		$this->str .= "\n\t\t\t\$sqlGroup = '';";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\telse";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$query = 'SELECT c.*, count(b.id)-1 AS treelevel, (c.treeright-c.treeleft-1)/2 AS treechilds FROM `".$n."` AS a, `".$n."` AS b, `".$n."` AS c WHERE c.treeleft BETWEEN b.treeleft AND b.treeright AND c.treeleft BETWEEN a.treeleft AND a.treeright AND  a.treeparentid = ?';";
		$this->str .= "\n\t\t\t\$sqlGroup = ' GROUP BY c.treeleft';";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\tif (sizeof(\$fcv) > 0)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$whereArray = array();";
		
		$this->str .= "\n\t\t\tforeach (\$fcv as \$a)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tif (count(\$a) === 1 && is_string(\$a[0]))";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$whereArray[] = trim(DB::instance($this->db)->quote(\$a[0]), '\'');";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\telse";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\tif (is_array(\$a[0]))";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$orArray = array();";
		$this->str .= "\n\t\t\t\t\t\tforeach (\$a as \$o)";
		$this->str .= "\n\t\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$o[2], \$this->__columns['".$this->objectName."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$o[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$o[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$orArray[] = 'c.`' . \$o[0] . '` ' . \$o[1] . ' ' . \$value;";
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = '(' . implode(' OR ', \$orArray) . ')';";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$a[2], \$this->__columns['".$this->objectName."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$a[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$a[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = 'c.`' . \$a[0] . '` ' . \$a[1] . ' ' . \$value;";
		
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t\$query .= ' AND ' . implode(' AND ', \$whereArray);";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sortArray = array();";
		$this->str .= "\n\t\tforeach (\$sort as \$field => \$dir)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (strtolower(\$dir) === 'asc' || strtolower(\$dir) === 'desc')";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$sortArray[] = 'c.' . preg_replace('/\W/','', \$field) . ' ' . \$dir;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t\$sortArray[] = 'c.treeleft';";

		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sqlLimit = (intval(\$limit) > 0 ? ' LIMIT ' . \$limit : '');";
		$this->str .= "\n\t\t\$sqlOffset = (intval(\$offset) > 0 ? ' OFFSET ' . \$offset : '');";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$query .= \$sqlGroup . ' ORDER BY ' . implode(',', \$sortArray) . \$sqlLimit . \$sqlOffset;";// 
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->str .= "\n\t\t\t\$prepare->execute(\$bindings);";
		$this->str .= "\n\t\t\t\$list = \$prepare->fetchAll();";
		
		$this->str .= "\n\t\t\t";
		//$this->str .= "\n\t\t\t\$thisObjectName = get_class(\$this);";
		$this->str .= "\n\t\t\tforeach (\$list as \$key => \$result)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tif (\$result->treelevel < \$depth)";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$".$n." = new ".$n."();";// define new object
		
		// add two special fields to the column-list
		$cols = $this->elementList['col'];
		$cols['treelevel']  = array('type'=>'INTEGER');
		$cols['treechilds'] = array('type'=>'INTEGER');
		
		$this->CreateOutputFilter($this, $n, "\t\t\t\t\t", $cols);
		
		//$this->str .= "\n\t\t\t\t\t\$".$n." = \$thisObjectName::instance();";// define new object
		//$this->str .= "\n\t\t\t\t\tforeach (\$lst as \$key => \$val)";
		//$this->str .= "\n\t\t\t\t\t{";
		//$this->str .= "\n\t\t\t\t\t\t\$".$n."->\$key = \$val;";
		//$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\$".$n."List[] = \$".$n.";";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		
		
		$this->str .= "\n\t\t\treturn \$".$n."List;";
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		$this->str .= "\n\t}";
	}
	
	
	
	
	
	function CreateAddTreeChildFunction()
	{
		
		/*  see: http://stackoverflow.com/questions/889527/mysql-move-node-in-nested-set
		Change positions of node and all it's sub nodes into negative values, which are equal to current ones by module.
		Move all positions "up", which are more, that pos_right of current node.
		Move all positions "down", which are more, that pos_right of new parent node.
		Change positions of current node and all it's subnodes, so that it's now will be exactly "after" (or "down") of new parent node.
		
		step 1: temporary "remove" moving node (setting to negative)
		step 2 (2 queries): decrease left and/or right position values of currently 'lower' items (and parents)
		step 3 (2 queries): increase left and/or right position values of future 'lower' items (and parents)
		step 4: move node (and it's subnodes)
		* and update it's parent-id (as a prepared statement)
		* if there is any precursor we have to "swap"
	*/
		/*$this->str .= "\tpublic function Add".$n." (\$child, \$precursor=false)\n\t{";
		$this->str .= "\n\t\t";
		
		
		$this->str .= "\n\t\t// Recursion-Check";
		$this->str .= "\n\t\tforeach (DB::instance($this->db)->query('SELECT `id` FROM `".$n."` WHERE `treeleft` BETWEEN ' . intval(\$child->treeleft) . ' AND ' . intval(\$child->treeright)) as \$row)\n\t\t{";
		$this->str .= "\n\t\t\tif(\$row->id == \$this->id)\n\t\t\t{\n\t\t\t\treturn false;\n\t\t\t}";
		$this->str .= "\n\t\t}";
		
		
		// insert-actions
		$this->str .= "\n\t\t\$size = \$child->treeright - \$child->treeleft + 1;";
	
		$this->str .= "\n\t\t\$sql = array (
			'UPDATE `".$n."` SET `treeleft` = 0-(`treeleft`), `treeright` = 0-(`treeright`) WHERE `treeleft` >= ' . intval(\$child->treeleft) . ' AND `treeright` <= ' . intval(\$child->treeright),
			'UPDATE `".$n."` SET `treeleft` = `treeleft` - ' . \$size . ' WHERE `treeleft` > ' . intval(\$child->treeright),
			'UPDATE `".$n."` SET `treeright` = `treeright` - ' . \$size . ' WHERE `treeright` > ' . intval(\$child->treeright),
			'UPDATE `".$n."` SET `treeleft` = `treeleft` + ' . \$size . ' WHERE `treeleft` >= ' . intval(\$this->treeright > \$child->treeright ? \$this->treeright - \$size : \$this->treeright),
			'UPDATE `".$n."` SET `treeright` = `treeright` + ' . \$size . ' WHERE `treeright` >= ' . intval(\$this->treeright > \$child->treeright ? \$this->treeright - \$size : \$this->treeright),
			'UPDATE `".$n."` SET `treeleft` = 0-(`treeleft`)+' . (\$this->treeright > \$child->treeright ? \$this->treeright - \$child->treeright - 1 : \$this->treeright - \$child->treeright - 1 + \$size) . ', `treeright` = 0-(`treeright`)+' . (\$this->treeright > \$child->treeright ? \$this->treeright - \$child->treeright - 1 : \$this->treeright - \$child->treeright - 1 + \$size) . ' WHERE `treeleft` <= ' . (0-\$child->treeleft) . ' AND `treeright` >= ' . (0-\$child->treeright),
		);";
		$this->str .= "\n\t\tif (\$precursor)\n\t\t{";
		$this->str .= "\n\t\t\t//\$sql[] = 'UPDATE `".$n."` SET `treeleft`=`treeleft`-'.\$x.', `treeright`=`treeright`-'.\$x.' WHERE `treeleft` BETWEEN ';";
		$this->str .= "\n\t\t\t//\$sql[] = '';";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tforeach (\$sql as \$query)\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$prepare = DB::instance($this->db)->query(\$query);";
		$this->str .= "\n\t\t\t}";
		
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeparentid`=? WHERE `id`=?');";
		$this->str .= "\n\t\t\t\$prepare->execute(array(intval(\$this->id), \$child->id));";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch(Exception \$e)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\treturn '[['.\$e->getMessage().']]';";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\treturn true;";
		$this->str .= "\n\t}";
		
		
		
		
		
		*/
		$n = strtolower($this->objectName);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates a Node/Branch of a Tree as Child to a Parent-Object", 
											array("object \$child", "object \$precursor"),
											'');
		// adapted from: http://www.ninthavenue.com.au/how-to-move-a-node-in-nested-sets-with-sql
		$this->str .= "\tpublic function Add".$n." (\$child, \$precursor=false)\n\t{";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t// fix possible SQL-Injections";
		$this->str .= "\n\t\t\$child->treeleft = intval(\$child->treeleft);";
		$this->str .= "\n\t\t\$child->treeright = intval(\$child->treeright);";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t// Recursion-Check";
		$this->str .= "\n\t\tforeach (DB::instance($this->db)->query('SELECT `id` FROM `".$n."` WHERE `treeleft` BETWEEN ' . \$child->treeleft . ' AND ' . \$child->treeright) as \$row)\n\t\t{";
		$this->str .= "\n\t\t\tif(\$row->id == \$this->id)\n\t\t\t{\n\t\t\t\treturn false;\n\t\t\t}";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t// define Position adjustment Variables";
		$this->str .= "\n\t\t\$newpos = intval((\$precursor) ? \$precursor->treeright+1 : \$this->treeleft+1);";
		$this->str .= "\n\t\t\$width = \$child->treeright - \$child->treeleft + 1;";
		$this->str .= "\n\t\t\$distance = \$newpos - \$child->treeleft;";
		$this->str .= "\n\t\t\$tmppos = \$child->treeleft;";
		$this->str .= "\n\t\t\$oldrpos = \$child->treeright;";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\tif (\$distance < 0)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$distance -= \$width;";
		$this->str .= "\n\t\t\t\$tmppos += \$width;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$queries = array (";
		$this->str .= "\n\t\t\t// create new Space for the Subtree";
		$this->str .= "\n\t\t\t'UPDATE `".$n."` SET `treeleft` = `treeleft` + ' . \$width . ' WHERE `treeleft` >= ' . \$newpos . ';',";
		$this->str .= "\n\t\t\t'UPDATE `".$n."` SET `treeright` = `treeright` + ' . \$width . ' WHERE `treeright` >= ' . \$newpos . ';',";
		$this->str .= "\n\t\t\t// move Subtree into new Space";
		$this->str .= "\n\t\t\t'UPDATE `".$n."` SET `treeleft` = `treeleft` + ' . \$distance . ', `treeright` = `treeright` + ' . \$distance . ' WHERE `treeleft` >= ' . \$tmppos . ' AND `treeright` < ' . (\$tmppos + \$width) . ';',";
		$this->str .= "\n\t\t\t// remove old Space vacated by the Subtree";
		$this->str .= "\n\t\t\t'UPDATE `".$n."` SET `treeleft` = `treeleft` - ' . \$width . ' WHERE `treeleft` > ' . \$oldrpos . ';',";
		$this->str .= "\n\t\t\t'UPDATE `".$n."` SET `treeright` = `treeright` - ' . \$width . ' WHERE `treeright` > ' . \$oldrpos . ';'";
		$this->str .= "\n\t\t);";
		$this->str .= "\n\t\t";
		
		
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tforeach (\$queries as \$query)\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$prepare = DB::instance($this->db)->query(\$query);";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t// set the new Parent-ID";
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeparentid` = ? WHERE `id` = ?');";
		$this->str .= "\n\t\t\t// force parent-id to be an integer (to flatten tree)";
		$this->str .= "\n\t\t\t\$prepare->execute(array(intval(\$this->id), \$child->id));";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch(Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\treturn true;";
		$this->str .= "\n\t}";
		// Add-Function end
		
		
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates a Parent-Object to a Tree-Node/Branch", 
											array("object \$parent", "object \$precursor the Node, the Child-Node is placed after"),
											'');
		$this->str .= "\tpublic function Set".$n." (\$parent, \$precursor=false)\n\t{";
		$this->str .= "\n\t\t\$parent->Add".$n."(\$this, \$precursor);";
		$this->str .= "\n\t}";
		$this->str .= "\n\t\n\t";
		
		/*
		Detach Tree-Child/Branch
			step 1: set parentid=0 and temporary "remove" moving node
			step 2 (2 queries): decrease left and/or right position values of currently 'lower' items (and parents)
			step 3: move node (and it's subnodes) to the end and remove parentid from the first child
		*/
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Removes a Node/Branch from a Parent-Object", 
											array("object \$parent"),'');
		$this->str .= "\tpublic function Remove".$n." (\$child)\n\t{";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t// fix possible SQL-Injections";
		$this->str .= "\n\t\t\$child->treeleft = intval(\$child->treeleft);";
		$this->str .= "\n\t\t\$child->treeright = intval(\$child->treeright);";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$width = \$child->treeright - \$child->treeleft + 1;";
		$this->str .= "\n\t\t\$max = intval(DB::instance($this->db)->query('SELECT MAX(`treeright`) AS m FROM `".$n."`')->fetch()->m);";
	
		$this->str .= "\n\t\t\$queries = array (
			'UPDATE `".$n."` SET `treeleft` = 0-(`treeleft`), `treeright` = 0-(`treeright`) WHERE `treeleft` >= ' . \$child->treeleft . ' AND `treeright` <= ' . \$child->treeright . ';',
			'UPDATE `".$n."` SET `treeleft` = `treeleft` - ' . \$width . ' WHERE `treeleft` > ' . \$child->treeright . ';',
			'UPDATE `".$n."` SET `treeright` = `treeright` - ' . \$width . ' WHERE `treeright` > ' . \$child->treeright . ';',
			'UPDATE `".$n."` SET `treeleft` = 0-(`treeleft`)-' . \$child->treeleft . '+' . (\$max - \$width + 1) . ', `treeright` = 0-(`treeright`)-' . \$child->treeleft . '+' . (\$max - \$width + 1) . ' WHERE `treeright` < 0;'
		);";
		
		
		
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tforeach (\$queries as \$query)\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$prepare = DB::instance($this->db)->query(\$query);";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t// set the new Parent-ID";
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeparentid` = 0  WHERE `id` = ?');";
		$this->str .= "\n\t\t\t\$prepare->execute(array(\$child->id));";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch(Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		
		
		$this->str .= "\n\t\treturn true;";
		$this->str .= "\n\t}";
		$this->str .= "\n\t\n\t";
		
	}
	
	
	
		
	/*
	 * ziel:
	 * einen Objekt-Array des (Teil-)Graphen
	 * der Tiefe Z
	 * unterhalb des Knotens X
	 * 
	 * 
	 * */
	
	// creates a function to get the (Sub-)Graph
	function CreateGetGraphFunction()
	{
		$n = strtolower($this->objectName);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Returns a hierachical array of objects that match given conditions", 
											array(	"mixed \$fcv {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}",
													"array \$sort",
													"integer \$limit default unlimited",
													"integer \$offset default no Offset",
													(($this->manualId)?'string':'integer')." \$parentId 0 means top Level",
													"integer \$depth per default limited to 99 Levels", 
											),
											"array \$".$n."List");
		
		$this->str .= "\tpublic function GetTreeList (\$fcv = array(), \$sort = array(), \$limit = 0, \$offset = 0, \$parentId = 0, \$depth = 99)\n\t{";
		$this->str .= "\n\t\t";
		
		$this->str .= "\n\t\t\$".$n."List = array();";
		$this->str .= "\n\t\t\$bindings = array();";
		$this->str .= "\n\t\t\$startDepth = 0;";
		$this->str .= "\n\t\t\$query = 'SELECT c.*, m.hops AS treelevel, (SELECT COUNT(id)-1 FROM `".$n."matrix` WHERE pid = m.id) AS treechilds FROM `".$n."` c JOIN `".$n."matrix` m ON (c.id = m.id) WHERE m.pid';";
		$this->str .= "\n\t\tif (\$parentId != 0)\n\t\t{";
		$this->str .= "\n\t\t\t\$query .= ' = ?';";
		$this->str .= "\n\t\t\t\$bindings[] = \$parentId;";
		$this->str .= "\n\t\t\t\$startDepth = 1;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\telse";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$query .= ' NOT IN (SELECT id FROM `".$n."matrix` WHERE hops > 0) AND m.hops < 1';";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\tif (sizeof(\$fcv) > 0)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$whereArray = array();";
		
		$this->str .= "\n\t\t\tforeach (\$fcv as \$a)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tif (count(\$a) === 1 && is_string(\$a[0]))";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$whereArray[] = trim(DB::instance($this->db)->quote(\$a[0]), '\'');";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\telse if (is_array(\$a))";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\tif (is_array(\$a[0]))";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$orArray = array();";
		$this->str .= "\n\t\t\t\t\t\tforeach (\$a as \$o)";
		$this->str .= "\n\t\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$o[2], \$this->__columns['".$this->objectName."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$o[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$o[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$orArray[] = 'a.`' . \$o[0] . '` ' . \$o[1] . ' ' . \$value;";
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = '(' . implode(' OR ', \$orArray) . ')';";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$a[2], \$this->__columns['".$this->objectName."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$a[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$a[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = 'a.`' . \$a[0] . '` ' . \$a[1] . ' ' . \$value;";
		
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t\$query .= ' AND ' . implode(' AND ', \$whereArray);";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sortArray = array();";
		$this->str .= "\n\t\tforeach (\$sort as \$field => \$dir)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (strtolower(\$dir) === 'asc' || strtolower(\$dir) === 'desc')";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$sortArray[] = 'c.`' . preg_replace('/\W/','', \$field) . '` ' . \$dir;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t\$sortArray[] = 'm.`pid` ASC, m.`sort` ASC, m.`hops` ASC';";
		
		
		//$this->str .= "\n\t\t\$query .= ' ORDER BY ' . ((count(\$sortByArray)>0) ? implode(', ', \$sortByArray) : 'm.`pid` ASC, m.`sort` ASC, m.`hops` ASC');";
		//$this->str .= "\n\t\t\$query .= ' ORDER BY m.`pid` ASC, m.`sort` ASC, m.`hops` ASC';";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sqlLimit = (intval(\$limit) > 0 ? ' LIMIT ' . \$limit : '');";
		$this->str .= "\n\t\t\$sqlOffset = (intval(\$offset) > 0 ? ' OFFSET ' . \$offset : '');";
		
		$this->str .= "\n\t\t";
		
		
		$this->str .= "\n\t\t\$query .= ' ORDER BY ' . implode(',', \$sortArray) . \$sqlLimit . \$sqlOffset;";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$object = get_class(\$this);";
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		
		$this->str .= "\n\t\t\t\$prepare->execute(\$bindings);";
		$this->str .= "\n\t\t\t\$list = \$prepare->fetchAll();";
		
		$this->str .= "\n\t\t\t";
		//$this->str .= "\n\t\t\t\$thisObjectName = get_class(\$this);";
		$this->str .= "\n\t\t\tforeach (\$list as \$key => \$lst)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tif (\$lst->treelevel >= \$startDepth && \$lst->treelevel <= \$depth)";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$".$n." = new ".$n."();";// define new object
		//$this->str .= "\n\t\t\t\t\t\$".$n." = \$thisObjectName::instance();";// define new object
		$this->str .= "\n\t\t\t\t\tforeach (\$lst as \$key => \$val)";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$".$n."->\$key = \$val;";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\$".$n."List[] = \$".$n.";";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		
		
		$this->str .= "\n\t\t\treturn \$".$n."List;";
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		$this->str .= "\n\t}";
	}
	
	// hänge kind an eltern-element
	function CreateAddGraphChildFunction()
	{
		$n = strtolower($this->objectName);
		
		
		$this->str .= "\n\t\n\t";
		$this->str .= "\n\t\n\t";
		
		$this->str .= $this->CreateComments("Associates a Node/Branch of a Graph to a Parent-Object", 
											array("object \$child", "object \$precursor"),'');
		$this->str .= "\tpublic function Add".$n." (\$child, \$precursor=false)\n\t{";
		$this->str .= "\n\t\treturn \$this->setTreeMapping(\$this, \$child, \$precursor, false);";
		$this->str .= "\n\t}";
		$this->str .= "\n\t\n\t";
		
		$this->str .= $this->CreateComments("Associates a Parent-Object to a Node/Branch as Child", 
											array("object \$parent", "object \$precursor"),'');
		$this->str .= "\tpublic function Set".$n." (\$parent, \$precursor=false)\n\t{";
		$this->str .= "\n\t\treturn \$this->setTreeMapping(\$parent, \$this, \$precursor, false);";
		$this->str .= "\n\t}";
		$this->str .= "\n\t\n\t";
		
		$this->str .= $this->CreateComments("Removes a Node/Branch from a Parent-Object", 
											array("object \$child"),'');
		$this->str .= "\tpublic function Remove".$n." (\$child)\n\t{";
		$this->str .= "\n\t\treturn \$this->setTreeMapping(\$this, \$child, false, true);";
		$this->str .= "\n\t}";
		$this->str .= "\n\t\n\t";
		
		
		// internal function to handle the three functions above
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates a Node/Branch of a Graph to a Parent-Object", 
											array("object \$parent", "object \$child", "object \$precursor", "bool \$delete"),
											'');
		
		$this->str .= "\tprivate function setTreeMapping (\$parent, \$child, \$precursor=false, \$delete=false)\n\t{";
		
		$this->str .= "\n\t\t\$parents = array();";
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		
		// get all parents from parent-element (including itself)
		$this->str .= "\n\t\t\t// get all parents from parent-Node (including itself)";
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare('SELECT `pid`, `hops` FROM `".$n."matrix` WHERE `id` = ?');";
		$this->str .= "\n\t\t\t\$prepare->execute(array(\$parent->id));";
		$this->str .= "\n\t\t\twhile (\$row = \$prepare->fetch())\n\t\t\t{";
		$this->str .= "\n\t\t\t\t// Recursion-Check";
		$this->str .= "\n\t\t\t\tif (\$row->pid == \$child->id)";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\treturn false;";
		$this->str .= "\n\t\t\t\t}";
		
		
		$this->str .= "\n\t\t\t\t\$parents[] = array(\$row->pid, \$row->hops);";
		//$this->str .= "\n\t\t\t";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t";
		
		
		$this->str .= "\n\t\t\t// get all Children from the Child-Node (including itself)";
		$this->str .= "\n\t\t\t\$prepare0 = DB::instance($this->db)->prepare('SELECT `id`, `hops`, `sort` FROM `".$n."matrix` WHERE `pid` = ?');";
		$this->str .= "\n\t\t\t\$prepare0->execute(array(\$child->id));";
		$this->str .= "\n\t\t\t";
		$this->str .= "\n\t\t\t// decide wether to delete or add the Child-Node";
		$this->str .= "\n\t\t\tif (\$delete)\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$prepare1 = DB::instance($this->db)->prepare('DELETE FROM `".$n."matrix` WHERE `pid` = ? AND `id` = ? AND `hops` = ?');";
		
		$this->str .= "\n\t\t\t\t\$prepare2 = DB::instance($this->db)->prepare('SELECT `sort` FROM `grafmatrix` WHERE `hops` = 1 AND `pid` = ? AND `id` = ? LIMIT 1');";
		$this->str .= "\n\t\t\t\t\$prepare2->execute( array( \$parent->id, \$child->id) );";
		
		$this->str .= "\n\t\t\t}\n\t\t\telse\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$prepare1 = DB::instance($this->db)->prepare('INSERT INTO `".$n."matrix` (`pid`, `id`, `hops`, `sort`) VALUES (?,?,?,0)');";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t";
		$this->str .= "\n\t\t\twhile (\$row = \$prepare0->fetch())\n\t\t\t{";
		$this->str .= "\n\t\t\t\tforeach (\$parents as \$p)\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$prepare1->execute( array( \$p[0], \$row->id, (\$p[1] + \$row->hops + 1) ) );";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t";
		
		
		
		
		
		$this->str .= "\n\t\t\tif (\$delete)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t// remove Space";
		$this->str .= "\n\t\t\t\t\$prepare3 = DB::instance($this->db)->prepare('UPDATE `grafmatrix` SET `sort` = (`sort`-1) WHERE `hops` = 1 AND `pid`= ? AND `sort` > ?');";
		$this->str .= "\n\t\t\t\t\$prepare3->execute( array( \$parent->id, intval(\$prepare2->fetch()->sort) ) );";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\telse";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t// if there is a Precursor to place the Child after";
		$this->str .= "\n\t\t\t\tif (\$precursor)";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$prepare2 = DB::instance($this->db)->prepare('SELECT `sort` FROM `".$n."matrix` WHERE `hops` = 1 AND `pid` = ? AND `id` = ? LIMIT 1');";
		$this->str .= "\n\t\t\t\t\t\$prepare2->execute( array( \$parent->id, \$precursor->id) );";
		$this->str .= "\n\t\t\t\t\tif (\$row = \$prepare2->fetch())";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$sort = intval(\$row->sort);";
		$this->str .= "\n\t\t\t\t\t\t// create Space for the new Node";
		$this->str .= "\n\t\t\t\t\t\t\$prepare3 = DB::instance($this->db)->prepare('UPDATE `grafmatrix` SET `sort` = (`sort`+1) WHERE `hops` = 1 AND `pid`= ? AND `sort` > ?');";
		$this->str .= "\n\t\t\t\t\t\t\$prepare3->execute( array( \$parent->id, \$sort ) );";
		$this->str .= "\n\t\t\t\t\t\t// place the Child after Precursor";
		$this->str .= "\n\t\t\t\t\t\t\$prepare4 = DB::instance($this->db)->prepare('UPDATE `grafmatrix` SET `sort` = ? WHERE `hops` = 1 AND `pid` = ? AND `id` = ?');";
		$this->str .= "\n\t\t\t\t\t\t\$prepare4->execute( array( (\$sort+1), \$parent->id, \$child->id ) );";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";//precursor end
		$this->str .= "\n\t\t\t}";
		
		
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\treturn true;";
		$this->str .= "\n\t}";
	}
	
	
	
	// -------------------------------------------------------------
	function CreateSaveFunction($deep = false)
	{
		$n = strtolower($this->objectName);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Saves the Object to the Database",
											array('bool $deep'),
											(($this->manualId)?'string':'integer')." \$id");
		if ($deep)
		{
			$this->str .= "\tpublic function Save (\$deep = true)\n\t{";
		}
		else
		{
			$this->str .= "\tpublic function Save ()\n\t{";
		}
		
		$this->str .= "\n\t\t\$prepare0 = DB::instance($this->db)->prepare('SELECT * FROM `".$n."` WHERE `id`=:id LIMIT 1');";
		$this->str .= "\n\t\t\$prepare0->execute(array(':id'=>".($this->manualId?'trim':'intval')."(\$this->id)));";//
		$this->str .= "\n\t\ttry{\$result = \$prepare0->fetchAll();}catch(Exception \$e){\$result = array();}\n\t\t";
		$this->str .= "\n\t\t\$prepare0->closeCursor();\n\t\t";
		
		$this->CreateInputFilter('this', "\t\t");
		
		$this->str .= "\n\t\tif (count(\$result) === 1)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$mid = \$this->id;";
		$this->str .= "\n\t\t\t\$query = 'UPDATE `".$n."` SET ";
		
		
		$tmp0 = array();
		$tmp1 = array();
		foreach ($this->elementList['col'] as $key => $attr)
		{
			
			if($key != 'id') {
				$tmp0[] = "`".strtolower($key)."`=:".$key;
			}
			$tmp1[] = "':".$key."'=>\$this->".$key;
		}
		$this->str .= implode(', ', $tmp0);
		
		$this->str .= " WHERE `id`=:id';";
		$this->str .= "\n\t\t\t\$map = array(".implode(', ', $tmp1).");";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\telse";
		$this->str .= "\n\t\t{";
		
		//
		$tmp0 = array();
		$tmp1 = array();
		$tmp2 = array();
		
		// add manual ID-Insertion
		if($this->manualId)
		{
			$tmp0[] = '`id`';
			$tmp1[] = ":id";
			$tmp2[] = "':id'=>\$mid";
			$this->str .= "\n\t\t\t\$mid = (\$this->id > 0) ? \$this->id : DB::uid();";
		}
		else
		{
			$tmp0[] = '`id`';
			$tmp1[] = ":id";
			$tmp2[] = "':id'=>((\$this->id > 0) ? \$this->id : null)";
		}
		
		
		if($this->treeType=='Tree')
		{
			$this->str .= "\n\t\t\t";
			//
			$this->str .= "\n\t\t\tif (\$this->treeleft == 0)\n\t\t\t{";
			$this->str .= "\n\t\t\t\t\$this->treeparentid = 0;";
			$this->str .= "\n\t\t\t\t\$this->treeleft = intval(DB::instance($this->db)->query('SELECT MAX(`treeright`) AS m FROM `".$n."`')->fetch()->m) + 1;";
			$this->str .= "\n\t\t\t\t\$this->treeright = \$this->treeleft + 1;";
			$this->str .= "\n\t\t\t}";
			$this->str .= "\n\t\t\t";
		}
		
		
		
		$this->str .= "\n\t\t\t\$query = 'INSERT INTO `".$n."` (";
		
		foreach ($this->elementList['col'] as $key => $attr)
		{
			if($key != 'id') {
				$tmp0[] = '`'.$key.'`';
				$tmp1[] = ':'.$key;
				$tmp2[] = "':".$key."'=>\$this->".$key;
			}
		}
		$this->str .= implode(', ', $tmp0);
		$this->str .= ") VALUES (";
		$this->str .= implode(', ', $tmp1);
		$this->str .= ")';";
		$this->str .= "\n\t\t\t\$map = array(".implode(', ', $tmp2).");";
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->str .= "\n\t\t\t\$prepare->execute(\$map);";
		$this->str .= "\n\t\t\tif (!isset(\$mid))";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$mid = DB::instance($this->db)->lastInsertId();";
		$this->str .= "\n\t\t\t}";
		
		$this->str .= "\n\t\t\t\$this->id = \$mid;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		
		if ($deep)
		{
			$this->str .= "\n\t\tif (\$deep)";
			$this->str .= "\n\t\t{";
			if(isset($this->elementList['rel']))
			{
				foreach ($this->elementList['rel'] as $key => $attr)
				{
					if ($attr == 'c')
					{
						$this->str .= "\n\t\t\tforeach (\$this->_".strtolower($key)."List as $".strtolower($key).")";
						$this->str .= "\n\t\t\t{";
						$this->str .= "\n\t\t\t\t\$".strtolower($key)."->".$n."id = \$this->id;";
						$this->str .= "\n\t\t\t\t\$".strtolower($key)."->Save(\$deep);";
						$this->str .= "\n\t\t\t}";
					}
					else if ($attr == 's')
					{
						$this->str .= "\n\t\t\tforeach (\$this->_".strtolower($key)."List as $".strtolower($key).")";
						$this->str .= "\n\t\t\t{";
						$this->str .= "\n\t\t\t\t\$".strtolower($key)."->Save();";
						
						$this->str .= "\n\t\t\t\t\$map = new ".$this->MappingName($this->objectName, $key, 'Map', false)."();";
						$this->str .= "\n\t\t\t\t\$map->AddMapping(\$this, \$".$key.");";
						$this->str .= "\n\t\t\t}";
					}
				}
			}
			$this->str .= "\n\t\t}";
		}
		
		
		if($this->treeType=='Graph')
		{
			$this->str .= "\n\t\t";
			$this->str .= "\n\t\tif (count(\$result) == 0)";
			$this->str .= "\n\t\t{";
			$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare('INSERT INTO `".$n."matrix` (`pid`, `id`, `hops`, `sort`) VALUES (:id, :id, 0, 0)');";
			$this->str .= "\n\t\t\t\$prepare->execute(array(':id'=>\$this->id));";
			$this->str .= "\n\t\t}";
		}
		
		$this->str .= "\n\t\treturn \$this->id;";
		$this->str .= "\n\t}";
	}
	

	
	// -------------------------------------------------------------
	function CreateSaveNewFunction($deep = false)
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Clones the object and saves it to the database",
											array('bool $deep'),
											(($this->manualId)?'string':'integer')." \$id");
		if ($deep)
		{
			$this->str .="\tpublic function SaveNew (\$deep = false)\n\t{";
		}
		else
		{
			$this->str .="\tpublic function SaveNew ()\n\t{";
		}
		$this->str .= "\n\t\t\$this->id = '';";
		
		if ($deep)
		{
			$this->str .= "\n\t\treturn \$this->Save(\$deep);";
		}
		else
		{
			$this->str .= "\n\t\treturn \$this->Save();";
		}
		$this->str .= "\n\t}";
	}

	
	// -------------------------------------------------------------
	function CreateDeleteFunction($deep = false)
	{
		$n = strtolower($this->objectName);
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Deletes the object from the database",
											array('bool $deep','bool $across'),
											"bool successfull deleted");
		if ($deep)
		{
			$this->str .= "\tpublic function Delete (\$deep = false, \$across = false)\n\t{";
		}
		else
		{
			$this->str .= "\tpublic function Delete ()\n\t{";
		}
		
		if ($deep)
		{
			
				
			$tmp = '';
			if (isset($this->elementList['rel']))
			{
				foreach ($this->elementList['rel'] as $key => $attr)//$this->typeList as $type
				{
					if ($attr == 'c')
					{
						$tmp .= "\n\t\t\t$".strtolower($key)."List = \$this->Get".strtolower($key)."List();";
						$tmp .= "\n\t\t\tforeach ($".strtolower($key)."List as $".strtolower($key).")";
						$tmp .= "\n\t\t\t{";
						$tmp .= "\n\t\t\t\t\$".strtolower($key)."->Delete(\$deep, \$across);";
						$tmp .= "\n\t\t\t}";
					}
				}
			}
			if ($tmp != '')
			{
				$this->str .= "\n\t\tif (\$deep)";
				$this->str .= "\n\t\t{";
				$this->str .= $tmp;
				$this->str .= "\n\t\t}";
			}
			
			
			$tmp = $tmp1 = '';
			if(isset($this->elementList['rel']))
			{
				foreach ($this->elementList['rel'] as $key => $attr)
				{
					if ($attr == 's')
					{
						$tmp .= "\n\t\t\t$".strtolower($key)."List = \$this->Get".strtolower($key)."List();";
						$map = $this->MappingName($this->objectName, $key, 'Map', false);
						$tmp .= "\n\t\t\t\$map = new ".$map."();";
						$tmp .= "\n\t\t\tif (is_callable(\$map->RemoveMapping))\n\t\t\t{\n\t\t\t\t\$map->RemoveMapping(\$this);\n\t\t\t}";
						$tmp .= "\n\t\t\tforeach (\$".strtolower($key)."List as \$".strtolower($key).")";
						$tmp .= "\n\t\t\t{";
						$tmp .= "\n\t\t\t\t\$".strtolower($key)."->Delete(\$deep, \$across);";
						$tmp .= "\n\t\t\t}";
						
						$tmp1 .= "\n\t\t\t\$map = new ".$map."();";
						$tmp1 .= "\n\t\t\tif (is_callable(\$map->RemoveMapping))\n\t\t\t{\n\t\t\t\t\$map->RemoveMapping(\$this);\n\t\t\t}";
					}
				}
			}
			if ($tmp != '')
			{
				$this->str .= "\n\t\tif (\$across)";
				$this->str .= "\n\t\t{";
				$this->str .= $tmp;
				$this->str .= "\n\t\t}";
				$this->str .= "\n\t\telse";
				$this->str .= "\n\t\t{";
				$this->str .= $tmp1;
				$this->str .= "\n\t\t}";
			}
			
		}
		
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\ttry";
		$this->str .= "\n\t\t{";
		
		$this->str .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare('DELETE FROM `".$n."` WHERE `id`=:id');";
		$this->str .= "\n\t\t\t\$prepare->execute(array(':id'=>\$this->id));";
		
		if($this->treeType=='Tree')
		{
			$this->str .= "\n\t\t\t";
			$this->str .= "\n\t\t\t\$prepare0 = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeparentid`=:pid WHERE `treeparentid`=:id');";
			$this->str .= "\n\t\t\t\$prepare0->execute(array(':pid'=>\$this->treeparentid,':id'=>\$this->id));";
			$this->str .= "\n\t\t\t\$prepare1 = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeleft`=`treeleft`-1, `treeright`=`treeright`-1 WHERE `treeleft` BETWEEN :lft AND :rgt');";
			$this->str .= "\n\t\t\t\$prepare1->execute(array(':lft'=>\$this->treeleft,':rgt'=>\$this->treeright));";
			$this->str .= "\n\t\t\t\$prepare2 = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeleft`=`treeleft`-2 WHERE `treeleft`>:rgt');";
			$this->str .= "\n\t\t\t\$prepare2->execute(array(':rgt'=>\$this->treeright));";
			$this->str .= "\n\t\t\t\$prepare3 = DB::instance($this->db)->prepare('UPDATE `".$n."` SET `treeright`=`treeright`-2 WHERE `treeright`>:rgt');";
			$this->str .= "\n\t\t\t\$prepare3->execute(array(':rgt'=>\$this->treeright));";
		}
		
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t{";
		if($this->debug)
		{ 
			$this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n";
		}
		$this->str .= "\n\t\t\treturn false;";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t";
		
		if($this->treeType=='Graph')
		{
			$this->str .= "\n\t\ttry";
			$this->str .= "\n\t\t{";
			$this->str .= "\n\t\t\t\$prepare0 = DB::instance($this->db)->prepare('SELECT `id` FROM `".$n."matrix` WHERE `pid`=?');";
			$this->str .= "\n\t\t\t\$prepare0->execute(array(\$this->id));";
			$this->str .= "\n\t\t\t\$bindings = array();";
			$this->str .= "\n\t\t\t\$placeholders = array();";
			$this->str .= "\n\t\t\twhile (\$row = \$prepare0->fetch())\n\t\t\t{";
			$this->str .= "\n\t\t\t\t\$bindings[] = \$row->id;";
			$this->str .= "\n\t\t\t\t\$placeholders[] = '?';";
			$this->str .= "\n\t\t\t}";
			
			$this->str .= "\n\t\t\t\$prepare2 = DB::instance($this->db)->prepare('DELETE FROM `".$n."matrix` WHERE `pid` IN (' . implode(',', \$placeholders) . ')');";
			$this->str .= "\n\t\t\t\$prepare2->execute(\$bindings);";
			$this->str .= "\n\t\t}";
			$this->str .= "\n\t\tcatch (Exception \$e)";
			$this->str .= "\n\t\t{";
			if($this->debug)
			{
				$this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n";
			}
			$this->str .= "\n\t\t\treturn false;";
			$this->str .= "\n\t\t}";
			$this->str .= "\n\t\t";
		}
		
		
		$this->str .= "\n\t\treturn true;";
		$this->str .= "\n\t}";
	}
	
	
	
	// --------------------- NOT USED ATM ----------------------------------------
	function CreateDeleteListFunction($deep = false)
	{
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Deletes a list of objects that match given conditions",
											array("multidimensional array {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}","bool \$deep","bool \$across"));
		if ($deep)
		{
			$this->str .= "\tpublic function DeleteList (\$fcv, \$deep = false, \$across = false)\n\t{";
		}
		else
		{
			$this->str .= "\tpublic function DeleteList (\$fcv)\n\t{";
		}
		$this->str .= "\n\t\tif (sizeof(\$fcv) > 0)";
		$this->str .= "\n\t\t{";
		
		if ($deep)
		{
			$this->str .= "\n\t\t\tif (\$deep || \$across)";
			$this->str .= "\n\t\t\t{";
			$this->str .= "\n\t\t\t\t\$objectList = \$this->GetList(\$fcv);";
			$this->str .= "\n\t\t\t\tforeach (\$objectList as \$object)";
			$this->str .= "\n\t\t\t\t{";
			$this->str .= "\n\t\t\t\t\t\$object->Delete(\$deep, \$across);";
			$this->str .= "\n\t\t\t\t}";
			$this->str .= "\n\t\t\t}";
			$this->str .= "\n\t\t\telse";
			$this->str .= "\n\t\t\t{";
			
		}
		
		$this->str .= "\n\t\t\t\t\$whereArray = array();";
		$this->str .= "\n\t\t\t\t\$bindings = array();";
		$this->str .= "\n\t\t\t\tforeach (\$fcv as \$a)";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\tif (count(\$a) === 1 && is_string(\$a[0]))";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = trim(DB::instance($this->db)->quote(\$a[0]), '\'');";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\telse if (is_array(\$a))";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\tif (is_array(\$a[0]))";
		$this->str .= "\n\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\$orArray = array();";
		$this->str .= "\n\t\t\t\t\t\t\tforeach (\$a as \$o)";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\t\tif (in_array(\$o[2], \$this->__columns['".$this->objectName."']))";
		$this->str .= "\n\t\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\t\$value = '`' . \$o[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\t\$bindings[] = \$o[2];";
		$this->str .= "\n\t\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$orArray[] = '`' . \$o[0] . '` ' . \$o[1] . ' ' . \$value;";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$whereArray[] = '(' . implode(' OR ', \$orArray) . ')';";
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$a[2], \$this->__columns['".$this->objectName."']))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$a[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$a[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$whereArray[] = '`' . \$a[0] . '` ' . \$a[1] . ' ' . \$value;";
		
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\$query = 'DELETE FROM `".strtolower($this->objectName)."` WHERE ' . implode(' AND ', \$whereArray);";
		
		
		
		$this->str .= "\n\t\t\t\t";
		$this->str .= "\n\t\t\t\ttry";
		$this->str .= "\n\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->str .= "\n\t\t\t\t\t\$prepare->execute(\$bindings);";
		
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\tcatch (Exception \$e)";
		$this->str .= "\n\t\t\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$this->objectName.":[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->str .= "\n\t\t\t\t\treturn false;";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\t";
		
		if ($deep)
		{
			$this->str .= "\n\t\t\t}";
		}
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t}";
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////	
	
	// -------------------------------------------------------------
	function CreateReferencesFunction()
	{
		if(isset($this->elementList['rel']))
		{
			foreach ($this->elementList['rel'] as $key => $attr)
			{
				if ($attr == 'c')
				{
					$this->CreateGetChildrenFunction($key);
					$this->CreateSetChildrenFunction($key);
					$this->CreateAddChildFunction($key);
					$this->CreateRemoveChildFunction($key);
				}
				if ($attr == 'p')
				{
					//$name = substr($key,0,-2);
					$this->CreateGetParentFunction($key);
					$this->CreateSetParentFunction($key);
					$this->CreateRemoveParentFunction($key);
				}
				if ($attr == 's')
				{
					$this->CreateGetAssociationsFunction($key);
					$this->CreateSetAssociationsFunction($key);
					$this->CreateAddAssociationFunction($key);
					$this->CreateRemoveAssociationFunction($key);
					
					
					// create the Mapping-Class
					if($this->savePath)
					{
						new ObjectMap($this->projectName, $this->objectName, $key, $this->model, $this->savePath, $this->db, $this->debug);
					}
					
				}
			}
		}
	}
	
	// Relations Children
	// -------------------------------------------------------------
	function CreateGetChildrenFunction($child)
	{
		$childLower = strtolower($child);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Gets a list of $child objects associated to this one", 
											array(
											"multidimensional array {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}",
											"array \$sortBy",
											"integer \$limit",
											"integer \$offset"),
											"array of $child objects");
		
		$this->str .= "\tpublic function Get".$child."List (\$fcv = array(), \$sortBy = array(), \$limit = 0, \$offset = 0)\n\t{";
		$this->str .= "\n\t\t\$".$childLower." = new ".$child."();";
		$this->str .= "\n\t\t\$fcv[] = array('".strtolower($this->objectName)."id', '=', \$this->id);";
		$this->str .= "\n\t\t\$dbObjects = \$".$childLower."->GetList(\$fcv, \$sortBy, \$limit, \$offset);";
		$this->str .= "\n\t\treturn \$dbObjects;";
		$this->str .= "\n\t}";
	}
	
	// -------------------------------------------------------------
	function CreateSetChildrenFunction($child)
	{
		$childLower = strtolower($child);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Makes this the parent of all $child objects in the $child List array. Any existing $child will become orphan(s)",
											array('array $list List of objects'),
											'');
		
		$this->str .= "\tpublic function Set".$child."List (&\$list)\n\t{";
		$this->str .= "\n\t\t\$this->_".$childLower."List = array();";
		$this->str .= "\n\t\t\$existing".$child."List = \$this->Get".$child."List();";
		$this->str .= "\n\t\tforeach (\$existing".$child."List as \$".$childLower.")";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$".$childLower."->".strtolower($this->objectName)."id = '';";
		$this->str .= "\n\t\t\t\$".$childLower."->Save(false);";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\t\$this->_".$childLower."List = \$list;";
		$this->str .= "\n\t}";
	}
	// -------------------------------------------------------------
	function CreateAddChildFunction($child)
	{
		$childLower = strtolower($child);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates the $child object to this one",
											array('object $'.$childLower),
											'');
											
		$this->str .= "\tpublic function Add".$childLower." (&\$".$childLower.")\n\t{";
		$this->str .= "\n\t\t\$".$childLower."->".strtolower($this->objectName)."id = \$this->id;";
		$this->str .= "\n\t\t\$found = false;";
		$this->str .= "\n\t\tforeach (\$this->_".$childLower."List as \$".$childLower."2)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (\$".$childLower."->id == \$".$childLower."2->id)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$found = true;";
		$this->str .= "\n\t\t\t\tbreak;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\tif (!\$found)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$this->_".$childLower."List[] = \$".$childLower.";";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t}";
	}
	
	// Relations Parents
	
	// -------------------------------------------------------------
	function CreateGetParentFunction($parent)
	{
		$parentLower = strtolower($parent);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Gets the parent object \"$parent\" if any",
											'',
											"object $parent");
		
		$this->str .= "\tpublic function Get".$parent." ()\n\t{";
		$this->str .= "\n\t\t\$".$parentLower." = new ".$parent."();";
		$this->str .= "\n\t\treturn $".$parentLower."->Get(\$this->".$parentLower."id);";
		$this->str .= "\n\t}";
		
		$this->str .= $this->CreateComments("Gets the parent object \"$parent\" as array emulating GetList-Function",
											'',
											"object $parent");
		$this->str .= "\tpublic function Get".$parent."List ()\n\t{";
		$this->str .= "\n\t\treturn array(\$this->Get".$parent."());";
		$this->str .= "\n\t}";
	}
	
	
	// -------------------------------------------------------------
	function CreateSetParentFunction($parent)
	{
		$parentLower = strtolower($parent);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates the parent object \"$parent\" to this one",
											array('object $'.$parent.' the new Parent-Object'),
											'');
		$this->str .= "\tpublic function Set".$parent." (&\$".$parentLower.")\n\t{";
		$this->str .= "\n\t\t\$this->".$parentLower."id = $".$parentLower."->id;";
		$this->str .= "\n\t}";
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates the parent object \"$parent\" to this one",
											array('object $'.$parent.' the new Parent-Object'),
											'');
		$this->str .= "\tpublic function Add".$parent." (&\$".$parentLower.")\n\t{";
		$this->str .= "\n\t\t\$this->".$parentLower."id = $".$parentLower."->id;";
		$this->str .= "\n\t}";
		
	}

	


	// Relations {Many-Many} functions
	
	//-------------------------------------------------------------
	function CreateGetAssociationsFunction($sibling)
	{
		$sls = '';
		$sle = '';
		if (get_magic_quotes_gpc())
		{
			
			$sls = 'stripslashes(';
			$sle = ')';
		}
		$siblingLower = strtolower($sibling);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Returns a sorted array of objects that match given conditions", 
											array(	"mixed \$fcv {(\"field\", \"comparator\", \"value\"), (\"field\", \"comparator\", \"value\"), ...}",
													"array \$sortBy",
													"integer \$limit",
													"integer \$offset"),
											"array \$".$siblingLower."List");
		
		$this->str .= "\tpublic function Get".$sibling."List (\$fcv = array(), \$sortBy = array(), \$limit = 0, \$offset = 0)\n\t{";
		
		$this->str .= "\n\t\t\$".$siblingLower."List = array();";
		$this->str .= "\n\t\t\$bindings = array(\$this->id);";
		
		$adds = '';
		$map = strtolower($this->MappingName($this->objectName, $sibling));
		
		//  if we have a enhanced mapping-table we must add the fields
		if (isset($this->model[$map]))
		{
			foreach ($this->model[$map]['col'] as $colk=>$colv)
			{
				if (substr($colk,-2)!='id' && substr($colk,-4)!='sort')
				{
					// add the Column-Name to the Query-Output (eg. SELECT a.*, ... )
					$adds .= ', m.'.$colk;
					// add the column to the Fields processed by CreateOutputFilter
					$this->model[$siblingLower]['col'][$colk]  = array('type'=>'VARCHAR');
				}
			}
		}
		
		$this->str .= "\n\t\t\$query = 'SELECT DISTINCT a.*" . $adds . " FROM `" . $siblingLower . "` a INNER JOIN `" . $map . "` m ON m." . $siblingLower . "id = a.id WHERE m." . strtolower($this->objectName) . "id = ?';";
		
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\tif (sizeof(\$fcv) > 0)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$whereArray = array();";
		
		$this->str .= "\n\t\t\tforeach (\$fcv as \$a)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\tif (count(\$a) === 1 && is_string(\$a[0]))";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$whereArray[] = trim(DB::instance($this->db)->quote(\$a[0]), '\'');";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\telse if (is_array(\$a))";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\tif (is_array(\$a[0]))";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$orArray = array();";
		$this->str .= "\n\t\t\t\t\t\tforeach (\$a as \$o)";
		$this->str .= "\n\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$o[2], \$this->__columns['".$sibling."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$o[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$o[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$orArray[] = '`' . \$o[0] . '` ' . \$o[1] . ' ' . \$value;";
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\$whereArray[] = '(' . implode(' OR ', \$orArray) . ')';";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\tif (isset(\$a[2]))";
		$this->str .= "\n\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\tif (in_array(\$a[2], \$this->__columns['".$sibling."'], true))";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '`' . \$a[2] . '`';";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\telse";
		$this->str .= "\n\t\t\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$value = '?';";
		$this->str .= "\n\t\t\t\t\t\t\t\t\$bindings[] = \$a[2];";
		$this->str .= "\n\t\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t\t\t\$whereArray[] = '`' . \$a[0] . '` ' . \$a[1] . ' ' . \$value;";
		$this->str .= "\n\t\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t";
		$this->str .= "\n\t\t\tif (count(\$whereArray) > 0)";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$query .= ' AND ' . implode(' AND ', \$whereArray);";
		$this->str .= "\n\t\t\t}";
		
		$this->str .= "\n\t\t}";
		
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sortByArray = array();";
		$this->str .= "\n\t\tforeach (\$sortBy as \$field => \$direction)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (strtolower(\$direction) === 'asc' || strtolower(\$direction) === 'desc')";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$sortByArray[] = 'a.' . trim(DB::instance($this->db)->quote(\$field), '\'') . ' ' . \$direction;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\tif (!isset(\$sortBy['".$siblingLower."id']))";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$sortByArray[] = 'm.".$siblingLower."sort ASC, a.id ASC';";
		$this->str .= "\n\t\t}";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\t\$sqlLimit = (intval(\$limit) > 0 ? ' LIMIT '.\$limit : '');";
		$this->str .= "\n\t\t\$sqlOffset = (intval(\$offset) > 0 ? ' OFFSET '.\$offset : '');";
		
		$this->str .= "\n\t\t\$query .= ' ORDER BY ' . implode(', ', \$sortByArray) . \$sqlLimit . \$sqlOffset;";
		
		$this->str .= "\n\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->str .= "\n\t\t\$prepare->execute(\$bindings);";
		$this->str .= "\n\t\t\$list = \$prepare->fetchAll();";
		
		$this->str .= "\n\t\t";
		$this->str .= "\n\t\tforeach (\$list as \$key => \$result)";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\t\$".$siblingLower." = new ".$sibling."();";
		
		$this->CreateOutputFilter($this, $siblingLower, "\t\t\t", $this->model[$siblingLower]['col']);
		//$this->str .= "\n\t\t\tforeach (\$lst as \$key => \$val)";
		//$this->str .= "\n\t\t\t{";
		//$this->str .= "\n\t\t\t\t\$".$siblingLower."->\$key = ".$sls."\$val".$sle.";";
		//$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\t\$".$siblingLower."List[] = \$".$siblingLower.";";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t\treturn \$".$siblingLower."List;";
		
		
		$this->str .= "\n\t}";
	}
	
	// -------------------------------------------------------------
	function CreateSetAssociationsFunction($sibling)
	{
		$siblingLower = strtolower($sibling);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Creates mappings between this and all objects in the $sibling List array. Any existing mapping will become orphan(s)",
											array('array $'.$siblingLower.'List'),
											'');
		
		$this->str .= "\tpublic function Set".$sibling."List (&\$".$siblingLower."List)\n\t{";
		
		
		$this->str .= "\n\t\t\$map = new ".$this->MappingName($this->objectName, $sibling,'Map',false)."();";
		$this->str .= "\n\t\tif (is_callable(\$map->RemoveMapping))\n\t\t{\n\t\t\t\$map->RemoveMapping(\$this);\n\t\t}";
		$this->str .= "\n\t\t\$this->_".$siblingLower."List = \$".$siblingLower."List;";
		$this->str .= "\n\t}";
	}
	
	// -------------------------------------------------------------
	function CreateAddAssociationFunction($sibling)
	{
		$siblingLower = strtolower($sibling);
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Associates the $sibling object to this one",'',"");
		$this->str .= "\tpublic function Add".$sibling." (&\$".$siblingLower.")\n\t{";
		$this->str .= "\n\t\tif (\$".$siblingLower." instanceof ".$sibling.")";
		$this->str .= "\n\t\t{";
		$this->str .= "\n\t\t\tif (isset(\$".$siblingLower."->".$this->objectName."List) && is_array(\$".$siblingLower."->".$this->objectName."List) && in_array(\$this, \$".$siblingLower."->".$this->objectName."List, true))";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\treturn false;";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t\telse";
		$this->str .= "\n\t\t\t{";
		$this->str .= "\n\t\t\t\t\$found = false;";
		$this->str .= "\n\t\t\t\tforeach (\$this->_".$siblingLower."List as \$".$siblingLower."2)";
		$this->str .= "\n\t\t\t\t{";
		
		$this->str .= "\n\t\t\t\t\tif (\$".$siblingLower."->id == \$".$siblingLower."2->id)";
		$this->str .= "\n\t\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\t\$found = true;";
		$this->str .= "\n\t\t\t\t\t\tbreak;";
		$this->str .= "\n\t\t\t\t\t}";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t\tif (!\$found)";
		$this->str .= "\n\t\t\t\t{";
		$this->str .= "\n\t\t\t\t\t\$".$siblingLower."->".$this->objectName."sort = count(\$this->_".$siblingLower."List);";
		$this->str .= "\n\t\t\t\t\t\$this->_".$siblingLower."List[] = \$".$siblingLower.";";
		$this->str .= "\n\t\t\t\t}";
		$this->str .= "\n\t\t\t}";
		$this->str .= "\n\t\t}";
		$this->str .= "\n\t}";
	}
	function CreateRemoveAssociationFunction($sibling)
	{
		
		$map = $this->MappingName($this->objectName, $sibling);
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Removes the $sibling object from this one",'',"");
		$this->str .= "\tpublic function Remove".$sibling." (&\$".$sibling.")\n\t{";
		$this->str .= "\n\t\t\$map = new ".$map."();";
		$this->str .= "\n\t\tif (is_callable(\$map->RemoveMapping))\n\t\t{\n\t\t\t\$map->RemoveMapping(\$".$sibling.");\n\t\t}";
		$this->str .= "\n\t}";
	}
	
	function CreateRemoveChildFunction($child)
	{
		
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Removes the child object $child from this one",'',"");
		$this->str .= "\tpublic function Remove".$child." (&\$".$child.")\n\t{";
		$this->str .= "\n\t\t\$".$child."->".$this->objectName."id = 0;";
		$this->str .= "\n\t\treturn \$".$child."->Save();";
		$this->str .= "\n\t}";
	}
	
	function CreateRemoveParentFunction($parent)
	{
		
		
		$this->str .= "\n\t\n\t";
		$this->str .= $this->CreateComments("Removes the parent-object $parent from this one",'',"");
		$this->str .= "\tpublic function Remove".$parent." (&\$".$parent.")\n\t{";
		$this->str .= "\n\t\t\$this->".$parent."id = 0;";
		$this->str .= "\n\t\treturn \$this->Save();";
		$this->str .= "\n\t}";
	}
	
	
	// -------------------------------------------------------------
	function EndObject()
	{
		$this->str .= "\n}\n?>";
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////// Create Mapping Object Code ///////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


class ObjectMap
{
 	var $mstr;

	var $object1;
	var $object2;
	var $savePath = false;
	
	
	function MappingName($objectName1, $objectName2, $add='Map', $lower=true, $as_array=false)
	{
		$array = array($objectName1, $objectName2);
		natcasesort($array);
		$str = array_shift($array) . $array[0];
		return  ($lower ? strtolower($str) : $str) . $add;
	}
	
	
	function __construct ($projectName, $object1, $object2, $model, $save, $db, $version, $debug=false)
	{
		$this->projectName = $projectName;
		$this->debug = $debug;
		
		$this->model = $model;
		
		// enforce alphabetical Order of Object-Names
		$arr = array($object1, $object2);
		natcasesort($arr);
		
		$this->object1 = array_shift($arr);
		$this->object2 = array_shift($arr);
		
		$this->db = $db;
		$this->version = $version;
		$this->debug = $debug;
		
		$this->savePath = $save;
		
		$this->BeginObject();
		
		$this->CreateSingleton();
		
		$this->CreateSaveFunction();
		$this->CreateAddMappingFunction();
		$this->CreateRemoveMappingFunction();
		
		$this->EndObject();
		
		
		$path = $this->savePath . 'class.' . strtolower($this->object1 . $this->object2) . 'map.php';
		
		if ( !file_exists($path) || (time()-filemtime($path))>100 )
		{
			// reformat code-style according to PSR2
			if (isset($_GET['psr2']))
			{
				$this->mstr = strtr($this->mstr, array(
					//"\n\t{" => " {",
					"\n\t\t{" => " {",
					"\n\t\t\t{" => " {",
					"\n\t\t\t\t{" => " {",
					"\n\t\t\t\t\t{" => " {",
					"\n\t\t\t\t\t\t{" => " {",
					"\n\t\t\t\t\t\t\t{" => " {",
					"\t" => "    ",
				));
			}
			file_put_contents($path, $this->mstr);
			@chmod($path, 0776);
		}
		
		
	}
	
	// -------------------------------------------------------------
	function BeginObject()
	{
		
		$t1 = (($this->model[$this->object1]['inc']==1)?'string':'integer');
		$t2 = (($this->model[$this->object2]['inc']==1)?'string':'integer');
		
		
		
		$this->mstr  = "<?php\n";
		$this->mstr .= $this->CreatePreface();
		
		$this->mstr .= "\n\nnamespace ".$this->projectName.";";
		
		// we have the right alphabetical order here ;-)
		$this->mstr .= "\n\nclass ".$this->object1 . $this->object2."Map\n{";
		
		$this->mstr .="\n\t\n\t/**\n\t";
		$this->mstr .="* @const integer DB\n\t";
		$this->mstr .="*/\n\tconst DB = $this->db;";
		
		$this->mstr .="\n\t\n\t/**\n\t";
		$this->mstr .="* @var object _INSTANCE_\n\t";
		$this->mstr .="* @access private\n\t";
		$this->mstr .="*/\n\t";
		$this->mstr .="static private \$_INSTANCE_ = null;";

		
		$this->mstr .="\n\t\n\t/**\n\t";
		$this->mstr .="* @var $t1 ".$this->object1."id\n\t";
		$this->mstr .="*/\n\tpublic \$".$this->object1."id = '';";
		
		$this->mstr .="\n\t\n\t/**\n\t";
		$this->mstr .="* @var $t2 ".$this->object2."id\n\t";
		$this->mstr .="*/\n\tpublic \$".$this->object2."id = '';";
		
		$this->mstr .="\n\t\n\t/**\n\t";
		$this->mstr .="* @var integer ".$this->object1."sort\n\t";
		$this->mstr .="*/\n\tpublic \$".$this->object1."sort = 0;";
		
		$this->mstr .="\n\t\n\t/**\n\t";
		$this->mstr .="* @var integer ".$this->object2."sort\n\t";
		$this->mstr .="*/\n\tpublic \$".$this->object2."sort = 0;";
		
	}
	
	function CreateSingleton()
	{
		$this->mstr .= "\n\t\n\t";
		$this->mstr .= $this->CreateComments("Singleton-Function to define the Object ".$this->object1 . $this->object2."Map only once",
											array(),
											"object ".$this->object1 . $this->object2."Map");
		$this->mstr .= "\tpublic static function instance ()\n\t{";
		$this->mstr .= "\n\t\tif (!isset(self::\$_INSTANCE_))\n\t\t{";
		$this->mstr .= "\n\t\t\tself::\$_INSTANCE_ = new ".$this->object1 . $this->object2."Map();";
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t\treturn self::\$_INSTANCE_;";
		$this->mstr .= "\n\t}";
	}
	
	// -------------------------------------------------------------
	function CreatePreface()
	{
		$this->mstr .= "/*\n*\t class for Mapping Objects '".$this->object1."' and '".$this->object2."' with integrated CRUD methods.";
		$this->mstr .= "\n*\t @author " . $GLOBALS['cconfiguration']['author'];
		$this->mstr .= "\n*\t @version " . $this->version;
		$this->mstr .= "\n*\t @copyright " . $GLOBALS['cconfiguration']['copyright'];
		$this->mstr .= "\n*\t @link " . $GLOBALS['cconfiguration']['link'];
		$this->mstr .= "\n*\t @package " . $this->projectName;
		$this->mstr .= "\n*/\n";
	}
	
	
	function CreateComments($description='', $parameterDescriptionArray='', $returnType='')
	{
		
		$this->mstr .= "/**\n\t* ".$description."\n\t*\n";
		
 		if ($parameterDescriptionArray != '')
 		{
	 		foreach ($parameterDescriptionArray as $parameter)
	 		{
	 			$this->mstr .= "\t* @param ".$parameter."\n";
	 		}
 		}
 		
 		if ($returnType != '')
 		{
			$this->mstr .= "\t* @return ".$returnType."\n";
		}
		
	    $this->mstr .= "\t*/\n";
	}
	
	
	
	
	// -------------------------------------------------------------
	function CreateSaveFunction()
	{
		
		$o1 = strtolower($this->object1);
		$o2 = strtolower($this->object2);
		$on = implode('', array($o1, $o2));
		
		
		$this->mstr .= "\n\t\n\t";
		$this->mstr .= $this->CreateComments("Physically saves the Mapping to the Database", '', '');
		
		$this->mstr .= "\tpublic function Save()\n\t{";
		
		
		
		$this->mstr .= "\n\t\t\$query = 'SELECT `".$o1."id` FROM `".$on."map` WHERE `".$o1."id`=:".$o1."id AND `".$o2."id`=:".$o2."id LIMIT 1';";
		
		$this->mstr .= "\n\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->mstr .= "\n\t\t\$prepare->execute(array(':".$o1."id'=>\$this->".$o1."id, ':".$o2."id'=>\$this->".$o2."id));";
		$this->mstr .= "\n\t\t\$result = \$prepare->fetchAll();\n\t\t";
		
		$this->mstr .= "\n\t\tif (count(\$result) === 0)";
		$this->mstr .= "\n\t\t{";
		$this->mstr .= "\n\t\t\t\$query = 'INSERT INTO `".$on."map` (`".$o1."id`, `".$o2."id`, `".$o1."sort`, `".$o2."sort`) VALUES (:".$o1."id, :".$o2."id, :".$o1."sort, :".$o2."sort)';";
		
		$this->mstr .= "\n\t\t\t";
		$this->mstr .= "\n\t\t\ttry";
		$this->mstr .= "\n\t\t\t{";
		
		$this->mstr .= "\n\t\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->mstr .= "\n\t\t\t\t\$prepare->execute(array(':".$o1."id'=>\$this->".$o1."id, ':".$o2."id'=>\$this->".$o2."id,':".$o1."sort'=>\$this->".$o1."sort, ':".$o2."sort'=>\$this->".$o2."sort));";
		
		
		$this->mstr .= "\n\t\t\t}";
		$this->mstr .= "\n\t\t\tcatch(Exception \$e)";
		$this->mstr .= "\n\t\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$on."map:[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->mstr .= "\n\t\t\t\treturn false;";
		$this->mstr .= "\n\t\t\t}";
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t}";
	}
	
	// -------------------------------------------------------------
	function CreateAddMappingFunction()
	{
		
		$o1 = strtolower($this->object1);
		$o2 = strtolower($this->object2);
		
		$this->mstr .= "\n\t\n\t";
		$this->mstr .= $this->CreateComments("Creates a Mapping between the two Objects", 
											array(	
													"object $this->object1 \$object", 
													"object $this->object2 \$otherObject"
												),
											'object Save or false');
		$this->mstr .= "\tpublic function AddMapping(\$object, \$otherObject)\n\t{";
		$this->mstr .= "\n\t\tif (\$object instanceof ".$this->object1." && \$object->id != '')";
		$this->mstr .= "\n\t\t{";
		$this->mstr .= "\n\t\t\t\$this->".$o1."id = \$object->id;";
		$this->mstr .= "\n\t\t\t\$this->".$o2."id = \$otherObject->id;";
		$this->mstr .= "\n\t\t\t@\$this->".$o1."sort = intval(\$object->".$o2."sort);";
		$this->mstr .= "\n\t\t\t@\$this->".$o2."sort = intval(\$otherObject->".$o1."sort);";
		$this->mstr .= "\n\t\t\treturn \$this->Save();";
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t\telse if (\$object instanceof ".$this->object2." && \$object->id != '')";
		$this->mstr .= "\n\t\t{";
		$this->mstr .= "\n\t\t\t\$this->".$o2."id = \$object->id;";
		$this->mstr .= "\n\t\t\t\$this->".$o1."id = \$otherObject->id;";
		$this->mstr .= "\n\t\t\t@\$this->".$o2."sort = intval(\$object->".$o1."sort);";
		$this->mstr .= "\n\t\t\t@\$this->".$o1."sort = intval(\$otherObject->".$o2."sort);";
		$this->mstr .= "\n\t\t\treturn \$this->Save();";
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t\telse";
		$this->mstr .= "\n\t\t{";
		$this->mstr .= "\n\t\t\treturn false;";
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t}";
	}

	// -------------------------------------------------------------
	function CreateRemoveMappingFunction()
	{
		
		// already sorted
		$o1 = strtolower($this->object1);
		$o2 = strtolower($this->object2);
		$on = $o1.$o2;
		
		
		$this->mstr .= "\n\t\n\t";
		$this->mstr .= $this->CreateComments("Removes the Mapping between the two Objects", 
											array("object \$object", "object \$otherObject"),
											'');
		
		$this->mstr .= "\tpublic function RemoveMapping (\$object, \$otherObject = null)\n\t{";
		
		$this->mstr .= "\n\t\tif (\$object instanceof ".$this->object1.")";
		$this->mstr .= "\n\t\t{";
		$this->mstr .= "\n\t\t\t\$query = 'DELETE FROM `".$on."map` WHERE `".$o1."id` = ?';";
		$this->mstr .= "\n\t\t\t\$bindings = array(\$object->id);";
		
		$this->mstr .= "\n\t\t\tif (\$otherObject != null && \$otherObject instanceof ".$this->object2.")";
		$this->mstr .= "\n\t\t\t{";
		$this->mstr .= "\n\t\t\t\t\$query .= ' AND `".$o2."id` = ?';";
		$this->mstr .= "\n\t\t\t\t\$bindings[] = \$otherObject->id;";
		$this->mstr .= "\n\t\t\t}";
		
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t\telse if (\$object instanceof ".$this->object2.")";
		$this->mstr .= "\n\t\t{";
		$this->mstr .= "\n\t\t\t\$query = 'DELETE FROM `".$on."map` WHERE `".$o2."id` = ?';";
		$this->mstr .= "\n\t\t\t\$bindings = array(\$object->id);";
		
		$this->mstr .= "\n\t\t\tif (\$otherObject != null && \$otherObject instanceof ".$this->object1.")";
		$this->mstr .= "\n\t\t\t{";
		$this->mstr .= "\n\t\t\t\t\$query .= ' AND `".$o1."id` = ?';";
		$this->mstr .= "\n\t\t\t\t\$bindings[] = \$otherObject->id;";
		$this->mstr .= "\n\t\t\t}";
		
		$this->mstr .= "\n\t\t}";
		
		
		$this->mstr .= "\n\t\t";
		$this->mstr .= "\n\t\ttry";
		$this->mstr .= "\n\t\t{";
		
		$this->mstr .= "\n\t\t\t\$prepare = DB::instance($this->db)->prepare(\$query);";
		$this->mstr .= "\n\t\t\t\$prepare->execute(\$bindings);";
		
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t\tcatch (Exception \$e)";
		$this->mstr .= "\n\t\t{";
		if($this->debug){ $this->str .= "\n\t\t\ttrigger_error('ERROR in ".$on."map:[['.\$e->getMessage().']]', E_USER_ERROR);\n"; }
		$this->mstr .= "\n\t\t\treturn false;";
		$this->mstr .= "\n\t\t}";
		$this->mstr .= "\n\t\t";
		$this->mstr .= "\n\t}";
	}

	
	// -------------------------------------------------------------
	function EndObject()
	{
		$this->mstr .= "\n}\n?>\n";
	}
	
	
}

?>
