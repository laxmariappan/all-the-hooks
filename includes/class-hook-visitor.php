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
	 * Collected hooks (do_action/apply_filters calls).
	 *
	 * @var array
	 */
	private $hooks = array();

	/**
	 * Collected hook listeners (add_action/add_filter calls).
	 *
	 * @var array
	 */
	private $listeners = array();

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

			// Check if this is a hook definition (do_action/apply_filters)
			if ( in_array( $function_name, array( 'apply_filters', 'do_action', 'do_action_ref_array', 'apply_filters_ref_array' ), true ) ) {
				$this->process_hook_definition( $node, $function_name );
			}
			// Check if this is a hook registration (add_action/add_filter)
			elseif ( in_array( $function_name, array( 'add_action', 'add_filter' ), true ) ) {
				$this->process_hook_listener( $node, $function_name );
			}
		}
	}

	/**
	 * Process a hook definition (do_action/apply_filters).
	 *
	 * @param Node   $node          The AST node.
	 * @param string $function_name The function name.
	 * @return void
	 */
	private function process_hook_definition( Node $node, $function_name ) {
		// Need at least one argument (the hook name)
		if ( ! isset( $node->args[0] ) ) {
			return;
		}

		// Get the hook name from the first argument
		$hook_name = $this->getHookName( $node->args[0]->value );
		if ( ! $hook_name ) {
			return;
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

	/**
	 * Process a hook listener (add_action/add_filter).
	 *
	 * @param Node   $node          The AST node.
	 * @param string $function_name The function name.
	 * @return void
	 */
	private function process_hook_listener( Node $node, $function_name ) {
		// Need at least two arguments (hook name and callback)
		if ( ! isset( $node->args[0] ) || ! isset( $node->args[1] ) ) {
			return;
		}

		// Get the hook name from the first argument
		$hook_name = $this->getHookName( $node->args[0]->value );
		if ( ! $hook_name ) {
			return;
		}

		// Get the callback from the second argument
		$callback = $this->getCallback( $node->args[1]->value );

		// Get priority (default: 10)
		$priority = 10;
		if ( isset( $node->args[2] ) ) {
			$priority = $this->getNumericValue( $node->args[2]->value, 10 );
		}

		// Get accepted args (default: 1)
		$accepted_args = 1;
		if ( isset( $node->args[3] ) ) {
			$accepted_args = $this->getNumericValue( $node->args[3]->value, 1 );
		}

		// Determine if it's an action or filter
		$type = $this->getHookType( $function_name );

		// Store the listener
		$this->listeners[] = array(
			'hook_name'     => $hook_name,
			'type'          => $type,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
			'line'          => $node->getLine(),
		);
	}

	/**
	 * Get callback name from a node.
	 *
	 * @param Node $value Node value to extract callback from.
	 * @return string Callback name or description.
	 */
	private function getCallback( Node $value ) {
		// String callback: 'my_function'
		if ( $value instanceof Node\Scalar\String_ ) {
			return $value->value;
		}

		// Array callback: array( $this, 'method' ) or array( 'ClassName', 'method' )
		if ( $value instanceof Node\Expr\Array_ ) {
			if ( count( $value->items ) >= 2 ) {
				$object = $value->items[0]->value ?? null;
				$method = $value->items[1]->value ?? null;

				$object_str = '';
				if ( $object instanceof Node\Expr\Variable && isset( $object->name ) ) {
					$object_str = '$' . $object->name;
				} elseif ( $object instanceof Node\Scalar\String_ ) {
					$object_str = $object->value;
				}

				$method_str = '';
				if ( $method instanceof Node\Scalar\String_ ) {
					$method_str = $method->value;
				}

				if ( $object_str && $method_str ) {
					return $object_str . '::' . $method_str;
				}
			}
		}

		// Closure/anonymous function
		if ( $value instanceof Node\Expr\Closure ) {
			return '{closure}';
		}

		// Arrow function
		if ( $value instanceof Node\Expr\ArrowFunction ) {
			return '{arrow function}';
		}

		return '{unknown}';
	}

	/**
	 * Get numeric value from a node.
	 *
	 * @param Node $value       Node value to extract number from.
	 * @param int  $default     Default value if not found.
	 * @return int Numeric value.
	 */
	private function getNumericValue( Node $value, $default = 0 ) {
		if ( $value instanceof Node\Scalar\LNumber ) {
			return $value->value;
		}
		return $default;
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
	 * Get the collected listeners.
	 *
	 * @return array Array of listeners.
	 */
	public function get_listeners() {
		return $this->listeners;
	}

	/**
	 * Reset the hooks and listeners collection.
	 *
	 * @return void
	 */
	public function reset() {
		$this->hooks = array();
		$this->listeners = array();
	}
} 