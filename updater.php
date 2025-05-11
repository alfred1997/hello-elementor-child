<?php
class WP_GPT_Theme_Updater
{
    private $theme;
    private $theme_name;
    private $theme_version;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;
    private $theme_data;
    private $update_server;

    public function __construct($theme)
    {
        $this->theme = $theme;
        $this->theme_name = wp_get_theme($theme)->get('Name');
        $this->theme_version = wp_get_theme($theme)->get('Version');

        // Set repository or server and access details
        $this->username = 'alfred1997'; // Github username
        $this->repository = 'hello-elementor-child';  // Repository name
        $this->authorize_token = false;

        $this->update_server = get_option('wp_gpt_update_server', '');

        add_action('admin_init', [$this, 'set_theme_properties']);
        add_filter('pre_set_site_transient_update_themes', [$this, 'check_update']);
        add_filter('themes_api', [$this, 'theme_popup'], 10, 3);
    }

    public function set_theme_properties()
    {
        $this->theme_data = wp_get_theme($this->theme);
    }

    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get remote version from private server or github
        $remote_version = $this->get_remote_version();

        // Check for new version
        if (
            $remote_version
            && version_compare($this->theme_version, $remote_version, '<')
        ) {
            $res = new stdClass();
            $res->theme = $this->theme;
            $res->new_version = $remote_version;
            $res->tested = '6.4';  // Tested with WordPress version
            $res->package = $this->get_download_url();
            $transient->response[$res->theme] = $res;
        }

        return $transient;
    }

    private function get_remote_version()
    {
        if (!empty($this->update_server)) {
            $request = wp_remote_get($this->update_server . '/version.php');
            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                return trim(wp_remote_retrieve_body($request));
            }
        } else {
            // If update server is not specified, use github
            $request = wp_remote_get(
                "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest",
                [
                    'headers' => [
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                    ],
                ]
            );

            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                $response = json_decode(wp_remote_retrieve_body($request));
                if (isset($response->tag_name)) {
                    return ltrim($response->tag_name, 'v');
                }
            }
        }
        return false;
    }

    private function get_download_url()
    {
        if (!empty($this->update_server)) {
            return $this->update_server . '/download.php?theme=' . $this->theme;
        } else {
            $request = wp_remote_get(
                "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest",
                [
                    'headers' => [
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                    ],
                ]
            );

            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                $response = json_decode(wp_remote_retrieve_body($request));

                // First try to get the attached ZIP file if available
                if (!empty($response->assets) && is_array($response->assets)) {
                    foreach ($response->assets as $asset) {
                        if (isset($asset->browser_download_url) && strpos($asset->name, '.zip') !== false) {
                            return $asset->browser_download_url;
                        }
                    }
                }

                // Fallback to the source code ZIP if no attached ZIP file
                if (isset($response->zipball_url)) {
                    return $response->zipball_url;
                }
            }
        }
        return false;
    }

    public function theme_popup($result, $action, $args)
    {
        if ('theme_information' !== $action || $args->slug !== $this->theme) {
            return $result;
        }

        $response = $this->get_theme_info();
        if ($response) {
            return $response;
        }

        return $result;
    }

    private function get_theme_info()
    {
        if (!empty($this->update_server)) {
            $request = wp_remote_get($this->update_server . '/info.php?theme=' . $this->theme);
            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                $response = json_decode(wp_remote_retrieve_body($request));
                return $this->format_theme_info($response);
            }
        } else {
            $request = wp_remote_get(
                "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest",
                [
                    'headers' => [
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                    ],
                ]
            );

            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                $response = json_decode(wp_remote_retrieve_body($request));

                $theme_info = new stdClass();
                $theme_info->name = $this->theme_data->get('Name');
                $theme_info->slug = $this->theme;
                $theme_info->version = ltrim($response->tag_name, 'v');
                $theme_info->author = $this->theme_data->get('Author');
                $theme_info->requires = '5.0';
                $theme_info->tested = '6.4';
                $theme_info->downloaded = 0;
                $theme_info->last_updated = $response->published_at;
                $theme_info->sections = [
                    'description' => $this->theme_data->get('Description'),
                    'changelog' => nl2br($response->body),
                ];

                if (!empty($response->assets) && is_array($response->assets)) {
                    foreach ($response->assets as $asset) {
                        if (isset($asset->browser_download_url) && strpos($asset->name, '.zip') !== false) {
                            $theme_info->download_link = $asset->browser_download_url;
                            break;
                        }
                    }
                }

                if (!isset($theme_info->download_link) && isset($response->zipball_url)) {
                    $theme_info->download_link = $response->zipball_url;
                }

                return $theme_info;
            }
        }

        return false;
    }

    private function format_theme_info($response)
    {
        if (is_object($response)) {
            $theme_info = new stdClass();
            $theme_info->name = isset($response->name) ? $response->name : $this->theme_data->get('Name');
            $theme_info->slug = $this->theme;
            $theme_info->version = isset($response->version) ? $response->version : '';
            $theme_info->author = isset($response->author) ? $response->author : $this->theme_data->get('Author');
            $theme_info->requires = isset($response->requires) ? $response->requires : '5.0';
            $theme_info->tested = isset($response->tested) ? $response->tested : '6.4';
            $theme_info->downloaded = isset($response->downloaded) ? $response->downloaded : 0;
            $theme_info->last_updated = isset($response->last_updated) ? $response->last_updated : '';
            $theme_info->sections = [
                'description' => isset($response->description) ? $response->description : $this->theme_data->get('Description'),
                'changelog' => isset($response->changelog) ? $response->changelog : '',
            ];
            $theme_info->download_link = isset($response->download_url) ? $response->download_url : '';

            return $theme_info;
        }

        return false;
    }
}
