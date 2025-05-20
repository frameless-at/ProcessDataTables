<?php

require_once __DIR__ . '/TemplateGenerator.php';

/**
 * ProcessDataTable
 *
 * A configurable module to display selected fields of a selected template
 * in a table within the admin area.
 *
 * @author frameless Media
 * @version 0.0.3-beta
 * @license MIT
 */
class ProcessDataTable extends Process {

	/** @var string Where generated templates live */
	protected static $outputPath;

	/** @var TemplateGenerator */
	protected $templateGenerator;

	/** Module info tells PW to auto-create /setup/data-tables/ */
	public static function getModuleInfo() {
		return [
			'title'      => 'ProcessDataTable',
			'version'    => '0.0.3-beta',
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

	/** Setup generator and output path */
	public function __construct() {
		parent::__construct();
		if (!self::$outputPath) {
			self::$outputPath = __DIR__ . '/output templates/';
		}
		$this->templateGenerator = new TemplateGenerator(self::$outputPath);
	}

	public function init() {
		parent::init();
		// regenerate on save
		wire('pages')->addHookAfter('save', $this, 'onPagesSave');
		
		// ensure new children use datatable template
		wire('pages')->addHookBefore('saveReady', $this, 'ensureChildHasCorrectTemplate');
	}

/**
	 * Renders the DataTables overview interface.
	 *
	 * Fetches all DataTable instances under the “data-tables” parent, determines which
	 * table is active (via ?dt_id= or defaulting to the first), and outputs:
	 *   1. A single uk-tab item showing the active table’s title with a chevron-down icon,
	 *      which toggles a dropdown of the other tables.
	 *   2. The data table itself, built via MarkupAdminDataTable, showing selected fields
	 *      and virtual columns for the active instance.
	 *   3. A right-aligned “Edit” link beneath the table for quick editing of the active instance.
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
		$dtIdParam   = (int) $this->input->get->dt_id;
		$ids         = $instances->each('id');
		$activeId    = in_array($dtIdParam, $ids, true) ? $dtIdParam : $ids[0];
		$activeInst  = $this->pages->get($activeId);
		$activeTitle = htmlentities($activeInst->title);
	
		// 4) Build the uk-tab + dropdown structure
		$html  = '<ul uk-tab class="uk-margin-small-bottom">';
		$html .= '<li>';
		  // active tab link
		  $html .= "<a href='?dt_id={$activeId}'>{$activeTitle}";
		  $html .= "<span uk-icon=\"icon: triangle-down\"></span></a>";
		  // dropdown of all *other* tables
		  $html .= '<div uk-dropdown="mode: click">';
		  $html .= '<ul class="uk-nav uk-dropdown-nav">';
		  foreach($instances as $inst) {
			  if($inst->id === $activeId) continue;
			  $label = htmlentities($inst->title);
			  $url   = "?dt_id={$inst->id}";
			  $html .= "<li><a href='{$url}'>{$label}</a></li>";
		  }
	
		  $html .= '</ul>';
		  $html .= '</div>';
		$html .= '</li>';
		// 11) Edit link 
		  $editlink = "/cms/page/edit/?id=" . $activeId;
		  $html  .=    "<li><a onclick=\"window.location.href='$editlink'\" href>Edit</a><li>";
		$html .= '</ul>';
	
		// 5) Gather config from active instance
		$dataTemplate = trim($activeInst->data_template);
		$selector     = trim($activeInst->data_selector);
		$rawFields    = preg_split('/[\r\n,]+/', $activeInst->data_fields);
		$fields       = array_filter(array_map('trim', $rawFields));
		$virtualCols  = $this->parseVirtualColumns($activeInst->virtual_columns);
	
		// 6) Fetch pages to show
		$selString   = "template={$dataTemplate}";
		if($selector) $selString .= ", {$selector}";
		$pagesToShow = $this->pages->find($selString);
	
		// 7) Prepare the MarkupAdminDataTable
		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setSortable(true);
		$table->setEncodeEntities(false);
	
		// 8) Header row
		$labels = $this->getStandardPropertyLabels();
		$header = [];
		foreach($fields as $fn) {
			$header[] = htmlentities(
				$labels[$fn]
				?? ($this->fields->get($fn)->label ?? ucfirst($fn))
			);
		}
		foreach(array_keys($virtualCols) as $col) {
			$header[] = htmlentities($col);
		}
		$table->headerRow($header);
	
		// 9) Data rows
		foreach($pagesToShow as $page) {
			$row = [];
			foreach($fields as $fn) {
				$row[] = $this->templateGenerator->renderTemplateFile($fn, $page->get($fn));
			}
			foreach($virtualCols as $colName => $fieldName) {
				$row[] = $this->templateGenerator->renderTemplateFile($colName, $page->get($fieldName));
			}
			$table->row($row);
		}
	
		// 10) Render dropdown‐tab + table
		$html .= $table->render();
	
	
	
		return $html;
	}

	/**
	 * Before any Page is saved, if it’s being created under our DataTables container,
	 * force its template to “datatable” so the editor never has to choose it.
	 *
	 * @param HookEvent $event
	 */
	public function ensureChildHasCorrectTemplate(HookEvent $event) {
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
	 * Fires after Pages->save()
	 * Creates/overwrites *.table-output.php files for each field.
	 */
	public function onPagesSave($event) {
		$page = $event->arguments(0);
		if(!($page instanceof Page) || $page->template->name !== 'datatable') return;

		// parse explicit fields (commas or line breaks)
		$raw  = preg_split('/[\r\n,]+/', $page->data_fields);
		$fields = array_filter(array_map('trim', $raw));

		// parse virtual columns into [label=>fieldName]
		$virtualCols = $this->parseVirtualColumns($page->virtual_columns);
		
		// virtual columns: [columnLabel => fieldName]
		foreach($virtualCols as $columnLabel => $fieldName) {
			// generate template for "Items" based on real field "items"
			$this->templateGenerator->createTemplateFile($columnLabel, $fieldName);
		}
		
		// now generate for real fields, too
		foreach($fields as $fieldName) {
			$this->templateGenerator->createTemplateFile($fieldName);
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
			// name             Fieldtype            label               description
			['data_template',   'FieldtypeText',     'Data Template',    'Enter the template name, e.g. “product”'],
			['data_selector',   'FieldtypeText',     'Data Selector',    'Optional selector, e.g. status=published'],
			['data_fields',     'FieldtypeTextarea', 'Data Fields',      'Comma- or newline-separated list of field names'],
			['virtual_columns', 'FieldtypeTextarea', 'Virtual Columns',  'One per line: columnName=fieldName'],
		];
		$fields = [];

		$titleField = wire('fields')->get('title');
		if($titleField && !$fg->hasField($titleField)) {
			$fg->add($titleField);
			wire('fieldgroups')->save($fg);
		}
		foreach($defs as list($name, $typeClass, $label, $desc)) {
			// get existing or new
			$f = $fieldsApi->get("name={$name}") ?: new Field();
			$f->name        = $name;
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
	$fieldNames = ['data_template','data_selector','data_fields','virtual_columns'];
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

	/** Parse "Label=fieldName" lines into [Label=>fieldName] */
	protected function parseVirtualColumns($cfg) {
		$out = [];
		$lines = explode("\n", trim($cfg));
		foreach($lines as $line) {
			if(strpos($line, '=') === false) continue;
			list($label,$field) = array_map('trim', explode('=', $line, 2));
			if($label && $field) $out[$label] = $field;
		}
		return $out;
	}

	
}
