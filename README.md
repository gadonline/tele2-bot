Бот предназначен для продажи минут в маркете tele2, устанавливается на модемы Huawei E8372.

# Порядок установки бота

Прошиваем свежекупленный модем:

```
https://yadi.sk/d/Ty7XhwrrkmlRtQ
```

Через веб интерфейс включаем telnet и узнаем IMSI:

```
http://192.168.8.1
```

Подключаемся к telnet:

```
#login: root
#password: IMSI value
telnet 192.168.8.1
```

Ставим entware с зависимостями:

```
entware install
entware shell
/opt/bin/opkg update
/opt/bin/opkg install cron curl php7-cli php7-mod-curl php7-mod-json php7-mod-simplexml
exit
```

Устанавливаем tele2-bot:

```
/opt/bin/curl https://codeload.github.com/gadonline/tele2-bot/zip/master | /opt/bin/unzip -o -d /opt/ -
/opt/bin/chmod +x /opt/tele2-bot-master/sntp-setting
```

Дополняем cron задачами:

```
echo '@reboot root /opt/tele2-bot-master/sntp-setting' >> /opt/etc/crontab
echo '33 23,0,1,2,3 * * * root /opt/bin/php-cli /opt/tele2-bot-master/index.php -h 192.168.8.1 -u admin -p PaSSword -n 70000000000 -d ivanovo.tele2.ru > /mnt/obb/tele2-bot.log 2> /mnt/obb/error-tele2-bot.log' >> /opt/etc/crontab
```

Разрешаем выполнение init скриптов:

```
echo > /data/userdata/entware_autorun
```

Перезапускам систему:

```
reboot
```
