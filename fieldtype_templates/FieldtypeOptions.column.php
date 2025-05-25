<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeOptions
 * Available variable: $value
 * Uses global config: $config['optionLabelMap']
 */
 return function($value, $config = []) {
       $out='';
       if($value->count()){
        foreach($value as $item)
            $out.=$item->title;
        }
        else
            $out.=$value->title;
        return $out;    
  };