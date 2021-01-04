<?php
require_once(dirname(__FILE__) . "/modem.class.php");
require_once(dirname(__FILE__) . "/library.php");

$active     = true;
$host       = false;
$user       = false;
$password   = false;
$number     = false;
$domain     = false;
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
    
    if (isset($config["domain"])) {
        $domain = $config["domain"];
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

if (isset($options["d"])) {
    $domain = $options["d"];
}

if ($host === false || $user === false || $password === false || $number === false || $domain === false ) {
    echo date('d.m.Y H:i:s ') . "Не заданы все необходимые параметры\n";
    exit();
}

$modem = new Manoaratefy\NetworkTools\Modem($host, $user, $password);

clean_sms($modem);

/*
$csrf_token = readline('Введите X-csrftoken токен: ');
$ajax_token = readline('Введите X-Ajax-Token токен: ');
*/

$access_token = readline('Введите access_token токен: ');

/*
if (request_code($domain, $number, $csrf_token, $ajax_token)) {
    sleeping(5);
} else {
    exit();
}

$code = get_code($modem);

if ($code) {
    $access_token = get_access_token($domain, $number, $code, $csrf_token, $ajax_token);
}

if ($access_token == false) {
    exit();
}
*/

while ($active) {
    sleeping(3);
    $lot = set_lot($domain, $number, $access_token);
    
    if (isset($lot['data']['id'])){
        do {
            sleeping(10);
            $first_position = get_first_position($domain, $number, $access_token);
            
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
        $delete = delete_lot($domain, $number, $access_token, $lot['data']['id']);
        
        if ($delete['meta']['status'] == "ERROR") {
            break;
        }
        
    }
    
}
