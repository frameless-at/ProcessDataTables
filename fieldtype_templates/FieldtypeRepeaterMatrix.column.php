<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeRepeaterMatrix
 * Available variable: $value
 */
return function($value, $config = []) {
	$count   = $value->count();
	$modalId = 'modal_' . uniqid();
	// Link to open modal
	$out  = "<a href=\"#{$modalId}\" uk-toggle>{$count}</a>";

	// Build DataTable
	$table = wire('modules')->get('MarkupAdminDataTable');
	$table->setClass('uk-table-divider uk-table-small');
	$table->setSortable(true);
	$table->setEncodeEntities(false);

	// Determine headers from first matrix row
	$first = $value->first();
	if ($first) {
		$rowArray = $first->getArray();
		$headers  = array_keys($rowArray);
	} else {
		$headers = [];
	}
	$table->headerRow($headers);

	// Populate rows
	foreach ($value as $item) {
		$rowArray = $item->getArray();
		$cells    = [];
		foreach ($headers as $col) {
			$cells[] = htmlspecialchars((string) ($rowArray[$col] ?? ''));
		}
		$table->row($cells);
	}

	$tableHtml = $table->render();

	// Modal markup
	$out .= "<div id=\"{$modalId}\" uk-modal>";
	$out .=   "<div class=\"uk-modal-dialog uk-modal-body\">";
	$out .=     "<button class=\"uk-modal-close-default\" type=\"button\" uk-close></button>";
	$out .=     "<h3 class=\"uk-modal-title\">Details ({$count})</h3>";
	$out .=     $tableHtml;
	$out .=   "</div>";
	$out .= "</div>";

	return $out;
};