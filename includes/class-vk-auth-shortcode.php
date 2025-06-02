<?php

class VK_Auth_Shortcode {
    private $code_verifier;
    private $code_challenge;

    public function init() {
        add_shortcode('vk_auth', array($this, 'render_shortcode'));
    }

    private function generate_pkce_values() {
        // Generate code verifier (43-128 characters)
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $code_verifier = '';
        $length = random_int(43, 128);
        
        for ($i = 0; $i < $length; $i++) {
            $code_verifier .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Generate code challenge using SHA256
        $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
        
        return array(
            'code_verifier' => $code_verifier,
            'code_challenge' => $code_challenge
        );
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => __('Login with VK', 'vk-auth'),
            'class' => 'vk-auth-button',
            'redirect' => '',
            'scope' => 'vkid.personal_info',
            'prompt' => 'select_account',
            'lang_id' => '0',
            'scheme' => 'light'
        ), $atts, 'vk_auth');

        if (is_user_logged_in()) {
            return '';
        }

        $client_id = get_option('vk_auth_client_id');
        $redirect_uri = !empty($atts['redirect']) ? $atts['redirect'] : get_option('vk_auth_redirect_uri');

        if (empty($client_id) || empty($redirect_uri)) {
            return '<!-- VK Auth not configured -->';
        }

        // Generate PKCE values
        $pkce = $this->generate_pkce_values();
        
        // Store code_verifier in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['vk_auth_code_verifier'] = $pkce['code_verifier'];

        // Generate state
        $state = wp_create_nonce('vk_auth_state');

        // Build authorization URL
        $auth_url = add_query_arg(array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => $atts['scope'],
            'state' => $state,
            'code_challenge' => $pkce['code_challenge'],
            'code_challenge_method' => 'S256',
            'prompt' => $atts['prompt'],
            'lang_id' => $atts['lang_id'],
            'scheme' => $atts['scheme']
        ), 'https://id.vk.com/authorize');

        // Add debug info for administrators
        $debug_html = '';
        if (current_user_can('administrator')) {
            $debug_html = '<div style="display:none;" class="vk-auth-debug-info">';
            $debug_html .= 'Client ID: ' . esc_html($client_id) . '<br>';
            $debug_html .= 'Redirect URI: ' . esc_html($redirect_uri) . '<br>';
            $debug_html .= '</div>';
        }

        ob_start();
        ?>
        <div class="vk-auth-container" style="min-height: 40px;">
            <div class="vk-auth-button-wrapper">
                <a href="<?php echo esc_url($auth_url); ?>" class="<?php echo esc_attr($atts['class']); ?>">
                    <svg class="vk-icon" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="currentColor" d="M15.07 2H8.93C3.33 2 2 3.33 2 8.93V15.07C2 20.67 3.33 22 8.93 22H15.07C20.67 22 22 20.67 22 15.07V8.93C22 3.33 20.67 2 15.07 2ZM18.15 16.27H16.69C16.14 16.27 15.97 15.97 14.86 14.94C13.86 14 13.47 13.74 13.18 13.74C12.88 13.74 12.75 13.88 12.75 14.26V15.69C12.75 16.04 12.59 16.27 11.73 16.27C10.29 16.27 8.61 15.31 7.39 13.74C5.45 11.04 4.86 9.14 4.86 8.75C4.86 8.54 4.98 8.34 5.32 8.34H6.78C7.15 8.34 7.38 8.54 7.55 8.88C8.23 10.54 9.53 12.09 10.11 12.09C10.4 12.09 10.53 11.95 10.53 11.57V10.19C10.5 9.12 9.81 9.03 9.81 8.68C9.81 8.5 9.96 8.34 10.16 8.34H12.83C13.15 8.34 13.34 8.5 13.34 8.82V11.16C13.34 11.5 13.5 11.66 13.59 11.66C13.88 11.66 14.18 11.5 14.76 10.95C16.03 9.53 16.77 7.85 16.77 7.85C16.9 7.55 17.13 7.34 17.5 7.34H18.96C19.3 7.34 19.42 7.55 19.3 7.85C19.14 8.26 17.95 10.04 17.95 10.04C17.77 10.34 17.67 10.5 17.95 10.81C18.15 11.04 18.8 11.66 19.27 12.14C20.05 12.95 20.77 13.66 20.93 13.95C21.1 14.25 20.93 14.5 20.59 14.5L18.15 16.27Z"/>
                    </svg>
                    <?php echo esc_html($atts['text']); ?>
                </a>
            </div>
            <?php echo $debug_html; ?>
        </div>
        <?php
        return ob_get_clean();
    }
} 