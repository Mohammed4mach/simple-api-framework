<?php

namespace MFunc;


/**
 * Simple router implementation.
 * This implementation depends on SplQueue class.
 *
 * */
class Router
{
	private static $routes = [
		"GET"    => [],
		"POST"   => [],
		"PUT"    => [],
		"PATCH"  => [],
		"DELETE" => []
	];


	/**
	 * Indicate wether `$target` is parameter given in a route.
	 *
	 * @param string $target string to test against
	 * */
	private static function is_param(string $target) : bool
	{
		return $target[0] == ":" ? true : false;
	}

	/**
	 * Extract parameter name from its form that indicates it is a parameter.
	 *
	 * ## Example
	 * If `$param = ":paramName"`, then `get_param_name($param)` will return
	 * "paramName", truncating ":".
	 *
	 * @param string $param Route parameter
	 *
	 * @return string Parameter name
	 * */
	private static function get_param_name(string $param) : string
	{
		return substr($param, 1);
	}

	/**
	 * Trim array from first and last elements that contains empty strings
	 * or string that contains query string.
	 *
	 * ## Note
	 * This function changes the array given, and return nothing
	 *
	 * @param array &$arr Array to trim
	 * */
	private static function trim_arr(array &$arr)
	{
		if($arr[0] == "") array_shift($arr);

		// Trim last if it is empty or contains query string
		$lastIndex     = count($arr) - 1;
		$lastNeedsTrim = $arr[$lastIndex] == "" || $arr[$lastIndex][0] == "?";

		if($lastNeedsTrim) array_pop($arr);

		// Strip query string
		$lastIndex       = count($arr) - 1;
		if($lastIndex >= 0)
			$arr[$lastIndex] = preg_replace("/\?.*/", "", $arr[$lastIndex]);
	}

	private static function respond_not_found()
	{
		http_response_code(404);
		exit();
	}

	/**
	 *	Build the route to the end-point, and map a file path to that end-point.
	 *  After building all routes, `Router::route()` is used to include the file
	 *  mapped to the end-point called in the request
	 *
	 *  @param string $route End-point to build its route
	 *  @param string $path File to map the end-point to it
	 *  @param array &$routes Array that route tree built in
	 *  @param callable $callback A callback function that is called before including the file
	 *  @param mixed $args Arguments to supply to the callback
	 * */
	private static function map_all(string $route, string $path, array &$routes, ?callable $callback = null, ...$args)
	{
		// Check if file of $path exists
		if(!file_exists(__DIR__ . "/../$path"))
			throw new \Exception("File at $path not found");

		$paramNameQue = new \SplQueue();
		$routeFrags   = explode("/", $route);

		self::trim_arr($routeFrags);

		// Building route tree
		if(!array_key_exists($routeFrags[0], $routes)) // Create key => array, if key not exists
			$routes[$routeFrags[0]] = [];

		$tempArr = &$routes[$routeFrags[0]];

		for($i = 1; $i < count($routeFrags); $i++)
		{
			$fragment = $routeFrags[$i];

			if(self::is_param($fragment))
			{
				$paramName = self::get_param_name($fragment);
				$paramNameQue->enqueue($paramName);

				$fragment  = ":param";
			}

			if(!array_key_exists($fragment, $tempArr)) // Create key => array if key not exists
				$tempArr[$fragment] = [];

			$tempArr = &$tempArr[$fragment];
		}

		// Set provided path & params
		$tempArr["__PATH__"] = $path;
		$tempArr["__PARAMS_NAME__"] = $paramNameQue->count() != 0
			? []
			: null;

		// Set callback function
		$tempArr["__CALLBACK__"] = null;
		$tempArr["__CALLBACK_ARGS__"] = null;

		$callbackIsValid = !is_null($callback);

		if($callbackIsValid)
		{
			if(!is_callable($callback))
				throw new \Exception("Callback provided is not callable");

			// Otherwise
			$tempArr["__CALLBACK__"] = $callback;
			$tempArr["__CALLBACK_ARGS__"] = $args;
		}

		// Extract URI params names
		foreach($paramNameQue as $paramName)
			array_push($tempArr["__PARAMS_NAME__"], $paramName);
	}

