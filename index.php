<?php

namespace MFunc;

require_once __DIR__ . "/config/Configurations.php";
require_once PROJ_ROOT ."/includes/JWT/JWT.php";
require_once PROJ_ROOT . "/vendor/autoload.php";

use EllipticCurve;

global $Configurations;

$header = [
	"alg" => "RS256",
	"type" => "JWT"
];

$payload = [
	"id" => "31ededfqwfwqfqwfqfw",
	"name" => "Mojaf",
	"role" => "Secretary"
];


$jwt = new JWT();

echo "<h1>RSA /w SHA265</h1>";
$jwt_str = $jwt->genToken($payload, $Configurations->authGenKey);
var_dump($jwt_str);
echo "<br>";
echo "<br>";
var_dump((gzdeflate($jwt_str, 9)));
echo "<br>";
echo "<br>";

$tmp = explode(".", $jwt_str);
$body = $tmp[1];
$body = JWT::base64url_decode($body);
$body2 = substr($body, 0, strlen($body) - 19) . '"role": "Admin"}';
$bodyEnc = JWT::base64url_encode($body2);


$jwt_str2 = "{$tmp[0]}.$bodyEnc.{$tmp[2]}";

$jwt = new JWT($jwt_str);
/* var_dump($jwt->verify($Configurations->authVerKey)); */

echo "<h1>ECDSA /w SHA265</h1>";
echo "<br>";
echo "<br>";

$privateKey = new EllipticCurve\PrivateKey;
$publicKey = $privateKey->publicKey();

$header = JWT::base64url_encode(json_encode([
	"alg"  => "ES256",
	"type" => "JWT"
]));

$payload = JWT::base64url_encode(json_encode($payload));

$sign = EllipticCurve\Ecdsa::sign("$header.$payload", $privateKey);
var_dump("$header.$payload.{$sign->toBase64()}");

