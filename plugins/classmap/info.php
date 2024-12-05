<?php
$plugin_info = array(
    'shortname'  => 'vie_plugin',
    'description' => 'Mô tả plugin',
    'version'     => '1.0',
    'author'      => 'Your Name',
    'homepage'    => 'https://yourpluginwebsite.com',
    'email'       => 'your-email@example.com'
);

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