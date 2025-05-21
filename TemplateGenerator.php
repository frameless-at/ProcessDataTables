<?php

/**
 * TemplateGenerator
 *
 * Handles the creation of output template files for selected fields for the ProcessDataTable module.
 *
 * @author frameless Media
 * @version 0.4.0-RC
 * @license MIT
 */

class TemplateGenerator {
	/** 
 	* File suffix for generated column templates 
 	* @var string 
 	*/
	const TEMPLATE_SUFFIX = '.column.php';

	/**
	 * Directory path for output templates
	 *
	 * @var string
	 */
	protected $templateDir;

	/**
	 * Constructor
	 *
	 * @param string $templateDir
	 */
	public function __construct($templateDir) {
		$this->templateDir = rtrim($templateDir, '/') . '/';
		$this->ensureTemplateDir();
	}

	/**
	 * Ensure that the output template directory exists
	 */
	public function ensureTemplateDir() {
		if (!is_dir($this->templateDir)) {
			mkdir($this->templateDir, 0777, true);
		}
	}

	/**
	 * Delete a template file for a specific field
	 *
	 * @param string $fieldName
	 */
	public function deleteTemplateFile($fieldName) {
		$templateFile = $this->templateDir . $fieldName . self::TEMPLATE_SUFFIX;
	
		if (file_exists($templateFile)) {
	
			// Schreibrechte setzen, bevor die Datei gelöscht wird
			if (!is_writable($templateFile)) {
				chmod($templateFile, 0777);
			}
	
			if (unlink($templateFile)) {
				wire('log')->save('ProcessDataTable', "Template gelöscht: $templateFile");
			} else {
				wire('log')->save('ProcessDataTable', "Fehler beim Löschen des Templates: $templateFile");
			}
		} else {
			wire('log')->save('ProcessDataTable', "Template existiert nicht und konnte nicht gelöscht werden: $templateFile");
		}
	}
	 
/**
	 * Create or overwrite a single-column stub.
	 *
	 * @param string      $label          The human-readable column label (used for filename)
	 * @param string|null $realFieldName  The actual field/property name for type detection
	 */
	public function createTemplateFile(string $label, string $realFieldName = null) {
		// 1) Determine safe filename: slugify the label
		$slug = preg_replace('/[^a-z0-9]+/i','_', trim(strtolower($label)));
		$slug = trim($slug, '_');
		$file = $this->templateDir . $slug . self::TEMPLATE_SUFFIX;	
			
		// 2) Bail early if it already exists
		if (is_file($file)) return;
	
		// 3) Ensure directory
		if (!is_dir($this->templateDir)) {
			mkdir($this->templateDir, 0755, true);
			wire('log')->save('ProcessDataTable', "Created template directory: {$this->templateDir}");
		}
	
		// 4) Decide which category this is for stub content
		$real = $realFieldName ?: $slug; 
	
		if ($real === 'meta') {
			$typeClass = 'WireData';
		} elseif (in_array($real, ['id','name','created','modified','parent','url'], true)) {
			$typeClass = 'PageProperty';
		} elseif ($field = wire('fields')->get($real)) {
			$typeClass = $field->type->className; 
		} else {
			// invalid field/property, skip stub
			return;
		}
	
		// 5) Generate the stub
		$content = $this->getTemplateContent($typeClass, $real);
	
		// 6) Write it out
		file_put_contents($file, $content);
		chmod($file, 0664);
		wire('log')->save('ProcessDataTable', "Wrote template file: {$file}");
	}
			
	/**
	 * Render the template file for a specific field
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return string
	 */
	 public function renderTemplateFile(string $label, $value) {
		 // 1) aus Label den Slug bilden (wie in createTemplateFile)
		 $slug = preg_replace('/[^a-z0-9]+/i','_', strtolower($label));
		 $slug = trim($slug, '_');
		 $file = $this->templateDir . $slug . self::TEMPLATE_SUFFIX;
	 
		 // 2) falls nicht existent, versuchen zu erzeugen
		 if (!file_exists($file)) {
			 wire('log')->save('ProcessDataTable', "Template not found, creating: $file");
			 // Da createTemplateFile slugify auf Label macht, kann Label direkt übergeben werden:
			 $this->createTemplateFile($label);
		 }
	 
		 // 3) nach erneutem Check
		 if (!file_exists($file)) {
			 wire('log')->save('ProcessDataTable', "Failed to create template: $file");
			 return htmlentities($value);
		 }
	 
		 // 4) rendern
		 ob_start();
		 include $file;
		 return ob_get_clean();
	 }
	 
	 /**
	  * Delete the entire template directory and its contents
	  */
	 public function deleteTemplateDirectory() {
		 if (!is_dir($this->templateDir)) {
			 wire('log')->save('ProcessDataTable', "Template directory does not exist: " . $this->templateDir);
			 return;
		 }
	 
		 $files = glob($this->templateDir . '*');
	 
		 foreach ($files as $file) {
			 if (is_file($file)) {
				 unlink($file);
				 wire('log')->save('ProcessDataTable', "Template file deleted: $file");
			 }
		 }
	 
		 // Remove the directory itself
		 if (rmdir($this->templateDir)) {
			 wire('log')->save('ProcessDataTable', "Template directory deleted: " . $this->templateDir);
		 } else {
			 wire('log')->save('ProcessDataTable', "Failed to delete template directory: " . $this->templateDir);
		 }
	 }
	 
