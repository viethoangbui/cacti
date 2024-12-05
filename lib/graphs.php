<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group                                 |
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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/
function get_graph_template_details($local_graph_id) {
	global $config;

	$graph_local = db_fetch_row_prepared('SELECT gl.*, gt.name AS template_name, sqg.name AS query_name
		FROM graph_local AS gl
		LEFT JOIN graph_templates AS gt
		ON gl.graph_template_id = gt.id
		LEFT JOIN snmp_query_graph AS sqg
		ON sqg.id = gl.snmp_query_graph_id
		WHERE gl.id = ?',
		array($local_graph_id));

	$aggregate = db_fetch_row_prepared('SELECT agt.id, agt.name
		FROM aggregate_graphs AS ag
		LEFT JOIN aggregate_graph_templates AS agt
		ON ag.aggregate_template_id=agt.id
		WHERE local_graph_id = ?',
		array($local_graph_id));

	if (!empty($aggregate)) {
		$url = $config['url_path'] . 'aggregate_graphs.php?action=edit&id=';

		if (!empty($aggregate['id'])) {
			return array(
				'id'                => $local_graph_id,
				'name'              => $aggregate['name'],
				'graph_description' => __('Aggregated Device'),
				'url'               => $url . $local_graph_id,
				'source'            => GRAPH_SOURCE_AGGREGATE,
			);
		} else {
			return array(
				'id'                => $local_graph_id,
				'name'              => __('Not Templated'),
				'graph_description' => __('Not Applicable'),
				'url'               => $url . $local_graph_id,
				'source'            => GRAPH_SOURCE_AGGREGATE,
			);
		}
	} elseif ($graph_local['graph_template_id'] == 0) {
		return array(
			'id'     => $local_graph_id,
			'name'   => __('Not Templated'),
			'url'    => '',
			'source' => GRAPH_SOURCE_PLAIN,
		);
	} elseif ($graph_local['snmp_query_id'] > 0 && $graph_local['snmp_query_graph_id'] > 0) {
		$url = $config['url_path'] . 'data_queries.php' .
			'?action=item_edit' .
			'&id=' . $graph_local['snmp_query_graph_id'] .
			'&snmp_query_id=' . $graph_local['snmp_query_id'];

		return array(
			'id'     => $graph_local['snmp_query_graph_id'],
			'name'   => (!empty($graph_local['query_name']) ? $graph_local['query_name'] : __('Not Found')),
			'url'    => $url,
			'source' => GRAPH_SOURCE_DATA_QUERY
		);
	} elseif ($graph_local['snmp_query_id'] > 0 && $graph_local['snmp_query_graph_id'] == 0) {
		return array(
			'id'     => 0,
			'name'   => __('Damaged Graph'),
			'url'    => '',
			'source' => GRAPH_SOURCE_DATA_QUERY
		);
	} else {
		if (!empty($graph_local['template_name'])) {
			$url = $config['url_path'] . 'graph_templates.php?action=template_edit&id=' . $graph_local['graph_template_id'];

			return array(
				'id'     => $graph_local['graph_template_id'],
				'name'   => $graph_local['template_name'],
				'url'    => $url,
				'source' => GRAPH_SOURCE_TEMPLATE,
			);
		} else {
			return array(
				'id'     => 0,
				'name'   => __('Not Found'),
				'url'    => '',
				'source' => GRAPH_SOURCE_TEMPLATE,
			);
		}
	}
}

function unixToDatetime($timestamp) {
    // Create DateTime object from Unix timestamp
    $date = new DateTime("@$timestamp", new DateTimeZone('UTC')); // Start with UTC
    // Set the desired time zone
    $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
    // Format the date as 'Y-m-d H:i:s'
    $formattedDate = $date->format('Y-m-d H:i:s');
    // If needed, escape colons (though typically not required)
    $formattedDatetime = str_replace(':', '\\:', $formattedDate);
    return $formattedDatetime;
}

