<?php
include_once('./lib/rrd.php');
include_once('./lib/graph_variables.php');

function local_data_array($arr)
{
    $resultArr = array();
    foreach ($arr as $value) {
        $resultArr[$value] = array(
            0 => "traffic_in",
            1 => "traffic_out"
        );
    }
    return $resultArr;
}

function convertUnitToByte($value)
{
    $value = (float) $value;
    $kbVal = $value / 1000;
    $mbVal = $kbVal / 1000;
    $gbVal = $mbVal / 1000;
    $tbVal = $gbVal / 1000;

    if ($kbVal <= 1) {
        return number_format($kbVal, 2) . " KB";
    }
    if ($mbVal <= 1) {
        return number_format($kbVal, 2) . " KB";
    }
    if ($gbVal <= 1) {
        return number_format($mbVal, 2) . " MB";
    }
    if ($tbVal <= 1) {
        return number_format($gbVal, 2) . " GB";
    }
    if ($tbVal > 1) {
        return number_format($tbVal, 2) . " TB";
    }

    return "0.00 B";
}

function unixToDatetime($timestamp)
{
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

// function exec_file_RRD($arr_local_graph_id,$start,$end,$title){
//     $data_source_path= array();
//     foreach ($arr_local_graph_id as $key => $value) {
//         $data_source_path[] = [rrdtool_escape_string(get_data_source_path($value, true)),"traffic_in"];
//         $data_source_path[] = [rrdtool_escape_string(get_data_source_path($value, true)),"traffic_out"];
//     }
//     $local_data_array = local_data_array ($arr_local_graph_id);
//     $str95 = Get_Value_95th($local_data_array,$start,$end);
//     $data_source_path[] = ["","Total_IN"];
//     $data_source_path[] = ["","Total_OUT"];

//     $graph_DEF = '';
//     $graph_CDEF = '';
//     $total_CDEF_IN = '';
//     $total_CDEF_OUT = '';
//     $operation = '';
//     $CDEF_IN = '';
//     $CDEF_OUT = '';
//     $line = '';
//     $key_CDEF_IN = '';
//     $key_CDEF_OUT = '';
//     foreach ($data_source_path as $key=> $value) {
//         if($value[1] !== 'Total_IN' && $value[1] !== 'Total_OUT'){
//             $graph_DEF .= 'DEF:' . generate_graph_def_name(strval($key)) . '=' . cacti_escapeshellarg($value[0]) . ':' . $value[1] . ':' . "MAX ";
//             $graph_CDEF .= 'CDEF:cdef'.generate_graph_def_name(strval($key)).'="'.generate_graph_def_name(strval($key)).',0,*" ';
//         }
//         if ($key < ((count($data_source_path)-2) / 2) - 1) {
//             $operation .= "+,";
//         }
//         if($value[1] === 'traffic_in'){                              
//             $CDEF_IN .= 'TIME,'.$start.',GT,'.generate_graph_def_name(strval($key)).','.
//             generate_graph_def_name(strval($key)).',UN,0,'.generate_graph_def_name(strval($key)).',IF,IF,';
//             $line .= 'AREA:cdef'.generate_graph_def_name(strval($key)).'#00CF007F: ';
//         }else if($value[1] === 'traffic_out'){
//             $CDEF_OUT .= 'TIME,'.$start.',GT,'.generate_graph_def_name(strval($key)).','.
//             generate_graph_def_name(strval($key)).',UN,0,'.generate_graph_def_name(strval($key)).',IF,IF,';
//             $line .= 'LINE1:cdef'.generate_graph_def_name(strval($key)).'#002A977F: ';
//         }
//         if($value[1] === 'Total_IN'){
//             $total_CDEF_IN = 'CDEF:cdef'.generate_graph_def_name(strval($key)).'="'.$CDEF_IN.$operation.'8,*" ';
//             $key_CDEF_IN = "cdef".generate_graph_def_name(strval($key));
//         } else if($value[1] === 'Total_OUT'){
//             $total_CDEF_OUT = 'CDEF:cdef'.generate_graph_def_name(strval($key)).'="'.$CDEF_OUT.$operation.'8,*" ';
//             $key_CDEF_OUT = "cdef".generate_graph_def_name(strval($key));
//         }  
//     }

//     $result = '';
//     $result .= ' graphv - ';
//     $result .= '--imgformat=SVG ';
//     $result .= '--start="'.$start.'" ';
//     $result .= '--end="'.$end.'" ';
//     $result .= '--pango-markup ';
//     $result .= '--title="'.$title.'" ';
//     $result .= '--vertical-label="bits per second" ';
//     $result .= '--slope-mode ';
//     $result .= '--base=1000 ';
//     $result .= '--height=200 ';
//     $result .= '--width=700 ';
//     $result .= '--rigid ';
//     $result .= '--alt-autoscale-max ';
//     $result .= '--lower-limit="0" ';
//     $result .= 'COMMENT:"From '.unixToDatetime($start).' To '.unixToDatetime($end).'\c" COMMENT:" \n" ';
//     $result .= 'COMMENT:"  \n" ';
//     $result .= '--color BACK#F3F3F3 ';
//     $result .= '--color CANVAS#FDFDFD ';
//     $result .= '--color SHADEA#CBCBCB ';
//     $result .= '--color SHADEB#999999 ';
//     $result .= '--color FONT#000000 ';
//     $result .= '--color AXIS#2C4D43 ';
//     $result .= '--color ARROW#2C4D43 ';
//     $result .= '--color FRAME#2C4D43 ';
//     $result .= '--border 1 ';
//     $result .= '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';
//     $result .= '--font AXIS:8:"Verdana, Arial, Helvetica, sans-serif" ';
//     $result .= '--font LEGEND:8:"Courier" ';
//     $result .= '--font ';
//     $result .= 'UNIT:8:"Verdana, Arial, Helvetica, sans-serif" ';
//     $result .= '--font WATERMARK:8:"Verdana, Arial, Helvetica, sans-serif" ';
//     $result .= '--slope-mode ';
//     $result .= '--watermark "VIETTEL IDC" ';

//     $result .= $graph_DEF;

//     $result .= $graph_CDEF;

//     $result .= $total_CDEF_IN;
//     $result .= $total_CDEF_OUT;

//     $result .= $line;

//     $result .= 'AREA:'.$key_CDEF_IN.'#00CF007F:"Total Inbound "  ';
//     $result .= 'GPRINT:'.$key_CDEF_IN.':LAST:"Current\:%8.2lf %s"  ';
//     $result .= 'GPRINT:'.$key_CDEF_IN.':AVERAGE:"Average\:%8.2lf %s" ';
//     $result .= 'GPRINT:'.$key_CDEF_IN.':MAX:"Maximum\:%8.2lf %s\n"  ';

//     $result .= 'LINE1:'.$key_CDEF_OUT.'#002A977F:"Total Outbound" ';
//     $result .= 'GPRINT:'.$key_CDEF_OUT.':LAST:"Current\:%8.2lf %s" ';
//     $result .= 'GPRINT:'.$key_CDEF_OUT.':AVERAGE:"Average\:%8.2lf %s"  ';
//     $result .= 'GPRINT:'.$key_CDEF_OUT.':MAX:"Maximum\:%8.2lf %s\n"  ';

//     $result .= 'COMMENT:" \n" ';
//     $result .= 'HRULE:'.$str95.'#FF0000FF:"95th Percentile" ';
//     $result .= 'COMMENT:"('.convertUnitToByte($str95).')\n"';
//     return $result;
// }

function Get_Value_95th($local_data_array, $start, $end)
{
    return Get95th_Total($local_data_array, $start, $end);
}

function exec_file_RRD($listSources, $start, $end, $title)
{
    $result = graphSource($listSources, $title, 1, 1, $start, $end);
    return $result;
}
function graphSource($listSources, $title, $unit, $format, $start, $end)
{
    $execs = [];
    $execs[] = ' graphv';
    $execs[] = ' - ';
    $execs[] = '--imgformat=SVG ';
    $execs[] = '--width=700 ';
    $execs[] = '--base=1000 ';
    $execs[] = '--height=200 ';
    $execs[] = '--interlaced ';
    $execs[] = '--title "' . $title . '"';
    $execs[] = '-v "Network Usage" ';
    $execs[] = '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';

    $cdefA = "CDEF:a=";
    $cdefB = "CDEF:b=";
    for ($i = 0; $i < count($listSources); $i++) {
        if (!empty($listSources[$i])) {
            $execs[] = "DEF:avgin_" . $i . "=" . $listSources[$i] . ":traffic_in:AVERAGE";
            $execs[] = "DEF:avgout_" . $i . "=" . $listSources[$i] . ":traffic_out:AVERAGE";
            $cdefA .= "avgin_" . $i . ",UN,0,avgin_" . $i . ",IF,";
            $cdefB .= "avgout_" . $i . ",UN,0,avgout_" . $i . ",IF,";
            if ($i > 0) {
                $cdefA .= "+,";
                $cdefB .= "+,";
            }
        }
    }
    $execs[] = $cdefA;
    $execs[] = $cdefB;

    $execs[] = 'CDEF:in=a,8,*';
    $execs[] = 'CDEF:out=b,8,*';
    $execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
    $execs[] = 'VDEF:95th=95per,95,PERCENT';

    $execs[] = 'COMMENT:"From ' . unixToDatetime($start) . ' To ' . unixToDatetime($end) . '\c" COMMENT:" \n" ';

    $execs[] = 'AREA:in#00FF00:" Inbound  "';
    $execs[] = 'COMMENT:"Max\:"';
    $execs[] = 'GPRINT:in:MAX:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Avg\:"';
    $execs[] = 'GPRINT:in:AVERAGE:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Last\:"';
    $execs[] = 'GPRINT:in:LAST:"%8.2lf %S\n"';

    $execs[] = 'LINE1:out#0000FF:" Outbound " COMMENT:"Max\:"';
    $execs[] = 'GPRINT:out:MAX:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Avg\:"';
    $execs[] = 'GPRINT:out:AVERAGE:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Last\:"';
    $execs[] = 'GPRINT:out:LAST:"%8.2lf %S\n"';

    $execs[] = 'LINE:95th#CF000F:" 95th Percentile"';
    $execs[] = 'GPRINT:95th:"%8.2lf %S\n"';
    //$execs[] = 'COMMENT:"Unit\: bps"';
    $execs[] = '--font AXIS:8:"Arial" ';
    $execs[] = '--font LEGEND:8:"Courier" ';
    $execs[] = '--font ';
    $execs[] = 'UNIT:8:"Arial" ';
    $execs[] = '--font WATERMARK:8:"Arial" ';
    $execs[] = '--slope-mode ';
    $execs[] = '--watermark "VIETTEL IDC" ';

    $execs[] = '--start=' . $start;
    $execs[] = '--end=' . $end;

    $command =  implode(' ', $execs);
    //var_dump($command );
    //var_dump(parse_url('https://hl-mrtg.vtdc.local/api/backup/get_device_backup_info'));
    return $command;
}

function callApi($url, $data, $token)
{
    //     $url = 'https://172.16.85.18:8686/api/sandvine/token'; // Replace with your actual endpoint
    //     // The data to send in the request body
    // $data = [
    //     'username' => 'cacti-public',
    //     'password' => 'L$w?Q?7dgU=v7m4=',
    // ];

    // Initialize a cURL session
    $ch = curl_init($url);
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    // curl_setopt($ch, CURLOPT_POST, true); // Set the method to POST
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Set the request body
    // Optional: Set headers if needed
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded', // Adjust as needed
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // Optional: Disable SSL verification (not recommended for production)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    } else {
        // Decode the response
        $responseData = json_decode($response, true);
        return $responseData; // Output the response data
    }
    // Close the cURL session
    curl_close($ch);
}


