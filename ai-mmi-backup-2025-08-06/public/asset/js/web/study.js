$(document).ready(function() {
    // Handle study option button clicks
    $('.study-option-button').on('click', function(e) {
        const action = $(this).data('action');
        const href = $(this).attr('href');
        
        // If the button has a valid href (not javascript:void), follow it
        if (href && href !== 'javascript:void(0);') {
            // Let the browser follow the link naturally
            return true;
        }
        
        // For buttons without proper links, prevent default and show a message
        e.preventDefault();
        console.log('Study action:', action, '- Coming soon!');
    });
});
