<?php
/**
 * Output template for field: {{FIELDNAME}}
 * Column label: {{LABEL}}
 * Fieldtype: FieldtypeImage
 * Available variable: $value
 * Uses global config: $config['imageThumbnailMaxWidth']
 */
return function($value, $config = []) {
      // no images? just escape whatever’s there
      if (!$value || !$value->count()) {
          return htmlspecialchars((string) $value);
      }
  
      // pick first image and generate IDs
      $first   = $value->first();
      $thumbW  = (int) ($config['imageThumbnailMaxWidth'] ?? 120);
      $modalId = 'imgModal_' . uniqid();
  
      // build thumbnail/link
      if ($first->url && $first->ext) {
          $thumbUrl = $first->width($thumbW)->url;
          $link     = "<a href=\"#{$modalId}\" uk-toggle><img src=\"{$thumbUrl}\" alt=\"\"></a>";
      } else {
          $link  = htmlspecialchars($first->name);
      }
  
      // build modal with full-size image
      $fullUrl = htmlspecialchars($first->url);
      $modal   = <<<HTML
  <div id="{$modalId}" uk-modal>
    <div class="uk-modal-dialog uk-modal-body uk-flex uk-flex-center">
      <button class="uk-modal-close-default" type="button" uk-close></button>
      <img src="{$fullUrl}" alt="" />
    </div>
  </div>
  HTML;
  
      return $link . $modal;
  };