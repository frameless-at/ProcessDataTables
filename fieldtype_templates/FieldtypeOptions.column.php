<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeOptions
 * Available variable: $value
 * Uses global config: $config['optionLabelMap']
 */
 return function($value, $config = []) {
      if (is_iterable($value)) {
          $out = '';
          foreach ($value as $raw => $label) {
              $out .= '<span class="uk-label uk-label-small" style="margin-right:0.25em;">'
                    . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                    . '</span>';
          }
          return $out;
      }
      return '<span class="uk-label uk-label-small">'
           . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')
           . '</span>';
  };