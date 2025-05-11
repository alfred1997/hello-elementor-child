<?php
// The output of this file must be a JSON that returns the new version information
header('Content-Type: application/json');

$theme = isset($_GET['theme']) ? $_GET['theme'] : '';

if ($theme === 'hello-elementor-child/functions.php') {
    $info = [
        'name' => 'hello-elementor-child',
        'version' => '1.0.5',
        'author' => 'AradBranding',
        'requires' => '5.0',
        'tested' => '6.4',
        'last_updated' => date('Y-m-d'),
        'description' => 'A custom theme powered by GPT integration.',
        'changelog' => '<h4>نسخه 1.0.5</h4>
        <ul>
            <li>بهبود عملکرد قالب</li>
            <li>سازگاری با نسخه‌های جدید وردپرس</li>
            <li>رفع اشکالات گزارش‌شده</li>
        </ul>',
        'download_url' => 'https://example.com/themes/my-custom-theme-1.0.5.zip'
    ];
    
    echo json_encode($info);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Theme not found']);
}
?>
