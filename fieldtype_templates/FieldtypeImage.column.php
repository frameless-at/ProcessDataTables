<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeImage
 * Available variable: $value
 * Uses global config: $config['imageThumbnailMaxWidth']
 */
return function($value, $config = []) {
    $out = '';
    $thumbWidth = $config['imageThumbnailMaxWidth'] ?? 120;

    if ($value && $value->count()) {
        $first = $value->first();
        if ($first && $first->ext && $first->url) {
            // Thumbnail for the first image
            $out .= '<a href="' . $first->url . '"><img src="' . $first->url . '" style="max-width:' . $thumbWidth . 'px"></a> ';
        } else {
            $out .= '<a href="' . $first->url . '">' . htmlspecialchars($first->name) . '</a> ';
        }
    } else {
        $out .= htmlspecialchars((string) $value);
    }

    return $out;
};