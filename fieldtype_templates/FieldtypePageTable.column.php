<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypePageTable
 * Available variable: $value (PageArray)
 */
return function($value, $config = []) {
	// Anzahl der Einträge
	$count   = $value->count(); // PageArray::count()  [oai_citation:0‡ProcessWire](https://processwire.com/api/ref/pages/?utm_source=chatgpt.com)
	$modalId = 'modal_' . uniqid();

	// Link, der das Modal öffnet
	$out  = "<a href=\"#{$modalId}\" uk-toggle>{$count}</a>";

	// MarkupAdminDataTable holen
	$table = wire('modules')->get('MarkupAdminDataTable');
	$table->setClass('uk-table-divider uk-table-small');
	$table->setSortable(true);
	$table->setEncodeEntities(false);

	// Dynamisch die Header aus der ersten Zeile ermitteln
	$first = $value->first();
	if($first) {
		// getArray liefert alle Page-Eigenschaften und Feldwerte als Array  [oai_citation:1‡ProcessWire](https://processwire.com/api/ref/wire-array/get-array/?utm_source=chatgpt.com)
		$rowArray = $first->getArray();  
		$headers  = array_keys($rowArray);
	} else {
		$headers = [];
	}
	$table->headerRow($headers);

	// Jede Zeile befüllen
	foreach($value as $page) {
		$rowArray = $page->getArray();
		$cells = [];
		foreach($headers as $key) {
			$cells[] = htmlspecialchars((string) ($rowArray[$key] ?? ''));
		}
		$table->row($cells);
	}

	$tableHtml = $table->render();

	// Modal-Markup
	$out .= "<div id=\"{$modalId}\" uk-modal>";
	$out .=   "<div class=\"uk-modal-dialog uk-modal-body\">";
	$out .=     "<button class=\"uk-modal-close-default\" type=\"button\" uk-close></button>";
	$out .=     "<h3 class=\"uk-modal-title\">Details ({$count})</h3>";
	$out .=     $tableHtml;
	$out .=   "</div>";
	$out .= "</div>";

	return $out;
};