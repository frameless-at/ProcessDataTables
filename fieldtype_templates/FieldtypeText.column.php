<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeText
 * Available variable: $value
 * Uses global config: $config['textMaxLength']
 */
return function($value, $config = []) {
    // Kürzt den Text auf die maximale Länge aus der Config (Standard: 80)
    $maxLength = $config['textMaxLength'] ?? 80;
    $text = (string)$value;
    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength) . '…';
    }
    return htmlspecialchars($text);
};