function executeClassMapd($in, $out, $title, $start, $end)
{
    $execs = [];
    $execs[] = ' graphv';
    $execs[] = ' - ';
    $execs[] = '--imgformat=SVG ';
    $execs[] = '--width=700 ';
    $execs[] = '--base=1000 ';
    $execs[] = '--height=200 ';
    $execs[] = '--interlaced ';
    $execs[] = '--title "' . $title . '"';
    $execs[] = '-v "Network Usage" ';
    $execs[] = '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';

    $cdefA = "CDEF:a=";
    $cdefB = "CDEF:b=";

    $execs[] = "DEF:avgin_" . 0 . "=" . $in . ":ds0:AVERAGE";
    $execs[] = "DEF:avgout_" . 0 . "=" . $out . ":ds0:AVERAGE";
    $cdefA .= "avgin_" . 0 . ",UN,0,avgin_" . 0 . ",IF,";
    $cdefB .= "avgout_" . 0 . ",UN,0,avgout_" . 0 . ",IF,";

    $execs[] = $cdefA;
    $execs[] = $cdefB;

    $execs[] = 'CDEF:in=a,8,*';
    $execs[] = 'CDEF:out=b,8,*';
    $execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
    $execs[] = 'VDEF:95th=95per,95,PERCENT';

    $execs[] = 'COMMENT:"From ' . unixToDatetime($start) . ' To ' . unixToDatetime($end) . '\c" COMMENT:" \n" ';

    $execs[] = 'AREA:in#00FF00:" Inbound  "';
    $execs[] = 'COMMENT:"Max\:"';
    $execs[] = 'GPRINT:in:MAX:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Avg\:"';
    $execs[] = 'GPRINT:in:AVERAGE:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Last\:"';
    $execs[] = 'GPRINT:in:LAST:"%8.2lf %S\n"';

    $execs[] = 'LINE1:out#0000FF:" Outbound " COMMENT:"Max\:"';
    $execs[] = 'GPRINT:out:MAX:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Avg\:"';
    $execs[] = 'GPRINT:out:AVERAGE:"%8.2lf %S"';
    $execs[] = 'COMMENT:"Last\:"';
    $execs[] = 'GPRINT:out:LAST:"%8.2lf %S\n"';

    $execs[] = 'LINE:95th#CF000F:" 95th Percentile"';
    $execs[] = 'GPRINT:95th:"%8.2lf %S\n"';
    $execs[] = '--font AXIS:8:"Arial" ';
    $execs[] = '--font LEGEND:8:"Courier" ';
    $execs[] = '--font ';
    $execs[] = 'UNIT:8:"Arial" ';
    $execs[] = '--font WATERMARK:8:"Arial" ';
    $execs[] = '--slope-mode ';
    $execs[] = '--watermark "VIETTEL IDC" ';

    $execs[] = '--start=' . $start;
    $execs[] = '--end=' . $end;

    $command =  implode(' ', $execs);
    $descriptorspec = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w')
    );
    $process = proc_open("c:/rrdtool/rrdtool.exe - ", $descriptorspec, $pipes);

    if (!is_resource($process)) {
        unset($process);
    } else {
        fwrite($pipes[0], $command . "\r\nquit\r\n");
        fclose($pipes[0]);
        $fp = $pipes[1];
    }

    if (!isset($fp)) {
        return;
    }

    $output = '';
    while (!feof($fp)) {
        $output .= fgets($fp, 4096);
    }

    if (isset($process)) {
        fclose($fp);
        proc_close($process);
    }

    $output = rtrim($output, "OK \n\r");

    $strSvg = substr($output, strpos($output, '<svg '));
    $base64_svg = base64_encode($strSvg);
    // $myfile = fopen("testfile.txt", "w");
    // fwrite($myfile, 'data:image/svg+xml;base64,' . $base64_svg);
    // fclose($myfile);
    return 'data:image/svg+xml;base64,' . $base64_svg;
}
