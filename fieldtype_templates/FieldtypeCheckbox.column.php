<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeCheckbox
 * Available variable: $value
 * Uses global config: $config['checkboxYesLabel'], $config['checkboxNoLabel']
 */
return function($value, $config = []) {
	return $value
		? ($config['checkboxYesLabel'] ?? 'Yes')
		: ($config['checkboxNoLabel'] ?? 'No');
};