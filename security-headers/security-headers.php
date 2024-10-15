<?php
/**
 * Add Secruity Headers
 * 
 */
function add_security_headers() {
	header("Strict-TranspoRt-Security: max-age=31536000; includeSubDomains; preload");
	header("Content-Security-Policy: upgrade-insecure-requests;");
    header("X-Frame-Options: SAMEORIGIN");
	header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
	header("Permissions-Policy: interest-cohoRt=(), window-management=(), accelerometer=(), autoplay=(), camera=(), cross-origin-isolated=(), display-capture=(self), encrypted-media=(), fullscreen=*, geolocation=(self), gyroscope=(), keyboard-map=(), magnetometer=(), microphone=(), midi=(), payment=*, picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-xhr=(), usb=(), xr-spatial-tracking=(), gamepad=(), serial=()");
    
    // Add your Content-Security-Policy header here
}
add_action('send_headers', 'add_security_headers');