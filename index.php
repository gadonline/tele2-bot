<?php
require_once(dirname(__FILE__) . "/modem.class.php");
require_once(dirname(__FILE__) . "/library.php");

$config = parse_ini_file(dirname(__FILE__) . "/.config.ini");
$modem  = new Manoaratefy\NetworkTools\Modem($config['host'], $config['user'], $config['password']);

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
    echo "$access_token\n";
} else {
    echo "Ошибка получения access_token\n";
    exit();
}

$lot = set_lot($config['domen'], $config['number'], $access_token);
$first_position = get_first_position($config['domen'], $config['number'], $access_token);
