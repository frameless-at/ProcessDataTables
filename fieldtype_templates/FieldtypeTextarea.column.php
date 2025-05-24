<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeTextarea
 * Available variable: $value
 * Uses global config: $config['textareaStripTags'], $config['textareaMaxLength']
 */
return function($value, $config = []) {
    $val = $config['textareaStripTags'] ? strip_tags($value) : $value;
    $max = $config['textareaMaxLength'] ?? 120;
    return htmlspecialchars(mb_strimwidth((string)$val, 0, $max, '…'));
};