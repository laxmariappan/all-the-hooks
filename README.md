# All The Hooks

A WordPress plugin to discover and document hooks (actions and filters) in other WordPress plugins. The primary interface is a WP-CLI command which can scan plugin files and output detailed hook information in JSON, Markdown, or HTML format.

## Why This Tool? Isn't Documentation Enough?

Many WordPress plugins contain action and filter hooks that aren't formally documented. While experienced developers can often identify and understand these hooks by examining the source code, manually searching through codebases each time you need to find an extension point can be time-consuming and inefficient.

This utility was created to simplify that process, providing a quick and straightforward way to discover all available hooks within a plugin.

## Installation

- Clone the repo on wp-content/plugins, cd all-the-hooks and then run composer install

## Features

- Scan WordPress plugins for all defined/used hooks
- Identify both actions and filters
- Extract and parse DocBlock comments for hooks when available
- Output in JSON, Markdown, or HTML format
- Source context display with syntax highlighting (3-5 lines surrounding each hook)
- Related hooks identification based on naming patterns and proximity
- Interactive HTML output with dark/light mode and searchable interface
- Structured parameter documentation with tables
- Save results to file or output to CLI
- Filter hooks by type (action or filter)
- Responsive design for better viewing on all devices

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
wp all-the-hooks scan --plugin=<plugin-slug> [--format=<json|markdown|html>] [--include_docblocks=<true|false>] [--output_path=<path>] [--hook_type=<all|action|filter>]
```

### Options

- `--plugin=<plugin-slug>`: (Required) The slug of the installed WordPress plugin to scan.
- `--format=<json|markdown|html>`: (Optional, default: `json`) Specifies the output format.
- `--include_docblocks=<true|false>`: (Optional, default: `false`) If `true`, the tool will extract and include the PHP DocBlock comments associated with hooks.
- `--output_path=<path>`: (Optional) Specifies a custom file path to save the output. If not provided, the output is directed to STDOUT.
- `--hook_type=<all|action|filter>`: (Optional, default: `all`) Filters the results by hook type.

### Screenshots

Search form and filtering by type ( actions and filters ) and source ( core or plugin specific )
![image](https://github.com/user-attachments/assets/eaeddccd-d183-432e-b752-db5983471da4)


![image](https://github.com/user-attachments/assets/74c5b8e7-5c55-4676-8508-17689fa6ee7f)
Hook with parameters


![image](https://github.com/user-attachments/assets/de9ab44c-a46a-4076-a818-b1adbe4bff36)
WooCommerce hook's context

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

Scan Easy Digital Downloads Pro plugin with full documentation and output as HTML to current directory:
```
wp all-the-hooks scan --plugin=easy-digital-downloads-pro --format=html --include_docblocks=true --output_path=./
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

/**
 * Description of the hook.
 *
 * @param string $param1 Description of param1.
 */

- **Summary:** Description of the hook.
- **Parameters:**
  - `$param1` (string): Description of param1.

## Filters

### `filter_hook_name`

- **File:** `path/to/file.php`
- **Line:** 123
- **Function:** `apply_filters`
- **DocBlock:**

/**
 * Description of the hook.
 *
 * @param string $param1 Description of param1.
 */
- **Summary:** Description of the hook.
- **Parameters:**
  - `$param1` (string): Description of param1.
```

### HTML

HTML output provides an interactive, user-friendly interface with the following features:

- **Search and filter capabilities** - Find hooks by name, type (action/filter), or source (core/plugin)
- **Dark/light mode toggle** - Adjust the interface for different viewing preferences
- **Source context display** - View the actual code surrounding each hook with syntax highlighting
- **Related hooks section** - Discover hooks that are related by naming patterns or proximity in code
- **Structured documentation** - Well-formatted parameter tables and docblock information
- **Responsive design** - Optimized viewing on desktop and mobile devices

The HTML output is a standalone file that can be viewed in any browser without additional dependencies.

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
