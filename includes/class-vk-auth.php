<?php

class VK_Auth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $api_version = '5.131';
    private $code_verifier;
    private $code_challenge;

    public function init() {
        $this->client_id = get_option('vk_auth_client_id');
        $this->client_secret = get_option('vk_auth_client_secret');
        $this->redirect_uri = get_option('vk_auth_redirect_uri');

        // Generate PKCE values
        $this->generate_pkce_values();

        // Add actions
        add_action('init', array($this, 'handle_vk_callback'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    private function generate_pkce_values() {
        // Generate code verifier
        $random_bytes = random_bytes(32);
        $this->code_verifier = rtrim(strtr(base64_encode($random_bytes), '+/', '-_'), '=');

        // Generate code challenge using SHA256
        $this->code_challenge = rtrim(strtr(base64_encode(hash('sha256', $this->code_verifier, true)), '+/', '-_'), '=');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('vk-auth-style', VK_AUTH_PLUGIN_URL . 'assets/css/vk-auth.css', array(), VK_AUTH_VERSION);
        wp_enqueue_script('vk-auth-script', VK_AUTH_PLUGIN_URL . 'assets/js/vk-auth.js', array('jquery'), VK_AUTH_VERSION, true);
        
        wp_localize_script('vk-auth-script', 'vkAuth', array(
            'clientId' => $this->client_id,
            'redirectUri' => $this->redirect_uri,
            'codeChallenge' => $this->code_challenge,
            'codeVerifier' => $this->code_verifier,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vk_auth_nonce')
        ));
    }

    public function handle_vk_callback() {
        if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== 'vk_auth') {
            return;
        }

        $code = sanitize_text_field($_GET['code']);
        
        // Get code verifier from cookie
        if (!isset($_COOKIE['vk_auth_code_verifier'])) {
            wp_die('Invalid authentication state');
        }
        
        $code_verifier = $_COOKIE['vk_auth_code_verifier'];
        // Clear the cookie after use
        setcookie('vk_auth_code_verifier', '', time() - 3600, '/', '', true, true);
        
        // Exchange code for access token
        $token_url = 'https://oauth.vk.com/access_token';
        $token_params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'code' => $code,
            'code_verifier' => $code_verifier
        );

        // Add device_id and ext_id if they exist
        if (isset($_GET['device_id'])) {
            $token_params['device_id'] = sanitize_text_field($_GET['device_id']);
        }
        if (isset($_GET['ext_id'])) {
            $token_params['ext_id'] = sanitize_text_field($_GET['ext_id']);
        }

        $response = wp_remote_post($token_url, array(
            'body' => $token_params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('VK Auth Error: ' . $response->get_error_message());
            wp_die('Error getting access token: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            error_log('VK API Error: ' . print_r($body, true));
            wp_die('VK API Error: ' . (isset($body['error_description']) ? $body['error_description'] : $body['error']));
        }
        
        if (!isset($body['access_token']) || !isset($body['user_id'])) {
            error_log('VK Auth Response: ' . print_r($body, true));
            wp_die('Invalid response from VK. Please check the error log for details.');
        }

        // Get user info
        $user_info = $this->get_user_info($body['access_token'], $body['user_id']);
        
        if (!$user_info) {
            wp_die('Error getting user info');
        }

        // Create or update WordPress user
        $this->create_or_update_user($user_info, $body['user_id']);
    }

    private function get_user_info($access_token, $user_id) {
        $api_url = 'https://api.vk.com/method/users.get';
        $params = array(
            'user_ids' => $user_id,
            'fields' => 'first_name,last_name,photo_200,email',
            'access_token' => $access_token,
            'v' => $this->api_version
        );

        $response = wp_remote_post($api_url, array(
            'body' => $params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('VK API Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            error_log('VK API Error: ' . print_r($body['error'], true));
            return false;
        }
        
        if (!isset($body['response'][0])) {
            error_log('VK API Response: ' . print_r($body, true));
            return false;
        }

        return $body['response'][0];
    }

    private function create_or_update_user($user_info, $vk_user_id) {
        $user_login = 'vk_' . $vk_user_id;
        $user = get_user_by('login', $user_login);

        if (!$user) {
            // Create new user
            $random_password = wp_generate_password();
            $user_id = wp_create_user($user_login, $random_password, $user_info['email'] ?? '');
            
            if (is_wp_error($user_id)) {
                wp_die($user_id->get_error_message());
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

        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        // Redirect to home page or specified page
        wp_redirect(home_url());
        exit;
    }
} 