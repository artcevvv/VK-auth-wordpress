<?php
/**
 * Plugin Name: VK Authentication
 * Plugin URI: https://github.com/yourusername/vk-auth
 * Description: VKontakte authentication for WordPress
 * Version: 1.0.0
 * Author: artcevvv
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vk-auth
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VK_AUTH_VERSION', '1.0.0');
define('VK_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VK_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once VK_AUTH_PLUGIN_DIR . 'includes/class-vk-auth.php';
require_once VK_AUTH_PLUGIN_DIR . 'includes/class-vk-auth-admin.php';
require_once VK_AUTH_PLUGIN_DIR . 'includes/class-vk-auth-shortcode.php';

// Initialize the plugin
function vk_auth_init() {
    $plugin = new VK_Auth();
    $plugin->init();
    
    // Initialize admin
    $admin = new VK_Auth_Admin();
    $admin->init();
    
    // Initialize shortcode
    $shortcode = new VK_Auth_Shortcode();
    $shortcode->init();
}
add_action('plugins_loaded', 'vk_auth_init');

// Activation hook
register_activation_hook(__FILE__, 'vk_auth_activate');
function vk_auth_activate() {
    // Add default options
    add_option('vk_auth_client_id', '');
    add_option('vk_auth_client_secret', '');
    add_option('vk_auth_redirect_uri', '');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'vk_auth_deactivate');
function vk_auth_deactivate() {
    // Cleanup if needed
} 