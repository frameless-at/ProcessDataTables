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
	 * Directory path for output templates
	 *
	 * @var string
	 */
	protected $templateDir;

	/**
	 * ProcessWire Fields API
	 *
	 * @var Fields
	 */
	protected $fields;

	/**
	 * Constructor
	 *
	 * @param string $templateDir
	 * @param Fields $fields
	 */
	public function __construct($templateDir) {
		$this->templateDir = $templateDir;
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
		$templateFile = $this->templateDir . "{$fieldName}.table-output.php";
	
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
	 
public function createTemplateFile(string $templateName, string $realFieldName = null) {
		$filePath = $this->templateDir . $templateName . '.table-output.php';
	
		// 1) If the file is already there, bail out early
		if (is_file($filePath)) {
			// optionally log:
			// wire('log')->save('ProcessDataTable', "Skipped existing template: {$templateName}");
			return;
		}
	
		// 2) Otherwise, ensure directory exists...
		if (!is_dir($this->templateDir)) {
			mkdir($this->templateDir, 0777, true);
			wire('log')->save('ProcessDataTable', "Re-created template directory: {$this->templateDir}");
		}
	
		// 3) Type‐detection logic (meta → WireData, realFieldName → lookup)
		$lookupName = $realFieldName ?: $templateName;
		if ($lookupName === 'meta') {
			$typeClass = 'WireData';
		} else {
			$field     = wire('fields')->get($lookupName);
			$typeClass = $field ? get_class($field->type) : 'unknown';
			$shortType = preg_replace('/^ProcessWire\\\\/', '', $typeClass);
		}
	
		// 4) Build & write out the stub
		$templateContent = $this->getTemplateContent($typeClass, $templateName);
		file_put_contents($filePath, $templateContent);
		chmod($filePath, 0777);
		wire('log')->save('ProcessDataTable', "Wrote template file: {$filePath}");
	}

	
	/**
	 * Render the template file for a specific field
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return string
	 */
	 public function renderTemplateFile($fieldName, $value) {
		 $filePath = $this->templateDir . $fieldName . '.table-output.php';
	 
		 // If the file does not exist, attempt to create it
		 if (!file_exists($filePath)) {
			 wire('log')->save('ProcessDataTable', "Template not found. Attempting to create: $filePath");
			 $this->createTemplateFile($fieldName);
		 }
	 
		 // Check again after attempting to create
		 if (!file_exists($filePath)) {
			 wire('log')->save('ProcessDataTable', "Failed to create template: $filePath");
			 return htmlentities($value); // Fallback: raw output
		 }
	 
		 // Capture output
		 ob_start();
		 include $filePath;
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
	 
	 /**
	  * Get the default template content for a given field or property.
	  *
	  * @param string $fieldType Fieldtype or "property"
	  * @param string $fieldName Field or property name
	  * @return string
	  */
	protected function getTemplateContent($fieldType, $fieldName) {
		$template = "<?php\n";
		$template .= "/**\n";
		$template .= " * Output template for field: $fieldName\n";
		$template .= " * Fieldtype: $fieldType\n";
		$template .= " * Available variable: \$value\n";
		$template .= " */\n\n";

 // Handle properties first
		$propertyTemplates = [
			'created' => "// Format created date\n" .
						 "echo date('Y-m-d H:i:s', \$value);\n",
		
			'modified' => "// Format modified date\n" .
						  "echo date('Y-m-d H:i:s', \$value);\n",
		
			'name' => "// Output page name\n" .
					  "echo htmlspecialchars(\$value);\n",
		
			'id' => "// Output page ID\n" .
					"echo (int)\$value;\n",
		
			'parent' => "// Link to parent page\n" .
						"if(\$value instanceof Page) {\n" .
						"    echo '<a href=\"' . \$value->url . '\">' . htmlspecialchars(\$value->title) . '</a>';\n" .
						"}\n",
		
			'url' => "// Output URL as link\n" .
					 "echo '<a href=\"' . \$value . '\">' . htmlspecialchars(\$value) . '</a>';\n"
		];
		
		if (isset($propertyTemplates[$fieldName])) {
			$template .= $propertyTemplates[$fieldName];
			return $template;
		}
		
		switch($fieldType) {
		
			// Textual fields
			case 'FieldtypeText':
			case 'FieldtypeTextarea':
			case 'FieldtypeMarkupAdmin':
			case 'FieldtypeTextareaBasic':
				$template .= "// Text output\n";
				$template .= "echo htmlspecialchars(\$value);\n";
				break;
		
			// Numeric fields
			case 'FieldtypeInteger':
			case 'FieldtypeFloat':
			case 'FieldtypeFieldtypeDecimal':  // if you have a decimal type
				$template .= "// Number output\n";
				$template .= "echo \$value;\n";
				break;
		
			// Boolean
			case 'FieldtypeCheckbox':
			case 'FieldtypeToggle':
				$template .= "// Yes/No\n";
				$template .= "echo \$value ? 'Yes' : 'No';\n";
				break;
		
			// Single‐selection fields
			case 'FieldtypeOptions':
			case 'FieldtypeAsmSelect':
			case 'FieldtypeSelect':
				$template .= "// Multiple or single select (comma-separated)\n";
				$template .= "if(is_array(\$value)) {\n";
				$template .= "    echo implode(', ', array_map('htmlspecialchars', \$value));\n";
				$template .= "} else {\n";
				$template .= "    echo htmlspecialchars(\$value);\n";
				$template .= "}\n";
				break;
		
			// Page reference(s)
			case 'FieldtypePage':
			case 'FieldtypePageReference':  // alias in some versions
				$template .= "// Linked page(s)\n";
				$template .= "if(\$value instanceof PageArray || is_array(\$value)) {\n";
				$template .= "    foreach(\$value as \$p) echo '<a href=\"' . \$p->url . '\">' . htmlspecialchars(\$p->title) . '</a><br>';\n";
				$template .= "} elseif(\$value instanceof Page) {\n";
				$template .= "    echo '<a href=\"' . \$value->url . '\">' . htmlspecialchars(\$value->title) . '</a>';\n";
				$template .= "} else {\n";
				$template .= "    echo htmlspecialchars(\$value);\n";
				$template .= "}\n";
				break;
		
			// Files
			case 'FieldtypeImage':
			case 'FieldtypeFile':
				$template .= "// File or image output\n";
				$template .= "if(\$value && \$value->count()) {\n";
				$template .= "    foreach(\$value as \$file) {\n";
				$template .= "        echo '<a href=\"' . \$file->url . '\">';\n";
				$template .= "        if(\$file->width) echo '<img src=\"' . \$file->url . '\" style=\"max-width:100px\">';\n";
				$template .= "        else echo htmlspecialchars(\$file->name);\n";
				$template .= "        echo '</a> ';\n";
				$template .= "    }\n";
				$template .= "}\n";
				break;
		
			// Dates and times
			case 'FieldtypeDate':
			case 'FieldtypeDatetime':
			case 'FieldtypeTime':
				$template .= "// Date/Time formatting\n";
				$template .= "if(\$value) echo \$value ? date('Y-m-d H:i:s', \$value) : '';\n";
				break;
		
			// Email & URL
			case 'FieldtypeEmail':
				$template .= "// Email link\n";
				$template .= "echo '<a href=\"mailto:' . htmlspecialchars(\$value) . '\">' . htmlspecialchars(\$value) . '</a>';\n";
				break;
			case 'FieldtypeUrl':
				$template .= "// External link\n";
				$template .= "if(\$value) echo '<a href=\"' . htmlspecialchars(\$value) . '\" target=\"_blank\">' . htmlspecialchars(\$value) . '</a>';\n";
				break;
		
			// Location / LatLng (if using FieldtypeLocation)
			case 'FieldtypeLocation':
				$template .= "// Location (latitude, longitude)\n";
				$template .= "if(\$value) echo \$value->lat . ', ' . \$value->lng;\n";
				break;
		
			// Repeater / PageTable
			case 'FieldtypeRepeater':
			case 'FieldtypeRepeaterMatrix':
			case 'FieldtypePageTable':
				$template .= "// Repeater / Table: count and link\n";
				$template .= "echo \$value->count() . ' items';\n";
				break;
			case 'WireData':
				// Meta or other WireData-backed values → pretty JSON dump
				$template .= "// WireData / metadata: render as JSON\n";
				$template .= "echo '<pre style=\"white-space:pre-wrap;font-size:0.9em;margin:0;\">'\n";
				$template .= "   . htmlentities(json_encode((array)\$value->getArray(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))\n";
				$template .= "   . '</pre>';\n";
				break;
			// Default fallback
			default:
				$template .= "// Default rendering\n";
				$template .= "echo htmlspecialchars(\$value);\n";
				break;
		}
		
		return $template;
	}
}