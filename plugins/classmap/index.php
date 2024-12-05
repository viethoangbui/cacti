<?php
function plugin_classmap_version() {
    return [
        'name'        => 'Vie Plugin', // Tên plugin của bạn
        'version'     => '1.0.0',      // Phiên bản hiện tại
        'longname'    => 'Vietnam Custom Plugin', // Tên đầy đủ
        'author'      => 'Your Name',  // Tên tác giả
        'email'       => 'your.email@example.com', // Email hỗ trợ
        'homepage'    => 'http://yourwebsite.com', // Link tới website (nếu có)
        'license'     => 'GPL',        // Loại giấy phép
    ];
}
include_once(__DIR__ . '/functions.php');
echo "Hello từ plugin của bạn!";
