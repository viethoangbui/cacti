<?php
    // function unixToDatetime($timestamp)
    // {
    //     // Create DateTime object from Unix timestamp
    //     $date = new DateTime("@$timestamp", new DateTimeZone('UTC')); // Start with UTC
    //     // Set the desired time zone
    //     $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
    //     // Format the date as 'Y-m-d H:i:s'
    //     $formattedDate = $date->format('Y-m-d H:i:s');
    //     // If needed, escape colons (though typically not required)
    //     $formattedDatetime = str_replace(':', '\\:', $formattedDate);
    //     return $formattedDatetime;
    // }
// $runtime1 = new \parallel\Runtime();
// $future = $runtime1->run(function () {
//     $path1 = "C\:/xampp/htdocs/cacti/rra/test1.rrd";
//     $path2 = "C\:/xampp/htdocs/cacti/rra/test2.rrd";
//     $title = 'TOTAL BANDWITH';
//     $start = 0;
//     $end = time();

//     // return executeClassMapd($path1, $path2, 'TOTAL BANDWITH', 1732589622 - 2 * 86400, 1732676022 - 2 * 86400);

//     $execs = [];
//     $execs[] = ' graphv';
//     $execs[] = ' - ';
//     $execs[] = '--imgformat=SVG ';
//     $execs[] = '--width=700 ';
//     $execs[] = '--base=1000 ';
//     $execs[] = '--height=200 ';
//     $execs[] = '--interlaced ';
//     $execs[] = '--title "' . $title . '"';
//     $execs[] = '-v "Network Usage" ';
//     $execs[] = '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';

//     $cdefA = "CDEF:a=";
//     $cdefB = "CDEF:b=";

//     $execs[] = "DEF:avgin_" . 0 . "=" . $path1 . ":ds0:AVERAGE";
//     $execs[] = "DEF:avgout_" . 0 . "=" . $path2 . ":ds0:AVERAGE";
//     $cdefA .= "avgin_" . 0 . ",UN,0,avgin_" . 0 . ",IF,";
//     $cdefB .= "avgout_" . 0 . ",UN,0,avgout_" . 0 . ",IF,";

//     $execs[] = $cdefA;
//     $execs[] = $cdefB;

//     $execs[] = 'CDEF:in=a,8,*';
//     $execs[] = 'CDEF:out=b,8,*';
//     $execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
//     $execs[] = 'VDEF:95th=95per,95,PERCENT';

//     $execs[] = 'COMMENT:"From ' . $start . ' To ' . $end . '\c" COMMENT:" \n" ';

//     $execs[] = 'AREA:in#00FF00:" Inbound  "';
//     $execs[] = 'COMMENT:"Max\:"';
//     $execs[] = 'GPRINT:in:MAX:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Avg\:"';
//     $execs[] = 'GPRINT:in:AVERAGE:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Last\:"';
//     $execs[] = 'GPRINT:in:LAST:"%8.2lf %S\n"';

//     $execs[] = 'LINE1:out#0000FF:" Outbound " COMMENT:"Max\:"';
//     $execs[] = 'GPRINT:out:MAX:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Avg\:"';
//     $execs[] = 'GPRINT:out:AVERAGE:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Last\:"';
//     $execs[] = 'GPRINT:out:LAST:"%8.2lf %S\n"';

//     $execs[] = 'LINE:95th#CF000F:" 95th Percentile"';
//     $execs[] = 'GPRINT:95th:"%8.2lf %S\n"';
//     $execs[] = '--font AXIS:8:"Arial" ';
//     $execs[] = '--font LEGEND:8:"Courier" ';
//     $execs[] = '--font ';
//     $execs[] = 'UNIT:8:"Arial" ';
//     $execs[] = '--font WATERMARK:8:"Arial" ';
//     $execs[] = '--slope-mode ';
//     $execs[] = '--watermark "VIETTEL IDC" ';

//     $execs[] = '--start=' . $start;
//     $execs[] = '--end=' . $end;

