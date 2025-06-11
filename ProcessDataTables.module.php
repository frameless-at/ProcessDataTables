<?php

require_once __DIR__ . '/TemplateGenerator.php';

/**
 * ProcessDataTable
 *
 * A configurable module to display selected fields of a selected template
 * in a table within the admin area.
 *
 * @author frameless Media
 * @version 0.6.5
 * @license MIT
 */
 
class ProcessDataTables extends Process {
	
	protected static $outputPath;
	protected $templateGenerator;
	
	/** Module info tells PW to auto-create /setup/data-tables/ */
	public static function getModuleInfo() {
		return [
			'title'      => 'ProcessDataTables',
			'version'    => '0.6.5',
			'summary'    => 'Displays customizable backend tables for any ProcessWire template with flexible column selection, per-field output templates, and global formatting options.',
			'author'     => 'frameless Media',
			'autoload'   => true,
			'requires'   => ['ProcessWire>=3.0.0','MarkupAdminDataTable'],  
			'permission' => 'data-table-view',
			'page' => [
				'name'   => 'data-tables',
				'parent' => 'setup',
				'title'  => 'DataTables'
			],
			'icon'       => 'table'
		];
	}

	public function __construct() {
		parent::__construct();
		if (!self::$outputPath) {
			// site/assets/ProcessDataTables/column_templates/
			$assetsPath      = wire('config')->paths->assets;
			$moduleName      = 'ProcessDataTables';
			self::$outputPath = "{$assetsPath}{$moduleName}/column_templates/";
		}
		$config = wire('modules')->getModuleConfigData($this);
		$standardProps = array_keys($this->getStandardPropertyLabels());
		$this->templateGenerator = new TemplateGenerator(self::$outputPath, $config, $standardProps);	
			
	}

	public function init() {
		parent::init();
		// regenerate on save
		wire('pages')->addHookAfter('save', $this, 'updateColumnTemplates');
		wire('pages')->addHookBefore('saveReady', $this, 'setTemplateForNewChild');
		wire('pages')->addHookBefore('saveReady', $this, 'validateDataTemplate');
	}

