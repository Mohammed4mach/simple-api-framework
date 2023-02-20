<?php

namespace MFunc
{
	class Router
	{
		private static $routes = [
			"GET"    => [],
			"POST"   => [],
			"PUT"    => [],
			"PATCH"  => [],
			"DELETE" => []
		];


		private static function is_param(string $target)
		{
			return $target[0] == ":" ? true : false;
		}

		private static function get_param_name(string $param)
		{
			return substr($param, 1);
		}

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

		private static function map_all(string $route, string $path, array &$routes)
		{
			$paramNameQue = new \SplQueue();
			$routeFrags   = explode("/", $route);

			self::trim_arr($routeFrags);

			// Building route tree
			if(!array_key_exists($routeFrags[0], $routes)) // Create key => array if key not exists
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

			// Extract URI params names
			foreach($paramNameQue as $paramName)
				array_push($tempArr["__PARAMS_NAME__"], $paramName);
		}

		public static function route()
		{
			$paramQue  = new \SplQueue();
			$uriFrags  = explode("/", $_SERVER["REQUEST_URI"]);
			$reqMethod = $_SERVER["REQUEST_METHOD"];
			$requestHeaders = apache_request_headers();
			
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

			// Call the controller
			$path = $target["__PATH__"][0] == "/"
				? $target["__PATH__"]
				: "/" . $target["__PATH__"];

			require_once __DIR__ . "/..$path";
		}

		public static function mapGET(string $route, string $path)
		{
			self::map_all($route, $path, self::$routes["GET"]);
		}

		public static function mapPOST(string $route, string $path)
		{
			self::map_all($route, $path, self::$routes["POST"]);
		}

		public static function mapPUT(string $route, string $path)
		{
			self::map_all($route, $path, self::$routes["PUT"]);
		}

		public static function mapPATCH(string $route, string $path)
		{
			self::map_all($route, $path, self::$routes["PATCH"]);
		}

		public static function mapDELETE(string $route, string $path)
		{
			self::map_all($route, $path, self::$routes["DELETE"]);
		}

		/* public static function print_routes() */
		/* { */
		/* 	echo "<pre>"; */
		/* 	print_r(self::$routes); */
		/* 	echo "</pre>"; */
		/* } */
	}
}

