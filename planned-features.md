# Planned Features for All The Hooks

This document outlines planned enhancements to make the plugin more useful for WordPress veterans, developers, and site owners.

## 🚀 In Progress (v1.1.0)

### 1. Theme Scanning Support ⭐
**Status:** Planned for next release
**Priority:** High
**Effort:** Low-Medium

Currently only scans plugins, but themes have many useful hooks too.

**Implementation:**
- Add `--theme=<theme-slug>` parameter to WP-CLI command
- Modify HookScanner to detect and scan theme directories
- Support both parent and child themes
- Include theme template files in scan results

**Use Cases:**
- Document custom theme hooks for client handoff
- Discover available hooks in third-party themes
- Analyze theme compatibility before customization

---

### 2. Hook Usage Analyzer (Show Who's Listening) ⭐
**Status:** Planned for next release
**Priority:** High
**Effort:** Medium

Scan for `add_action()` and `add_filter()` calls to show what's hooked where.

**Implementation:**
- Detect `add_action()` and `add_filter()` function calls
- Extract callback functions, priorities, and accepted arguments
- Show which plugins/themes are listening to each hook
- Display execution order based on priority
- Identify potential conflicts (multiple callbacks at same priority)

**Use Cases:**
- Debug hook conflicts and execution order
- Understand what functions run on specific hooks
- Identify performance bottlenecks (too many callbacks)
- Audit plugin interactions

**Output Additions:**
```json
{
  "name": "woocommerce_after_checkout",
  "type": "action",
  "listeners": [
    {
      "callback": "my_custom_function",
      "priority": 10,
      "accepted_args": 2,
      "source_file": "my-plugin/includes/checkout.php",
      "source_line": 45
    }
  ]
}
```

---

### 3. Admin GUI Interface ⭐
**Status:** Planned for next release
**Priority:** High
**Effort:** Medium-High

Replace placeholder admin page with functional GUI using WordPress native components.

**Implementation:**
- Use WordPress DataViews component (WP 6.3+) or WP_List_Table
- AJAX-based scanning with progress indicators
- Real-time results display with search/filter
- Download results in JSON/Markdown/HTML
- No custom CSS - use WordPress admin styles only
- Responsive design using WordPress grid system

**Features:**
- Select plugin/theme from dropdown
- Configure scan options (format, docblocks, hook type)
- View results in sortable/filterable table
- Export/download functionality
- Scan history and saved results
- Non-CLI access for site owners

**UI Components:**
- WordPress admin notices for status messages
- Core button styles and form elements
- Native modal dialogs for settings
- WordPress color schemes support
- Accessibility compliant (WCAG 2.1 AA)

---

## 📋 Backlog (Future Releases)

### 4. Multi-Plugin Search
**Priority:** Medium
**Effort:** Medium

Search for specific hooks across ALL installed plugins/themes.

**Example:** "Show me everywhere `woocommerce_after_checkout` is used"

**Use Cases:**
- Integration work across multiple plugins
- Compatibility checks before updates
- Security audits

---

### 5. Dynamic Hook Name Detection
**Priority:** Medium
**Effort:** High

Parse variable hook names and concatenated strings.

**Examples:**
```php
apply_filters( "prefix_{$type}_suffix", $value )
do_action( $hook_name . '_complete', $data )
```

**Implementation:**
- AST analysis to detect variable patterns
- Show possible variations based on code context
- Mark dynamic hooks with confidence score

---

### 6. Hook Comparison Tool (Version Diff)
**Priority:** Medium
**Effort:** Medium

Compare hooks between plugin versions to track changes.

**Features:**
- Show added/removed/modified hooks
- Highlight breaking changes
- Generate upgrade notes
- Critical for plugin update planning

---

### 7. Export for IDE Autocomplete
**Priority:** Medium
**Effort:** Low

Generate IDE-specific files for autocomplete support.

