<?php

namespace MFunc
{
	date_default_timezone_set('Africa/Cairo'); // Set Timezone to Cairo

	class Func
	{
		// Encapsulate input sanitize functions in one
		public static function sanInput($input, $sanHTML = false) {
			$sanitized = stripslashes($input);

			if($sanHTML)
				$sanitized = htmlspecialchars($sanitized);

			$sanitized = trim($sanitized);

			return $sanitized;
		}


		// Unique id function generator
		public static function genId($length) {
			$min = (int)(0.1 * (10**$length));
			$max = (int)(0.99999999999 * (10**$length));
			$random = \rand($min, $max);
			return $random;
		}


		// Decode url parameter
		public static function parseURIParam(string $param)
		{
			return str_replace("-", " ", $param);
		}

		/**
		 * Parse fields option from query string
		 * It splits fields names by comma "," separator, then return array of those names
		 * Fields are chosen columns names of a model to return to user
		 *
		 * @param string $fields Fields names separated by comma
		 * @return array Array of fields names
		 * */
		public static function parseFieldsOption(string $fields)
		{
			$fields = preg_replace("/\s+/", "", $fields);

			return explode(",", $fields);
		}

		/**
		 * Parse filters provided by query string to a form understood by `AbstractModel` methods
		 * The form is conform to sql filter commands after `WHERE` statement
		 *
		 * @param array $filters Filters options from query strings
		 * @param array $columns Columns of the model to check integrity of filters
		 *  */
		public static function parseFiltersOption(array $filters = [], array $columns = []) : array
		{
			$options = [];
			$key_operator = [
				"lt"  => "<",
				"gt"  => ">",
				"leq" => "<=",
				"geq" => ">=",
				"eq"  => "="
			];

			foreach($filters as $col => $operators)
			{
				$colNotValid = !array_key_exists($col, $columns) ||
					!is_array($operators);

				if($colNotValid)
					continue;

				foreach($operators as $operator => $value)
				{
					if($operator == "like") // The case for like operator (usually for searching)
						array_push($options, "$col LIKE \"%$value%\"");
					else if($operator == "in")
					{
						$value =  preg_replace("/([\w-]+)/", '"$1"', $value); // Quote values => "value"
						$values = self::parseFieldsOption($value);

						self::trimArr($values); // Trim from empty strings

						$values = implode(",", $values);

						array_push($options, "$col IN ($values)");
					}
					else if(array_key_exists($operator, $key_operator))
						array_push($options, "$col {$key_operator[$operator]} \"$value\"");
				}
			}

			return $options;
		}

		/**
		 * Parse value of `order_by` option in query string, then returns sql condition command
		 * For example, `order_by=+start_date` will return `"ORDER BY start_date DESC"` by passing the value
		 * of `order_by` to the function. Take care that empty string is returned if `start_date` is not in
		 * `$columns` array
		 *
		 * @param string $orderVal Value of `order_by` key in the query string
		 * @param array $columns Columns of the model to check integrity of filters
		 * @return string Valid sql `ORDER BY` condition
		 *  */
		public static function parseOrderOption(string $orderVal, array $columns) : string
		{
			$result   = "";
			$operator = "";


			if($orderVal[0] == "+" || $orderVal[0] == "-")
			{
				$operator = $orderVal[0];
				$orderVal = substr($orderVal, 1);
			}

			$order = $operator == "+" ? "DESC" : "ASC";

			if(array_key_exists($orderVal, $columns))
				$result = "ORDER BY $orderVal $order";

			return $result;
		}

		// Print Array
		public static function printArr(&$arr)
		{
			echo "<pre>";
			print_r($arr);
			echo "</pre>";
		}

		/**
		 * Trim array from first and last elements that contains empty strings
		 *
		 * ## Note
		 * This function changes the array given, and return nothing
		 *
		 * @param array &$arr Array to trim
		 * */
		public static function trimArr(array &$arr)
		{
			if($arr[0] == "") array_shift($arr);

			// Trim last if it is empty or contains query string
			$lastIndex     = count($arr) - 1;
			$lastNeedsTrim = $arr[$lastIndex] == "";

			if($lastNeedsTrim) array_pop($arr);

			// Strip query string
			$lastIndex       = count($arr) - 1;
			if($lastIndex >= 0)
				$arr[$lastIndex] = preg_replace("/\?.*/", "", $arr[$lastIndex]);
		}


