<?php

namespace MFunc
{
	use \Exception;

	class HTTPException extends Exception
	{
		private $statusCode;

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