function graphSource($listSources, $title) {
	$start = time() - (4*86400);
	$end   = time();

	$execs = [];
	$execs[] = ' graphv';
	$execs[] = ' - ';
	$execs[] = '--imgformat=SVG ';
	$execs[] = '--width=700 ';
	$execs[] = '--base=1000 ';
	$execs[] = '--height=200 ';
	$execs[] = '--interlaced ';
	$execs[] = '--title "'.$title.'"';
	$execs[] = '-v "Network Usage" ';
	$execs[] = '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';

	$cdefA = "CDEF:a=";
	$cdefB = "CDEF:b=";
	for( $i = 0; $i < count($listSources); $i++){                 
		$execs[] = "DEF:avgin_" . $i . "=" . $listSources[$i] . ":traffic_in:AVERAGE";
		$execs[] = "DEF:avgout_" . $i . "=" . $listSources[$i] . ":traffic_out:AVERAGE";
		$cdefA .= "avgin_" . $i . ",UN,0,avgin_" . $i . ",IF,";
		$cdefB .= "avgout_" . $i . ",UN,0,avgout_" . $i . ",IF,";
		if ($i > 0) {
			$cdefA .= "+,";
			$cdefB .= "+,";
		}
	}
	$execs[]= $cdefA;
	$execs[]= $cdefB;
	
	$execs[] = 'CDEF:in=a,8,*';
	$execs[] = 'CDEF:out=b,8,*';
	$execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
	$execs[] = 'VDEF:95th=95per,95,PERCENT';

	$execs[] = 'COMMENT:"From '.unixToDatetime($start).' To '.unixToDatetime($end).'\c" COMMENT:" \n" ';

	$execs[] = 'AREA:in#00FF00:" IN"';
	$execs[] = 'COMMENT:"Max\:"';
	$execs[] = 'GPRINT:in:MAX:"%6.2lf %S"';
	$execs[] = 'COMMENT:"Avg\:"';
	$execs[] = 'GPRINT:in:AVERAGE:"%6.2lf %S"';
	$execs[] = 'COMMENT:"Last\:"';
	$execs[] = 'GPRINT:in:LAST:"%6.2lf %S\n"';

	$execs[] = 'LINE1:out#0000FF:" OUT" COMMENT:"Max\:"';
	$execs[] = 'GPRINT:out:MAX:"%6.2lf %S"';
	$execs[] = 'COMMENT:"Avg\:"';
	$execs[] = 'GPRINT:out:AVERAGE:"%6.2lf %S"';
	$execs[] = 'COMMENT:"Last\:"';
	$execs[] = 'GPRINT:out:LAST:"%6.2lf %S\n"';

	$execs[] = 'LINE:95th#CF000F:" 95th Percentile"';
	$execs[] = 'GPRINT:95th:"%6.2lf %S\n"';
	//$execs[] = 'COMMENT:"Unit\: bps"';
	$execs[] = '--font AXIS:8:"Arial" ';
	$execs[] = '--font LEGEND:8:"Courier" ';
	$execs[] = '--font ';
	$execs[] = 'UNIT:8:"Arial" ';
	$execs[] = '--font WATERMARK:8:"Arial" ';
	$execs[] = '--slope-mode ';
	$execs[] = '--watermark "VIETTEL IDC" ';

	$execs[] = '--start='.$start;
	$execs[] = '--end='.$end;

	$command =  implode(' ', $execs);
	return $command ;
}


function view_Graph($strRRD){
	$output = rrdtool_execute($strRRD, false, '1', false);
	$local_graph_id = 0;
	$rra_id = 0;
	$gtype = 'svg';
	$output = trim($output);
	$oarray = array('type' => $gtype, 'local_graph_id' => $local_graph_id, 'rra_id' => $rra_id);

	// Check if we received back something populated from rrdtool
	if ($output !== false && $output != '' && strpos($output, 'image = ') !== false) {
		// Find the beginning of the image definition row
		$image_begin_pos  = strpos($output, 'image = ');
		// Find the end of the line of the image definition row, after this the raw image data will come
		$image_data_pos   = strpos($output, "\n", $image_begin_pos) + 1;
		// Insert the raw image data to the array
		$oarray['image']  = base64_encode(substr($output, $image_data_pos));
		// Parse and populate everything before the image definition row
		$header_lines = explode("\n", substr($output, 0, $image_begin_pos - 1));
		foreach ($header_lines as $line) {
			$parts = explode(' = ', $line);
			$oarray[$parts[0]] = trim($parts[1]);
		}
	} else {
		/* image type now png */
		$oarray['type'] = 'png';
		ob_start();
		$graph_data_array['get_error'] = true;
		$null_param = array();
		rrdtool_function_graph($local_graph_id, $rra_id, $graph_data_array, '', $null_param, '1');
		$error = ob_get_contents();
		ob_end_clean();
		if (read_config_option('stats_poller') == '') {
			$error = __('The Cacti Poller has not run yet.');
		}
		if (isset($graph_data_array['graph_width']) && isset($graph_data_array['graph_height'])) {
			$image = rrdtool_create_error_image($error, $graph_data_array['graph_width'], $graph_data_array['graph_height']);
		} else {
			$image = rrdtool_create_error_image($error);
		}
		if (isset($graph_data_array['graph_width'])) {
			if (isset($graph_data_array['graph_nolegend'])) {
				$oarray['image_width']  = round($graph_data_array['graph_width']  * 1.24, 0);
				$oarray['image_height'] = round($graph_data_array['graph_height'] * 1.45, 0);
			} else {
				$oarray['image_width']  = round($graph_data_array['graph_width']  * 1.15, 0);
				$oarray['image_height'] = round($graph_data_array['graph_height'] * 1.8, 0);
			}
		} else {
			$oarray['image_width']  = round(db_fetch_cell_prepared(
				'SELECT width
				FROM graph_templates_graph
				WHERE local_graph_id = ?',
				array($local_graph_id)
			), 0);
			$oarray['image_height']  = round(db_fetch_cell_prepared(
				'SELECT height
				FROM graph_templates_graph
				WHERE local_graph_id = ?',
				array($local_graph_id)
			), 0);
		}
		if ($image !== false) {
			$oarray['image'] = base64_encode($image);
		} else {
			$oarray['image'] = base64_encode(file_get_contents(__DIR__ . '/images/cacti_error_image.png'));
		}
	}
	return $oarray;
}