//     $command =  implode(' ', $execs);
//     $descriptorspec = array(
//         0 => array('pipe', 'r'),
//         1 => array('pipe', 'w')
//     );
//     $process = proc_open("c:/rrdtool/rrdtool.exe - ", $descriptorspec, $pipes);

//     if (!is_resource($process)) {
//         unset($process);
//     } else {
//         fwrite($pipes[0], $command . "\r\nquit\r\n");
//         fclose($pipes[0]);
//         $fp = $pipes[1];
//     }

//     if (!isset($fp)) {
//         return;
//     }

//     $output = '';
//     while (!feof($fp)) {
//         $output .= fgets($fp, 4096);
//     }

//     if (isset($process)) {
//         fclose($fp);
//         proc_close($process);
//     }

//     $output = rtrim($output, "OK \n\r");

//     $strSvg = substr($output, strpos($output, '<svg '));
//     $base64_svg = base64_encode($strSvg);
//     $myfile = fopen("testfile.txt", "w");
//     fwrite($myfile, 'data:image/svg+xml;base64,' . $base64_svg);
//     fclose($myfile);
//     // return 'data:image/svg+xml;base64,' . $base64_svg;
//     return 1;
//     // sleep(2);
//     // return "Task completed! \n";
// });

// $runtime2 = new \parallel\Runtime();
// $future2 = $runtime2->run(function () {
//     $path1 = "C\:/xampp/htdocs/cacti/rra/test1.rrd";
//     $path2 = "C\:/xampp/htdocs/cacti/rra/test2.rrd";
//     $title = 'TOTAL BANDWITH';
//     $start = 0;
//     $end = time();

//     // return executeClassMapd($path1, $path2, 'TOTAL BANDWITH', 1732589622 - 2 * 86400, 1732676022 - 2 * 86400);

//     $execs = [];
//     $execs[] = ' graphv';
//     $execs[] = ' - ';
//     $execs[] = '--imgformat=SVG ';
//     $execs[] = '--width=700 ';
//     $execs[] = '--base=1000 ';
//     $execs[] = '--height=200 ';
//     $execs[] = '--interlaced ';
//     $execs[] = '--title "' . $title . '"';
//     $execs[] = '-v "Network Usage" ';
//     $execs[] = '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';

//     $cdefA = "CDEF:a=";
//     $cdefB = "CDEF:b=";

//     $execs[] = "DEF:avgin_" . 0 . "=" . $path1 . ":ds0:AVERAGE";
//     $execs[] = "DEF:avgout_" . 0 . "=" . $path2 . ":ds0:AVERAGE";
//     $cdefA .= "avgin_" . 0 . ",UN,0,avgin_" . 0 . ",IF,";
//     $cdefB .= "avgout_" . 0 . ",UN,0,avgout_" . 0 . ",IF,";

//     $execs[] = $cdefA;
//     $execs[] = $cdefB;

//     $execs[] = 'CDEF:in=a,8,*';
//     $execs[] = 'CDEF:out=b,8,*';
//     $execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
//     $execs[] = 'VDEF:95th=95per,95,PERCENT';

//     $execs[] = 'COMMENT:"From ' . $start . ' To ' . $end . '\c" COMMENT:" \n" ';

//     $execs[] = 'AREA:in#00FF00:" Inbound  "';
//     $execs[] = 'COMMENT:"Max\:"';
//     $execs[] = 'GPRINT:in:MAX:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Avg\:"';
//     $execs[] = 'GPRINT:in:AVERAGE:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Last\:"';
//     $execs[] = 'GPRINT:in:LAST:"%8.2lf %S\n"';

//     $execs[] = 'LINE1:out#0000FF:" Outbound " COMMENT:"Max\:"';
//     $execs[] = 'GPRINT:out:MAX:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Avg\:"';
//     $execs[] = 'GPRINT:out:AVERAGE:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Last\:"';
//     $execs[] = 'GPRINT:out:LAST:"%8.2lf %S\n"';

