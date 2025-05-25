<?php

/**
 * ProcessDataTablesConfig
 *
 * Simple config module for ProcessDataTables global settings.
 *
 * @author frameless Media
 * @version 0.5.0
 * @license MIT
 */
 
class ProcessDataTablesConfig extends ModuleConfig {

	public static function getModuleInfo() {
		return [
			'title' => 'ProcessDataTables Config',
			'version' => 1,
			'summary' => 'Global configuration for ProcessDataTables module.',
			'autoload' => false,
			'singular' => true,
		];
	}
	
public function getInputfields() {
		$inputfields = new InputfieldWrapper();
	
		// Checkbox labels
		$f = $this->modules->get('InputfieldText');
		$f->name = 'checkboxYesLabel';
		$f->label = __('Label for "Yes" checkbox output');
		$f->description = __('Displayed for "Yes" values in checkbox fields.');
		$f->value = $this->checkboxYesLabel ?? 'Yes';
		$f->columnWidth = 25;
		$inputfields->add($f);
	
		$f = $this->modules->get('InputfieldText');
		$f->name = 'checkboxNoLabel';
		$f->label = __('Label for "No" checkbox output');
		$f->description = __('Displayed for "No" values in checkbox fields.');
		$f->columnWidth = 25;
		$f->value = $this->checkboxNoLabel ?? 'No';
		$inputfields->add($f);
	
		// Date format
		$f = $this->modules->get('InputfieldText');
		$f->name = 'dateFormat';
		$f->label = __('Date Format');
		$f->description = __('PHP date format string, e.g. "d.m.Y H:i".');
		$f->columnWidth = 25;
		$f->value = $this->dateFormat ?? 'd.m.Y H:i';
		$inputfields->add($f);
	
		// Currency format
		$f = $this->modules->get('InputfieldText');
		$f->name = 'currencyFormat';
		$f->label = __('Currency Format');
		$f->description = __('e.g. de_AT:EUR or en_US:USD');
		$f->columnWidth = 25;
		$f->value = $this->currencyFormat ?? 'de_AT:EUR';
		$inputfields->add($f);
	
		// Decimal places for numbers
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'numberDecimals';
		$f->label = __('Number of decimals for numbers');
		$f->description = __('How many decimal places should numbers display?');
		$f->columnWidth = 25;
		$f->value = $this->numberDecimals ?? 2;
		$inputfields->add($f);
	
		// Maximum image thumbnail width
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'imageThumbnailMaxWidth';
		$f->label = __('Maximum image thumbnail width (px)');
		$f->description = __('Default: 100px');
		$f->columnWidth = 25;
		$f->value = $this->imageThumbnailMaxWidth ?? 100;
		$inputfields->add($f);
	
		// Option label map
		$f = $this->modules->get('InputfieldTextarea');
		$f->name = 'optionLabelMap';
		$f->label = __('Options Label Mapping');
		$f->description = __('Optional: Map option values to labels (one per line: value=Label)');
		$f->columnWidth = 50;
		$f->value = $this->optionLabelMap ?? '';
		$inputfields->add($f);
	
		// Page reference separator
		$f = $this->modules->get('InputfieldText');
		$f->name = 'pageRefSeparator';
		$f->label = __('Page reference separator');
		$f->description = __('Separator for multiple referenced pages (e.g. comma, semicolon, etc.)');
		$f->columnWidth = 25;
		$f->value = $this->pageRefSeparator ?? ', ';
		$inputfields->add($f);
	
		// Text field max length
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'textMaxLength';
		$f->label = __('Max length for text output');
		$f->description = __('Maximum character length for simple text fields');
		$f->columnWidth = 25;
		$f->value = $this->textMaxLength ?? 80;
		$inputfields->add($f);
	
		// Textarea: strip HTML tags
		$f = $this->modules->get('InputfieldCheckbox');
		$f->name = 'textareaStripTags';
		$f->label = __('Strip HTML tags from textarea?');
		$f->description = __('If enabled, HTML tags will be removed from textarea output.');
		$f->columnWidth = 25;
		$f->checked = !empty($this->textareaStripTags);
		$inputfields->add($f);
	
		// Textarea max length
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'textareaMaxLength';
		$f->label = __('Max length for textarea output');
		$f->description = __('Maximum character length for textarea fields');
		$f->columnWidth = 25;
		$f->value = $this->textareaMaxLength ?? 120;
		$inputfields->add($f);
	
		return $inputfields;
	}
}
