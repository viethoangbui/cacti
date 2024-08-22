#!/usr/bin/env php
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

global $config;

if (!isset($called_by_script_server)) {

	include_once(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/snmp.php');

	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_fortigate_ips', $_SERVER['argv']);
} else {
	include_once(dirname(__FILE__) . '/../lib/snmp.php');
}



function ss_fortigate_ips ($host_id = 0) {
	global $environ, $poller_id, $config;
	
	if (empty($host_id) || $host_id === NULL || !is_numeric($host_id))  {
		return 'vir_detected:0' . PHP_EOL;
	}

	$host = db_fetch_row_prepared('SELECT *
		FROM host
		WHERE id = ?',
		array($host_id));


	$oids = array(
        	'ips_detected'  => '.1.3.6.1.4.1.12356.101.9.2.1.1.1.1',
            	'ips_blocked'	=> '.1.3.6.1.4.1.12356.101.9.2.1.1.2.1',
            	'ips_crit'	=> '.1.3.6.1.4.1.12356.101.9.2.1.1.3.1',
           	'ips_high'	=> '.1.3.6.1.4.1.12356.101.9.2.1.1.4.1',
             	'ips_ano'	=> '.1.3.6.1.4.1.12356.101.9.2.1.1.9.1'             	

    	);
    	
    	$result = '';

        foreach ($oids as $name => $oid)        {
        	$x = cacti_snmp_get($host['hostname'],
		$host['snmp_community'],
		$oid,
		$host['snmp_version'],
		$host['snmp_username'],
		$host['snmp_password'],
		$host['snmp_auth_protocol'],
		$host['snmp_priv_passphrase'],
		$host['snmp_priv_protocol'],
		$host['snmp_context'],
		$host['snmp_port'],
		$host['snmp_timeout'],
		$host['ping_retries'],
		SNMP_POLLER,
		$host['snmp_engine_id']);

                $result .= $name . ':' . $x . ' '; 
        }

	return ($result);
}

?>