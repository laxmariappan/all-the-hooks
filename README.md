# All The Hooks

A WordPress plugin to discover and document hooks (actions and filters) in WordPress plugins and themes. Features both a WP-CLI command and a modern admin GUI interface to scan and analyze hooks with detailed information including DocBlocks, listeners, and relationships.

## Why This Tool? Isn't Documentation Enough?

Many WordPress plugins contain action and filter hooks that aren't formally documented. While experienced developers can often identify and understand these hooks by examining the source code, manually searching through codebases each time you need to find an extension point can be time-consuming and inefficient.

This utility was created to simplify that process, providing a quick and straightforward way to discover all available hooks within a plugin.

## Installation

- Clone the repo on wp-content/plugins, cd all-the-hooks and then run composer install

## Features

### Core Functionality
- **Scan WordPress plugins AND themes** for all defined/used hooks
- **Hook Usage Analyzer** - See what functions are listening to each hook
- Identify both actions and filters
- Extract and parse DocBlock comments for hooks when available
- Track hook listeners (callbacks, priorities, accepted arguments)
- Output in JSON, Markdown, or HTML format
- Source context display with syntax highlighting (3-5 lines surrounding each hook)
- Related hooks identification based on naming patterns and proximity

### Admin Interface (NEW in v1.1.0, Enhanced in v1.2.0)
- **Modern WordPress admin GUI** - No CLI required
- **Adaptive layout** - Centered form transitions to compact sticky bar when viewing results
- **Hook definition tracking** - See both hooks defined in plugins/themes AND external hooks they use
- AJAX-based scanning with real-time progress
- Interactive results table with search and filtering
- Filter by definition status (Defined Here vs Used/External)
- View hook listeners/callbacks with priority and arguments
- Download results directly from the admin
- Smooth transitions and animations for better UX
- Fully responsive design using WordPress native components

### Output Formats
- Interactive HTML output with dark/light mode and searchable interface
- Structured parameter documentation with tables
- Save results to file or output to CLI
- Filter hooks by type (action or filter)

## Installation

1. Clone this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/laxmariappan/all-the-hooks.git
   ```

2. Install PHP dependencies using Composer:
   ```bash
   cd all-the-hooks
   composer install
   ```

3. Install JavaScript dependencies and build the admin interface:
   ```bash
   npm install
   npm run build
   ```

4. Activate the plugin in the WordPress admin or using WP-CLI:
   ```bash
   wp plugin activate all-the-hooks
   ```

## Development

### Building the React Admin Interface

The plugin uses React with WordPress's DataViews components for the admin interface.

**Development mode (with hot reload):**
```bash
npm start
```

**Production build:**
```bash
npm run build
```

**Code formatting:**
```bash
npm run format
```

**Linting:**
```bash
npm run lint:js
```

### File Structure
```
all-the-hooks/
├── src/admin/              # React admin interface source
│   ├── App.js             # Main app component
│   ├── components/        # React components
│   │   ├── ScanForm.js   # Scan configuration form (DataForm)
│   │   └── ResultsView.js # Results table (DataViews)
│   ├── index.js          # Entry point
│   └── style.css         # Admin styles
├── includes/              # PHP classes
│   ├── class-admin-interface.php
│   ├── class-hook-scanner.php
│   ├── class-hook-visitor.php
│   └── class-output-formatter.php
├── build/                 # Built assets (generated)
└── vendor/                # Composer dependencies
```

## Usage

The plugin can be used in two ways:

### 1. Admin GUI (Recommended for most users)

1. Navigate to **All The Hooks** in your WordPress admin menu
2. Select whether you want to scan a plugin or theme
3. Choose the plugin/theme from the dropdown
4. Configure scan options (hook type, docblocks, output format)
5. Click "Scan for Hooks"
6. View results in an interactive table
7. Download results in your preferred format

### 2. WP-CLI Command (For developers and automation)

The plugin provides a WP-CLI command with several options:

```bash
# Scan a plugin
wp all-the-hooks scan --plugin=<plugin-slug> [--format=<json|markdown|html>] [--include_docblocks=<true|false>] [--output_path=<path>] [--hook_type=<all|action|filter>]