	/**
	 * Route request to the file path mapped to the end-point in the request and call associated callback if exists
	 * The path depends on request method and URI. This function is used after mapping
	 * all end-points to their paths using, for example, `Router::mapGET("/your/:id/end-point", "/path/to/logic.php")`
	 *
	 * **Note**:
	 * This function quirks requests with **OPTIONS** method by taking the method in
	 * _Access-Control-Request-Method_ header as the actual request method to provide valid path.
	 * Also, requests with **HEAD** method may not works as expected
	 * */
	public static function route()
	{
		$paramQue  = new \SplQueue();
		$uriFrags  = explode("/", $_SERVER["REQUEST_URI"]);
		$reqMethod = $_SERVER["REQUEST_METHOD"];
		$requestHeaders = apache_request_headers();

		// Quirks `OPTIONS` requests
		$switchHeader = $reqMethod == "OPTIONS" && array_key_exists("Access-Control-Request-Method", $requestHeaders);
		if($switchHeader)
			$reqMethod = $requestHeaders["Access-Control-Request-Method"];

		self::trim_arr($uriFrags);

		$target = &self::$routes[$reqMethod];

		foreach($uriFrags as $fragment)
		{
			if(array_key_exists($fragment, $target))
				$target = &$target[$fragment];
			else if(array_key_exists(":param", $target))
			{
				$paramQue->enqueue($fragment);

				$target = &$target[":param"];
			}
			else
				self::respond_not_found();
		}

		if(!array_key_exists("__PATH__", $target))
			self::respond_not_found();

		// Set data variables for controllers
		$_Request_Params   = null;
		$_Request_Query_String = null;
		$_Request_Body = json_decode(
			file_get_contents("php://input")
		);
		$_Request_Headers = $requestHeaders;

		// Extract URI params
		$paramNameCount = !is_null($target["__PARAMS_NAME__"])
			? count($target["__PARAMS_NAME__"])
			: 0;

		for($i = 0; $i < $paramNameCount; $i++)
		{
			$paramName                       = $target["__PARAMS_NAME__"][$i];
			$param                           = $paramQue->dequeue();

			$_Request_Params[$paramName] = $param;
		}

		$_Request_Params = (object) $_Request_Params;

		// Extract query string
		if(array_key_exists("QUERY_STRING", $_SERVER))
			parse_str($_SERVER["QUERY_STRING"], $_Request_Query_String);

		$_Request_Query_String = (object) $_Request_Query_String;

		// Call the callback if exists
		$args = $target["__CALLBACK_ARGS__"];

		if(!is_null($target["__CALLBACK__"]))
			$target["__CALLBACK__"](...$args);

		// Call the controller
		$path = $target["__PATH__"][0] == "/"
			? $target["__PATH__"]
			: "/" . $target["__PATH__"];

		// Unset temporary variables
		unset($paramQue);
		unset($uriFrags);
		unset($reqMethod);
		unset($requestHeaders);
		unset($switchHeader);
		unset($target);
		unset($args);

		require_once __DIR__ . "/..$path";
	}

	/**
	 * Map an end-point to a file in case of **GET** request
	 * Path given in the seconde parameter must be with respect to src folder
	 * For example, "api/example/logic.php" will resolve to "/path/srcParent/src/api/example/logic.php"
	 *
	 * @param string $route The end-point
	 * @param string $path Path to the file
	 * @param callable $callback A callback function that is called before including the file
	 * @param mixed $args Arguments to supply to the callback
	 * */
	public static function mapGET(string $route, string $path, ?callable $callback = null, ...$args)
	{
		self::map_all($route, $path, self::$routes["GET"], $callback, ...$args);
	}

	/**
	 * Map an end-point to a file in case of **POST** request
	 * Path given in the seconde parameter must be with respect to src folder
	 * For example, "api/example/logic.php" will resolve to "/path/srcParent/src/api/example/logic.php"
	 *
	 * @param string $route The end-point
	 * @param string $path Path to the file
	 * @param callable $callback A callback function that is called before including the file
	 * @param mixed $args Arguments to supply to the callback
	 * */
	public static function mapPOST(string $route, string $path, ?callable $callback = null, ...$args)
	{
		self::map_all($route, $path, self::$routes["POST"], $callback, ...$args);
	}


	/**
	 * Map an end-point to a file in case of **PUT** request
	 * Path given in the seconde parameter must be with respect to src folder
	 * For example, "api/example/logic.php" will resolve to "/path/srcParent/src/api/example/logic.php"
	 *
	 * @param string $route The end-point
	 * @param string $path Path to the file
	 * @param callable $callback A callback function that is called before including the file
	 * @param mixed $args Arguments to supply to the callback
	 * */
	public static function mapPUT(string $route, string $path, ?callable $callback = null, ...$args)
	{
		self::map_all($route, $path, self::$routes["PUT"], $callback, ...$args);
	}

	/**
	 * Map an end-point to a file in case of **PATCH** request
	 * Path given in the seconde parameter must be with respect to src folder
	 * For example, "api/example/logic.php" will resolve to "/path/srcParent/src/api/example/logic.php"
	 *
	 * @param string $route The end-point
	 * @param string $path Path to the file
	 * @param callable $callback A callback function that is called before including the file
	 * @param mixed $args Arguments to supply to the callback
	 * */
	public static function mapPATCH(string $route, string $path, ?callable $callback = null, ...$args)
	{
		self::map_all($route, $path, self::$routes["PATCH"], $callback, ...$args);
	}

	/**
	 * Map an end-point to a file in case of **DELETE** request
	 * Path given in the seconde parameter must be with respect to src folder
	 * For example, "api/example/logic.php" will resolve to "/path/srcParent/src/api/example/logic.php"
	 *
	 * @param string $route The end-point
	 * @param string $path Path to the file
	 * @param callable $callback A callback function that is called before including the file
	 * @param mixed $args Arguments to supply to the callback
	 * */
	public static function mapDELETE(string $route, string $path, ?callable $callback = null, ...$args)
	{
		self::map_all($route, $path, self::$routes["DELETE"], $callback, ...$args);
	}

	public static function print_routes()
	{
		echo "<pre>";
		print_r(self::$routes);
		echo "</pre>";
	}
}

