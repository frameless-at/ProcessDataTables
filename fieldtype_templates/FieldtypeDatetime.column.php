<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeDatetime
  * Available variable: $value
  * Uses global config: $config['dateFormat']
  */
 
 $fmt = $config['dateFormat'] ?? 'Y-m-d H:i';
 if($value) echo date($fmt, $value);