# Scan a theme
wp all-the-hooks scan --theme=<theme-slug> [--format=<json|markdown|html>] [--include_docblocks=<true|false>] [--output_path=<path>] [--hook_type=<all|action|filter>]
```

### WP-CLI Options

- `--plugin=<plugin-slug>`: The slug of the installed WordPress plugin to scan.
- `--theme=<theme-slug>`: The slug of the installed WordPress theme to scan.
- `--format=<json|markdown|html>`: (Optional, default: `json`) Specifies the output format.
- `--include_docblocks=<true|false>`: (Optional, default: `false`) If `true`, the tool will extract and include the PHP DocBlock comments associated with hooks.
- `--output_path=<path>`: (Optional) Specifies a custom file path to save the output. If not provided, saves to `.hooks/` directory.
- `--hook_type=<all|action|filter>`: (Optional, default: `all`) Filters the results by hook type.

**Note:** You must specify either `--plugin` OR `--theme`, not both.

### Screenshots

Search form and filtering by type ( actions and filters ) and source ( core or plugin specific )
![image](https://github.com/user-attachments/assets/eaeddccd-d183-432e-b752-db5983471da4)


![image](https://github.com/user-attachments/assets/74c5b8e7-5c55-4676-8508-17689fa6ee7f)
Hook with parameters


![image](https://github.com/user-attachments/assets/de9ab44c-a46a-4076-a818-b1adbe4bff36)
WooCommerce hook's context

### WP-CLI Examples

**Scan a plugin:**
```bash
wp all-the-hooks scan --plugin=woocommerce
```

**Scan a theme:**
```bash
wp all-the-hooks scan --theme=twentytwentyfour
```

**Scan with docblocks and output as Markdown:**
```bash
wp all-the-hooks scan --plugin=akismet --include_docblocks=true --format=markdown
```

**Scan for actions only:**
```bash
wp all-the-hooks scan --plugin=jetpack --hook_type=action --output_path=/path/to/jetpack-actions.json
```

**Scan with full documentation as HTML:**
```bash
wp all-the-hooks scan --plugin=easy-digital-downloads-pro --format=html --include_docblocks=true --output_path=./
```

**Scan active theme:**
```bash
wp all-the-hooks scan --theme=storefront --format=html --include_docblocks=true
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
    "is_core": "yes|no",
    "defined_here": true,
    "docblock_raw": "/**\n * DocBlock comment\n */",
    "docblock_parsed": {
      "summary": "Short description",
      "description": "Long description",
      "params": [
        {"name": "$param1", "type": "string", "description": "Description of param1"}
      ],
      "return": {"type": "mixed", "description": "Return description"}
    },
    "listeners": [
      {
        "callback": "my_function_name",
        "priority": 10,
        "accepted_args": 1,
        "file": "path/to/file.php",
        "line": 45
      }
    ],
    "related_hooks": [
      {
        "name": "related_hook_name",
        "type": "action|filter",
        "relationship": "naming pattern|proximity"
      }
    ]
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
- DocBlock extraction requires properly formatted DocBlocks immediately preceding hook functions
- Listener detection works for standard WordPress hook registration patterns

## Credits

- [nikic/PHP-Parser](https://github.com/nikic/PHP-Parser) - Used for PHP code parsing
- [phpDocumentor/ReflectionDocBlock](https://github.com/phpDocumentor/ReflectionDocBlock) - Used for DocBlock parsing

## Changelog

### v1.2.0 (2025-02-15)
- **NEW:** Track both defined and used hooks - see hooks a plugin defines AND external hooks it uses
- **NEW:** Adaptive layout - centered form transforms to compact sticky bar when viewing results
- **NEW:** Definition filter - filter results by "Defined Here" vs "Used (External)"
- **NEW:** Enhanced statistics - separate counts for defined vs used hooks
- **IMPROVED:** Sleek UI with smooth transitions and animations
- **IMPROVED:** Better form alignment and spacing in compact mode
- **IMPROVED:** Full-width results table for better data visibility (95% width to prevent scrollbar)
- **IMPROVED:** Renamed "Listeners" to "Callbacks" for clarity
- **FIXED:** Horizontal scrollbar issue in results view
- **FIXED:** Button alignment in compact form layout

### v1.1.0 (2025-02-15)
- **NEW:** Theme scanning support - scan WordPress themes in addition to plugins
- **NEW:** Hook Usage Analyzer - see what functions are listening to each hook
- **NEW:** Admin GUI interface with WordPress native components
- **NEW:** AJAX-based scanning with real-time progress
- **NEW:** Interactive results table with search and filtering
- **NEW:** Download results directly from admin interface
- **IMPROVED:** Enhanced JSON output with listener information
- **IMPROVED:** Better hook relationship detection

### v1.0.0
- Initial release
- WP-CLI command for scanning plugins
- JSON, Markdown, and HTML output formats
- DocBlock extraction
- Source context display
- Related hooks identification

## License

GPL-2.0-or-later
