<?php

namespace MFunc;

require_once __DIR__ . "/../../config/Configurations.php";
require_once PROJ_ROOT . "/includes/HTTP/HTTPResponse.php";
require_once PROJ_ROOT . "/includes/JWT/JWT.php";
require_once PROJ_ROOT . "/includes/JWT/JWTException.php";


class Auth
{
	private static $AUTH_TOKEN_HEADER = "X-MFunc-Token";
	private static $AUTH_REGISTERED_METHODS_ON_AUTHENTICATE = [];

	/**
	 *	Set header name that holds the authentication token
	 *
	 *	@param string $headerName Name of the header
	 *	@author Mohammed Abdulsalam
	 * */
	public static function setTokenHeader(string $headerName)
	{
		self::$AUTH_TOKEN_HEADER = $headerName;
	}

	/**
	 *  Set method to call on authentication process. This method
	 *  should receive one argument that hold the token
	 *
	 *  @param string $key Key that links the method
	 *  @param callable $callback Routine to check on every authentication
	 *	@author Mohammed Abdulsalam
	 * */
	public static function addAuthCallback(string $key, callable $callback)
	{
		self::$AUTH_REGISTERED_METHODS_ON_AUTHENTICATE[$key] = $callback;
	}

	/**
	 *  Remove callback that check on every `Auth::authentication()`
	 *
	 *  @param string $key Key that links the method
	 *	@author Mohammed Abdulsalam
	 * */
	public static function removeAuthCallback(string $key)
	{
		self::$AUTH_REGISTERED_METHODS_ON_AUTHENTICATE[$key] = null;
	}

	/**
	 *	Authenticate the user through JWT in request headers
	 *	This method send response code 401 (Unauthorized) in case of
	 *	invalid or expired token
	 *
	 *	@author Mohammed Abdulsalam
	 * */
	public static function authenticate()
	{
		global $Configurations;

		$headers = apache_request_headers();

		if(!$headers || !array_key_exists(self::$AUTH_TOKEN_HEADER, $headers))
			HTTPResponse::clientUnauthorized("The token is not set properly");

		$token = $headers[self::$AUTH_TOKEN_HEADER];

		try
		{
			$jwt = new JWT($token);
		}
		catch(JWTException $ex)
		{
			if($ex->getStatusCode() == JWTException::INVALID_TOKEN)
				HTTPResponse::clientUnauthorized($ex->getMessage());
			else
				HTTPResponse::serverInternalError();
		}
		catch(\Exception $ex)
		{
			HTTPResponse::serverInternalError();
		}

		if(!$jwt->verify($Configurations->authVerKey))
			HTTPResponse::clientUnauthorized("Invalid Token");

		foreach(self::$AUTH_REGISTERED_METHODS_ON_AUTHENTICATE as $key => $callback)
		{
			if(!is_null($callback))
				$callback($jwt);
		}
	}
}

