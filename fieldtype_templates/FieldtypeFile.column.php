<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeFile
 * Available variable: $value
 */
return function($value, $config = []) {
    $out = '';
    if ($value && $value->count()) {
        foreach ($value as $file) {
            $out .= '<a href="' . $file->url . '">' . htmlspecialchars($file->name) . '</a> ';
        }
    } else {
        $out .= htmlspecialchars((string) $value);
    }
    return $out;
};