<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeDatetime
 * Available variable: $value
 * Uses global config: $config['dateFormat']
 */
return function($value, $config = []) {
    $fmt = $config['dateFormat'] ?? 'Y-m-d H:i';
    return $value
        ? date($fmt, $value)
        : '';
};