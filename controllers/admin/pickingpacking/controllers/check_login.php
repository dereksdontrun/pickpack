<?php

require_once(dirname(__FILE__).'/../../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../../init.php');

$cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));

if (empty($cookie->id_employee)) {
  echo 'ATENCIÓN: DEBES HACER LOGIN EN LAFRIKILERIA.COM';

  exit;    
}