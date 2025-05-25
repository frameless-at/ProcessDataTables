<?php

/**
 * ProcessDataTablesConfig
 *
 * Simple config module for ProcessDataTables global settings.
 *
 * @author frameless Media
 * @version 0.5.2
 * @license MIT
 */
 
class ProcessDataTablesConfig extends ModuleConfig {

	public static function getModuleInfo() {
		return [
			'title' => 'ProcessDataTables Config',
			'version' => 0.5.2,
			'summary' => 'Global configuration for ProcessDataTables module.',
			'autoload' => false,
			'singular' => true,
		];
	}
	
	public static function getDefaultConfig(): array {
		return [
			'checkboxYesLabel'       => 'Yes',
			'checkboxNoLabel'        => 'No',
			'dateFormat'             => 'd.m.Y H:i',
			'currencyFormat'         => 'de_AT:EUR',
			'numberDecimals'         => 2,
			'imageThumbnailMaxWidth' => 100,
			'optionLabelMap'         => '',
			'pageRefSeparator'       => ', ',
			'textMaxLength'          => 80,
			'textareaStripTags'      => false,
			'textareaMaxLength'      => 120,
		];
	}
	
	public function getInputfields() {
		$defaults = self::getDefaultConfig();
		$inputfields = new InputfieldWrapper();
	
		// Checkbox labels
		$f = $this->modules->get('InputfieldText');
		$f->name = 'checkboxYesLabel';
		$f->label = __('Label for "Yes" checkbox output');
		$f->description = __('Displayed for "Yes" values in checkbox fields.');
		$f->value = $this->checkboxYesLabel ?? $defaults['checkboxYesLabel'];
		$f->columnWidth = 25;
		$inputfields->add($f);
	
		$f = $this->modules->get('InputfieldText');
		$f->name = 'checkboxNoLabel';
		$f->label = __('Label for "No" checkbox output');
		$f->description = __('Displayed for "No" values in checkbox fields.');
		$f->columnWidth = 25;
		$f->value = $this->checkboxNoLabel ?? $defaults['checkboxNoLabel'];
		$inputfields->add($f);
	
		// Date format
		$f = $this->modules->get('InputfieldText');
		$f->name = 'dateFormat';
		$f->label = __('Date Format');
		$f->description = __('PHP date format string, e.g. "d.m.Y H:i".');
		$f->columnWidth = 25;
		$f->value = $this->dateFormat ?? $defaults['dateFormat'];
		$inputfields->add($f);
	
		// Currency format
		$f = $this->modules->get('InputfieldText');
		$f->name = 'currencyFormat';
		$f->label = __('Currency Format');
		$f->description = __('e.g. de_AT:EUR or en_US:USD');
		$f->columnWidth = 25;
		$f->value = $this->currencyFormat ?? $defaults['currencyFormat'];
		$inputfields->add($f);
	
		// Decimal places for numbers
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'numberDecimals';
		$f->label = __('Number of decimals for numbers');
		$f->description = __('How many decimal places should numbers display?');
		$f->columnWidth = 25;
		$f->value = $this->numberDecimals ?? $defaults['numberDecimals'];
		$inputfields->add($f);
	
		// Maximum image thumbnail width
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'imageThumbnailMaxWidth';
		$f->label = __('Maximum image thumbnail width (px)');
		$f->description = __('Default: 100px');
		$f->columnWidth = 25;
		$f->value = $this->imageThumbnailMaxWidth ?? $defaults['imageThumbnailMaxWidth'];
		$inputfields->add($f);
	
		// Option label map
		$f = $this->modules->get('InputfieldTextarea');
		$f->name = 'optionLabelMap';
		$f->label = __('Options Label Mapping');
		$f->description = __('Optional: Map option values to labels (one per line: value=Label)');
		$f->columnWidth = 50;
		$f->value = $this->optionLabelMap ?? $defaults['optionLabelMap'];
		$inputfields->add($f);
	
		// Page reference separator
		$f = $this->modules->get('InputfieldText');
		$f->name = 'pageRefSeparator';
		$f->label = __('Page reference separator');
		$f->description = __('Separator for multiple referenced pages (e.g. comma, semicolon, etc.)');
		$f->columnWidth = 25;
		$f->value = $this->pageRefSeparator ?? $defaults['pageRefSeparator'];
		$inputfields->add($f);
	
		// Text field max length
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'textMaxLength';
		$f->label = __('Max length for text output');
		$f->description = __('Maximum character length for simple text fields');
		$f->columnWidth = 25;
		$f->value = (int) ($this->numberDecimals ?? $defaults['numberDecimals']);
		$inputfields->add($f);
	
		// Textarea: strip HTML tags
		$f = $this->modules->get('InputfieldCheckbox');
		$f->name = 'textareaStripTags';
		$f->label = __('Strip HTML tags from textarea?');
		$f->description = __('If enabled, HTML tags will be removed from textarea output.');
		$f->columnWidth = 25;
		$f->checked = (bool) ($this->textareaStripTags ?? $defaults['textareaStripTags']);
		$inputfields->add($f);
	
		// Textarea max length
		$f = $this->modules->get('InputfieldInteger');
		$f->name = 'textareaMaxLength';
		$f->label = __('Max length for textarea output');
		$f->description = __('Maximum character length for textarea fields');
		$f->columnWidth = 25;
		$f->value = $this->textareaMaxLength ?? $defaults['textareaMaxLength'];
		$inputfields->add($f);
	
		return $inputfields;
	}
}
