<?php
/**
 * Hook Visitor class.
 *
 * @package AllTheHooks
 */

namespace AllTheHooks;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Comment\Doc;
use phpDocumentor\Reflection\DocBlockFactory;

/**
 * Class HookVisitor
 *
 * Visitor for PHP-Parser to identify WordPress hooks.
 */
class HookVisitor extends NodeVisitorAbstract {

	/**
	 * Whether to include DocBlocks in the hook data.
	 *
	 * @var bool
	 */
	private $include_docblocks;

	/**
	 * Collected hooks.
	 *
	 * @var array
	 */
	private $hooks = array();

	/**
	 * DocBlock factory for parsing docblocks.
	 *
	 * @var DocBlockFactory
	 */
	private $docblock_factory;

	/**
	 * HookVisitor constructor.
	 *
	 * @param bool $include_docblocks Whether to include DocBlocks in the results.
	 */
	public function __construct( $include_docblocks = false ) {
		$this->include_docblocks = $include_docblocks;
		$this->docblock_factory  = DocBlockFactory::createInstance();
	}

	/**
	 * Visit a node in the AST.
	 *
	 * @param Node $node Node to visit.
	 * @return void
	 */
	public function enterNode( Node $node ) {
		// Check if this is a function call
		if ( ! $node instanceof Node\Expr\FuncCall ) {
			return;
		}
		
		// Check if the name is available and a string
		if ( ! $node->name instanceof Node\Name ) {
			return;
		}
		
		$function_name = $node->name->toString();
		
		// Skip if not a WordPress hook function
		if ( ! in_array( $function_name, array( 'add_action', 'add_filter', 'do_action', 'do_action_ref_array', 'apply_filters', 'apply_filters_ref_array' ), true ) ) {
			return;
		}

		// Get the first argument (hook name)
		if ( ! isset( $node->args[0] ) || empty( $node->args[0]->value ) ) {
			return;
		}
		
		// Get hook name from first argument
		$hook_name = $this->extract_hook_name( $node->args[0]->value );
		
		if ( empty( $hook_name ) ) {
			return;
		}
		
		// Determine hook type
		$type = 'action';
		if ( in_array( $function_name, array( 'add_filter', 'apply_filters', 'apply_filters_ref_array' ), true ) ) {
			$type = 'filter';
		}

		// Build hook data
		$hook_data = array(
			'name'           => $hook_name,
			'type'           => $type,
			'line_number'    => $node->getLine(),
			'function_call'  => $function_name,
		);
		
		// Add DocBlock if enabled
		if ( $this->include_docblocks ) {
			$docblock_raw = $this->get_docblock( $node );
			
			if ( $docblock_raw ) {
				$hook_data['docblock_raw'] = $docblock_raw;
				$hook_data['docblock_parsed'] = $this->parse_docblock( $docblock_raw );
			} else {
				$hook_data['docblock_raw'] = null;
				$hook_data['docblock_parsed'] = null;
			}
		}
		
		$this->hooks[] = $hook_data;
	}

	/**
	 * Extract hook name from a node value.
	 *
	 * @param Node $value Node value to extract hook name from.
	 * @return string|null Hook name or null if not a string.
	 */
	private function extract_hook_name( $value ) {
		if ( $value instanceof Node\Scalar\String_ ) {
			return $value->value;
		}
		
		// For now, only support simple string literals
		// Future improvement: handle concatenated strings, variables, etc.
		
		return null;
	}

	/**
	 * Get the DocBlock preceding a node.
	 *
	 * @param Node $node Node to get DocBlock for.
	 * @return string|null DocBlock comment or null if not found.
	 */
	private function get_docblock( Node $node ) {
		$comments = $node->getAttributes()['comments'] ?? null;
		
		if ( ! $comments ) {
			return null;
		}
		
		// Find the closest DocBlock comment
		$docblock = null;
		foreach ( array_reverse( $comments ) as $comment ) {
			if ( $comment instanceof Doc ) {
				$docblock = $comment->getText();
				break;
			}
		}
		
		return $docblock;
	}

	/**
	 * Parse a DocBlock comment into structured data.
	 *
	 * @param string $docblock_raw Raw DocBlock comment.
	 * @return array|null Structured DocBlock data or null on error.
	 */
	private function parse_docblock( $docblock_raw ) {
		try {
			$docblock = $this->docblock_factory->create( $docblock_raw );
			
			$params = array();
			foreach ( $docblock->getTagsByName( 'param' ) as $param ) {
				$params[] = array(
					'name'        => '$' . $param->getVariableName(),
					'type'        => (string) $param->getType(),
					'description' => (string) $param->getDescription(),
				);
			}
			
			$return = null;
			$return_tags = $docblock->getTagsByName( 'return' );
			if ( ! empty( $return_tags ) ) {
				$return_tag = reset( $return_tags );
				$return = array(
					'type'        => (string) $return_tag->getType(),
					'description' => (string) $return_tag->getDescription(),
				);
			}
			
			return array(
				'summary'     => $docblock->getSummary(),
				'description' => (string) $docblock->getDescription(),
				'params'      => $params,
				'return'      => $return,
			);
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Get the collected hooks.
	 *
	 * @return array Array of hooks.
	 */
	public function get_hooks() {
		return $this->hooks;
	}

	/**
	 * Reset the hooks collection.
	 *
	 * @return void
	 */
	public function reset() {
		$this->hooks = array();
	}
} 