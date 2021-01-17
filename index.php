<?php
require_once(dirname(__FILE__) . "/modem.class.php");
require_once(dirname(__FILE__) . "/library.php");

$active     = true;
$host       = false;
$user       = false;
$password   = false;
$number     = false;
$shortopts  = "";
$shortopts .= "h:";
$shortopts .= "u:";
$shortopts .= "p:";
$shortopts .= "n:";
$shortopts .= "d:";
$options    = getopt($shortopts);

if (is_file(dirname(__FILE__) . "/.config.ini")){
    $config = parse_ini_file(dirname(__FILE__) . "/.config.ini");
    
    if (isset($config["host"])) {
        $host = $config["host"];
    }
    
    if (isset($config["user"])) {
        $user = $config["user"];
    }
    
    if (isset($config["password"])) {
        $password = $config["password"];
    }
    
    if (isset($config["number"])) {
        $number = $config["number"];
    }
    
}

if (isset($options["h"])) {
    $host = $options["h"];
}

if (isset($options["u"])) {
    $user = $options["u"];
}

if (isset($options["p"])) {
    $password = $options["p"];
}

if (isset($options["n"])) {
    $number = $options["n"];
}

if ($host === false || $user === false || $password === false || $number === false) {
    echo date('d.m.Y H:i:s ') . "Не заданы все необходимые параметры\n";
    exit();
}

$modem = new Manoaratefy\NetworkTools\Modem($host, $user, $password);

clean_sms($modem);

if (request_code($number)) {
    sleeping(5);
} else {
    exit();
}

$code = get_code($modem);

if ($code) {
    $access_token = get_access_token($number, $code);
}

if ($access_token == false) {
    exit();
}

while ($active) {
    sleeping(3);
    $lot = set_lot($number, $access_token);
    
    if (isset($lot['data']['id'])){
        do {
            sleeping(10);
            $first_position = get_first_position($number, $access_token);
            
            if (isset($first_position['data'][0]['id'])) {
                
            } else {
                $active = false;
                break;
            }
            
        } while (array_search($lot['data']['id'], array_column($first_position['data'], 'id')));
    } else {
        break;
    }
    
    if ($active) {
        sleeping(3);
        $delete = delete_lot($number, $access_token, $lot['data']['id']);
        
        if ($delete['meta']['status'] == "ERROR") {
            break;
        }
        
    }
    
}
