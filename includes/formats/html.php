<?php
/**
 * HTML Formatter class.
 *
 * @package AllTheHooks
 */

namespace AllTheHooks\Formats;

/**
 * Class HTML_Formatter
 * 
 * Formats hook data to HTML output.
 */
class HTML_Formatter {
    
    /**
     * Generate HTML output for hooks data
     *
     * @param array  $hooks       Array of hooks data.
     * @param string $plugin_name Plugin name.
     * @return string HTML content.
     */
    public static function generate( $hooks, $plugin_name ) {
        $html = '';
        
        $html .= '<style>
            /* ... existing styles ... */
            .context-code {
                background-color: #f5f5f5;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                white-space: pre;
                margin-top: 10px;
                display: none;
                overflow-x: auto;
            }
            .context-line-highlight {
                background-color: #ffeb3b;
                display: block;
            }
            .view-context-btn {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 5px 10px;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                font-size: 12px;
                margin: 5px 0;
                cursor: pointer;
                border-radius: 3px;
            }
        </style>';
        
        foreach ( $hooks as $hook ) {
            $html .= '<div class="hook-item">';
            $html .= '<h3>' . $hook['type'] . ': ' . $hook['name'] . '</h3>';
            $html .= '<p><strong>File:</strong> ' . $hook['file'] . ' (Line: ' . $hook['line'] . ')</p>';
            
            if ( isset( $hook['docblock'] ) && ! empty( $hook['docblock'] ) ) {
                $html .= '<p><strong>Description:</strong> ' . nl2br( $hook['docblock'] ) . '</p>';
            }
            
            // Add View Source Context button and code
            if ( isset( $hook['context'] ) ) {
                $html .= '<button class="view-context-btn" onclick="toggleContext(this)">View Source Context</button>';
                $html .= '<div class="context-code">';
                
                foreach ( $hook['context'] as $index => $line ) {
                    $line_number = $hook['context_start'] + $index;
                    $is_hook_line = ($line_number == $hook['line']);
                    $html .= '<div class="' . ($is_hook_line ? 'context-line-highlight' : '') . '">';
                    $html .= $line_number . ': ' . htmlspecialchars($line) . '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Add JavaScript for toggling context
        $html .= '<script>
        function toggleContext(button) {
            var contextCode = button.nextElementSibling;
            if (contextCode.style.display === "block") {
                contextCode.style.display = "none";
                button.textContent = "View Source Context";
            } else {
                contextCode.style.display = "block";
                button.textContent = "Hide Source Context";
            }
        }
        </script>';
        
        return $html;
    }
} 