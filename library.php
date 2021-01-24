<?php

function clean_sms($modem)
{
    echo date('d.m.Y H:i:s ') . "clean_sms\n";
    do {
        $stop = true;
        $sms = $modem->smsread();
        
        if ($sms->Index) {
            $modem->smsdelete($sms->Index);
        } else {
            $stop = false;
        }
        
    } while ($stop);
}

function request_code($number) {
    echo date('d.m.Y H:i:s ') . "request_code\n";
    $url = "https://my.tele2.ru/api/validation/number/${number}";
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"sender":"Tele2"}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer',
        'Connection: keep-alive',
        'Tele2-User-Agent: "mytele2-app/3.17.0"; "unknown"; "Android/9"; "Build/12998710"',
        'X-API-Version: 1',
        'User-Agent: okhttp/4.2.0',
        'Content-Type: application/json'
    ));
    $data = curl_exec($ch);
    $data = json_decode($data, true);
    curl_close($ch);
    
    if ($data != NULL) {
        echo date('d.m.Y H:i:s ') . $data['name'] . ": " . $data['detail']. "\n";
        return false;
    }
    
    return true;
}

function get_code($modem) {
    echo date('d.m.Y H:i:s ') . "get_code\n";
    $sms = $modem->smsread();
    
    if (isset($sms->Content) && preg_match('/ - ваш код для входа. Им можно воспользоваться только один раз.$/', $sms->Content)) {
        $code = preg_replace('/ - ваш код для входа. Им можно воспользоваться только один раз.$/', '', $sms->Content);
        return $code;
    }
    
    return false;
}

function get_access_token($number, $code) {
    echo date('d.m.Y H:i:s ') . "get_access_token\n";
    $url     = "https://my.tele2.ru/auth/realms/tele2-b2c/protocol/openid-connect/token";
    $params  = array(
        'client_id' => 'digital-suite-web-app',
        'grant_type' => 'password',
        'username' => $number,
        'password' => $code,
        'password_type' => 'sms_code'
    );
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer',
        'Connection: keep-alive',
        'Tele2-User-Agent: "mytele2-app/3.17.0"; "unknown"; "Android/9"; "Build/12998710"',
        'X-API-Version: 1',
        'User-Agent: okhttp/4.2.0',
        'Content-Type: application/x-www-form-urlencoded'
    ));
    $data = curl_exec($ch);
    $data = json_decode($data, true);
    curl_close($ch);
    
    if (isset($data['access_token'])) {
        return $data['access_token'];
    } else {
        echo date('d.m.Y H:i:s ') . $data['error'] . ": " . $data['error_description']. "\n";
    }
    
    return false;
}

function set_lot($number, $access_token, $type) {
    echo date('d.m.Y H:i:s ') . "set_lot\n";
    
    if ($type == 'data') {
        $request = '{"volume":{"value":1,"uom":"gb"},"cost":{"amount":15,"currency":"rub"},"trafficType":"data"}';
    } else {
        $request = '{"volume":{"value":50,"uom":"min"},"cost":{"amount":40,"currency":"rub"},"trafficType":"voice"}';
    }
    
    $url     = "https://my.tele2.ru/api/subscribers/${number}/exchange/lots/created";
    $headers = array(
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0',
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    );
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if ($data['meta']['status'] == "OK") {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . "\n";
    } else {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . ": " . $data['meta']['message']. "\n";
    }
    
    return $data;
}

function get_first_position($number, $access_token, $type) {
    echo date('d.m.Y H:i:s ') . "get_first_position\n";
    
    if ($type == 'data') {
        $request = 'trafficType=data&volume=1&cost=15&offset=0&limit=4';
    } else {
        $request = 'trafficType=voice&volume=50&cost=40&offset=0&limit=4';
    }
    
    $url     = "https://my.tele2.ru/api/subscribers/${number}/exchange/lots?${request}";
    $headers = array(
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0',
        'Authorization: Bearer ' . $access_token,
    );
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if ($data['meta']['status'] == "OK") {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . "\n";
    } else {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . ": " . $data['meta']['message']. "\n";
    }
    
    return $data;
}

function delete_lot($number, $access_token, $id) {
    echo date('d.m.Y H:i:s ') . "delete_lot\n";
    $url     = "https://my.tele2.ru/api/subscribers/${number}/exchange/lots/created/${id}";
    $headers = array(
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0',
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    );
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if ($data['meta']['status'] == "OK") {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . "\n";
    } else {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . ": " . $data['meta']['message']. "\n";
    }
    
    return $data;
}

function get_my_lots($number, $access_token, $type) {
    echo date('d.m.Y H:i:s ') . "get_my_lots\n";
    
    $lots    = false;
    $url     = "https://my.tele2.ru/api/subscribers/${number}/exchange/lots/created";
    $headers = array(
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0',
        'Authorization: Bearer ' . $access_token,
    );
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $data = curl_exec($ch);
    $data = json_decode($data, true);
    curl_close($ch);
    
    if ($data['meta']['status'] == "OK") {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . "\n";
        
        foreach ($data['data'] as $lot) {
            if ($lot['status'] == 'bought' && $lot['type'] == $type) {
                $lots[] = $lot;
            }
        }
    } else {
        echo date('d.m.Y H:i:s ') . $data['meta']['status'] . ": " . $data['meta']['message']. "\n";
    }
    
    return $lot;
}

function sleeping($seconds) {
    echo date('d.m.Y H:i:s ') . "sleep $seconds\n";
    sleep($seconds);
}

function readline($prompt = null){
    if($prompt){
        echo $prompt;
    }
    $fp = fopen("php://stdin","r");
    $line = rtrim(fgets($fp, 2048));
    return $line;
}
