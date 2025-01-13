<?php
//creamos el array con los usuarios, pondremos nombre de empleado y como value el id_employee de Prestashop para poder guardar lo que hacen. Creamos 4 empleados auxiliares. Añadimos otro para usuarios invitados (becarios o lo que sea, que no tengan un usuario)
//04/11/2020 Añado varios usuarios para becarios etc, les pongo id inventado, no corresponde a usuarios de Prestashop ya que no les voy a crear cuentas.

require_once(dirname(__FILE__).'/../../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../../init.php');

/*
$usuarios = array(
    'Alberto Álvarez' => '1',    
    'Ana Mateo' => '47', 
    'Andrea Alfaro' => '39', 
    'Beatriz Álvarez' => '5', 
    'Cristina Iñiguez' => '53', 
    //'Idoia Casalé' => '15', 
    'Israel Alcantud' => '54', 
    'Lorena Ubierna' => '4', 
    'Nacho Martínez' => '17', 
    'Octavio Mariñán' => '18', 
    'Paula Marín' => '33', 
    'Sara El Bacali' => '38', 
    'Sergio Ortiz' => '22'
  );

  $invitados = array( 
    'Alex B' => '65',
    'Biemil B' => '66',
    'Daniel B' => '55',
    'Estefanía B' => '69',
    'Farhan B' => '63',
    'Ghulam B' => '64',
    'Iqra B' => '67',
    'Josemi B' => '68',
    'Mohamed B' => '70',
    'Teresa B' => '60', 
    'Ellen Ripley' => '48',
    'Jason Voorhees' => '49',  
    'Michael Myers' => '50',
    'Sarah Connor' => '51'
  );
*/
  //25/10/2021 Cambiamos la forma de obtener los usuarios, en lugar de crear el array vamos a sacar los empleados de Prestashop y meterlos al array, de modo que no hace falta entrar aquí cada vez que se pone una nueva persona. Quitamos la separación de invitados etc.
//sacamos el id_employee nombre y apellidos de la tabla employees, solo activos, y del perfil Superadmin, vendedor o gestion. Dejamos el array ordenado alfabeticamente por nombre
//30/03/2023 para asegurarme de que no utilicen mi usuario voy a sacar si soy yo el que usa un navegador comprobando si existe la cookie de la Frikileria y si soy el usuario
//13/01/2025 Vamos a utilizar la cookie para saber si además el usuario ha hecho login en Prestashop, si no es así no continuamos
//sacamos la cookie. Con ella sabremos si es un usuario logado y después generamos el token para dicho empleado y adminproducts
$cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
$excluir = '';
  if (empty($cookie->id_employee)) {
    echo 'ATENCIÓN: DEBES HACER LOGIN EN LAFRIKILERIA.COM';

    exit;    
  }

  if ($cookie->id_employee != 22) {
    //si no tiene id 22 de empleado, lo quitamso del select
    $excluir = ',22';
  }

 $sql_usuarios = 'SELECT id_employee, CONCAT(firstname," ",lastname) AS nombre 
 FROM lafrips_employee 
 WHERE active = 1 
 AND id_profile IN (1,4,5)
 AND id_employee NOT IN (44,8,2,81'.$excluir.')
 ORDER BY firstname';
 $usuarios = Db::getInstance()->ExecuteS($sql_usuarios);

 

  //Llamada a la vista
  require_once("../views/templates/loginview.php");

?>
