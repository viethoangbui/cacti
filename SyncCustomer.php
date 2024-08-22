<?php
include_once('include/auth.php');
include_once('lib/api_device.php');
top_header();
html_start_box(__('Load khách hàng lên hệ thống'), '100%', '', '3', 'center', "");
set_time_limit(3000);
function LoadDevice() {
    // Your API URL
    $url = "http://172.16.85.6:8089/service.asmx/List_Company";
    $switches = array();
    // Data to be sent in the POST request
	$data = array('RegionSN' => "");
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
	$region_switches = $xml->xpath('//List_Company');
	
	// Merge results into switches array
	if ($region_switches !== false) {
		$switches = array_merge($switches, $region_switches);
	}
	foreach ($switches as $switch) {
		$CompanyName = (string)$switch->CompanyName;
		$CompanyEmail = (string)$switch->CompanyEmail;
		$CompanyStatusSN =(string)$switch->CompanyStatusSN;
		$CompanyStatusID =(string)$switch->CompanyStatusID;
		$CompanyNote = (string)$switch->CompanyNote;
		$CompanySN = (string)$switch->CompanySN;
		$CompanyCL = (string)$switch->CompanyCL;
		$RegionSN = (string)$switch->RegionSN;
		
		$locations = db_fetch_cell_prepared('SELECT id
											 FROM sites
											 WHERE state = ?',
											 array($RegionSN));
		$count_check = db_fetch_cell_prepared(' SELECT count(*)
											    FROM customer
												WHERE company_sn = ?
												AND cl_number = ?',
												array($CompanySN,$CompanyCL));
		if ($count_check == 0){
			db_execute_prepared('INSERT INTO customer(cus_name, alter_email, status, note, company_sn, site_id, cl_number, StatusID)
							     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
							     array($CompanyName, $CompanyEmail, $CompanyStatusSN, $CompanyNote, $CompanySN, $locations, $CompanyCL, $CompanyStatusID));
		}
	}

    echo '<table class="cactiTable">
            <thead class="">
                <tr class="tableHeader">
                    <th>STT</th>
                    <th>CompanyCL</th>
                    <th>CompanyName</th>
					<th>CompanyStatusID</th>
					<th>CompanyStatusSN</th>
                </tr>
            </thead>
            <tbody>';
    // Display switch data
    $i = 0;
    if (!empty($switches)) {
			foreach ($switches as $switch) {
				if (!is_null($switch->CompanyStatusSN) || (string)$switch->CompanyStatusSN !== '0') {
					echo "<tr class='tableRow'>";
					echo "<td class='textInfo left'>" . htmlspecialchars(++$i) . "</td>";
                    echo "<td class='textInfo left'>" . htmlspecialchars($switch->CompanyCL) . "</td>";
                    echo "<td class='textInfo left'>" . htmlspecialchars($switch->CompanyName) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->CompanyStatusID) . "</td>";
					echo "<td class='textInfo left'>" . htmlspecialchars($switch->CompanyStatusSN) . "</td>";
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
