<?php
// این فایل برای ارائه فایل ZIP جدید استفاده می‌شود
$plugin = isset($_GET['theme']) ? $_GET['theme'] : '';

// بررسی درخواست پلاگین
if ($plugin === 'hello-elementor-child/functions.php') {
    // مسیر فایل ZIP نسخه جدید
    $file_path = __DIR__ . '/files/hello-elementor-child-1.1.1.zip';
    
    // بررسی وجود فایل
    if (file_exists($file_path)) {
        // تنظیم هدرهای مناسب برای دانلود
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="hello-elementor-child-1.1.1.zip"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // خواندن و ارسال فایل
        readfile($file_path);
        exit;
    } else {
        // فایل موجود نیست
        http_response_code(404);
        echo 'فایل بروزرسانی یافت نشد.';
    }
} else {
    // پلاگین نامعتبر
    http_response_code(400);
    echo 'درخواست نامعتبر است.';
}
?> 