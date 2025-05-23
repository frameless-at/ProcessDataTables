<?php

/**
 * TemplateGenerator for the Module ProccessDataTable
 *
 * Handles the creation of output template files for selected fields for each DataTable.
 *
 * @author frameless Media
 * @version 0.3.0
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
	 * Directory path for default output templates
	 *
	 * @var string
	 */
	protected $fieldtypeStubDir;
	
	/**
	 * Global Configuration from module
	 * @var array
	 */
	protected $config = [];
	
	/**
	 * Aktualisierter Konstruktor
	 */
	 public function __construct($templateDir, $config = []) {
		$this->templateDir = rtrim($templateDir, '/') . '/';
		$this->fieldtypeStubDir = __DIR__ . '/fieldtype_templates/';
		$this->ensureTemplateDir();
		$this->config = $config;
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
				wire('log')->save('ProcessDataTables', "Template gelöscht: $templateFile");
			} else {
				wire('log')->save('ProcessDataTables', "Fehler beim Löschen des Templates: $templateFile");
			}
		} else {
			wire('log')->save('ProcessDataTables', "Template existiert nicht und konnte nicht gelöscht werden: $templateFile");
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
			wire('log')->save('ProcessDataTables', "Created template directory: {$this->templateDir}");
		}
	
		// 4) Decide which category this is for stub content
		$real = $realFieldName ?: $slug;
		
		// Fieldtype ermitteln
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
		
		// 5) Prüfe, ob es für diesen Fieldtype ein Standard-Template gibt
		$fieldtypeStub = $this->fieldtypeStubDir . $typeClass . self::TEMPLATE_SUFFIX;
		
		if (is_file($fieldtypeStub)) {
			// Kopiere das Fieldtype-Stubb als Basis
			$stub    = file_get_contents($fieldtypeStub);
			$content = strtr($stub, [
				'{{FIELDNAME}}' => $realFieldName,
				'{{LABEL}}'     => $label,
			]);
			file_put_contents($file, $content);
			chmod($file, 0664);
			wire('log')->save('ProcessDataTables', "Copied fieldtype stub: {$fieldtypeStub} -> {$file}");
		} else {
			// 6) Fallback: Generate the stub wie bisher
			$content = $this->getTemplateContent($typeClass, $realFieldName, $label);
			file_put_contents($file, $content);
			chmod($file, 0664);
			wire('log')->save('ProcessDataTables', "Wrote template file: {$file}");
		}
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
			 wire('log')->save('ProcessDataTables', "Template not found, creating: $file");
			 // Da createTemplateFile slugify auf Label macht, kann Label direkt übergeben werden:
			 $this->createTemplateFile($label);
		 }
	 
		 // 3) nach erneutem Check
		 if (!file_exists($file)) {
			 wire('log')->save('ProcessDataTables', "Failed to create template: $file");
			 return htmlentities($value);
		 }
	 
		 // 4) rendern
		 $config = $this->config;
		 ob_start();
		 include $file;
		 return ob_get_clean();
	 }
	 
	 /**
	  * Delete the entire template directory and its contents
	  */
	 public function deleteTemplateDirectory() {
		 if (!is_dir($this->templateDir)) {
			 wire('log')->save('ProcessDataTables', "Template directory does not exist: " . $this->templateDir);
			 return;
		 }
	 
		 $files = glob($this->templateDir . '*');
	 
		 foreach ($files as $file) {
			 if (is_file($file)) {
				 unlink($file);
				 wire('log')->save('ProcessDataTables', "Template file deleted: $file");
			 }
		 }
	 
		 // Remove the directory itself
		 if (rmdir($this->templateDir)) {
			 wire('log')->save('ProcessDataTables', "Template directory deleted: " . $this->templateDir);
		 } else {
			 wire('log')->save('ProcessDataTables', "Failed to delete template directory: " . $this->templateDir);
		 }
	 }
	 
	 /**
	  * Liefert den PHP-Stub für ein Property oder ein Fallback-Stubb für andere Typen.
	  *
	  * @param string $fieldType
	  * @param string $fieldName
	  * @param string|null $columnLabel
	  * @return string PHP code für die Stub-Datei
	  */
	 protected function getTemplateContent(string $fieldType, string $fieldName, string $columnLabel = null) {
		 // 1) Header mit Platzhaltern (für spätere Automatisierung)
		 $php  = "<?php\n";
		 $php .= "/**\n";
		 $php .= " * Output template for field: {$fieldName}\n";
		 if($columnLabel) $php .= " * Column label: {$columnLabel}\n";
		 $php .= " * Fieldtype: {$fieldType}\n";
		 $php .= " * Available variable: \$value\n";
		 $php .= " */\n\n";
	 
		 // 2) Properties: DRY-Mapping
		 $propertyMap = [
			 'created'  => "echo date('Y-m-d H:i', \$value); // Created timestamp",
			 'modified' => "echo date('Y-m-d H:i', \$value); // Modified timestamp",
			 'id'       => "echo (int) \$value; // Page ID",
			 'name'     => "echo htmlspecialchars(\$value); // Page name",
			 'parent'   => "if(\$value instanceof Page) echo '<a href=\"'.\$value->url.'\">'.htmlspecialchars(\$value->title).'</a>'; // Parent Page link",
			 'url'      => "echo '<a href=\"'.\$value.'\">'.htmlspecialchars(\$value).'</a>'; // Page URL link",
		 ];
		 if(array_key_exists($fieldName, $propertyMap)) {
			 return $php . $propertyMap[$fieldName] . "\n";
		 }
	 
		 // 3) Allgemeines Fallback-Template (immer sicher und neutral!)
		 $php .= "// General fallback: outputs value as text\n";
		 $php .= "echo htmlspecialchars((string)\$value);\n";
		 return $php;
	 }
}