<?php

/**
 * TemplateGenerator for the Module ProccessDataTable
 *
 * Handles the creation of output template files for selected fields for each DataTable.
 *
 * @author frameless Media
 * @version 0.6.2
 * @license MIT
 */

class TemplateGenerator {
	/** 
 	* File suffix for generated column templates 
 	* @var string 
 	*/
	const TEMPLATE_SUFFIX = '.column.php';
	protected $templateDir;
	protected $fieldtypeStubDir;
	protected $standardProps = [];	
	protected $config = [];
	

	public function __construct(string $templateDir, array $config = [], array $standardProps = []) {
		$this->templateDir     = rtrim($templateDir, '/') . '/';
		$this->fieldtypeStubDir = __DIR__ . '/fieldtype_templates/';
		$this->ensureTemplateDir();
		$this->config          = $config;
		$this->standardProps   = $standardProps;
	}
	
	/**
	 * Build full path for a column stub, namespaced by DataTable page name.
	 *
	 * @param string $table  Name (or slug) of the DataTable page
	 * @param string $slug   Column slug
	 * @return string        Full path to .column.php stub
	 */
	public function getTemplateFilePath(string $table, string $slug): string {
		// normalize slug
		$slug     = preg_replace('/[^a-z0-9]+/i','_', strtolower($slug));
		$slug     = trim($slug, '_');
		// normalize table name
		$tableDir = preg_replace('/[^a-z0-9]+/i','_', strtolower($table));
		$tableDir = trim($tableDir, '_');
		// return path: <base>/<table>/<slug>.column.php
		$base = rtrim($this->templateDir, '/');
		return "{$base}/{$tableDir}/{$slug}" . self::TEMPLATE_SUFFIX;
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
	
			if (!is_writable($templateFile)) {
				chmod($templateFile, 0777);
			}
	
			if (unlink($templateFile)) {
				wire('log')->save('ProcessDataTables', "Template deleted: $templateFile");
			} else {
				wire('log')->save('ProcessDataTables', "Error deleting template: $templateFile");
			}
		} else {
			wire('log')->save('ProcessDataTables', "Template does not exist and could not be deleted: $templateFile");
		}
	}
	 
	/**
	 * Create or overwrite a single-column stub in table-specific subfolder.
	 *
	 * @param string $table         DataTable page name
	 * @param string $label         Human-readable column label
	 * @param string $realFieldName Actual field/property name
	 *
	 * examlpe stub with closure:
	 *
	 * Output template for field: {{FIELDNAME}}
	 * Column label: {{LABEL}}
	 * Fieldtype: FieldtypeText
	 * Available variable: $value
	 * Uses global config: $config['textMaxLength']
	 *
	 return function($value, $config = []) {
		 // Kürzt den Text auf die maximale Länge aus der Config (Standard: 80)
		 $maxLength = $config['textMaxLength'] ?? 80;
		 $text = (string)$value;
		 if (mb_strlen($text) > $maxLength) {
			 $text = mb_substr($text, 0, $maxLength) . '…';
		 }
		 return htmlspecialchars($text);
	 };
	 */
	public function createTemplateFile(string $table, string $label, string $realFieldName) {
		// build slug & path
		$slug = preg_replace('/[^a-z0-9]+/i', '_', trim(strtolower($label)));
		$slug = trim($slug, '_');
		$file = $this->getTemplateFilePath($table, $slug);
	
		// bail if already exists
		if (is_file($file)) return;
	
		// ensure subdirectory exists
		$dir = dirname($file);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
			wire('log')->save('ProcessDataTables', "Created template directory: {$dir}");
		}
	
		// detect typeClass (same logic as before)
		$real = $realFieldName;
		if ($real === 'meta') {
			$typeClass = 'WireData';
		} elseif (in_array($real, $this->standardProps ?? ['id','name','created','modified','parent','url','status'], true)) {
			$typeClass = 'PageProperty';
		} elseif ($field = wire('fields')->get($real)) {
			$typeClass = $field->type->className;
		} else {
			return; // invalid
		}
	
		// copy core stub if available
		$stubPath = $this->fieldtypeStubDir . $typeClass . self::TEMPLATE_SUFFIX;
		if (is_file($stubPath)) {
			$raw = file_get_contents($stubPath);
			$raw = strtr($raw, [
				'{{FIELDNAME}}' => $realFieldName,
				'{{LABEL}}'     => $label,
			]);
			file_put_contents($file, $raw);
			chmod($file, 0770);
			wire('log')->save('ProcessDataTables', "Copied fieldtype stub: {$stubPath} -> {$file}");
			return;
		}
	
		// fallback: generate closure stub
		$content = $this->getTemplateContent($typeClass, $realFieldName, $label);
		file_put_contents($file, $content);
		chmod($file, 0770);
		$message = ($typeClass === 'PageProperty')
			? "Wrote PageProperty stub: {$file}"
			: "Wrote fallback template file: {$file}";
		wire('log')->save('ProcessDataTables', $message);
	}  

	/**
	 * Render the template file for a specific field
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return string
	 */
	 public function renderTemplateFile(string $label, $value) {
		 $slug = preg_replace('/[^a-z0-9]+/i','_', strtolower($label));
		 $slug = trim($slug, '_');
		 $file = $this->templateDir . $slug . self::TEMPLATE_SUFFIX;
	 
		 if (!file_exists($file)) {
			 wire('log')->save('ProcessDataTables', "Template not found, creating: $file");
			 // Da createTemplateFile slugify auf Label macht, kann Label direkt übergeben werden:
			 $this->createTemplateFile($label);
		 }
	 
		 if (!file_exists($file)) {
			 wire('log')->save('ProcessDataTables', "Failed to create template: $file");
			 return htmlentities($value);
		 }
	 
		 $config = $this->config;
		 ob_start();
		 include $file;
		 return ob_get_clean();
	 }
	 
	 /**
	  * Delete the entire template directory and its contents
	  */
	  public function deleteTemplateDirectory() {
		  $dir = rtrim($this->templateDir, '/') . '/';
		  if (!is_dir($dir)) {
			  wire('log')->save('ProcessDataTables', "Template directory does not exist: {$dir}");
			  return;
		  }
	  
		  // Recursively delete all files and subdirectories
		  $iterator = new RecursiveIteratorIterator(
			  new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
			  RecursiveIteratorIterator::CHILD_FIRST
		  );
	  
		  foreach ($iterator as $item) {
			  $path = $item->getRealPath();
			  if ($item->isDir()) {
				  rmdir($path);
				  wire('log')->save('ProcessDataTables', "Removed directory: {$path}");
			  } else {
				  unlink($path);
				  wire('log')->save('ProcessDataTables', "Removed file: {$path}");
			  }
		  }
	  
		  // Finally remove the root directory itself
		  if (rmdir($dir)) {
			  wire('log')->save('ProcessDataTables', "Template directory deleted: {$dir}");
		  } else {
			  wire('log')->save('ProcessDataTables', "Failed to delete template directory: {$dir}");
		  }
	  }	 
	 /**
	  * Creates PHP stub for a Page property or a fallback stubb for unknown types.
	  *
	  * @param string $fieldType
	  * @param string $fieldName
	  * @param string|null $columnLabel
	  * @return string PHP code for stub file
	  */
	  protected function getTemplateContent(string $fieldType, string $fieldName, string $columnLabel = null) {
		  $php  = "<?php\n";
		  $php .= "/**\n";
		  $php .= " * Output template for field: {$fieldName}\n";
		  if ($columnLabel) {
			  $php .= " * Column label: {$columnLabel}\n";
		  }
		  $php .= " * Fieldtype: {$fieldType}\n";
		  $php .= " * Available vars: \$value, \$config\n";
		  $php .= " */\n\n";
		  $php .= "return function(\$value, \$config = []) {\n";
	  
		  $propertyMap = [
			  'created'  => "	return date('Y-m-d H:i', \$value);",
			  'modified' => "	return date('Y-m-d H:i', \$value);",
			  'id'       => "	return (string)(int)\$value;",
			  'name'     => "	return htmlspecialchars((string)\$value);",
			  'status' 	 => "    \$labels = [];\n"
						  . "    if (\$value & Page::statusOn)          \$labels[] = 'published';\n"
						  . "    if (\$value & Page::statusUnpublished)  \$labels[] = 'unpublished';\n"
						  . "    if (\$value & Page::statusHidden)       \$labels[] = 'hidden';\n"
						  . "    if (\$value & Page::statusLocked)       \$labels[] = 'locked';\n"
						  . "    if (\$value & Page::statusSystem)       \$labels[] = 'system';\n"
						  . "    if (\$value & Page::statusSystemID)     \$labels[] = 'systemID';\n"
						  . "    if (empty(\$labels)) return '';\n"
						  . "    return htmlspecialchars(implode(', ', \$labels));",
			  'parent'   => "	if(\$value instanceof Page) {
		  		return '<a href=\"'.\$value->url.'\">'.htmlspecialchars(\$value->title).'</a>';
	  			} else {
		  			return ''; }",
			  'url'      => "	return '<a href=\"'.\$value.'\">'.htmlspecialchars(\$value).'</a>';",
		  ];
	  
		  if (array_key_exists($fieldName, $propertyMap)) {
			  $php .= $propertyMap[$fieldName] . "\n";
		  } else {
			  $php .= "	return htmlspecialchars((string)\$value);\n";
		  }
	  
		  $php .= "};\n";
		  return $php;
	  }
  }
