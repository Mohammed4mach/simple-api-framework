<?php

namespace MFunc
{
	use \Exception;

	class JWTException extends Exception
	{
		const INVALID_TOKEN   = 1;
		const UNSUPPORTED_KEY = 2;

		public function __construct($message, $code = 0)
		{
			$this->code = $code;
			parent::__construct($message, $code);
		}

		public function getStatusCode() : int
		{
			return $this->code;
		}
	}
}

