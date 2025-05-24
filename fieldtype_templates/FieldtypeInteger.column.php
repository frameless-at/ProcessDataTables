<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeInteger
 * Available variable: $value
 * Uses global config: $config['currencyFormat'], $config['numberDecimals']
 */
return function($value, $config = []) {
    if (!empty($config['currencyFormat'])) {
        $parts = explode(':', $config['currencyFormat']);
        $formatter = new NumberFormatter($parts[0], NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((int) $value, $parts[1]);
    } else {
        $decimals = $config['numberDecimals'] ?? 0;
        return number_format((int) $value, $decimals, ',', '.');
    }
};