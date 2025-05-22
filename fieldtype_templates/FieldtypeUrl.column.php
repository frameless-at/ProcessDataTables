<?php
/**
* Output template for field: {{FIELDNAME}}
* Column label: {{LABEL}}
* Fieldtype: FieldtypeUrl
* Available variable: $value
*/
if($value) echo '<a href="'.htmlspecialchars($value).'" target="_blank">'.htmlspecialchars($value).'</a>';