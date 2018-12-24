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


function mysqlistring($string){
    global $mysqli;
    $yenistring = '"'.$mysqli->real_escape_string($string).'"';
    return $yenistring;
}


function servers(){
    global $mysqli;
    $servers = array();
    $mysqliquery = "SELECT * FROM servers";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $name = $rows['name'];
        array_push($servers, $name);
    endwhile;
    return $servers;
}


/* Veritabanı İşlemleri */

function tumhashtagler($limit){
    global $mysqli;
    $count = 0;
    $hashtagler = array();
    $mysqliquery = "SELECT * FROM hashtags order by lastscan asc, postcount desc limit $limit";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $id = $rows['id'];
        $hashtag = $rows['hashtag'];
        $count = $rows['count'];
        $count += 1;
        $mysqliquery2 = "UPDATE hashtags SET count=$count, lastscan=now() WHERE id='$id'";
        $runmysqli2 = $mysqli->query($mysqliquery2);
        array_push($hashtagler,$hashtag);
    endwhile;
    return $hashtagler;
}

function gonderivarmi($shortcode){
    global $mysqli;
    $id = 0;
    $mysqliquery = "SELECT * FROM gonderiler where  shortcode='$shortcode'";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $id = $rows['id'];
        if($id != 0){$id = 1;}
        return $id;
    endwhile;
    return $id;
}

function numaravarmi($numara){
    global $mysqli;
    $id = 0;
    $mysqliquery = "SELECT * FROM gonderiler where numara='$numara'";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $id = $rows['id'];
        if($id != 0){$id = 1;}
        return $id;
    endwhile;
    return $id;
}

function hesapvarmi($hesap){
    global $mysqli;
    $id = 0;
    $mysqliquery = "SELECT * FROM gonderiler where ownerid='$hesap'";
    $runmysqli =  mysqli_query($mysqli, $mysqliquery);
    WHILE($rows =mysqli_fetch_array($runmysqli)):
        $id = $rows['id'];
        if($id != 0){$id = 1;}
        return $id;
    endwhile;
    return $id;
}



function gonderiekle($ownerid,$hashtag,$shortcode,$numaravarmi,$numara){
    if(gonderivarmi($shortcode)) return "postexist";
    if(hesapvarmi($ownerid)) return "hesapexist";

    if($numara != "NULL"){
        if(numaravarmi($numara)) return "numberexist";
    }


    global $mysqli;
    $ownerid = mysqlistring($ownerid);
    $hashtag = mysqlistring($hashtag);
    $shortcode = mysqlistring($shortcode);
    $numara = mysqlistring($numara);
    $mysqliquery = "INSERT INTO gonderiler (ownerid,hashtag,shortcode,numaravarmi,numara) VALUES($ownerid,$hashtag,$shortcode,$numaravarmi,$numara)";
    $runmysqli = mysqli_query($mysqli, $mysqliquery);
    return "ok";
}



/* Helpers */


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




/* Curl İşlemleri */

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

function tara($limit){
    /* Tanımlar */
    $urllist = array();
    $numaralar = array();
    $hesaplar = array();
    $numaralar = array();
    $hashtags = tumhashtagler($limit);


    /* For Each Link Oluştur*/
    foreach ($hashtags as $key => $hashtag){
        $servers = servers();
        $servercount = count($servers);
        $server = $servers[$key%$servercount];
        $url = "http://$server.herokuapp.com/?hashtag=$hashtag";
        array_push($urllist, $url);
    }


    /* Multirequest response */
    $responses = multiRequest($urllist);

    /* response foreach */
    foreach ($responses as $reskey => $response){
        $json = json_decode($response,true);
        $edges = $json['graphql']['hashtag']['edge_hashtag_to_media']['edges'];

        /* Gönderiler */
        foreach ($edges as $edkey => $edge){
            $ownerid = $edge['node']['owner']['id'];
            $shortcode = $edge['node']['shortcode'];

            if(!in_array($ownerid, $hesaplar, true)){
                array_push($hesaplar, $ownerid);
            }else{
                continue;
            }

            $aciklama = "";
            if(isset($edge['node']['edge_media_to_caption']['edges'][0])){
                $aciklama = $edge['node']['edge_media_to_caption']['edges'][0]['node']['text'];
            }

            $aciklama = str_replace("'", "", $aciklama);
            $hashtagx = $hashtags[$reskey];
            $ayikla = ayikla($aciklama);

            if($ayikla != "NULL"){
                $numaravarmi = 1;
                $numara = $ayikla;
                echo $numara ."</br>";
            }else{
                $numaravarmi = 0;
                $numara = "NULL";
            }

            gonderiekle($ownerid,$hashtagx,$shortcode,$numaravarmi,$numara);

        }
    }
}


if(isset($_GET['limit'])){$limit = $_GET['limit'];}else{$limit = 1;}
tara($limit);