<?php

namespace MFunc
{
	use \Exception;

	define("PROJ_ROOT", __DIR__ . "/../");

	$env_mode  = getenv("PHP_ENV");

	$conf_path = __DIR__ . "/config.$env_mode.json";

	global $Configurations;
	$Configurations = json_decode(file_get_contents($conf_path));

	// Define Host
	define("PROJ_HOST", $Configurations->host);

	if(!$Configurations)
		throw new Exception("Issue in reading configurations !");

	// Extracting Auth private & public keys
	$Configurations->authKeysExists = false;

	$_keysPathsSet = isset($Configurations->privateKeyPath) &&
		isset($Configurations->publicKeyPath);

	$_keysFilesExist_ = $_keysPathsSet &&
		file_exists(__DIR__ . "/" . $Configurations->privateKeyPath) &&
		file_exists(__DIR__ . "/" . $Configurations->publicKeyPath);

	if($_keysFilesExist_)
	{
		$_privateKey_ = file_get_contents(__DIR__ . "/" . $Configurations->privateKeyPath);
		$_publicKey_  = file_get_contents(__DIR__ . "/" . $Configurations->publicKeyPath);

		if($_privateKey_ !== false && $_publicKey_ !== false)
		{
			$Configurations->authGenKey     = $_privateKey_;
			$Configurations->authVerKey     = $_publicKey_;
			$Configurations->authKeysExists = true;
		}
	}

	// Unset temporary variables
	unset($env_mode);
	unset($conf_path);
	unset($_keysFilesExist_);
	unset($_keysPathsSet);
	unset($_privateKey_);
	unset($_publicKey_);
}

