<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeInteger
  * Available variable: $value
  * Uses global config: $config['currencyFormat'], $config['numberDecimals']
  */
 
 // Currency format: e.g. "de_AT:EUR"
 if(!empty($config['currencyFormat'])) {
     $formatter = new NumberFormatter(explode(':', $config['currencyFormat'])[0], NumberFormatter::CURRENCY);
     echo $formatter->formatCurrency((float)$value, explode(':', $config['currencyFormat'])[1]);
 } else {
     $decimals = $config['numberDecimals'] ?? 0;
     echo number_format((float)$value, $decimals, ',', '.');
 }