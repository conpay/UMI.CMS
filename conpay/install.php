<?php
$INFO = array();
$INFO['name'] = "conpay";
$INFO['title'] = "Conpay";
$INFO['description'] = "Conpay";
$INFO['filename'] = "modules/conpay/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_conpay";
$INFO['default_method'] = "proxy";
$INFO['default_method_admin'] = "config";

$INFO['func_perms/proxy'] = "Покупка в кредит";
$INFO['func_perms/config'] = "Конфигурирование модуля conpay";

$SQL_INSTALL = array();
$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/seo/__admin.php";
$COMPONENTS[1] = "./classes/modules/seo/class.php";
$COMPONENTS[2] = "./classes/modules/seo/i18n.en.php";
$COMPONENTS[3] = "./classes/modules/seo/i18n.php";
$COMPONENTS[4] = "./classes/modules/seo/lang.php";
$COMPONENTS[5] = "./classes/modules/seo/permissions.php";