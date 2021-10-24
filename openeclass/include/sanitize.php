<?php
	
	
	function oSanitize($output) {
		$output = str_replace('javascript', 'java_script', $output);
		return	htmlspecialchars($output, ENT_QUOTES, "UTF-8");
		// return iSanitize($output);
	}
	
	function iSanitize($input) {	
		$iSanTemp2 = $input;
		do {
			$iSanTemp1 = $iSanTemp2;
			$iSanTemp2 = str_replace('&amp', '' ,$iSanTemp1);
			$iSanTemp2 = str_replace('&lt', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('&gt', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('/', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('%', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('&', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('=', '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('"', '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace("'", '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('javascript', 'java_script', $iSanTemp2);

			$iSanTemp2 = str_replace('jav', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('jaV', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('jAv', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('jAV', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('Jav', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('JaV', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('JAv', 'ja_v', $iSanTemp2);
			$iSanTemp2 = str_replace('JAV', 'ja_v', $iSanTemp2);

			$iSanTemp2 = strip_tags($iSanTemp2);
			$iSanTemp2 = trim($iSanTemp2);
		} while (strcmp($iSanTemp2, $iSanTemp1) != 0);
		
		// Clean up things like &amp;
		$clear = html_entity_decode($iSanTemp2);
		// $clear = str_replace('javascript', 'java_script', $clear);
		$clear = str_replace('&', '' ,$clear);
		// Strip out any url-encoded stuff
		$clear = urldecode($clear);
		// Replace non-AlNum characters with space
		// $clear = preg_replace('/[^A-Za-z0-9]/', ' ', $clear);
		// Replace Multiple spaces with single space
		$clear = preg_replace('/ +/', ' ', $clear);
		// Trim the string of leading/trailing space
		$clear = trim($clear);
		return $clear;
	}

	function dbSanitize($input) {	
		$iSanTemp2 = $input;
		do {
			$iSanTemp1 = $iSanTemp2;
			$iSanTemp2 = str_replace('&amp', '' ,$iSanTemp1);
			// $iSanTemp2 = str_replace('&lt', '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('&gt', '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('/', '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('%', '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('&', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('=', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace('"', '' ,$iSanTemp2);
			$iSanTemp2 = str_replace("'", '' ,$iSanTemp2);
			// $iSanTemp2 = str_replace('javascript', 'java_script', $iSanTemp2);
			$iSanTemp2 = str_replace('UNION', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNIOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNIoN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNIon', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNiON', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNiOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNioN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UNion', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UnION', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UnIOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UnIoN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UnIon', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UniON', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UniOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('UnioN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('Union', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNION', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNIOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNIoN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNIon', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNiON', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNiOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNioN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uNion', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('unION', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('unIOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('unIoN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('unIon', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uniON', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('uniOn', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('unioN', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('union', 'uni_on', $iSanTemp2);
			$iSanTemp2 = str_replace('OR', 'o_r', $iSanTemp2);
			$iSanTemp2 = str_replace('Or', 'o_r', $iSanTemp2);
			$iSanTemp2 = str_replace('oR', 'o_r', $iSanTemp2);
			$iSanTemp2 = str_replace('or', 'o_r', $iSanTemp2);
			$iSanTemp2 = str_replace('AND', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('ANd', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('AnD', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('And', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('aND', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('aNd', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('anD', 'an_d', $iSanTemp2);
			$iSanTemp2 = str_replace('and', 'an_d', $iSanTemp2);
			//$iSanTemp2 = strip_tags($iSanTemp2);
			$iSanTemp2 = trim($iSanTemp2);
		} while (strcmp($iSanTemp2, $iSanTemp1) != 0);
		
		// Clean up things like &amp;
		$clear = html_entity_decode($iSanTemp2);
		$clear = str_replace('javascript', 'java_script', $clear);
		$clear = str_replace('&', '' ,$clear);
		// Strip out any url-encoded stuff
		$clear = urldecode($clear);
		// Replace non-AlNum characters with space
		// $clear = preg_replace('/[^A-Za-z0-9]/', ' ', $clear);
		// Replace Multiple spaces with single space
		$clear = preg_replace('/ +/', ' ', $clear);
		// Trim the string of leading/trailing space
		$clear = trim($clear);
		$clear = mysql_real_escape_string($clear);
		return $clear;
	}


	function idbSanitize($input) {	
		$output = $input;
		do {
			$input = $output;
			$output = iSanitize($output);
			$output = dbSanitize($output);
		} while (strcmp($input, $output)!=0);
		return $output;
	}

?>