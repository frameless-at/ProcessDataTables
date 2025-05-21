<?php

require_once __DIR__ . '/TemplateGenerator.php';

/**
 * ProcessDataTable
 *
 * A configurable module to display selected fields of a selected template
 * in a table within the admin area.
 *
 * @author frameless Media
 * @version 0.1.0-beta
 * @license MIT
 */
class ProcessDataTable extends Process {
	
	
	/** @var string Where generated templates live */
	protected static $outputPath;
	
	/** @var TemplateGenerator */
	protected $templateGenerator;
	
	/** Liste der Core-Page-Properties, die als Feldnamen erlaubt sind */
	protected static $pageProperties = [
	  'id','name','created','modified','parent','url'
	];
	
	
	/** Module info tells PW to auto-create /setup/data-tables/ */
	public static function getModuleInfo() {
		return [
			'title'      => 'ProcessDataTable',
			'version'    => '0.1.0-beta',
			'summary'    => 'Display selected fields of a template in a backend table.',
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
			self::$outputPath = __DIR__ . '/column_templates/';
		}
		$this->templateGenerator = new TemplateGenerator(self::$outputPath);
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
	 /*
	
	/**
	 * Renders the DataTables overview interface using the unified "columns" config.
	 *
	 * @return string HTML markup for the dropdown selector, table, and edit link.
	 */
	public function execute() {
		// 1) Find the “DataTables” parent page
		$parent = $this->pages->get("name=data-tables, include=all");
		if(!$parent->id) {
			return $this->warning("DataTables parent page not found.");
		}
	
		// 2) Fetch all published DataTable instances
		$instances = $this->pages->find("parent={$parent->id}, status=published, sort=name");
		if(!$instances->count()) {
			$addUrl = "/cms/page/add/?parent_id={$parent->id}";
			return "<p>No DataTables defined yet. <a href='{$addUrl}'>Add one now</a>.</p>";
		}
	
		// 3) Determine active instance
		$dtIdParam  = (int) $this->input->get->dt_id;
		$ids        = $instances->each('id');
		$activeId   = in_array($dtIdParam, $ids, true) ? $dtIdParam : $ids[0];
		$activeInst = $this->pages->get($activeId);
		$activeTitle = htmlentities($activeInst->title);
	
		// 4) Build the uk-tab + dropdown selector
		$html  = '<ul uk-tab class="uk-margin-small-bottom">';
		
		$html .= '<li>';
		 
		 if ($instances->count() > 1) {
		  $html .= "<a href='?dt_id={$activeId}'>{$activeTitle}<span uk-icon=\"icon: triangle-down\"></span></a>";
		  $html .= '<div uk-dropdown="mode: click">';
		  $html .= '<ul class="uk-nav uk-dropdown-nav">';
		  foreach($instances as $inst) {
			  if($inst->id === $activeId) continue;
			  $label = htmlentities($inst->title);
			  $url   = "?dt_id={$inst->id}";
			  $html .= "<li><a href='{$url}'>{$label}</a></li>";
		  }
		  $html .= '</ul></div>';
	  	}
		else		
			$html .= "<a>{$activeTitle}</a>";

		$html .= '<li>';
		
		$editlink = "/cms/page/edit/?id=" . $activeId;
		$html  .=    "<li><a onclick=\"window.location.href='$editlink'\" href>Edit</a><li>";
		$addUrl = "/cms/page/add/?parent_id={$parent->id}";
		$html  .= "<li><a class='uk-text-primary' href onclick=\"window.location.href='{$addUrl}'\">Add New</a></li>";
		$html .= '</ul>';
	
		// 5) Parse unified columns config
		$columns = $this->parseColumns($activeInst->columns);
	
		// 6) Fetch pages to show
		$dataTemplate = trim($activeInst->data_template);
		$selector     = trim($activeInst->data_selector);
		$selString    = "template={$dataTemplate}";
		if($selector) $selString .= ", {$selector}";
		$pagesToShow  = $this->pages->find($selString);
	
		// 7) Prepare the MarkupAdminDataTable
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
		
		// Data-Rows
		foreach($pagesToShow as $page) {
			$row = [];
			foreach($columns as $col) {
				$value = $page->get($col['realName']);
				$row[] = $this->templateGenerator->renderTemplateFile(
					$col['slug'], // **Label** als Schlüssel
					$value
				);
			}
			$table->row($row);
		}
	
		// 10) Render selector + table
		$html .= $table->render();
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
	 * Fires after Pages->save()
	 * Creates/overwrites *.table-output.php files for each field.
	 */
	 public function updateColumnTemplates(HookEvent $event) {
	   $page = $event->arguments(0);
	   if(!($page instanceof Page) || $page->template->name !== 'datatable') return;
	 
	   $columns = $this->parseColumns($page->columns);
	   foreach($columns as $col) {
		 // slug als Basis, realName für die Typ-Erkennung
		 $this->templateGenerator->createTemplateFile(
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
	
		// 2) Ensure our output directory exists
		if(!is_dir(self::$outputPath)) {
			mkdir(self::$outputPath, 0755, true);
		}
		$this->templateGenerator->ensureTemplateDir();
	
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
			['data_selector', 'FieldtypeText',  false,   'Data Selector', 'Optional selector, e.g. status=published'],
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
	 	 
	/** Clean up on uninstall */
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

	/** Standard property labels used as defaults */
	public function getStandardPropertyLabels() {
		return [
			'id'       => 'Page ID',
			'name'     => 'Page Name',
			'created'  => 'Created Date',
			'modified' => 'Modified Date',
			'parent'   => 'Parent',
			'url'      => 'URL',
		];
	}


/**
	  * Parse the unified "columns" textarea into an ordered list of column definitions.
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
		 $props = self::$pageProperties; // ['id','name','created','modified','parent','url']
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
}
