<?php
include_once('include/auth.php');
top_header();
html_start_box(__('Load thiết bị lên hệ thống'), '100%', '', '3', 'center', "");

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

        // Merge results into switches array
        if ($region_switches !== false) {
            $switches = array_merge($switches, $region_switches);
        }
    }

    echo '<table class="cactiTable">
            <thead class="">
                <tr class="tableHeader">
                    <th>STT</th>
                    <th>SwitchSN</th>
                    <th>SwitchID</th>
                    <th>SwitchIP</th>
                    <th>SwitchCommunity</th>
                    <th>SwitchSnmpVersion</th>
                    <th>SwitchHostname</th>
                    <th>SwitchNote</th>
                    <th>SwitchStartDate</th>
                </tr>
            </thead>
            <tbody>';
    // Display switch data
    $i = 0;
    if (!empty($switches)) {
			foreach ($switches as $switch) {
				if (!is_null($switch->SwitchIP) || $switch->SwitchIP !== '') {
					echo "<tr class='tableRow'>";
					echo "<td class='textInfo left'>" . htmlspecialchars(++$i) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchSN) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchID) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchIP) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchCommunity) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchSnmpVersion) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchHostname) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchNote) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->SwitchStartDate) . "</td>";
					echo "</tr>";
				}
		}        
    } else {
        echo "<tr><td colspan='9'>No data available</td></tr>";
    }
    echo '</tbody></table>';
}
LoadDevice();
?>
