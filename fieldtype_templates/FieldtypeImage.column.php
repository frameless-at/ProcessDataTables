<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeImage
  * Available variable: $value
  * Uses global config: $config['imageThumbnailMaxWidth']
  */
 
 $thumbWidth = $config['imageThumbnailMaxWidth'] ?? 120;
 
 if($value && $value->count()) {
     $first = $value->first();
     if($first && $first->ext && $first->url) {
         // Thumbnail für das erste Bild
         if(property_exists($first, 'width') && $first->width) {
             echo '<a href="'.$first->url.'"><img src="'.$first->url.'" style="max-width:'.$thumbWidth.'px"></a> ';
         } else {
             echo '<a href="'.$first->url.'">'.htmlspecialchars($first->name).'</a> ';
         }
     }
 }
 else   
  echo htmlspecialchars($value)