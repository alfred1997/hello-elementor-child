<?php
// The output of this file must be a JSON that returns the new version information
header('Content-Type: application/json');

$plugin = isset($_GET['theme']) ? $_GET['theme'] : '';

if ($plugin === 'hello-elementor-child/functions.php') {
    $info = [
        'name' => 'CONTENT GENERATOR',
        'version' => '1.1.1',
        'author' => 'Majid',
        'requires' => '5.0',
        'tested' => '6.4',
        'downloaded' => 1000,
        'last_updated' => date('Y-m-d'),
        'description' => 'Generates content using CHAT-GPT.',
        'changelog' => '<h4>نسخه 1.1.1</h4>
        <ul>
            <li>بهبود عملکرد و حل مشکلات</li>
            <li>اضافه شدن قابلیت جدید</li>
            <li>رفع باگ‌های گزارش شده</li>
        </ul>',
        'download_url' => 'https://github.com/alfred1997/hello-elementor-child.git'
    ];
    
    echo json_encode($info);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'theme not found']);
}
?> 