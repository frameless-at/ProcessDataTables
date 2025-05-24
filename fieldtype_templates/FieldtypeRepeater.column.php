<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeRepeater
 * Available variable: $value
 */
return function($value, $config = []) {
	return $value->count();
};