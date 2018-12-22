<?php
$username = $_GET['username'];
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function fotolaricek($kullaniciadi){
    $site_url = "http://www.instagram.com/$kullaniciadi";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
    curl_setopt($ch, CURLOPT_URL, $site_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    global $base_url;
    $base_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    $response = explode("window._sharedData = ", $response)[1];
    $response = explode(";</script>", $response)[0];
    return $response;
}
$sonuc = fotolaricek($username);
print_r($sonuc);
?>