	/**
	 * Renders the DataTables overview interface.
	 *
	 * Fetches all DataTable instances under the “data-tables” parent, determines which
	 * table is active (via ?dt_id= or defaulting to the first), and outputs:
	 *   1. A single uk-tab item showing the active table’s title with a chevron-down icon,
	 *      which toggles a dropdown of the other tables.
	 *   2. The data table itself, built via MarkupAdminDataTable, showing selected Columns
	 *      and virtual columns for the active instance.
	 *   3. An “Edit” link for quick editing of the active instance.
	 *
	 * @return string HTML markup for the dropdown selector, table, and edit link.
	 */
public function execute() {
		$adminUrl = wire('config')->urls->admin; 

		 // 1) Handle import/export actions
		 if($this->input->post->ptables_action === 'import_config') {
			 $this->importConfigAndPages($_FILES['ptables_import_config'] ?? null);
		 }
		 if($this->input->post->ptables_action === 'import_templates') {
			 $this->importTemplates($_FILES['ptables_import_templates'] ?? null);
		 }
		 if($this->input->get->ptables_action === 'export_config') {
			 return $this->exportConfig();
		 }
		 if($this->input->get->ptables_action === 'export_templates') {
			 return $this->exportColumnTemplates();
		 }
	 
		 // 2) Find DataTables container
		 $parent = $this->pages->get("name=data-tables, include=all");
		 if(!$parent->id) {
			 return $this->warning("DataTables parent page not found.");
		 }
		 $addUrl   = $adminUrl . "page/add/?parent_id={$parent->id}";

		 // 3) Load DataTable instances
		 $instances = $this->pages->find("parent={$parent->id}, status=published, sort=name");
		 if(!$instances->count()) {
			 $msgEmpty = __('No DataTables defined yet.');
			 $msgAdd = __('Add one now');
			 return "<p>{$msgEmpty} <a href='{$addUrl}'>{$msgAdd}</a></p>"
				  . $this->buildImportExportForms('import');
		 }
	 
		 // 4) Determine active table
		 $dtIdParam  = (int) $this->input->get->dt_id;
		 $ids        = $instances->each('id');
		 $activeId   = in_array($dtIdParam, $ids, true) ? $dtIdParam : $ids[0];
		 $activeInst = $this->pages->get($activeId);
		 $activeTitle = htmlentities($activeInst->title);
	 
		 // 5) Build tab selector
		 $html  = '<ul uk-tab class="uk-margin-small-bottom">';
		 $html .= '<li>';
		 if($instances->count() > 1) {
			 $html .= "<a href='{$parent->url}?dt_id={$activeId}'>{$activeTitle}<span uk-icon=\"icon: triangle-down\"></span></a>";
			 $html .= '<div uk-dropdown="mode: click"><ul class="uk-nav uk-dropdown-nav">';
			 foreach($instances as $inst) {
				 if($inst->id === $activeId) continue;
				 $label = htmlentities($inst->title);
				 $url   = "{$parent->url}?dt_id={$inst->id}";
				 $html .= "<li><a href='{$url}'>{$label}</a></li>";
			 }
			 $html .= '</ul></div>';
		 } else {
			 $html .= "<a>{$activeTitle}</a>";
		 }
		 $editLink   = $adminUrl . "page/edit/?id={$activeId}";
		 $editLabel  = __('Edit');
		 $addLabel  = __('Add New');
		 $html .= '</li>';
		 $html .= "<li><a onclick=\"window.location.href='{$editLink}'\">{$editLabel}</a></li>";
		 $html .= "<li><a class='uk-text-primary' onclick=\"window.location.href='{$addUrl}'\">{$addLabel}</a></li>";
		 $html .= '</ul>';
	 
		 // 6) Parse columns & fetch data
		 $columns    = $this->parseColumns($activeInst->columns);
		 $template   = trim($activeInst->data_template);
		 $selector   = trim($activeInst->data_selector);
		 $selString  = "template={$template}" . ($selector ? ", {$selector}" : '');
	 
		 // 7) Pagination settings
		 //$pageNum    = max(1, (int) $this->input->get->dt_page);
		 $pageNum = wire('input')->pageNum; 
		 $config     = wire('modules')->getModuleConfigData($this);
		 $perPage    = (int) ($config['rowsPerPage'] ?? 50);
		 $offset     = ($pageNum - 1) * $perPage;
		 //$pagesToShow = $this->pages->find("{$selString}, start={$offset}, limit={$perPage}");
	 	$pagesToShow = $this->pages->find("{$selString}, limit={$perPage}");
	 
		 // 8) Render table via MarkupAdminDataTable
		 $table = $this->modules->get('MarkupAdminDataTable');
		 $table->setClass('uk-table-middle');
		 $table->setSortable(true);
		 $table->setEncodeEntities(false);
	 
		 // Header
		 $header = [];
		 foreach($columns as $col) {
			 $header[] = htmlentities($col['label']);
		 }
		 $table->headerRow($header);
	 
		 // Rows
		 $templateClosures = $this->loadColumnTemplates($columns, $activeInst->name);
		 foreach($pagesToShow as $page) {
			 $row = [];
			 foreach($columns as $col) {
				 $slug  = $col['slug'];
				 $value = $page->get($col['realName']);
				 $row[] = $templateClosures[$slug]($value);
			 }
			 $table->row($row);
		 }
	 
		 $html .= $table->render();
	 
	 	// 9) Pager via MarkupPagerNav
		 $totalItems = $this->pages->count($selString);
		 $pageNum    = wire('input')->pageNum; // picks up “/page3”
		 $pager      = wire('modules')->get('MarkupPagerNav');
		 
		 // ** Preserve dt_id properly **
		 $pager->setGetVars([
		   'dt_id' => $activeId,
		 ]);
		 
		 // Tell it how many items we have
		 $pager->setTotalItems($totalItems);
		 
		 // Now render with UK pagination styles
		 $html .= $pager->render($pagesToShow, [
		   'listClass'    => 'uk-pagination',
		   'numPageLinks' => 3,
		   'prevText'     => '‹',
		   'nextText'     => '›',
  		 	'listMarkup' => "<ul class='uk-pagination' style='margin-left:0'>{out}</ul>",

		 ]);
	 
		 // 10) Import/Export UI
		 $settLabel = __('Settings');
		 $html .= $this->buildImportExportForms('export');
	 	$html .= "<p><a href='".$adminUrl."module/edit?name=ProcessDataTables&collapse_info=1'><i class='fa fa-gear pw-nav-icon fa-fw'></i>{$settLabel}</a>";
		 return $html;
	 }
	 	 	
