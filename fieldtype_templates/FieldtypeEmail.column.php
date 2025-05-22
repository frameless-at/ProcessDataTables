<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeEmail
  * Available variable: $value
  */
 echo '<a href="mailto:'.htmlspecialchars($value).'">'.htmlspecialchars($value).'</a>';