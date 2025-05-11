<?php
add_filter('site_transient_update_themes', 'my_custom_theme_update_check');
function my_custom_theme_update_check($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // اطلاعات قالب فعلی
    $theme_slug = 'hello-elementor-child'; // نام پوشه قالب
    $current_version = wp_get_theme($theme_slug)->get('Version');

    // آدرس سرور به‌روزرسانی
    $remote_url = 'https://your-server.com/update-server/updater.php?theme=' . $theme_slug;

    // دریافت اطلاعات نسخه جدید از API
    $response = wp_remote_get($remote_url);
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (version_compare($current_version, $data['version'], '<')) {
            $transient->response[$theme_slug] = [
                'theme'       => $theme_slug,
                'new_version' => $data['version'],
                'url'         => 'https://your-server.com', // اختیاری
                'package'     => $data['download_url'],
            ];
        }
    }

    return $transient;
}
