<?php

// Adapté de http://wolf-et.ru/php/curl-vs-sockets-vs-file_get_contents-vs-multicurl/

function curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    //echo $result;
    curl_close($ch);
    return $result;
}

function sockets($host) {
    $fp = fsockopen($host, 9200, $errno, $errstr, 30);
    $out = "GET / HTTP/1.1\r\n";
    $out .= "Host: " . $host . "\r\n";
    $out .= "Connection: Close\r\n\r\n";
    fwrite($fp, $out);
    $f = '';
    while (!feof($fp)) {
        $f .= fgets($fp, 1024);
    }
    return $f;
}

function fgc($url) {
    return file_get_contents($url);
}

header('content-type: text/plain;charset=utf-8');
///wp_prisme/_search
$url = '127.0.0.1:9200';
$iter = 1000;
echo "Test de $iter requêtes http get $url\n\n";

echo "\nCURL :\n------\n";
$start = microtime(1);
for ($i = 0 ; $i < $iter ; $i++)
    curl($url);
$end = microtime(1);
echo "\nCurl: " . ($end - $start) . " secondes \n";


echo "\nFile_get_contents :\n-------------------\n";
$start = microtime(1);
for ($i = 0 ; $i < $iter ; $i++)
    fgc("http://$url/");
$end = microtime(1);
echo "\nfile_get_contents: " . ($end - $start) . " secondes\n";

echo "\nSockets :\n---------\n";
$start = microtime(1);
for ($i = 0 ; $i < $iter ; $i++)
    sockets($url);
$end = microtime(1);
echo "\nSockets: " . ($end - $start) . " secondes\n";