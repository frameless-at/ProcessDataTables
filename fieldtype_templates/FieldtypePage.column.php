<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypePage
 * Available variable: $value
 * Uses global config: $config['pageRefSeparator']
 */
return function($value, $config = []) {
	$sep = $config['pageRefSeparator'] ?? ', ';
	$out = '';

	if ($value instanceof PageArray || is_array($value)) {
		$links = [];
		foreach ($value as $p) {
			$links[] = '<a href="' . $p->url . '">' . htmlspecialchars($p->title) . '</a>';
		}
		$out = implode($sep, $links);
	} elseif ($value instanceof Page) {
		$out = '<a href="' . $value->url . '">' . htmlspecialchars($value->title) . '</a>';
	} else {
		$out = htmlspecialchars((string) $value);
	}

	return $out;
};