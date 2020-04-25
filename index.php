<?php
require_once(dirname(__FILE__) . "/modem.class.php");
require_once(dirname(__FILE__) . "/library.php");

$config = parse_ini_file(dirname(__FILE__) . "/.config.ini");
$modem  = new Manoaratefy\NetworkTools\Modem($config['host'], $config['user'], $config['password']);
$active = true;

clean_sms($modem);

if (request_code($config['domen'], $config['number'])) {
    sleep(5);
} else {
    exit();
}

$code = get_code($modem);

if ($code) {
    $access_token = get_access_token($config['domen'], $config['number'], $code);
}

if ($access_token) {
    echo "Получен access_token \n";
} else {
    echo "Ошибка получения access_token\n";
    exit();
}

while ($active) {
    echo "set_lot\n";
    $lot = set_lot($config['domen'], $config['number'], $access_token);
    
    if (isset($lot['data']['id'])){
        echo "sleep\n";
        sleep(10);
        do {
            echo "get_first_position\n";
            $first_position = get_first_position($config['domen'], $config['number'], $access_token);
            
            if (isset($first_position['data'][0]['id'])) {
                echo "sleep\n";
                sleep(10); 
            } else {
                $active = false;
                break;
            }
            
        } while (array_search($lot['data']['id'], array_column($first_position['data'], 'id')));
    } else {
        break;
    }
    
    if ($active) {
        echo "delete_lot\n";
        $delete = delete_lot($config['domen'], $config['number'], $access_token, $lot['data']['id']);
        
        if ($delete['meta']['status'] == "ERROR") {
            break;
        }
        
        echo "sleep\n";
        sleep(3); 
    }
    
}
