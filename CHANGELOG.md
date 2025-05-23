# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

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

---

For a full commit and change history, see the [GitHub commit log](https://github.com/frameless-at/ProcessDataTables/commits).