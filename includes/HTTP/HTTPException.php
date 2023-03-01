<?php

namespace MFunc
{
	use \Exception;

	class HTTPException extends Exception
	{
		private $statusCode;

		// Status Codes
		const BadRequest = 400;
		const NotFound = 404;
		const UnsupportedMediaType = 415;
		const NotAcceptable = 406;
		const Conflict = 409;
		const Unauthorized = 401;
		const Forbidden = 403;
		const NoContent = 204;
		const OK = 200;
		const Created = 201;
		const InternalError = 500;

		public function __construct($message, $statusCode)
		{
			$this->statusCode = $statusCode;
			parent::__construct($message, $statusCode);
		}

		public function getStatusCode() : int
		{
			return $this->statusCode;
		}
	}
}

