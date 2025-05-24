<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeOptions
 * Available variable: $value
 * Uses global config: $config['optionLabelMap']
 */
return function($value, $config = []) {
	$map = $config['optionLabelMap'] ?? [];
	return isset($map[$value])
		? htmlspecialchars($map[$value])
		: htmlspecialchars($value);
};