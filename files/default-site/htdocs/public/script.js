var pingTimeout = 500;


function initPing(forwardUrl) {
    setTimeout(function() {
        ping(forwardUrl);
    }, pingTimeout);
}
function ping(forwardUrl) {
    $.ajax({
        url: '/ping',
        timeout: 300,
        success: function() {
            document.location = forwardUrl;
        },
        error: function() {
            initPing(forwardUrl);
        }
    });
}
