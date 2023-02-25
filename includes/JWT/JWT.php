<?php

namespace MFunc
{
	require_once __DIR__ . "/JWTException.php";

	/*
	 * JWT utilities
	 * This class depend on openssl extension
	 * Currently, RS256 only are supported
	 *	@author Mohammed Abdulsalam
	 */
	class JWT
	{
		private $header;
		public $payload;
		private $signature;

		private $headerEncoded;
		private $payloadEncoded;

		/*
		 *
		 * Validate parts of the jwt in the object
		 *
		 * @author Mohammed Abdulsalam
		 */
		private function validParts() : bool
		{
			return !is_null($this->header) && !is_null($this->payload) && !is_null($this->signature);
		}

		public static function base64url_encode($str)
		{
			/* return rtrim(strtr(base64_encode($str), "+/", "-_"), "="); */
			$str = base64_encode($str);
			$str = strtr($str, "+/", "-_");
			$str = rtrim($str, "=");;

			return $str;
		}

		public static function base64url_decode($str)
		{
			$str = strtr($str, "-_", "+/");
			$str = base64_decode($str);

			return $str;
		}

		/**
		 * JWT utilities
		 * This class depend on openssl extension
		 * Currently, RS256 only are supported
		 * @author Mohammed Abdulsalam
		 * */
		public function __construct(string $jwt = null)
		{
			if(!is_null($jwt))
			{
				$jwtParts = explode(".", $jwt);

				$jwtExpected = count($jwtParts) == 3 &&
					!is_null($jwtParts[0]) && !is_null($jwtParts[1]) && !is_null($jwtParts[2]);

				if(!$jwtExpected)
					throw new JWTException("The Token is not in the right format", JWTException::INVALID_TOKEN);

				$this->headerEncoded  = $jwtParts[0];
				$this->payloadEncoded = $jwtParts[1];

				$this->header = json_decode(JWT::base64url_decode($jwtParts[0]));
				$this->payload = json_decode(JWT::base64url_decode($jwtParts[1]));
				$this->signature = JWT::base64url_decode($jwtParts[2]);

				if(!$this->validParts())
					throw new JWTException("The Token is not in the right format", JWTException::INVALID_TOKEN);
			}
		}

		/**
		 * Verify if the provided JWT is not manipulated
		 *
		 * @param string $publicKey public key in format supported by openssl extension
		 * @return bool true if not manipulated
		 * @author Mohammed Abdulsalam
		 * */
		public function verify(string $publicKey) : bool
		{
			$data = "{$this->headerEncoded}.{$this->payloadEncoded}";

			$valid = openssl_verify($data, $this->signature, $publicKey, "sha256WithRSAEncryption");

			if(!($valid == 1 || $valid == 0))
				throw new JWTException("Unsupported key format. Openssl error:" . openssl_error_string(), JWTException::UNSUPPORTED_KEY);

			return boolval($valid);
		}

		/**
		 * Generate JWT
		 *
		 * @param array $payload JWT body
		 * @param string $privateKey private key in format supported by openssl extension
		 * @return string The generated JWT
		 * @author Mohammed Abdulsalam
		 * */
		public function genToken(array $payload, string $privateKey) : string
		{
			$header = self::base64url_encode(json_encode([
				"alg"  => "RS256",
				"type" => "JWT"
			]));

			$payload = self::base64url_encode(json_encode($payload));

			$generated = openssl_sign("$header.$payload", $signature, $privateKey, "sha256WithRSAEncryption");

			if(!$generated)
				throw new JWTException("Unsupported key format. Openssl error:" . openssl_error_string(), JWTException::UNSUPPORTED_KEY);

			$signature = self::base64url_encode($signature);
			$jwt = "$header.$payload.$signature";

			return $jwt;
		}
	}
}