**Formats:**
- `.phpstorm.meta.php` for PhpStorm
- JSON schema for VS Code IntelliSense
- Intelephense stubs

**Benefits:**
- Autocomplete for hook names
- Parameter type hints
- Inline documentation

---

### 8. Priority & Callback Tracking
**Priority:** Low-Medium
**Effort:** Low

Enhanced tracking of hook priorities and callback details.

**Features:**
- Show all registered priorities
- Display callback function signatures
- Identify execution bottlenecks
- Visual priority timeline

---

### 9. Hook Dependency Visualization
**Priority:** Low
**Effort:** High

Generate visual graph of hook execution flow.

**Implementation:**
- Interactive diagram using Mermaid.js or D3.js
- Show hook relationships and dependencies
- Visualize execution order
- Export as SVG/PNG

---

### 10. Performance Profiler Integration
**Priority:** Low
**Effort:** High

Identify performance impact of hooks.

**Features:**
- Show callback count per hook
- Measure execution time (via profiler integration)
- Suggest optimization opportunities
- Highlight expensive operations

---

### 11. REST API Endpoint
**Priority:** Low
**Effort:** Medium

Expose hook data via WordPress REST API.

**Endpoints:**
```
GET /wp-json/all-the-hooks/v1/plugins/{slug}/hooks
GET /wp-json/all-the-hooks/v1/themes/{slug}/hooks
GET /wp-json/all-the-hooks/v1/search?hook={name}
```

**Use Cases:**
- Headless WordPress development
- External documentation tools
- CI/CD integration

---

### 12. Code Snippet Generator
**Priority:** Low
**Effort:** Low

Auto-generate boilerplate code for hooking into actions/filters.

**Example Output:**
```php
add_action( 'woocommerce_after_checkout', 'my_custom_function', 10, 2 );
function my_custom_function( $order_id, $posted_data ) {
    // Your code here
}
```

**Features:**
- Copy to clipboard
- Include parameter types from DocBlocks
- Multiple code style options
- Snippet library integration

---

## 🔧 Technical Improvements

### Code Quality
- [ ] Add unit tests (PHPUnit)
- [ ] Add integration tests for WP-CLI commands
- [ ] Implement caching for scan results
- [ ] Add WP-CLI progress bars for long scans

### Performance
- [ ] Optimize AST parsing for large plugins
- [ ] Implement parallel file processing
- [ ] Add scan result caching
- [ ] Database storage for historical scans

### Documentation
- [ ] Add inline code documentation
- [ ] Create developer API documentation
- [ ] Add video tutorials
- [ ] Create hook discovery best practices guide

---

## 💭 Ideas for Exploration

- **WordPress Playground Integration**: Test hooks in isolated environment
- **Hook Template Library**: Common hook implementation patterns
- **Compatibility Checker**: Analyze hook compatibility across WP versions
- **Security Scanner**: Identify potentially unsafe hook usage
- **Hook Analytics**: Track hook usage trends across WordPress ecosystem
- **AI-Powered Hook Suggestions**: Recommend appropriate hooks for specific tasks
- **Plugin Conflict Detector**: Identify plugins hooking into same actions

---

## 📊 Release Planning

### v1.1.0 (Current Sprint)
- ✅ Theme Scanning Support
- ✅ Hook Usage Analyzer
- ✅ Admin GUI Interface

### v1.2.0 (Next)
- Multi-Plugin Search
- Dynamic Hook Name Detection
- Export for IDE Autocomplete

### v2.0.0 (Future)
- Hook Comparison Tool
- REST API Endpoint
- Performance Profiler Integration
- Hook Dependency Visualization

---

## 🤝 Contributing

Have ideas for features? Open an issue or submit a pull request!

**Priority Definitions:**
- **High**: Critical for most users, high impact
- **Medium**: Useful for specific use cases
- **Low**: Nice to have, edge cases

**Effort Estimates:**
- **Low**: < 1 day
- **Medium**: 1-3 days
- **High**: > 3 days
