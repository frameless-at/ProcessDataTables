<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypePageTable
 * Available variable: $value (PageArray)
 */
return function($value, $config = []) {
	$out = '';
	
	  // 1) Columns to skip in the modal
	  $skip = ['field_name'];
  
	  // 2) Optional: Custom labels for modal table
	  $labelMap = [
		  'field_name'   => 'Label',
	  ];
  
	  $count   = $value->count();
	  $modalId = 'modal_' . uniqid();
	  if(!$count) return $out;
  	
	  $out = "<a href=\"#{$modalId}\" uk-toggle>{$count}</a>";

	  // 3) table
	  $table = wire('modules')->get('MarkupAdminDataTable');
	  $table->setClass('uk-table-divider uk-table-small');
	  $table->setSortable(true);
	  $table->setEncodeEntities(false);
  
	  $first    = $value->first();
	  $rowArray = $first->getArray();
	  $headers  = array_filter(array_keys($rowArray), fn($k) => !in_array($k, $skip, true));
  
	  // 4) Custom labels
	  $displayHeaders = array_map(
		  fn($key) => $labelMap[$key] ?? ucfirst(str_replace('_',' ',$key)),
		  $headers
	  );
	  $table->headerRow($displayHeaders);
  
	  // 5) Data
	  foreach ($value as $item) {
		  $rowArray = $item->getArray();
		  $cells    = [];
		  foreach ($headers as $key) {
			  $raw = $rowArray[$key] ?? '';
			  switch ($key) {
				  case 'field_name':
					  $cells[] = htmlentities((string)$raw);
					  break;
				  default:
					  $cells[] = htmlentities((string)$raw);
			  }
		  }
		  $table->row($cells);
	  }
	  
	  // 6) Modal-Markup
	  $tableHtml = $table->render();
	  $out .= "<div id=\"{$modalId}\" uk-modal>
				  <div class=\"uk-modal-dialog uk-modal-body\">
					<button class=\"uk-modal-close-default\" type=\"button\" uk-close></button>
					<h3 class=\"uk-modal-title\">Seiten/Aufrufe ({$count})</h3>
					{$tableHtml}
				  </div>
			   </div>";
  
	  return $out;
};