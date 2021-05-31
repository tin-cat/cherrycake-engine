<?php

namespace Cherrycake\Actions;

/**
 * A class that represents an Action for GraphQL requests
 *
 * @package Cherrycake
 * @category Classes
 */
class ActionGraphQl extends Action {
	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected $responseClass = "ResponseGraphQl";

	/**
	 * @var boolean $isRequireMappedMethod When set to true, actions of this class will require a mapped moduleName and methodName to exist
	 */
	protected $isRequireMappedMethod = false;

	/**
	 * @var int $queries A hash array of GraphQl queries just like the one you would pass as the schema to GraphQL::executeQuery
	 */
	private $queries;

	function __construct($setup) {
		parent::__construct($setup);
		$this->queries = $setup["queries"] ?? false;
	}

	/**
	 * Returns the result of this action
	 * @param Request $request
	 * @return mixed The result
	 */
	function getResult($request) {
		global $e;

		try {
			$schema = new \GraphQL\Type\Schema($this->queries);
			$rawInput = file_get_contents('php://input');
			$input = json_decode($rawInput, true);
			$query = $input['query'] ?? null;
			$variableValues = $input['variables'] ?? null;

			$result = \GraphQL\GraphQL::executeQuery($schema, $query, null, null, $variableValues);
			$output = $result->toArray();
		}
		catch (\Throwable $e) {
			$output = [
				'error' => [
					'message' => $e->getMessage(),
				]
			];
		}

		$e->Output->setResponse(new ResponseGraphQl([
			"payload" => json_encode($output)
		]));

		return true;
	}
}