	/**
	 * Before any Page is saved, if it’s being created under our DataTables container,
	 * force its template to “datatable” so the editor never has to choose it.
	 *
	 * @param HookEvent $event
	 */
	public function setTemplateForNewChild(HookEvent $event) {
		/** @var Page $page */
		$page = $event->arguments(0);
		// only care about brand-new pages under /setup/data-tables/
		if(!$page->isNew()) return;
		if(!$page->parent || $page->parent->name !== 'data-tables') return;
		// force the correct template
		$page->template = wire('templates')->get('datatable');
		// done – ProcessWire will now save it with the right template
	}
	
	/**
	 * Fires after Pages->save() on a datatable page; makes shure the new page created uses "data-table" as template
	 *
	 * @param HookEvent $event
	 */
	 public function validateDataTemplate(HookEvent $event) {
		$page = $event->arguments(0);
		if($page->template->name !== 'datatable') return;
	
		// SKIP on the initial "new page" save
		if($page->isNew() || (string) $this->input->get->new === '1') {
			return;
		}
		$tplName = trim((string) $page->data_template);
		$tpl     = $tplName ? wire('templates')->get($tplName) : null;
		if(!$tpl || !$tpl->id) {
			$event->error("“{$tplName}” is not a valid template. Please choose an existing template.");
		}
	}
	
	/**
	 * Fires after Pages->save() on a datatable page; regenerates all column stubs.
	 *
	 * @param HookEvent $event
	 */
	public function updateColumnTemplates(HookEvent $event) {
		$page = $event->arguments(0);
		if (!($page instanceof Page) || $page->template->name !== 'datatable') return;
	
		$columns = $this->parseColumns($page->columns);
		foreach ($columns as $col) {
			// use the DataTable page's name to namespace stubs
			$table = $page->name;
			$this->templateGenerator->createTemplateFile(
				$table,
				$col['slug'],
				$col['realName']
			);
		}
	}

	/**
	 * Runs when the module is installed.
	 */
	public function install() {
		 // 1) Core PW install (creates /setup/data-tables/)
		 parent::install();
	 
	 	 // 2) Ensure our output directory exists and is writable
		 //    (column_templates within the module directory)
		 $dir = self::$outputPath;
		 if (!is_dir($dir)) {
			 // try to create the directory
			 if (!mkdir($dir, 0755, true)) {
				 throw new WireException("ProcessDataTables: failed to create directory {$dir}. Please check permissions.");
			 }
			 wire('log')->save('ProcessDataTables', "Created template directory: {$dir}");
		 }
		 // check writability
		 if (!is_writable($dir)) {
			 // try to correct permissions
			 if (!chmod($dir, 0755)) {
				 throw new WireException("ProcessDataTables: directory {$dir} is not writable. Please adjust permissions.");
			 }
			 wire('log')->save('ProcessDataTables', "Adjusted permissions on template directory: {$dir}");
		 }
	 
		 // 3) Create or load the "datatable" Fieldgroup
		 $fg = wire('fieldgroups')->get("name=datatable");
		 if(!$fg) {
			 $fg = new Fieldgroup();
			 $fg->name = 'datatable';
			 wire('fieldgroups')->save($fg);
		 }
	 
		 // 4) Define and create/update all four config fields
		 $fieldsApi = wire('fields');
		 $defs = [
			 ['data_template', 'FieldtypeText',  true,  'Data Template', 'Enter the template name, e.g. “product”'],
			 ['data_selector', 'FieldtypeText',  false,   'Data Selector', 'Optional selector, e.g. include=hidden'],
			 ['columns',  true, 'FieldtypeTextarea', 'Columns',
				 "One per line, in the exact order you want your table columns to appear.\n"
			   . "Syntax:\n"
			   . "  FIELDNAME           — pulls that field/property and uses its built-in label\n"
			   . "  LABEL=FIELDNAME     — pulls FIELDNAME but shows header as LABEL"
			 ],
		 ];
		 $fields = [];
	 
		 $titleField = wire('fields')->get('title');
		 if($titleField && !$fg->hasField($titleField)) {
			 $fg->add($titleField);
			 wire('fieldgroups')->save($fg);
		 }
		 foreach($defs as list($name, $typeClass, $required, $label, $desc)) {
			 // get existing or new
			 $f = $fieldsApi->get("name={$name}") ?: new Field();
			 $f->name        = $name;
			 $f->required 	= $required;
			 $f->label       = $label;
			 $f->description = $desc;
			 $f->type        = wire('modules')->get($typeClass);
			 $fieldsApi->save($f);
			 $fields[] = $f;
		 }
	 
	 	$defaults = ProcessDataTablesConfig::getDefaultConfig();
		 wire('modules')->saveConfig($this, $defaults);
	 
		 // 5) Attach all four fields to the Fieldgroup
		 foreach($fields as $f) {
			 if(!$fg->hasField($f)) {
				 $fg->add($f);
			 }
		 }
		 wire('fieldgroups')->save($fg);
	 
		 // 6) Create or update the "datatable" template, assign it our Fieldgroup
		 $templatesApi = wire('templates');
		 $tpl = $templatesApi->get("name=datatable");
		 if(!$tpl) {
			 $tpl = new Template();
			 $tpl->name = 'datatable';
		 }
		 // always (re)assign the fieldgroup so new fields show up
		 $tpl->fieldgroup = $fg;
		 $templatesApi->save($tpl);
	 }
	 	 
