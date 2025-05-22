<?php
/**
* Output template for field: {{FIELDNAME}}
* Column label: {{LABEL}}
 * Fieldtype: FieldtypePage
 * Available variable: $value
 * Uses global config: $config['pageRefSeparator']
 */

$sep = $config['pageRefSeparator'] ?? ', ';
if($value instanceof PageArray || is_array($value)) {
	$out = [];
	foreach($value as $p) $out[] = '<a href="'.$p->url.'">'.htmlspecialchars($p->title).'</a>';
	echo implode($sep, $out);
} elseif($value instanceof Page) {
	echo '<a href="'.$value->url.'">'.htmlspecialchars($value->title).'</a>';
} else {
	echo htmlspecialchars((string)$value);
}