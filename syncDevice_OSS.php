<?php
include_once('include/auth.php');
include_once('lib/api_device.php');
top_header();
html_start_box(__('Load thiết bị lên hệ thống'), '100%', '', '3', 'center', "");
set_time_limit(300);
function LoadDevice() {
    // Your API URL
    $url = "http://172.16.251.1:8099/service.asmx/List_Switch_By_RegionSN";
    // Array of RegionSN values
    $regions = array(40, 50, 65, 80);
    // Initialize the switches array to accumulate results
    $switches = array();
    foreach ($regions as $region) {
        // Data to be sent in the POST request
        $data = array('RegionSN' => $region);
        
        // Initialize cURL session
        $ch = curl_init($url);
        // Set cURL options
        $postFields = http_build_query($data);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($postFields)
        ));
        // Execute cURL session
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
        $region_switches = $xml->xpath('//List_Switch_By_RegionSN');
        $locations = db_fetch_cell_prepared('SELECT id
		FROM sites
		WHERE state = ?',
		array($region));
        foreach ($region_switches as $switch) {
            $SwitchIP = (string)$switch->SwitchIP;
            $SwitchID = (string)$switch->SwitchID;
            if (!is_null($SwitchIP) || $SwitchIP!== '') {
                $count_check = db_fetch_cell_prepared('SELECT count(*)
                                                    FROM host
                                                    WHERE hostname = ?',
                                                    array($SwitchIP));
                if ($count_check==0){
                    $host = api_device_save(0, 3,$SwitchID , $SwitchIP, 'vtdc', 2, '', '', 161, 500,'' , 
                    1, 1, 23, 400, 1, '', 'MD5', '', 'DES', '', '', 10, 1, 1, $locations,'' , $region, 0);
                }
            }
        }
    }
    echo 'Đồng bộ thiết bị từ OSS thành công!';
}
LoadDevice();
?>