   /**
	 * Clean up on uninstall  
	 */	
	public function uninstall() {
	// 1) Remove all pages using the 'datatable' template
	$datatablePages = wire('pages')->find("template=datatable, include=all");
	foreach($datatablePages as $page) {
		$page->delete(true); // recursive delete just in case		
	}
	
	// 2) Delete the 'datatable' template
	$tpl = wire('templates')->get("name=datatable");
	if($tpl) {
		wire('templates')->delete($tpl);
	}
	
	// 3) Delete the config fields
	$fieldNames = ['data_template','data_selector','data_columns'];
	foreach($fieldNames as $name) {
		$f = wire('fields')->get("name={$name}");
		if($f) {
			wire('fields')->delete($f);
		}
	}
	
	// 4) Delete the 'datatable' fieldgroup
	$fg = wire('fieldgroups')->get("name=datatable");
	if($fg) {
		wire('fieldgroups')->delete($fg);
	}
	
	// 5) Delete the generated output templates directory
	$this->templateGenerator->deleteTemplateDirectory();
	
	// 6) Finally let PW remove the module page & config
	parent::uninstall();
	}

   /**
	* Standard property labels used as defaults 
    */
	public function getStandardPropertyLabels() {
		return [
			'id'       => 'Page ID',
			'name'     => 'Page Name',
			'created'  => 'Created Date',
			'modified' => 'Modified Date',
			'parent'   => 'Parent',
			'url'      => 'URL',
			'status'   => 'Page Status',
		];
	}
	
    /**
	 * Export module config + DataTable definitions as JSON.
	 */
	protected function exportConfig() {
		// permission
		if(!wire('user')->hasPermission('data-table-view')) {
			throw new WireException("Access denied");
		}
	
		// module config
		$config = wire('modules')->getModuleConfigData($this);
	
		// all DataTables
		$tables   = [];
		$parent   = wire('pages')->get("name=data-tables, include=all");
		if($parent->id) {
			$instances = wire('pages')->find("parent={$parent->id}, status=published");
			foreach($instances as $inst) {
				$tables[] = [
					'name'          => $inst->name,
					'title'         => $inst->title,
					'data_template' => (string) $inst->data_template,
					'data_selector' => (string) $inst->data_selector,
					'columns'       => array_map('trim', preg_split('/\r\n|\r|\n/', trim((string)$inst->columns))),
				];
			}
		}
	
		// payload
		$payload = [
			'moduleConfig' => $config,
			'dataTables'   => $tables,
		];
	
		// output
		$json = json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="ptables-export.json"');
		echo $json;
		exit;
	}
	
	/**
	 * Export all column templates as a ZIP archive.
	 */
	protected function exportColumnTemplates() {
		// permission
		if(!wire('user')->hasPermission('data-table-view')) {
			throw new WireException("Access denied");
		}
	
		// base dir
		$baseDir = self::$outputPath;
		if(!is_dir($baseDir)) {
			throw new WireException("Templates directory not found: {$baseDir}");
		}
	
		// create temp ZIP
		$zip     = new ZipArchive();
		$tmpFile = tempnam(sys_get_temp_dir(), 'ptpls_') . '.zip';
		if($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
			throw new WireException("Could not create ZIP archive");
		}
	
		// add files recursively
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		foreach($files as $file) {
			if(!$file->isFile()) continue;
			$filePath     = $file->getRealPath();
			$relativePath = ltrim(substr($filePath, strlen($baseDir)), DIRECTORY_SEPARATOR);
			$zip->addFile($filePath, $relativePath);
		}
		$zip->close();
	
		// deliver ZIP
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="ptables-templates.zip"');
		readfile($tmpFile);
		unlink($tmpFile);
		exit;
	}
	
