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

function request_code($domain, $number, $csrf_token, $ajax_token) {
    echo date('d.m.Y H:i:s ') . "request_code\n";
    $url = "https://${domain}/api/validation/number/${number}";
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"sender":"Tele2"}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0",
        "Content-Type: application/json;charset=utf-8",
        "X-csrftoken: $csrf_token",
        "X-Ajax-Token: $ajax_token"
    ));
    $data = json_decode(curl_exec($ch), true);
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
        $code = preg_replace('/ - ваш код для входа. Им можно воспользоваться только один раз.$/', '', $code);
        return $code;
    }
    
    return false;
}

function get_access_token($domain, $number, $code, $csrf_token, $ajax_token) {
    echo date('d.m.Y H:i:s ') . "get_access_token\n";
    $url     = "https://${domain}/auth/realms/tele2-b2c/protocol/openid-connect/token";
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
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0",
        "Content-Type: application/json;charset=utf-8",
        "X-csrftoken: $csrf_token",
        "X-Ajax-Token: $ajax_token"
    ));
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (isset($data['access_token'])) {
        return $data['access_token'];
    } else {
        echo date('d.m.Y H:i:s ') . $data['error'] . ": " . $data['error_description']. "\n";
    }
    
    return false;
}

function set_lot($domain, $number, $access_token) {
    echo date('d.m.Y H:i:s ') . "set_lot\n";
    $url     = "https://${domain}/api/subscribers/${number}/exchange/lots/created";
    $headers = array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    );
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"volume":{"value":50,"uom":"min"},"cost":{"amount":40,"currency":"rub"},"trafficType":"voice"}');
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

function get_first_position($domain, $number, $access_token) {
    echo date('d.m.Y H:i:s ') . "get_first_position\n";
    $url     = "https://${domain}/api/subscribers/${number}/exchange/lots?trafficType=voice&volume=50&cost=40&offset=0&limit=4";
    $headers = array(
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

function delete_lot($domain, $number, $access_token, $id) {
    echo date('d.m.Y H:i:s ') . "delete_lot\n";
    $url     = "https://${domain}/api/subscribers/${number}/exchange/lots/created/${id}";
    $headers = array(
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

function sleeping($seconds) {
    echo date('d.m.Y H:i:s ') . "sleep $seconds\n";
    sleep($seconds);
}

function get_csrf_token($domain) {
    echo date('d.m.Y H:i:s ') . "get_csrf_token\n";
    $url     = "https://${domain}";
    $ch      = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:84.0) Gecko/20100101 Firefox/84.0');
    $data = explode("\n", curl_exec($ch));
    curl_close($ch);
    
    foreach ($data as $string) {
        if (preg_match('/^<meta name=\'csrf-token-value\' content=\'/', $string)) {
            $csrf_token = preg_replace('/^<meta name=\'csrf-token-value\' content=\'/', '', $string);
            $csrf_token = preg_replace('/\'\/>$/', '', $csrf_token);
            return $csrf_token;
            break;
        }
    }
    
    return false;
}

function readline($prompt = null){
    if($prompt){
        echo $prompt;
    }
    $fp = fopen("php://stdin","r");
    $line = rtrim(fgets($fp, 1024));
    return $line;
}
