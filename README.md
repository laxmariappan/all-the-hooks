# All The Hooks

A WordPress plugin to discover and document hooks (actions and filters) in other WordPress plugins. The primary interface is a WP-CLI command which can scan plugin files and output detailed hook information in JSON or Markdown format.

## Features

- Scan WordPress plugins for all defined/used hooks
- Identify both actions and filters
- Extract and parse DocBlock comments for hooks when available
- Output in JSON or Markdown format
- Save results to file or output to CLI
- Filter hooks by type (action or filter)

## Installation

1. Clone this repository to your WordPress plugins directory:
   ```
   cd wp-content/plugins
   git clone https://github.com/laxmariappan/all-the-hooks.git
   ```

2. Install dependencies using Composer:
   ```
   cd all-the-hooks
   composer install
   ```

3. Activate the plugin in the WordPress admin or using WP-CLI:
   ```
   wp plugin activate all-the-hooks
   ```

## Usage

The plugin provides a WP-CLI command with several options:

```
wp all-the-hooks scan --plugin=<plugin-slug> [--format=<json|markdown>] [--include_docblocks=<true|false>] [--output_path=<path>] [--hook_type=<all|action|filter>]
```

### Options

- `--plugin=<plugin-slug>`: (Required) The slug of the installed WordPress plugin to scan.
- `--format=<json|markdown>`: (Optional, default: `json`) Specifies the output format.
- `--include_docblocks=<true|false>`: (Optional, default: `false`) If `true`, the tool will extract and include the PHP DocBlock comments associated with hooks.
- `--output_path=<path>`: (Optional) Specifies a custom file path to save the output. If not provided, the output is directed to STDOUT.
- `--hook_type=<all|action|filter>`: (Optional, default: `all`) Filters the results by hook type.

### Examples

Scan WooCommerce plugin for hooks and output JSON:
```
wp all-the-hooks scan --plugin=woocommerce
```

Scan Akismet plugin for hooks, include docblocks, and output as Markdown:
```
wp all-the-hooks scan --plugin=akismet --include_docblocks=true --format=markdown
```

Scan a plugin for actions only and save to a specific file:
```
wp all-the-hooks scan --plugin=jetpack --hook_type=action --output_path=/path/to/jetpack-actions.json
```

## Output Format

### JSON

JSON output follows this structure:

```json
[
  {
    "name": "hook_name",
    "type": "action|filter",
    "file": "relative/path/to/file.php",
    "line_number": 123,
    "function_call": "add_action|add_filter|do_action|apply_filters",
    "docblock_raw": "/**\n * DocBlock comment\n */",
    "docblock_parsed": {
      "summary": "Short description",
      "description": "Long description",
      "params": [
        {"name": "$param1", "type": "string", "description": "Description of param1"}
      ],
      "return": {"type": "mixed", "description": "Return description"}
    }
  }
]
```

### Markdown

Markdown output is structured as follows:

```markdown
# Hooks for Plugin: plugin-slug

This document lists all hooks (actions and filters) found in the plugin-slug plugin.

## Summary

- Total Hooks: 100
- Actions: 60
- Filters: 40

## Actions

### `action_hook_name`
- **File:** `path/to/file.php`
- **Line:** 123
- **Function:** `add_action`
- **DocBlock:**
```php
/**
 * Description of the hook.
 *
 * @param string $param1 Description of param1.
 */
```
- **Summary:** Description of the hook.
- **Parameters:**
  - `$param1` (string): Description of param1.

## Filters

### `filter_hook_name`
...
```

## How It Works

The plugin uses PHP-Parser to scan PHP files and construct an Abstract Syntax Tree (AST). This allows accurate identification of hook function calls and their associated DocBlocks.

For each file in the target plugin:
1. The file is parsed into an AST
2. Hook functions (`add_action`, `add_filter`, etc.) are identified
3. Hook names are extracted from string literal arguments
4. DocBlocks are associated with hook calls if enabled
5. Results are collected and formatted according to output preferences

## Requirements

- WordPress 5.0+
- PHP 7.2+
- WP-CLI
- Composer (for dependency installation)

## Limitations

- Currently only supports identifying hooks with literal string names (does not handle variable hook names or concatenated strings)
- Only scans plugin files (theme scanning is a future enhancement)
- DocBlock extraction requires properly formatted DocBlocks immediately preceding hook functions

## Credits

- [nikic/PHP-Parser](https://github.com/nikic/PHP-Parser) - Used for PHP code parsing
- [phpDocumentor/ReflectionDocBlock](https://github.com/phpDocumentor/ReflectionDocBlock) - Used for DocBlock parsing

## License

GPL-2.0-or-later