//     $execs[] = 'LINE:95th#CF000F:" 95th Percentile"';
//     $execs[] = 'GPRINT:95th:"%8.2lf %S\n"';
//     $execs[] = '--font AXIS:8:"Arial" ';
//     $execs[] = '--font LEGEND:8:"Courier" ';
//     $execs[] = '--font ';
//     $execs[] = 'UNIT:8:"Arial" ';
//     $execs[] = '--font WATERMARK:8:"Arial" ';
//     $execs[] = '--slope-mode ';
//     $execs[] = '--watermark "VIETTEL IDC" ';

//     $execs[] = '--start=' . $start;
//     $execs[] = '--end=' . $end;

//     $command =  implode(' ', $execs);
//     $descriptorspec = array(
//         0 => array('pipe', 'r'),
//         1 => array('pipe', 'w')
//     );
//     $process = proc_open("c:/rrdtool/rrdtool.exe - ", $descriptorspec, $pipes);

//     if (!is_resource($process)) {
//         unset($process);
//     } else {
//         fwrite($pipes[0], $command . "\r\nquit\r\n");
//         fclose($pipes[0]);
//         $fp = $pipes[1];
//     }

//     if (!isset($fp)) {
//         return;
//     }

//     $output = '';
//     while (!feof($fp)) {
//         $output .= fgets($fp, 4096);
//     }

//     if (isset($process)) {
//         fclose($fp);
//         proc_close($process);
//     }

//     $output = rtrim($output, "OK \n\r");

//     $strSvg = substr($output, strpos($output, '<svg '));
//     $base64_svg = base64_encode($strSvg);
//     $myfile = fopen("testfile2.txt", "w");
//     fwrite($myfile, 'data:image/svg+xml;base64,' . $base64_svg);
//     fclose($myfile);
//     // return 'data:image/svg+xml;base64,' . $base64_svg;
//     return 2;
//     // sleep(2);
//     // return "Task completed! \n";
// });


// $runtime3 = new \parallel\Runtime();
// $future3 = $runtime3->run(function () {
//     $path1 = "C\:/xampp/htdocs/cacti/rra/test1.rrd";
//     $path2 = "C\:/xampp/htdocs/cacti/rra/test2.rrd";
//     $title = 'TOTAL BANDWITH';
//     $start = 0;
//     $end = time();

//     // return executeClassMapd($path1, $path2, 'TOTAL BANDWITH', 1732589622 - 2 * 86400, 1732676022 - 2 * 86400);

//     $execs = [];
//     $execs[] = ' graphv';
//     $execs[] = ' - ';
//     $execs[] = '--imgformat=SVG ';
//     $execs[] = '--width=700 ';
//     $execs[] = '--base=1000 ';
//     $execs[] = '--height=200 ';
//     $execs[] = '--interlaced ';
//     $execs[] = '--title "' . $title . '"';
//     $execs[] = '-v "Network Usage" ';
//     $execs[] = '--font TITLE:11:"Verdana, Arial, Helvetica, sans-serif,Bold" ';

//     $cdefA = "CDEF:a=";
//     $cdefB = "CDEF:b=";

//     $execs[] = "DEF:avgin_" . 0 . "=" . $path1 . ":ds0:AVERAGE";
//     $execs[] = "DEF:avgout_" . 0 . "=" . $path2 . ":ds0:AVERAGE";
//     $cdefA .= "avgin_" . 0 . ",UN,0,avgin_" . 0 . ",IF,";
//     $cdefB .= "avgout_" . 0 . ",UN,0,avgout_" . 0 . ",IF,";

//     $execs[] = $cdefA;
//     $execs[] = $cdefB;

//     $execs[] = 'CDEF:in=a,8,*';
//     $execs[] = 'CDEF:out=b,8,*';
//     $execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
//     $execs[] = 'VDEF:95th=95per,95,PERCENT';

//     $execs[] = 'COMMENT:"From ' . $start . ' To ' . $end . '\c" COMMENT:" \n" ';

//     $execs[] = 'AREA:in#00FF00:" Inbound  "';
//     $execs[] = 'COMMENT:"Max\:"';
//     $execs[] = 'GPRINT:in:MAX:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Avg\:"';
//     $execs[] = 'GPRINT:in:AVERAGE:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Last\:"';
//     $execs[] = 'GPRINT:in:LAST:"%8.2lf %S\n"';