    /**
	 * Handle import of module config plus DataTable-Pages from uploaded JSON.
	 *
	 * @param array|null $file  The $_FILES entry for the JSON upload
	 * @return void
	 */
	protected function importConfigAndPages($file) {
		
		// 1) validation
		if(!wire('user')->hasPermission('data-table-view')) {
			throw new WireException("Access denied");
		}
		if(!$file || $file['error'] !== UPLOAD_ERR_OK) {
			return $this->error("No valid JSON file uploaded.");
		}
	
		// 2) read and check JSON
		$data = json_decode(file_get_contents($file['tmp_name']), true);
		if(!isset($data['moduleConfig'], $data['dataTables'])) {
			return $this->error("Invalid export JSON format.");
		}
	
		// 3) save config
		wire('modules')->saveConfig($this, $data['moduleConfig']);
	
		// 4) create/update DataTable
		$this->importDataTablePages($data['dataTables']);
	
		// 5) Feedback
		$this->message("Configuration and DataTables-Pages imported successfully.");
	}
	
	/**
	 * Create or update DataTable pages under /setup/data-tables/ based on definitions.
	 *
	 * @param array $definitions  Array of ['name','title','data_template','data_selector','columns']
	 */
	protected function importDataTablePages(array $definitions) {
		$parent = wire('pages')->get("name=data-tables, include=all");
		if(!$parent->id) {
			// Fallback: create parent if missing
			$parent = new Page();
			$parent->template = wire('templates')->get('admin');
			$parent->parent   = wire('pages')->get('/setup/');
			$parent->name     = 'data-tables';
			wire('pages')->save($parent);
		}
	
		foreach($definitions as $def) {
			// find or create datatable page if missing
			$p = wire('pages')->get("parent={$parent->id}, name={$def['name']}");
			if(!$p->id) {
				$p = new Page();
				$p->template = wire('templates')->get('datatable');
				$p->parent   = $parent;
				$p->name     = $def['name'];
			}
			// set fields
			$p->title         = $def['title'] ?? $def['name'];
			$p->of(true);
			$p->data_template = $def['data_template'] ?? '';
			$p->data_selector = $def['data_selector'] ?? '';
			$p->columns       = implode("\n", $def['columns'] ?? []);
			wire('pages')->save($p);
		}
	}
	
	/**
	 * Handle import of column-templates ZIP.
	 *
	 * @param array|null $file  The $_FILES entry for the ZIP upload
	 * @return void
	 */
	 protected function importTemplates($file) {
		
		 // 1) check permission
		 if(!wire('user')->hasPermission('data-table-view')) {
			 throw new WireException("Access denied");
		 }
		 if(!$file || $file['error'] !== UPLOAD_ERR_OK) {
			 return $this->error("No valid ZIP file uploaded.");
		 }
	 
		 // 2) extract ZIP
		 $tmpDir = sys_get_temp_dir() . '/ptpls_import_' . uniqid();
		 mkdir($tmpDir, 0755, true);
		 $zip = new ZipArchive();
		 if($zip->open($file['tmp_name']) !== true) {
			 return $this->error("Failed to open ZIP archive.");
		 }
		 $zip->extractTo($tmpDir);
		 $zip->close();
	 
		 // 3) strip unnecessary directories
		 $entries = array_diff(scandir($tmpDir), ['.','..']);
		 if(count($entries) === 1 && is_dir("$tmpDir/{$entries[0]}")) {
			 $workDir = "$tmpDir/{$entries[0]}";
		 } else {
			 $workDir = $tmpDir;
		 }
	 
		 // 4) copy files & set permissions
		 $baseDir = rtrim(self::$outputPath, '/') . '/';
		 $iterator = new RecursiveIteratorIterator(
			 new RecursiveDirectoryIterator($workDir, FilesystemIterator::SKIP_DOTS),
			 RecursiveIteratorIterator::SELF_FIRST
		 );
		 foreach($iterator as $item) {
			 $sourcePath = $item->getRealPath();
	 
			 // a) Skip macOS metadata
			 if (strpos($sourcePath, '__MACOSX') !== false) continue;
	 
			 // b) set destination dir 
			 $relative = substr($sourcePath, strlen($workDir) + 1);
			 $dest     = $baseDir . $relative;
	 
			 if ($item->isDir()) {
				 // create dir
				 if (!is_dir($dest)) {
					 mkdir($dest, 0755, true);
					 wire('log')->save('ProcessDataTables', "Created directory: {$dest}");
				 }
			 } else {
				 // backup file and copy
				 if (is_file($dest)) {
					 rename($dest, dirname($dest) . '/_' . basename($dest));
				 }
				 copy($sourcePath, $dest);
	 
				 // set file permissions
				 wire('files')->chmod($dest, '0770', false);
				 wire('log')->save('ProcessDataTables', "Imported template file with 0644: {$dest}");
			 }
		 }
	 
		 // 5) delete temp dir
		 wire('files')->rmdir($tmpDir, true);
	 
		 // 6) Feedback
		 $this->message("Templates imported successfully.");
	}

