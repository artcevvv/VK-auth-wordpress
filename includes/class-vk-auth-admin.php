<?php

class VK_Auth_Admin {
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'VK Authentication Settings',
            'VK Auth',
            'manage_options',
            'vk-auth-settings',
            array($this, 'render_settings_page'),
            'dashicons-vk',
            30
        );
    }

    public function register_settings() {
        register_setting('vk_auth_settings', 'vk_auth_client_id');
        register_setting('vk_auth_settings', 'vk_auth_client_secret');
        register_setting('vk_auth_settings', 'vk_auth_redirect_uri');

        add_settings_section(
            'vk_auth_main_section',
            'VK Authentication Settings',
            array($this, 'render_section_info'),
            'vk-auth-settings'
        );

        add_settings_field(
            'vk_auth_client_id',
            'Client ID',
            array($this, 'render_client_id_field'),
            'vk-auth-settings',
            'vk_auth_main_section'
        );

        add_settings_field(
            'vk_auth_client_secret',
            'Client Secret',
            array($this, 'render_client_secret_field'),
            'vk-auth-settings',
            'vk_auth_main_section'
        );

        add_settings_field(
            'vk_auth_redirect_uri',
            'Redirect URI',
            array($this, 'render_redirect_uri_field'),
            'vk-auth-settings',
            'vk_auth_main_section'
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('vk_auth_settings');
                do_settings_sections('vk-auth-settings');
                submit_button('Save Settings');
                ?>
            </form>
            <div class="vk-auth-instructions">
                <h2>Setup Instructions</h2>
                <ol>
                    <li>Create a new application at <a href="https://vk.com/apps?act=manage" target="_blank">VK Apps</a></li>
                    <li>Set the platform to "Website"</li>
                    <li>Enter your website URL</li>
                    <li>Copy the Client ID and Client Secret to the fields above</li>
                    <li>Set the Redirect URI to: <code><?php echo esc_url(site_url('/')); ?></code></li>
                </ol>
            </div>
        </div>
        <?php
    }

    public function render_section_info() {
        echo '<p>Enter your VK application credentials below:</p>';
    }

    public function render_client_id_field() {
        $value = get_option('vk_auth_client_id');
        echo '<input type="text" id="vk_auth_client_id" name="vk_auth_client_id" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_client_secret_field() {
        $value = get_option('vk_auth_client_secret');
        echo '<input type="password" id="vk_auth_client_secret" name="vk_auth_client_secret" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_redirect_uri_field() {
        $value = get_option('vk_auth_redirect_uri');
        if (empty($value)) {
            $value = site_url('/');
        }
        echo '<input type="text" id="vk_auth_redirect_uri" name="vk_auth_redirect_uri" value="' . esc_attr($value) . '" class="regular-text">';
    }
} 