//     $execs[] = 'LINE1:out#0000FF:" Outbound " COMMENT:"Max\:"';
//     $execs[] = 'GPRINT:out:MAX:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Avg\:"';
//     $execs[] = 'GPRINT:out:AVERAGE:"%8.2lf %S"';
//     $execs[] = 'COMMENT:"Last\:"';
//     $execs[] = 'GPRINT:out:LAST:"%8.2lf %S\n"';

//     $execs[] = 'LINE:95th#CF000F:" 95th Percentile"';
//     $execs[] = 'GPRINT:95th:"%8.2lf %S\n"';
//     $execs[] = '--font AXIS:8:"Arial" ';
//     $execs[] = '--font LEGEND:8:"Courier" ';
//     $execs[] = '--font ';
//     $execs[] = 'UNIT:8:"Arial" ';
//     $execs[] = '--font WATERMARK:8:"Arial" ';
//     $execs[] = '--slope-mode ';
//     $execs[] = '--watermark "VIETTEL IDC" ';

//     $execs[] = '--start=' . $start;
//     $execs[] = '--end=' . $end;

//     $command =  implode(' ', $execs);
//     $descriptorspec = array(
//         0 => array('pipe', 'r'),
//         1 => array('pipe', 'w')
//     );
//     $process = proc_open("c:/rrdtool/rrdtool.exe - ", $descriptorspec, $pipes);

//     if (!is_resource($process)) {
//         unset($process);
//     } else {
//         fwrite($pipes[0], $command . "\r\nquit\r\n");
//         fclose($pipes[0]);
//         $fp = $pipes[1];
//     }

//     if (!isset($fp)) {
//         return;
//     }

//     $output = '';
//     while (!feof($fp)) {
//         $output .= fgets($fp, 4096);
//     }

//     if (isset($process)) {
//         fclose($fp);
//         proc_close($process);
//     }

//     $output = rtrim($output, "OK \n\r");

//     $strSvg = substr($output, strpos($output, '<svg '));
//     $base64_svg = base64_encode($strSvg);
//     $myfile = fopen("testfile3.txt", "w");
//     fwrite($myfile, 'data:image/svg+xml;base64,' . $base64_svg);
//     fclose($myfile);
//     return 3;
//     // return 'data:image/svg+xml;base64,' . $base64_svg;
//     // sleep(2);
//     // return "Task completed! \n";
// });

// echo $future->value();
// echo $future2->value();
// echo $future3->value();

function test($number){
    $path1 = "C\:/xampp/htdocs/cacti/rra/test1.rrd";
    $path2 = "C\:/xampp/htdocs/cacti/rra/test2.rrd";
    $title = 'TOTAL BANDWITH';
    $start = 0;
    $end = time();

    // return executeClassMapd($path1, $path2, 'TOTAL BANDWITH', 1732589622 - 2 * 86400, 1732676022 - 2 * 86400);

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

    $execs[] = "DEF:avgin_" . 0 . "=" . $path1 . ":ds0:AVERAGE";
    $execs[] = "DEF:avgout_" . 0 . "=" . $path2 . ":ds0:AVERAGE";
    $cdefA .= "avgin_" . 0 . ",UN,0,avgin_" . 0 . ",IF,";
    $cdefB .= "avgout_" . 0 . ",UN,0,avgout_" . 0 . ",IF,";

    $execs[] = $cdefA;
    $execs[] = $cdefB;

    $execs[] = 'CDEF:in=a,8,*';
    $execs[] = 'CDEF:out=b,8,*';
    $execs[] = 'CDEF:95per=in,out,GT,in,out,IF';
    $execs[] = 'VDEF:95th=95per,95,PERCENT';

    $execs[] = 'COMMENT:"From ' . $start . ' To ' . $end . '\c" COMMENT:" \n" ';

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
    $myfile = fopen("testfile{$number}.txt", "w");
    fwrite($myfile, 'data:image/svg+xml;base64,' . $base64_svg);
    fclose($myfile);
}


test(1);
test(2);
test(3);
test(4);
test(5);
test(6);
test(7);
test(8);
test(9);
test(10);