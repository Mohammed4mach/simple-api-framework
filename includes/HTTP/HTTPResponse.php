<?php

namespace MFunc;

class HTTPResponse
{

	public static function sendResponse(int $statusCode, string $message = null)
	{
		$body = !is_null($message)
			? "{ \"message\": \"$message\" }"
			: null;

		http_response_code($statusCode);
		echo $body;
		exit;
	}

	/**
	 * If input is not in the required format
	 *	@author Mohammed Abdulsalam
	*/
	public static function clientBadRequest(string $message = null)
	{
		self::sendResponse(400, $message);
	}

	/**
	 * If the resource can not be found
	 *	@author Mohammed Abdulsalam
	*/
	public static function clientNotFound(string $message = null)
	{
		self::sendResponse(404, $message);
	}

	/**
	 *	If Content-Type is not the desired one
	 *	@author Mohammed Abdulsalam
	 * */
	public static function clientUnsupportedMediaType(string $message = null)
	{
		self::sendResponse(415, $message);
	}

	/**
	 *	If the response Content-Type can not be equals that of
	 *  the required in Accept header in request
	 *	@author Mohammed Abdulsalam
	 * */
	public static function clientNotAcceptable(string $message = null)
	{
		self::sendResponse(406, $message);
	}

	/**
	 *	If the request success is not possible
	 *	For example if the resource is not possible to be updated, or
	 *	the resource cannot be created due to wrong input
	 *	@author Mohammed Abdulsalam
	 * */
	public static function clientConflict(string $message = null)
	{
		self::sendResponse(409, $message);
	}

	/**
	 * If user needs authorization
	 *	@author Mohammed Abdulsalam
	 * */
	public static function clientUnauthorized(string $message = null)
	{
		self::sendResponse(401, $message);
	}

	/**
	 *	The user is identified but has no access
	 *	@author Mohammed Abdulsalam
	 * */
	public static function clientForbidden(string $message = null)
	{
		self::sendResponse(403, $message);
	}

	/**
	 *	If the request is success, and response has no body
	 *	@author Mohammed Abdulsalam
	 * */
	public static function successNoContent(string $message = null)
	{
		self::sendResponse(204, $message);
	}

	/**
	 *	If OK
	 *	@author Mohammed Abdulsalam
	 * */
	public static function successOK(string $message = null)
	{
		self::sendResponse(200, $message);
	}

	/**
	 *	If the requested resource  is created
	 *	@author Mohammed Abdulsalam
	 * */
	public static function successCreated(string $message = null)
	{
		self::sendResponse(201, $message);
	}

	/**
	 *	For internal errors
	 *	@author Mohammed Abdulsalam
	 * */
	public static function serverInternalError(string $message = null)
	{
		self::sendResponse(500, $message);
	}
}

