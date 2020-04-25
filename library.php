<?php

function clean_sms($modem)
{
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

function request_code($domen, $number) {
    $url = "https://${domen}/api/validation/number/${number}";
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"sender":"Tele2"}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if ($data != NULL) {
        print_r($data);
        return false;
    }
    
    return true;
}

function get_code($modem) {
    $sms = $modem->smsread();
    
    if (isset($sms->Content) && preg_match('/^Ваш код для входа: /', $sms->Content)) {
        $code = preg_replace('/^Ваш код для входа: /', '', $sms->Content);
        $code = preg_replace('/. Им можно воспользоваться только один раз.$/', '', $code);
        return $code;
    }
    
    return false;
}

function get_access_token($domen, $number, $code) {
    $url     = "https://${domen}/auth/realms/tele2-b2c/protocol/openid-connect/token";
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
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (isset($data['access_token'])) {
        return $data['access_token'];
    }
    
    return false;
}

function set_lot($domen, $number, $access_token) {
    $url     = "https://${domen}/api/subscribers/${number}/exchange/lots/created";
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
    
    return $data;
}

function get_first_position($domen, $number, $access_token) {
    $url     = "https://${domen}/api/subscribers/${number}/exchange/lots?trafficType=voice&volume=50&cost=40&offset=0&limit=4";
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
    
    return $data;
}

function delete_lot($domen, $number, $access_token, $id) {
    $url     = "https://${domen}/api/subscribers/${number}/exchange/lots/created/${id}";
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
    
    return $data;
}