	/**
  	* Builds the Import/Export UI as four discrete ProcessWire fieldsets.
  	*
  	* @return string
  	*/
	protected function buildImportExportForms($kind): string {
		$modules = wire('modules');
		$wrapper = $modules->get('InputfieldWrapper');
		$confExpLabel = __('Export Global & Table Configurations');
		$confExpBtnLabel = __('Export Config & Tables'); 
		$confExpDesc = __('This will EXPORT the module settings and all your defined DataTables as a JSON file.');
		$confImpLabel = __('Import Global & Table Configurations');
		$confImpBtnLabel = __('Import Config & Tables');
		$confImpDesc = __('This will IMPORT the module settings and all your defined DataTables from a JSON file.');
		$tmplExpLabel = __('Export Column Templates');
		$tmplExpBtnLabel = __('Export Templates');
		$tmplExpDesc = __('This will EXPORT all column templates of all DataTables as a ZIP file.');
		$tmplImpLabel = __('Import Column Templates');
		$tmplImpBtnLabel = __('Import Templates');
		$tmplImpDesc = __('This will IMPORT all column templates of all DataTables from a ZIP file.');

		$createFs = function(string $label, string $formHtml) use($modules) {
			$fs = $modules->get('InputfieldFieldset');
			$fs->label     = $label;
			$fs->collapsed = true;
			$markup = $modules->get('InputfieldMarkup');
			$markup->value = $formHtml;
			$fs->add($markup);
			return $fs;
		};
	
		// 1) Export Config (GET)
		$form1 = "<form method=\"get\" class=\"uk-form-stacked\">
				  	<button type=\"submit\" name=\"ptables_action\" value=\"export_config\" class=\"uk-button uk-button-primary ui-corner-all\">
						{$confExpBtnLabel}
				  	</button>	<span class=\"uk-text-muted\" style=\"margin-left:1em;\">{$confExpDesc}</span>
	
			  	</form>";
	
		// 2) Import Config (POST + multipart)
		$form2 = "<form method=\"post\" enctype=\"multipart/form-data\" class=\"uk-form-stacked\">
				  	<input type=\"file\" style=\"border:none;padding-left:0; background-color:transparent\" name=\"ptables_import_config\" id=\"ptables_import_config\" accept=\".json\" class=\"uk-input\" />
				  	<button type=\"submit\" name=\"ptables_action\" value=\"import_config\" class=\"uk-button uk-button-primary ui-corner-all\">
						{$confImpBtnLabel}
				  	</button>	<span class=\"uk-text-muted\" style=\"margin-left:1em;\">{$confImpDesc}</span>
			  	</form>";
	
		// 3) Export Templates (GET)
		$form3 = "<form method=\"get\" class=\"uk-form-stacked\">
				  	<button type=\"submit\" name=\"ptables_action\" value=\"export_templates\" class=\"uk-button uk-button-primary ui-corner-all\">
						{$tmplExpBtnLabel}
				  	</button>	<span class=\"uk-text-muted\" style=\"margin-left:1em;\">{$tmplExpDesc}</span>
			  	</form>";
	
		// 4) Import Templates (POST + multipart)
		$form4 = "<form method=\"post\" enctype=\"multipart/form-data\" class=\"uk-form-stacked\">
				  	<input type=\"file\" style=\"border:none;padding-left:0;background-color:transparent\" name=\"ptables_import_templates\" id=\"ptables_import_templates\" accept=\".zip\" class=\"uk-input\" />
				  	<button type=\"submit\" name=\"ptables_action\" value=\"import_templates\" class=\"uk-button uk-button-primary ui-corner-all\">
						{$tmplImpBtnLabel}
				  	</button>	<span class=\"uk-text-muted\" style=\"margin-left:1em;\">{$tmplImpDesc}</span>
			  	</form>";
	
		$wrapper->add($createFs($tmplImpLabel, $form4));
		$wrapper->add($createFs($confImpLabel, $form2));
	  	if($kind==='export') $wrapper->add($createFs($tmplExpLabel, $form3));
		if($kind==='export') $wrapper->add($createFs($confExpLabel, $form1));

	
		return '<div class="uk-margin-large-top">'.$wrapper->render().'</div>';
	} 

		 
	/** Parse the unified "columns" textarea into an ordered list of column definitions.
  	*
  	* Lines are either:
  	*   FIELDNAME
  	*   LABEL=FIELDNAME
  	*
  	* FIELDNAME must be a real field, a core page property (self::$pageProperties) oder “meta”.
  	*
  	* @param string $raw
  	* @return array
  	*/
	 protected function parseColumns(string $raw): array {
	 	 $props = array_keys($this->getStandardPropertyLabels());
		 $out   = [];
	 
		 foreach (preg_split('/\r\n|\r|\n/', trim($raw)) as $line) {
			 $line = trim($line);
			 if ($line === '') continue;
	 
			 if (strpos($line, '=') !== false) {
				 list($override, $field) = array_map('trim', explode('=', $line, 2));
				 $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($override));
			 } else {
				 $override = null;
				 $field    = $line;
				 $slug     = $field;
			 }
	 
			 $isMeta   = $field === 'meta';
			 $isProp   = in_array($field, $props, true);
			 $fieldObj = wire('fields')->get($field);
	 
			 if (! ($isMeta || $isProp || $fieldObj) ) continue;
	 
			 if ($override !== null) {
				 $label = $override;
			 } elseif ($fieldObj) {
				 $label = $fieldObj->label;
			 } else {
				 $label = ucfirst($field);
			 }
	 
			 $out[] = [
				 'realName' => $field,
				 'label'    => $label,
				 'slug'     => $slug,
			 ];
		 }
	 
