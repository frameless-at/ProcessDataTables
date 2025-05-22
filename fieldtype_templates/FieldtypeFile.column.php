<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeFile
* Available variable: $value
*/
 
 if($value && $value->count()) {
     foreach($value as $i => $file) {
         echo '<a href="'.$file->url.'">'.htmlspecialchars($file->name).'</a> ';
     }
 }
 else   
    echo htmlspecialchars($value)