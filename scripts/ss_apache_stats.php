<?php
/*

Original Template by 	ApacheStats 0.8.2 by RCK - http://forums.cacti.net/about25227.html
Tested and packged for Cacti 1.2.x  by Sean Mancini - bmfmancini - www.seanmancini.com


*/

$no_http_headers = true;

/* display No errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . "/../include/global.php");
	include_once(dirname(__FILE__) . "/../lib/snmp.php");

	array_shift($_SERVER["argv"]);

	print call_user_func_array("ss_apache_stats", $_SERVER["argv"]);
}

function ss_apache_stats($host="", $section = "all")
{
	/* perform initial error checking */
	if (empty($host)) {
		cacti_log("ERROR: ApacheStats08 - Host parameter missing, can not continue");
	}

	$variables = array('apache_total_hits', 'apache_total_kbytes', 'apache_busy_workers', 'apache_idle_workers', 'apache_cpuload');
	$threads = array('_W' => 0,'S' => 0,'R'=>0,'W'=>0,'K'=>0,'D'=>0,'C'=>0,'L'=>0,'G'=>0,'I'=>0,'_O'=>0);
/*	***Types of Threads***
	_W --- waiting
	S  --- starting up
	R  --- reading request
	W  --- sending reply
	K  --- keepalive
	D  --- DNS lookup
	C  --- closing connection
	L  --- logging
	G  --- graceful finishing
	I  --- idle cleanup
	_O --- open slot
*/
	$url = "http://$host/server-status?auto";
	$result = file_get_contents($url);
	$array_result = array();
	$array_result =  explode("\n",$result);
	$i = 0;
	$output = "";
	$line = array();
	foreach ( $array_result as $newline)
	{
		$line = explode(":", $newline);
	
		switch ($line[0])
		{
			case "Total Accesses":
				if (($section == "hits") or ($section == "all"))
					$output .= $variables[0].":".trim($line[1])." ";
			break;
			case "Total kBytes":
				if (($section == "kbytes") or ($section == "all"))
					$output .= $variables[1].":".trim($line[1])." ";
			break;
			case "BusyServers":
			case "BusyWorkers":
				if (($section == "workers") or ($section == "all"))
					$output .= $variables[2].":".trim($line[1])." ";
			break;
			case "IdleServers":
			case "IdleWorkers":
				if (($section == "workers") or ($section == "all"))
					$output .= $variables[3].":".trim($line[1])." ";
			break;
			case "CPULoad":
				if (($section == "cpuload") or ($section == "all"))
					$output .= $variables[4].":".trim($line[1])." "; 
			break;
			case "Scoreboard":
				$string = trim($line[1]);
				$length = strlen($string);
				for($j = 0; $j < $length ; $j++)
				{
					$char = $string[$j];
					if($char == '_')
						$threads['_W']++;
					else if($char == '.')
						$threads['_O']++;
					else
						$threads[$char]++;
				}
			break;
		}//end switch
	}//end foreach

	if (($section == "threads") or ($section == "all")) {
		foreach ($threads as $type => $num_threads)
		{
			$output.= "thread".$type.":".$num_threads." ";
		}
	}

	return trim(str_replace(array("\r","\n"),'', $output));

}

?>
