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
function plugin_my_plugin_install() {
    // Tạo bảng hoặc thực hiện cấu hình
}

function plugin_my_plugin_uninstall() {
    // Xóa cấu hình hoặc dữ liệu khi gỡ plugin
}