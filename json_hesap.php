<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('display_errors', 1);

$mysqli = new mysqli('89.252.178.128', 'db', 'Daf!191o');
if($mysqli->connect_errno){
    echo "Bağlantı Hatası:".$con->connect_errno;
    exit;
}
$mysqli->select_db('yeni_db');
$mysqli->set_charset("utf8");

function hesapal($limit){
    global $mysqli;
    $hesaplar = array();
    $mysqliquery = "SELECT * FROM gonderiler where fixed='1' order by numaravarmi desc, rand() limit $limit";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $username = $rows['username'];
        array_push($hesaplar,$username);
    endwhile;
    return $hesaplar;
}


function telefonbul($limit){
    $hesaplar = hesapal($limit);
    $linkler = array();
    $bulunanlar = array();
    $numaralar = array();
    foreach ($hesaplar as $key => $username){
        array_push($linkler, "https://www.instagram.com/$username/");
    }

    $responses = multiRequest($linkler);

    foreach ($responses as $key => $response){
        $response = explode("window._sharedData = ", $response)[1];
        $response = explode(";</script>", $response)[0];
        $array = json_decode($response,true);

        $username = $array['entry_data']['ProfilePage'][0]['graphql']['user']['username'];
        $takipci = $array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_followed_by']['count'];
        $takipedilen = $array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_follow']['count'];
        $gonderisayisi = $array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['count'];
        $biyografi = $array['entry_data']['ProfilePage'][0]['graphql']['user']['biography'];
        $biyografi = str_replace("'", "", $biyografi);
        $ayikla = ayikla($biyografi);

        if($ayikla != "NULL"){
            $numara = $ayikla;
            $numaravarmi = 1;
            array_push($numaralar, $numara);

        }else{
            if(isset($array['entry_data']['ProfilePage']['graphql']['user']['business_phone_number'])){
                $numaravarmi = 1;
                $numara = $array['entry_data']['ProfilePage']['graphql']['user']['business_phone_number'];
                array_push($numaralar, $numara);
            }else{
                $numaravarmi = 0;
                $numara = "NULL";
            }
        }

        global $mysqli;
        if($numara == "NULL"){
            $mysqliquery = "UPDATE gonderiler SET numaravarmi='$numaravarmi', takipci='$takipci', takipedilen='$takipedilen', gonderisayisi='$gonderisayisi', fixed=2 WHERE username='$username'";
        }else{
            $mysqliquery = "UPDATE gonderiler SET numaravarmi='$numaravarmi', numara='$numara', takipci='$takipci', takipedilen='$takipedilen', gonderisayisi='$gonderisayisi', fixed=2 WHERE username='$username'";
        }

        $runmysqli = $mysqli->query($mysqliquery);

    }
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


function ayiklakontrol($text){
    $number = "NULL";
    if(($pos = strpos($text, '5')) !== false){
        $text = substr($text, $pos);
        $text = preg_replace('/\s+/', '', $text);
        $text = str_replace(' ', '', $text);
        $text = str_replace('-', '', $text);
        $text = str_replace('(', '', $text);
        $text = str_replace(')', '', $text);
        if(is_numeric(substr($text, 0, 10))){
            $number = substr($text, 0, 10);
        }
    }
    return $number;
}


function ayikla($text){
    $number = "NULL";
    $newbio = "";
    if(($pos = strpos($text, '5')) !== false){
        $newbio = substr($text, $pos);
        $newbio = preg_replace('/\s+/', '', $newbio);
        $newbio = str_replace(' ', '', $newbio);
        $newbio = str_replace('-', '', $newbio);
        $newbio = str_replace('(', '', $newbio);
        $newbio = str_replace(')', '', $newbio);
        if(is_numeric(substr($newbio, 0, 10))){
            $number = substr($newbio, 0, 10);
        }
    }

    if($number == "NULL"){
        $newbio = substr($newbio, 1);
        $sonuc = ayiklakontrol($newbio);
        $number = $sonuc;
        if($sonuc = "NULL"){
            $newbio = substr($newbio, 1);
            $sonuc2 = ayiklakontrol($newbio);
            $number = $sonuc2;
            if($sonuc2 = "NULL"){
                $newbio = substr($newbio, 1);
                $sonuc3 = ayiklakontrol($newbio);
                $number = $sonuc3;
            }
        }
    }


    if(strlen($number) != 10){$number = "NULL";}

    return $number;
}

if(isset($_GET['limit'])){$limit = $_GET['limit'];}else{$limit = 1;}
telefonbul($limit);