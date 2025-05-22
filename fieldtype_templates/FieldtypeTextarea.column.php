<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeTextarea
 * Available variable: $value
 * Uses global config: $config['textareaStripTags'], $config['textareaMaxLength']
 */

$val = $config['textareaStripTags'] ? strip_tags($value) : $value;
$max = $config['textareaMaxLength'] ?? 120;
echo htmlspecialchars(mb_strimwidth((string)$val, 0, $max, '…'));