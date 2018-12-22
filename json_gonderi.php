<?php

$mysqli = new mysqli('89.252.178.128', 'db', 'Daf!191o');
if($mysqli->connect_errno){
    echo "Bağlantı Hatası:".$con->connect_errno;
    exit;
}
$mysqli->select_db('yeni_db');
$mysqli->set_charset("utf8");

function gonderial($tur,$limit){
    global $mysqli;
    $gonderiler = array();
    $mysqliquery = "SELECT * FROM gonderiler where fixed='0' order by numaravarmi desc, id asc limit $limit";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $shortcode = $rows['shortcode'];
        array_push($gonderiler,$shortcode);
    endwhile;
    return $gonderiler;
}

function gonderiguncelle($bulunanlar){
    global $mysqli;

    foreach ($bulunanlar as $key => $bulunan){
        $shortcode = $bulunan['shortcode'];
        $username = $bulunan['username'];
        $mysqliquery = "UPDATE gonderiler SET username='$username',fixed=1 WHERE shortcode='$shortcode'";
        $runmysqli = $mysqli->query($mysqliquery);
    }

}

function usernamebul($limit){
    $gonderiler = gonderial("username", $limit);
    $linkler = array();
    $bulunanlar = array();
    foreach ($gonderiler as $key => $shortcode){
        array_push($linkler, "https://www.instagram.com/p/$shortcode/?__a=1");
    }

    $responses = multiRequest($linkler);

    foreach ($responses as $key => $response){
        $array = json_decode($response,true);
        $shortcode = $array['graphql']['shortcode_media']['shortcode'];
        $username = $array['graphql']['shortcode_media']['owner']['username'];
        $ownerid = $array['graphql']['shortcode_media']['owner']['id'];
        array_push($bulunanlar, array("ownerid" => $ownerid, "username" => $username, "shortcode" => $shortcode));
    }

    gonderiguncelle($bulunanlar);
}

function multiRequest($data, $options = array()) {
    $curly = array();
    $result = array();
    $mh = curl_multi_init();

    foreach ($data as $id => $d) {
        $curly[$id] = curl_init();
        $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
        curl_setopt($curly[$id], CURLOPT_URL,            $url);
        curl_setopt($curly[$id], CURLOPT_HEADER,         0);
        curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

        if (is_array($d)) {
            if (!empty($d['post'])) {
                curl_setopt($curly[$id], CURLOPT_POST,       1);
                curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
            }
        }

        if (!empty($options)) {
            curl_setopt_array($curly[$id], $options);
        }

        curl_multi_add_handle($mh, $curly[$id]);
    }


    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while($running > 0);

    foreach($curly as $id => $c) {
        $result[$id] = curl_multi_getcontent($c);
        curl_multi_remove_handle($mh, $c);
    }

    curl_multi_close($mh);

    return $result;
}

if(isset($_GET['limit'])){$limit = $_GET['limit'];}else{$limit = 1;}
usernamebul($limit);