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
		if ( $node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name ) {
			$function_name = $node->name->toString();
			
			// Check if this is a hook registration function
			if ( in_array( $function_name, array( 'add_action', 'add_filter', 'apply_filters', 'do_action', 'do_action_ref_array', 'apply_filters_ref_array' ), true ) ) {
				// Need at least one argument (the hook name)
				if ( ! isset( $node->args[0] ) ) {
					return null;
				}

				// Get the hook name from the first argument
				$hook_name = $this->getHookName( $node->args[0]->value );
				if ( ! $hook_name ) {
					return null;
				}

				// Determine if it's an action or filter
				$type = $this->getHookType( $function_name );
				
				// Get the docblock if requested
				$docblock = '';
				if ( $this->include_docblocks ) {
					$docblock = $this->getDocComment( $node );
				}

				// Store the hook with its line number and function call
				$this->hooks[] = array(
					'name'           => $hook_name,
					'type'           => $type,
					'line'           => $node->getLine(),
					'function_call'  => $function_name,
					'docblock'       => $docblock,
				);
			}
		}
	}

	/**
	 * Extract hook name from a node value.
	 *
	 * @param Node $value Node value to extract hook name from.
	 * @return string|null Hook name or null if not a string.
	 */
	private function getHookName( Node $value ) {
		if ( $value instanceof Node\Scalar\String_ ) {
			return $value->value;
		}
		// Add other cases for dynamic hook names if needed
		return null;
	}

	/**
	 * Determine hook type based on function name.
	 *
	 * @param string $function_name Function name to determine hook type for.
	 * @return string Hook type ('action' or 'filter').
	 */
	private function getHookType( $function_name ) {
		if ( in_array( $function_name, array( 'add_action', 'do_action', 'do_action_ref_array' ), true ) ) {
			return 'action';
		} else {
			return 'filter';
		}
	}

	/**
	 * Get the DocBlock preceding a node.
	 *
	 * @param Node $node Node to get DocBlock for.
	 * @return string|null DocBlock comment or null if not found.
	 */
	private function getDocComment( Node $node ) {
		return $node->getDocComment() ? $node->getDocComment()->getText() : '';
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