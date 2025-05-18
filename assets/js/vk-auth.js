jQuery(document).ready(function($) {
    // Handle VK login button click
    $('.vk-auth-button').on('click', function(e) {
        // The actual authentication is handled by the PHP redirect
        // This is just for any additional client-side functionality
        $(this).addClass('loading');
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
}); 