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
	 
/**
	 * Create or overwrite a single template file
	 *
	 * @param string $fieldName
	 */
	public function createTemplateFile($fieldName) {
		$filePath = $this->templateDir . $fieldName . '.table-output.php';
	
		// Make absolutely sure the directory exists
		if (!is_dir($this->templateDir)) {
			mkdir($this->templateDir, 0777, true);
			wire('log')->save('ProcessDataTable', "Re-created template directory: {$this->templateDir}");
		}
	
		// Get the right fieldtype (or "unknown")
		$field     = wire('fields')->get($fieldName);
		$fieldType = $field ? $field->type->name : 'unknown';
	
		// Build the content and write (always overwrite)
		$templateContent = $this->getTemplateContent($fieldType, $fieldName);
		$bytesWritten    = file_put_contents($filePath, $templateContent);
	
		if ($bytesWritten === false) {
			wire('log')->save('ProcessDataTable', "Failed writing template file: {$filePath}");
		} else {
			chmod($filePath, 0777);
			wire('log')->save('ProcessDataTable', "Wrote template file ({$bytesWritten} bytes): {$filePath}");
		}
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
		
		switch ($fieldType) {
			case 'FieldtypeText':
			case 'FieldtypeTextarea':
				$template .= "// Example: output simple value\n";
				$template .= "echo \$value;\n";
				break;
			case 'FieldtypeEmail':
				$template .= "// Example: Link to user's edit page based on email\n";
				$template .= "\$user = wire('users')->get('email=' . \$value);\n";
				$template .= "if (\$user->id) {\n";
				$template .= "    \$editUrl = wire('config')->urls->admin . 'access/users/edit/?id=' . \$user->id;\n";
				$template .= "    echo '<a href=\"' . \$editUrl . '\">' . htmlspecialchars(\$value) . '</a>';\n";
				$template .= "} else {\n";
				$template .= "    echo htmlspecialchars(\$value);\n";
				$template .= "}\n";
				break;
			case 'FieldtypePage':
				$template .= "// Example: Output linked page title\n";
				$template .= "echo \$value->each(\"{title|name}<br>\");\n";
				break;

			case 'FieldtypeDate':
				$template .= "// Example: Format date\n";
				$template .= "echo date('Y-m-d H:i:s', \$value);\n";
				break;

			case 'FieldtypeImage':
				$template .= "// Example: Display image thumbnail\n";
				$template .= "if (\$value->count) {\n";
				$template .= "    echo '<img src=\"' . \$value->first()->url . '\" width=\"100\">';\n";
				$template .= "}\n";
				break;

			case 'FieldtypeCheckbox':		
			case 'FieldtypeToggle':
				$template .= "// Example: output as 'Yes' or 'No'\n";
				$template .= "echo \$value ? 'Yes' : 'No';\n";
			break;
				
			case 'FieldtypeTable':
				$template .= "// Example: Output the number of rows, leave empty if 0\n";
				$template .= "echo \$value->count() > 0 ? \$value->count() : '';\n";
				break;

			default:
				$template .= "// Default output\n";
				$template .= "echo htmlspecialchars(\$value);\n";
				break;
		}

		return $template;
	}
}