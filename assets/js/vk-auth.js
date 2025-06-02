jQuery(document).ready(function($) {
    console.log('VK Auth: Document ready');

    // Initialize VK ID SDK when the page loads
    if (typeof VKID !== 'undefined') {
        console.log('VK Auth: Initializing VK ID SDK');
        VKID.Config.set({
            app: vkAuth.client_id,
            redirectUrl: vkAuth.redirect_uri,
            state: vkAuth.state,
            codeChallenge: vkAuth.code_challenge,
            codeChallengeMethod: 'S256'
        });
        console.log('VK Auth: VK ID SDK initialized with config:', {
            app: vkAuth.client_id,
            redirectUrl: vkAuth.redirect_uri,
            state: vkAuth.state,
            codeChallenge: vkAuth.code_challenge
        });
    } else {
        console.error('VK Auth: VK ID SDK not loaded');
    }

    // Handle VK login button click
    $(document).on('click', '.vk-auth-button', function(e) {
        console.log('VK Auth: Button clicked');
        e.preventDefault();
        
        if (typeof VKID === 'undefined') {
            console.error('VK Auth: VK ID SDK not loaded');
            alert('VK authentication is not available at the moment. Please try again later.');
            return;
        }

        // Add loading state
        $(this).addClass('loading');
        console.log('VK Auth: Starting VK ID login');

        // Initialize VK ID login
        VKID.Auth.login({
            onAuth: function(data) {
                console.log('VK Auth: Auth success, data:', data);
                // Send the auth data to our server
                $.ajax({
                    url: vkAuth.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vk_auth_login',
                        nonce: vkAuth.nonce,
                        auth_data: data
                    },
                    success: function(response) {
                        console.log('VK Auth: Server response:', response);
                        if (response.success) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            console.error('VK Auth Error:', response.data.message);
                            alert('Authentication failed: ' + response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('VK Auth AJAX Error:', error);
                        alert('Authentication failed. Please try again later.');
                    },
                    complete: function() {
                        // Remove loading state
                        $('.vk-auth-button').removeClass('loading');
                    }
                });
            },
            onError: function(error) {
                console.error('VK Auth Error:', error);
                alert('Authentication failed. Please try again later.');
                $('.vk-auth-button').removeClass('loading');
            }
        });
    });

    // Add loading state styles
    $('<style>')
        .text(`
            .vk-auth-button.loading {
                opacity: 0.7;
                pointer-events: none;
            }
            .vk-auth-button.loading::after {
                content: '';
                display: inline-block;
                width: 12px;
                height: 12px;
                margin-left: 8px;
                border: 2px solid #fff;
                border-radius: 50%;
                border-top-color: transparent;
                animation: vk-auth-spin 1s linear infinite;
            }
            @keyframes vk-auth-spin {
                to { transform: rotate(360deg); }
            }
        `)
        .appendTo('head');

    // Log initial state
    console.log('VK Auth: Initial state', {
        vkAuth: vkAuth,
        buttonExists: $('.vk-auth-button').length > 0,
        vkIdLoaded: typeof VKID !== 'undefined'
    });
});

// Initialize VK ID SDK when the page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('VK Auth: Document ready');

    if (typeof VKID !== 'undefined') {
        console.log('VK Auth: Initializing VK ID SDK');
        VKID.Config.set({
            app: vkAuth.client_id,
            redirectUrl: vkAuth.redirect_uri,
            state: vkAuth.state,
            codeChallenge: vkAuth.code_challenge,
            codeChallengeMethod: 'S256'
        });
        console.log('VK Auth: VK ID SDK initialized with config:', {
            app: vkAuth.client_id,
            redirectUrl: vkAuth.redirect_uri,
            state: vkAuth.state,
            codeChallenge: vkAuth.code_challenge
        });
    } else {
        console.error('VK Auth: VK ID SDK not loaded');
    }

    // Add loading state styles
    const style = document.createElement('style');
    style.textContent = `
        .vk-auth-button.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        .vk-auth-button.loading::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-left: 8px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: vk-auth-spin 1s linear infinite;
        }
        @keyframes vk-auth-spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    // Log initial state
    console.log('VK Auth: Initial state', {
        vkAuth: vkAuth,
        buttonExists: document.querySelector('.vk-auth-button') !== null,
        vkIdLoaded: typeof VKID !== 'undefined'
    });
});

// Global function for button onclick
function initializeVKID() {
    console.log('VK Auth: Button clicked');
    
    if (typeof VKID === 'undefined') {
        console.error('VK Auth: VK ID SDK not loaded');
        alert('VK authentication is not available at the moment. Please try again later.');
        return;
    }

    // Add loading state
    const button = document.querySelector('.vk-auth-button');
    if (button) {
        button.classList.add('loading');
    }
    console.log('VK Auth: Starting VK ID login');

    // Initialize VK ID login
    VKID.Auth.login({
        onAuth: function(data) {
            console.log('VK Auth: Auth success, data:', data);
            // Send the auth data to our server
            jQuery.ajax({
                url: vkAuth.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vk_auth_login',
                    nonce: vkAuth.nonce,
                    auth_data: data
                },
                success: function(response) {
                    console.log('VK Auth: Server response:', response);
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        console.error('VK Auth Error:', response.data.message);
                        alert('Authentication failed: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('VK Auth AJAX Error:', error);
                    alert('Authentication failed. Please try again later.');
                },
                complete: function() {
                    // Remove loading state
                    const button = document.querySelector('.vk-auth-button');
                    if (button) {
                        button.classList.remove('loading');
                    }
                }
            });
        },
        onError: function(error) {
            console.error('VK Auth Error:', error);
            alert('Authentication failed. Please try again later.');
            // Remove loading state
            const button = document.querySelector('.vk-auth-button');
            if (button) {
                button.classList.remove('loading');
            }
        }
    });
} 