	   /* Build the PHP stub for a single column.
	   *
	   * @param string $fieldType   One of: "PageProperty", "WireData", or a Fieldtype short name
	   * @param string $fieldName   The field or property name (for reference in comments)
	   * @return string             Full PHP file contents
	   */
	  protected function getTemplateContent(string $fieldType, string $fieldName) {
		  // 1) File header and docblock
		  $php  = "<?php\n";
		  $php .= "/**\n";
		  $php .= " * Output template for field: {$fieldName}\n";
		  $php .= " * Fieldtype: {$fieldType}\n";
		  $php .= " * Available variable: \$value\n";
		  $php .= " */\n\n";
	  
		  // 2) Handle standard page properties explicitly
		  $propertyStubs = [
			  'created'  => "// Format created timestamp\n" .
							"echo date('Y-m-d H:i:s', \$value);\n",
	  
			  'modified' => "// Format modified timestamp\n" .
							"echo date('Y-m-d H:i:s', \$value);\n",
	  
			  'name'     => "// Page title\n" .
							"echo htmlspecialchars(\$value);\n",
	  
			  'id'       => "// Page ID\n" .
							"echo (int) \$value;\n",
	  
			  'parent'   => "// Parent link\n" .
							"if(\$value instanceof Page) {\n" .
							"    echo '<a href=\"'.\$value->url.'\">'.htmlspecialchars(\$value->title).'</a>';\n" .
							"}\n",
	  
			  'url'      => "// URL link\n" .
							"echo '<a href=\"'.\$value.'\">'.htmlspecialchars(\$value).'</a>';\n",
		  ];
	  
		  if (isset($propertyStubs[$fieldName])) {
			  // Return header + that stub
			  return $php . $propertyStubs[$fieldName];
		  }
	  
		  // 3) Switch on fieldType for all other cases
		  switch($fieldType) {
	  
			  case 'WireData':
				  $php .= "// Render WireData as JSON\n";
				  $php .= "echo '<pre style=\"white-space:pre-wrap;font-size:0.9em;margin:0;\">'\n"
						. "   . htmlentities(json_encode((array)\$value->getArray(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))\n"
						. "   . '</pre>';\n";
				  break;
	  
			  case 'FieldtypeText':
			  case 'FieldtypeTextarea':
			  case 'FieldtypeTextareaBasic':
			  case 'FieldtypeMarkupAdmin':
				  $php .= "// Textual output\n";
				  $php .= "echo htmlspecialchars(\$value);\n";
				  break;
	  
			  case 'FieldtypeInteger':
			  case 'FieldtypeFloat':
			  case 'FieldtypeDecimal':
				  $php .= "// Numeric output\n";
				  $php .= "echo \$value;\n";
				  break;
	  
			  case 'FieldtypeCheckbox':
			  case 'FieldtypeToggle':
				  $php .= "// Boolean\n";
				  $php .= "echo \$value ? 'Yes' : 'No';\n";
				  break;
	  
			  case 'FieldtypeOptions':
			  case 'FieldtypeSelect':
			  case 'FieldtypeAsmSelect':
				  $php .= "// Select (single or multiple)\n";
				  $php .= "if(is_array(\$value)) {\n";
				  $php .= "    echo implode(', ', array_map('htmlspecialchars', \$value));\n";
				  $php .= "} else {\n";
				  $php .= "    echo htmlspecialchars(\$value);\n";
				  $php .= "}\n";
				  break;
	  
			  case 'FieldtypePage':
			  case 'FieldtypePageReference':
				  $php .= "// Page reference(s)\n";
				  $php .= "if(\$value instanceof PageArray || is_array(\$value)) {\n";
				  $php .= "    foreach(\$value as \$p) echo '<a href=\"'.\$p->url.'\">'.htmlspecialchars(\$p->title).'</a><br>';\n";
				  $php .= "} elseif(\$value instanceof Page) {\n";
				  $php .= "    echo '<a href=\"'.\$value->url.'\">'.htmlspecialchars(\$value->title).'</a>';\n";
				  $php .= "} else {\n";
				  $php .= "    echo htmlspecialchars(\$value);\n";
				  $php .= "}\n";
				  break;
	  
			  case 'FieldtypeFile':
			  case 'FieldtypeImage':
				  $php .= "// File/Image output\n";
				  $php .= "if(\$value && \$value->count()) {\n";
				  $php .= "    foreach(\$value as \$file) {\n";
				  $php .= "        echo '<a href=\"'.\$file->url.'\">';\n";
				  $php .= "        if(\$file->width) echo '<img src=\"'.\$file->url.'\" style=\"max-width:100px\">';\n";
				  $php .= "        else echo htmlspecialchars(\$file->name);\n";
				  $php .= "        echo '</a> ';\n";
				  $php .= "    }\n";
				  $php .= "}\n";
				  break;
	  
			  case 'FieldtypeDate':
			  case 'FieldtypeDatetime':
			  case 'FieldtypeTime':
				  $php .= "// Date/time formatting\n";
				  $php .= "if(\$value) echo date('Y-m-d H:i:s', \$value);\n";
				  break;
	  
			  case 'FieldtypeEmail':
				  $php .= "// Email link\n";
				  $php .= "echo '<a href=\"mailto:'.htmlspecialchars(\$value).'\">'.htmlspecialchars(\$value).'</a>';\n";
				  break;
	  
			  case 'FieldtypeUrl':
				  $php .= "// External link\n";
				  $php .= "if(\$value) echo '<a href=\"'.htmlspecialchars(\$value).'\" target=\"_blank\">'.htmlspecialchars(\$value).'</a>';\n";
				  break;
	  
			  case 'FieldtypeRepeater':
			  case 'FieldtypeRepeaterMatrix':
			  case 'FieldtypePageTable':
				  $php .= "// Repeater/PageTable count\n";
				  $php .= "echo \$value->count() . ' items';\n";
				  break;
	  
			  default:
				  $php .= "// Fallback\n";
				  $php .= "echo htmlspecialchars((string)\$value);\n";
				  break;
		  }
	  
		  return $php;
	  }
}