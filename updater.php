<?php

/**
 * Class for managing plugin updates from github or private server
 */
class hello_elementor_child_Updater
{
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;
    private $plugin_data;
    private $update_server;

    public function __construct($file)
    {
        $this->file = $file;
        $this->plugin = plugin_basename($file);
        $this->basename = plugin_basename($file);
        $this->active = is_plugin_active($this->basename);

        add_action('admin_init', [$this, 'set_plugin_properties']);

        // Set repository or server and access details
        $this->username = 'alfred1997';
        $this->repository = 'hello-elementor-child';
        $this->authorize_token = false;

        $this->update_server = get_option('wp_gpt_update_server', '');

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    public function set_plugin_properties()
    {
        $this->plugin_data = get_plugin_data($this->file);
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
            && version_compare($this->plugin_data['Version'], $remote_version, '<')
        ) {
            $res = new stdClass();
            $res->slug = $this->basename;
            $res->plugin = $this->plugin;
            $res->new_version = $remote_version;
            $res->tested = '6.4';
            $res->package = $this->get_download_url();
            $transient->response[$res->plugin] = $res;
        }

        return $transient;
    }

    private function get_remote_version()
    {
        // Get remote version from private server or github
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
        // Download URL from private server or github
        if (!empty($this->update_server)) {
            return $this->update_server . '/download.php?plugin=' . $this->basename;
        } else {
            // If you want to use github
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

    public function plugin_popup($result, $action, $args)
    {
        if ('plugin_information' !== $action || $args->slug !== $this->basename) {
            return $result;
        }

        $response = $this->get_plugin_info();
        if ($response) {
            return $response;
        }

        return $result;
    }

    private function get_plugin_info()
    {
        // Get update info from private server or github
        if (!empty($this->update_server)) {
            $request = wp_remote_get($this->update_server . '/info.php?plugin=' . $this->basename);
            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                $response = json_decode(wp_remote_retrieve_body($request));
                return $this->format_plugin_info($response);
            }
        } else {
            // If you want to use github
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

                $plugin_info = new stdClass();
                $plugin_info->name = $this->plugin_data['Name'];
                $plugin_info->slug = $this->basename;
                $plugin_info->version = ltrim($response->tag_name, 'v');
                $plugin_info->author = $this->plugin_data['Author'];
                $plugin_info->requires = '5.0';
                $plugin_info->tested = '6.4';
                $plugin_info->downloaded = 0;
                $plugin_info->last_updated = $response->published_at;
                $plugin_info->sections = [
                    'description' => $this->plugin_data['Description'],
                    'changelog' => nl2br($response->body),
                ];

                // First try to get the attached ZIP file if available
                if (!empty($response->assets) && is_array($response->assets)) {
                    foreach ($response->assets as $asset) {
                        if (isset($asset->browser_download_url) && strpos($asset->name, '.zip') !== false) {
                            $plugin_info->download_link = $asset->browser_download_url;
                            break;
                        }
                    }
                }

                // Fallback to the source code ZIP if no attached ZIP file
                if (!isset($plugin_info->download_link) && isset($response->zipball_url)) {
                    $plugin_info->download_link = $response->zipball_url;
                }

                return $plugin_info;
            }
        }

        return false;
    }

    private function format_plugin_info($response)
    {
        if (is_object($response)) {
            // Convert response to wordpress format
            $plugin_info = new stdClass();
            $plugin_info->name = isset($response->name) ? $response->name : $this->plugin_data['Name'];
            $plugin_info->slug = $this->basename;
            $plugin_info->version = isset($response->version) ? $response->version : '';
            $plugin_info->author = isset($response->author) ? $response->author : $this->plugin_data['Author'];
            $plugin_info->requires = isset($response->requires) ? $response->requires : '5.0';
            $plugin_info->tested = isset($response->tested) ? $response->tested : '6.4';
            $plugin_info->downloaded = isset($response->downloaded) ? $response->downloaded : 0;
            $plugin_info->last_updated = isset($response->last_updated) ? $response->last_updated : '';
            $plugin_info->sections = [
                'description' => isset($response->description) ? $response->description : $this->plugin_data['Description'],
                'changelog' => isset($response->changelog) ? $response->changelog : '',
            ];
            $plugin_info->download_link = isset($response->download_url) ? $response->download_url : '';

            return $plugin_info;
        }

        return false;
    }

    public function after_install($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }
}