		// Encapsulate API (IP to Location API) request in a function
		public static function get_ip_location($ip)
		{
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.apilayer.com/ip_to_location/$ip",
				CURLOPT_HTTPHEADER => array(
					"Content-Type: text/plain",
					"apikey: MKuDl0tTC20bw3zvHtZQS7Shc25SRKO5"
				),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET"
			));

			$response = curl_exec($curl);

			curl_close($curl);

			return $response;
		}

		// Process IP to Location API response
		public static function process_ip_api_resp($response)
		{
			$respArr = json_decode($response, true);

			if(array_key_exists("message", $respArr))
				return;

			$result = array(
				"country" => $respArr["country_name"],
				"city" => $respArr["city"],
				"isp" => $respArr["connection"]["isp"]
			);

			return $result;
		}

		// Upload image to destination
		// @param $formName — name of the file in the form
		// @param $new_img_name — new image name
		// @param $folder — desired folder to upload in the 'upload' folder
		public static function uploadFile($formName, $new_img_name, $folder)
		{
			if(isset($_FILES[$formName]['name']))
			{
				$img_name = $_FILES[$formName]['name'];
				$tmp_name = $_FILES[$formName]['tmp_name'];
				$size = $_FILES[$formName]['size'];
				$error = $_FILES[$formName]['error'];
				
				$name_arr = explode('.', $img_name);
				$img_extension = strtolower(end($name_arr));
				$allowed_extensions = array('jpg', 'jpeg', 'png', 'jfif', 'svg', 'webp', 'mp4');


				// Error handling
				if($error)
					return array(0, "try_again");
				if($size > 160000000)
					return array(0, "large_size");
				if(!in_array($img_extension, $allowed_extensions))
					return array(0, "invalid_extension");

				// Rename the image and move to uploads
				$new_img_name = $new_img_name . '.' . $img_extension;
				$img_destination = "../uploads/$folder/$new_img_name";
				$uploaded = move_uploaded_file($tmp_name, $img_destination);

				if(!$uploaded)
					return array(0, "upload_failed");

				return array(1, $new_img_name);
			}
			return "noway";
		}

		public static function executeCMD($cmd)
		{
			if(substr(php_uname(), 0, 7) == "Windows")
				pclose(popen("start /B " . $cmd, "r"));
			else
				exec($cmd . " > /dev/null &");
		}

		public static function ordinal($number) {
			$ends = array('th','st','nd','rd','th','th','th','th','th','th');
			if ((($number % 100) >= 11) && (($number%100) <= 13))
				return 'th';
			else
				return $ends[$number % 10];
		}

		/**
		*   Process markdown format to HTML elements
		*   @param string $text Text contains markdown formats
		*   
		*   @return string|NULL
		*	@author Mohammed Abdulsalam
		*/
		public static function processMarkdown(&$text): string|NULL
		{
			$pattern = array(
				'/!#\(.*v=([^&]*).*\)/m', // Youtube iframe     !#(URL)
				'/!\[(.*?)\]\((.*?)\)/m', // Image                ![Alt text](URL)
				'/\[(.*?)\]\((.*?)\)/m', // Link                  [Text](link)
				'/\*\*(.*)\*\*/m', // Bold						**Text**
				'/__(.*)__/m', // Italic						__Text__
				'/#### *(.*)/m',
				'/### *(.*)/m',
				'/## *(.*)/m',  // Heading                      ## Text
				'/\n/m'   // Newline  \n
			);

			$replace = array(
				'<div class="iframe_container"><iframe loading="lazy" width="65%" height="100%" src="https://www.youtube.com/embed/$1" title="YouTube video player" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',
				'<img loading="lazy" src="$2" alt="$1"/>',
				'<a href="$2" rel="noopener noreference nofollower">$1</a>',
				'<strong>$1</strong>',
				'<i>$1</i>',
				'<h4>$1</h4>',
				'<h3>$1</h3>',
				'<h2>$1</h2>',
				'<br>'
			);

			return preg_replace($pattern, $replace, $text);
		}

		public static function processMarkdownCard(&$text) : string|NULL
		{
			$pattern = array(
				'/!#\(.*v=([^&]*).*\)/m', // Youtube iframe     !#(URL)
				'/!\[(.*)\]\((.*)\)/m', // Image                ![Alt text](URL)
				'/\[(.*)\]\((.*)\)/m', // Link                  [Text](link)
				'/## *(.*)/m',  // Heading                      ## Text
				'/\n/m'   // Newline  \n
			);

			$replace = array(
				'',
				'',
				'<a href="$2" rel="noopener noreference nofollower">$1</a>',
				'',
				'<br>'
			);

			return preg_replace($pattern, $replace, $text);
		}
	}
}

