<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeUrl
 * Available variable: $value
 */
return function($value, $config = []) {
	if ($value) {
		return '<a href="' . htmlspecialchars($value) . '" target="_blank">' 
			 . htmlspecialchars($value) . '</a>';
	}
	return '';
};