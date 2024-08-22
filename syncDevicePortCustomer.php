<?php
include_once('include/auth.php');
top_header();
html_start_box(__('Đồng bộ port khách hàng'), '100%', '', '3', 'center', "");
set_time_limit(0);
ini_set('max_execution_time', 30000);

function call_API($path_api,$data){
	$url = "http://172.16.85.6:8089/service.asmx/".$path_api;
    $ch = curl_init($url);
    $postFields = http_build_query($data);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . strlen($postFields)
    ));
    $response = curl_exec($ch);
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        exit;
    }
    // Close cURL session
    curl_close($ch);
    // Load XML string
    $xml = simplexml_load_string($response);
    // Check if XML is loaded successfully
    if ($xml === false) {
        echo "Failed to load XML data.";
        exit;
    }
    // Define namespace
    $ns = $xml->getNamespaces(true);
    // Register namespaces
    foreach ($ns as $prefix => $uri) {
        $xml->registerXPathNamespace($prefix, $uri);
    }
    // XPath query to fetch switch data
    $result = $xml->xpath('//'.$path_api);
	return $result;
}

function LoadDevicePort() {
    $start_time = new DateTime();

    $data = array();
    // Lấy danh sách khách hàng
    $list_Company = db_fetch_assoc('SELECT * FROM customer');
    
	foreach ($list_Company as  $company) {
        $Company_CL = (string)$company['cl_number'];
		
		// Thực hiện update port của KH về 0
        db_execute_prepared('UPDATE graph_local
					SET port_status = 0
					WHERE cl_number = ?',
					array($Company_CL));
		
        $data = array('CompanyCL' => $Company_CL);
        $list_port = call_API("List_SwitchPort_By_CompanyCL",$data);
        foreach ($list_port as $port) {
            $SwitchIP = (string)$port->SwitchIP;
            $SwitchPortDescription = (string)$port->SwitchPortDescription;
            $local_graph_id = db_fetch_cell_prepared('SELECT gtg.local_graph_id
                                                    FROM graph_templates_graph AS gtg
                                                    INNER JOIN graph_local AS gl ON gtg.local_graph_id = gl.id
                                                    LEFT JOIN host AS h ON gl.host_id = h.id
                                                    WHERE h.hostname = ?
                                                    AND Substring_Index(title_cache, "-Traffic-", -1) = ?',
                                                    array($SwitchIP,$SwitchPortDescription));
			if(!empty($local_graph_id) && $local_graph_id != ''){
				if($local_graph_id > 0 ){
					echo $local_graph_id. " _ " . $Company_CL." _ ".$SwitchIP . " _ " . $SwitchPortDescription . "</br>";
					db_execute_prepared('UPDATE graph_local
										SET cl_number = ?,
										port_status =  1
										WHERE id = ?',
										array($Company_CL,$local_graph_id));
				}
			}           
        }
	}
    $end_time = new DateTime();
    $interval = $start_time->diff($end_time);
    echo "Start time: " . $start_time->format('Y-m-d H:i:s.u') . "</br>";
    echo "End time: " . $end_time->format('Y-m-d H:i:s.u') . "</br>";
    echo "Elapsed time: " . $interval->format('%H hours %i minutes %s seconds %f microseconds') . "</br>";
}
LoadDevicePort();
?>
