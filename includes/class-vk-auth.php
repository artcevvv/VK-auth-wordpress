<?php

class VK_Auth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $api_version = '5.199';

    public function init() {
        $this->client_id = get_option('vk_auth_client_id');
        $this->client_secret = get_option('vk_auth_client_secret');
        $this->redirect_uri = get_option('vk_auth_redirect_uri');

        // Add actions
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'handle_vk_callback'));
    }

    public function handle_vk_callback() {
        // Check if this is a VK callback
        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            return;
        }

        // Verify state
        if (!wp_verify_nonce($_GET['state'], 'vk_auth_state')) {
            wp_die('Invalid state parameter');
        }

        // Get code verifier from session
        if (!session_id()) {
            session_start();
        }
        $code_verifier = isset($_SESSION['vk_auth_code_verifier']) ? $_SESSION['vk_auth_code_verifier'] : '';
        
        if (empty($code_verifier)) {
            wp_die('Missing code verifier');
        }

        // Exchange code for token
        $token_url = 'https://id.vk.com/oauth2/auth';
        $token_data = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $_GET['code'],
            'code_verifier' => $code_verifier,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code',
            'device_id' => $_GET['device_id'] ?? '',
            'ext_id' => $_GET['ext_id'] ?? ''
        );

        $response = wp_remote_post($token_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ),
            'body' => http_build_query($token_data),
            'cookies' => array()
        ));

        if (is_wp_error($response)) {
            wp_die('Token exchange failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_die('Token exchange failed with status ' . $response_code . '. Response: ' . $response_body);
        }

        $body = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die('Failed to parse token response: ' . json_last_error_msg() . '. Response: ' . $response_body);
        }
        
        if (isset($body['error'])) {
            wp_die('Token exchange failed: ' . ($body['error_description'] ?? $body['error']));
        }

        if (!isset($body['access_token'])) {
            wp_die('No access token received. Response: ' . $response_body);
        }

        // Get user info using access token
        $user_info_url = 'https://api.vk.com/method/users.get';
        $user_response = wp_remote_get(add_query_arg(array(
            'access_token' => $body['access_token'],
            'v' => $this->api_version,
            'fields' => 'photo_200,email'
        ), $user_info_url));

        if (is_wp_error($user_response)) {
            wp_die('Failed to get user info: ' . $user_response->get_error_message());
        }

        $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
        
        if (isset($user_data['error'])) {
            wp_die('Failed to get user info: ' . ($user_data['error']['error_msg'] ?? 'Unknown error'));
        }

        if (!isset($user_data['response'][0])) {
            wp_die('No user data received');
        }

        // Create or update WordPress user
        $user = $this->create_or_update_user($user_data['response'][0], $user_data['response'][0]['id']);

        if (is_wp_error($user)) {
            wp_die('User creation failed: ' . $user->get_error_message());
        }

        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        // Clear session data
        unset($_SESSION['vk_auth_code_verifier']);

        // Redirect to account page
        wp_redirect(home_url('/my-account/'));
        exit;
    }

    private function create_or_update_user($user_info, $vk_user_id) {
        $user_login = 'vk_' . $vk_user_id;
        $user = get_user_by('login', $user_login);

        if (!$user) {
            // Create new user
            $random_password = wp_generate_password();
            $user_id = wp_create_user($user_login, $random_password, $user_info['email'] ?? '');
            
            if (is_wp_error($user_id)) {
                return $user_id;
            }

            $user = get_user_by('id', $user_id);
        }

        // Update user meta
        update_user_meta($user->ID, 'vk_user_id', $vk_user_id);
        update_user_meta($user->ID, 'first_name', $user_info['first_name']);
        update_user_meta($user->ID, 'last_name', $user_info['last_name']);
        
        if (isset($user_info['photo_200'])) {
            update_user_meta($user->ID, 'vk_avatar', $user_info['photo_200']);
        }

        return $user;
    }

    public function enqueue_scripts() {
        // Enqueue our custom styles
        wp_enqueue_style(
            'vk-auth-style',
            plugins_url('assets/css/vk-auth.css', dirname(__FILE__)),
            array(),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/vk-auth.css')
        );
    }
} 