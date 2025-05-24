<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeFloat
 * Available variable: $value
 * Uses global config: $config['currencyFormat'], $config['numberDecimals']
 */
return function($value, $config = []) {
    $out = '';
    if (!empty($config['currencyFormat'])) {
        $parts = explode(':', $config['currencyFormat']);
        $formatter = new NumberFormatter($parts[0], NumberFormatter::CURRENCY);
        $out = $formatter->formatCurrency((float) $value, $parts[1]);
    } else {
        $decimals = $config['numberDecimals'] ?? 0;
        $out = number_format((float) $value, $decimals, ',', '.');
    }
    return $out;
};