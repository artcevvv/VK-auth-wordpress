# VK Authentication for WordPress

A WordPress plugin that allows users to log in using their VKontakte accounts.

## Features

- VKontakte OAuth authentication
- Automatic user registration
- User profile data synchronization
- Customizable login button
- Admin settings page

## Installation

1. Download the plugin files
2. Upload the plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > VK Auth to configure the plugin

## Configuration

1. Create a new application at [VK Apps](https://vk.com/apps?act=manage)
2. Set the platform to "Website"
3. Enter your website URL
4. Copy the Client ID and Client Secret
5. Go to WordPress admin > Settings > VK Auth
6. Enter your Client ID and Client Secret
7. Save the settings

## Usage

### Shortcode

Use the following shortcode to display the VK login button anywhere on your site:

```
[vk_auth_button]
```

Optional parameters:
- `text`: Custom button text (default: "Login with VK")
- `class`: Custom CSS class (default: "vk-auth-button")
- `redirect`: Custom redirect URL after login

Example:
```
[vk_auth_button text="Sign in with VK" class="custom-vk-button" redirect="https://example.com/custom-page"]
```

### PHP Function

You can also use the PHP function to display the button:

```php
<?php echo do_shortcode('[vk_auth_button]'); ?>
```

## Security

- All API requests are made server-side
- User data is properly sanitized
- OAuth state parameter is used to prevent CSRF attacks
- Secure password generation for new users

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- VKontakte developer account

## Support

For support, please create an issue in the GitHub repository.

## License

This plugin is licensed under the GPL v2 or later. 