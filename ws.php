#!/usr/bin/php -q 
<?php
    date_default_timezone_set('Asia/Yekaterinburg');
    include 'lib/ws_webrtc.php';
    header('Content-Type: text/html; charset=UTF-8');
    
    
    
    $master = new ws_webrtc("localhost", 8082);