<?php
/*
    This file is part of OSM Route Manager.

    OSM Route Manager is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OSM Route Manager is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OSM Route Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

	error_reporting(E_ALL);
	set_time_limit(300);

	header("Content-type: text/html; charset=UTF-8");

	bindtextdomain("osmrm", dirname(__FILE__)."/locale");
	bind_textdomain_codeset("osmrm", "utf-8");
	textdomain("osmrm");

	$languages = array (
		"de_DE" => array("de_DE.utf8", "de_DE@utf8", "de_DE", "de", "german", "ger", "deutsch", "deu"),
		"en_GB" => array("en_GB.utf8", "en_GB@utf8", "en_US.utf8", "en_US@utf8", "en", "english", "eng")
	);
	if(isset($_GET["lang"]) && isset($languages[$_GET["lang"]]))
	{
		setcookie("lang", $_GET["lang"], time()+86400*365*2, dirname($_SERVER["PHP_SELF"])."/");
		$_COOKIE["lang"] = $_GET["lang"];
	}
	$lang = null;
	if(isset($_COOKIE["lang"]) && isset($languages[$_COOKIE["lang"]]))
		$lang = $_COOKIE["lang"];
	elseif(isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
	{
		$accept_language = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		$min = null;
		foreach(array("en" => "en_GB", "de" => "de_DE") as $k=>$v)
		{
			$strpos = strpos($accept_language, $k);
			if($strpos !== false)
			{
				if(is_null($min) || $strpos < $min[0])
					$min = array($strpos, $v);
			}
		}
		if(!is_null($min))
			$lang = $min[1];
	}
	if(is_null($lang))
		$lang = "en_GB";

	$locale = setlocale(LC_MESSAGES, $languages[$lang]);
	putenv("LANGUAGE=".$lang);
	putenv("LANG=".$lang);
	putenv("LC_MESSAGES=".$locale);
	$_ENV["LANGUAGE"] = $_ENV["LANG"] = $lang;
	$_ENV["LC_MESSAGES"] = $locale;

	$GUI = new GUI();

	function __autoload($classname)
	{
		$fname = dirname(__FILE__)."/classes/".strtolower($classname).".php";
		if(!is_file($fname))
			throw new BadMethodCall("Class ".$classname." does not exist.");
		require_once($fname);
	}

	function jsescape($mixed)
	{
		return "'".str_replace(array("\\", "'"), array("\\\\", "\\'"), $mixed)."'";
	}

	function getDistance($point1, $point2)
	{
		// http://mathforum.org/library/drmath/view/51879.html
		$lat1 = $point1[0]*pi()/180;
		$lat2 = $point2[0]*pi()/180;
		$lon1 = $point1[1]*pi()/180;
		$lon2 = $point2[1]*pi()/180;

		$R = 6367;
		$dlon = $lon2 - $lon1;
		$dlat = $lat2 - $lat1;
		$a = pow((sin($dlat/2)),2) + cos($lat1) * cos($lat2) * pow((sin($dlon/2)),2);
		$c = 2 * atan2(sqrt($a), sqrt(1-$a));
		$d = $R * $c;

		return $d;
	}

	/**
	 * An exception has occured while reading or writing data from/to the harddisk or a socket.
	*/

	class IOException extends Exception { }