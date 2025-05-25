# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [0.5.0] – 2025-05-25

### Added
- **Write-permission checks** in `install()`:  
  - Ensure `column_templates/` directory exists with correct permissions.  
  - Attempt to fix unwritable directory or throw a clear `WireException`.

### Changed
- **Template file structure**  
  - Stubs are now namespaced into subfolders per DataTable (`column_templates/<table>/<slug>.column.php`) instead of a flat directory.  
  - `TemplateGenerator::getTemplateFilePath()` and `createTemplateFile()` updated to build and create these subdirectories automatically.

- **Legacy-stub upgrade**  
  - `loadColumnTemplates()` now checks each stub for a `return function(...)` signature.  
  - If missing, it **archives** the old stub (prefixing its filename with `_`) before regenerating a new closure-based stub.

- **Page-property handling**  
  - Unified list of core properties driven by `getStandardPropertyLabels()` (including `status`).  
  - All Page-property stubs now use `return …;` inside closures and map bitmask flags (`status`) to human-readable labels.

### Fixed
- Avoid nested PHP tags or duplicate wrappers when copying core‐stub templates.  
- Ensure `$config` defaults are merged so flags like `textareaStripTags` never trigger “undefined key” warnings.

---

## [0.4.0] - 2025-05-24

### Changed
- **Loader (`ProcessDataTables`)**  
  - Refactored `loadColumnTemplates()` to read module config once and wrap each per-column stub as a callable that accepts both `$value` **and** `$config`. Includes each stub file only once per column instead of once per cell.
- **Stub Generation (`TemplateGenerator`)**  
  - Overhauled `createTemplateFile()` to copy core PHP stub files 1:1 (with `{{FIELDNAME}}`/`{{LABEL}}` token replacement) or fall back to generated closures.  
  - Overhauled `getTemplateContent()` so that all Page-property stubs (`id`, `name`, `created`, `modified`, `parent`, `url`) use `return …;` inside a closure instead of direct `echo` calls, and that the fallback stub also returns rather than echoes its output.
- **Fieldtype-specific Stubs**  
  - All provided fieldtype templates (Checkbox, Datetime, Email, File, Float, Image, Integer, Options, Page, PageTable, Repeater, RepeaterMatrix, Text, Textarea, Url) have been converted from inline‐echo files to `<?php return function($value, $config = []) { … };` closures, collecting their output into `$out` or returning directly.

### Removed
- Old non-closure stub files under `column_templates/` must be deleted to allow regeneration of the new closure-based stubs.

## [0.3.0] - 2025-05-23

### Added
- Initial public release of the module, including:
  - `ProcessDataTables.module.php`: Main module for fully customizable backend tables in ProcessWire.
  - `ProcessDataTablesConfig.php`: Global configuration module for various output formats and options.
  - `TemplateGenerator.php`: Generates PHP template stubs for each column output.
  - Fieldtype-specific template stubs under `fieldtype_templates/` (Checkbox, Datetime, Email, File, Float, Image, Integer, Options, Page, PageTable, Repeater, RepeaterMatrix, Text, Textarea, Url).
  - Comprehensive README with installation and usage instructions, plus screenshots.
  - License file (GNU GPLv3) added to the project.

### Notes
- Version 0.3.0 provides a flexible way to display any fields and page properties as columns in backend tables.
- A PHP template stub is generated for each column and can be customized individually.
- Global formatting options are configurable via the configuration module.
- All customization and output options are for backend/admin use only.

For a full commit and change history, see the [GitHub commit log](https://github.com/frameless-at/ProcessDataTables/commits).
