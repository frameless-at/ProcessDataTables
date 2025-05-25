<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: WireData (meta)
 * Available variable: $value
 */
return function($value, $config = []) {
	// convert page to array, then to nicely formatted JSON
	$data = (array) $value->getArray();
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	// wrap in a styled <pre> for readability
	return '<pre style="white-space: pre-wrap; font-size: 0.9em; margin: 0; padding: 0.5em; background: #f8f8f8; border: 1px solid #ddd;">'
		 . htmlentities($json)
		 . '</pre>';
};