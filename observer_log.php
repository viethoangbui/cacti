<?php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

function sendLogMessage($message) {
    echo "data: $message\n\n";
    ob_flush();
    flush();
}

$logFilePath = __DIR__."/log/cacti.log";

if (!file_exists($logFilePath)) {
    sendLogMessage("Log file not available");
    exit();
}

$lastPosition = filesize($logFilePath);

while (true) {
    clearstatcache();
    $currentSize = filesize($logFilePath);

    if ($currentSize > $lastPosition) {
        $file = fopen($logFilePath, "r");
        fseek($file, $lastPosition);

        while ($line = fgets($file)) {
            if (stripos($line, 'ERROR') !== false) {
                $message = trim($line);
                sendLogMessage(substr($message, strpos($message, 'CMDPHP')));
            }
        }

        fclose($file);
        $lastPosition = $currentSize;
    }

    sleep(2);
}
?>

?>