		 return $out;
	 }	
	  
	  
	/**
	* Loads all column templates as callables, in table-specific subfolder.
	*
	* @param array  $columns Output from parseColumns()
	* @param string $table   DataTable page name
	* @return array<string, callable>  [slug => function($value, $config): string]
	*/
	  protected function loadColumnTemplates(array $columns, string $table): array {
		  // 1) read module config
		  $config = wire('modules')->getModuleConfigData($this);
	  
		  $templateClosures = [];
		  foreach ($columns as $col) {
			  $slug         = $col['slug'];
			  $templateFile = $this->templateGenerator->getTemplateFilePath($table, $slug);
	  
			  // auto-upgrade legacy stub with backup (if needed)
			  if (is_file($templateFile)) {
				  $raw = file_get_contents($templateFile);
				  if (!preg_match('/return\s+function\s*\(/', $raw)) {
					  $backup = dirname($templateFile) . '/_' . basename($templateFile);
					  rename($templateFile, $backup);
					  wire('log')->save('ProcessDataTables', "Backed up legacy stub to: {$backup}");
					  $this->templateGenerator->createTemplateFile($table, $slug, $col['realName']);
				  }
			  }
	  
			  // load stub or fallback
			  if (is_file($templateFile)) {
				  $stubFunc = include $templateFile;
				  if (!is_callable($stubFunc)) {
					  $stubFunc = function($value) use ($templateFile) {
						  ob_start();
						  include $templateFile;
						  return ob_get_clean();
					  };
				  }
			  } else {
				  $stubFunc = function($value) {
					  return htmlentities((string)$value);
				  };
			  }
	  
			  // wrap so stubFunc receives config too
			  $templateClosures[$slug] = function($value) use ($stubFunc, $config) {
				  return $stubFunc($value, $config);
			  };
		  }
	  
		  return $templateClosures;
	  }

}
