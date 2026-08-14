<?php
/**
 * OkoTunes — Single Page Entry Point
 * Built for Mphathisi Ndlovu (The Teq-master) Digital Ecosystem
 */

// Set caching headers for high performance
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Serve the standalone index.html single page application
readfile(__DIR__ . '/index.html');
exit;
