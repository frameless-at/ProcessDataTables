<?php
/**
* Output template for field: {{FIELDNAME}}
* Column label: {{LABEL}}
* Fieldtype: FieldtypeOptions
* Available variable: $value
* Uses global config: $config['optionLabelMap']
*/

$map = $config['optionLabelMap'] ?? [];
echo isset($map[$value]) ? htmlspecialchars($map[$value]) : htmlspecialchars($value);