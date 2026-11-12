<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

$file = $_GET['file'] ?? '';
$file = basename($file); // security: filename only
$path = '../uploads/lessons/pdfs/' . $file;

if (!$file || !file_exists($path)) { http_response_code(404); exit('File not found'); }

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $file . '"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
// Allow embedding in iframe (same origin)
header('X-Frame-Options: SAMEORIGIN');
// Required for iframe embedding on some mobile browsers
header('Content-Security-Policy: default-src \'self\'');
$size = filesize($path);
header('Content-Length: ' . $size);
readfile($path);
exit();
?>