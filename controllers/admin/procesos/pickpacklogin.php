<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/

// https://lafrikileria.com/test/modules/pickpack/controllers/admin/procesos/pickpacklogin.php

//En AdminPicking.php apuntamos a este archivo fuera de Prestashop, se carga la cabecera html y despendiendo de como llega la llamada, GET o POST y si contiene alguna variable lo orientamos a una función u otra, haciendo todo el proceso de picking en este script


?>
<!DOCTYPE html>
<html>
<title>PEDIDOS - La Frikilería</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
  <link href='../../../views/css/back.css' rel="stylesheet">  
  <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <script type="text/javascript" src="../../../views/js/back.js"></script>
</head>
<body>


<?php

require_once(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');
//hacemos include de la clase para poder usar la función de buscar id_pickpack por id_order
include dirname(__FILE__).'/../../../classes/PickPackOrder.php';

//si entramos después de pulsar un submit direccionamos al proceso asignado al submit, picking o packing, si no, mostramos login
if(isset($_POST['submit_hacer_picking'])){
  $id_usuario = $_POST['usuario'];
  echo $id_usuario;
  $token = Tools::getAdminTokenLite('AdminModules');
  $url_modulos = _MODULE_DIR_;

  $url = $url_modulos.'pickpack/controllers/admin/procesos/picking.php?token='.$token.'&id_empleado='.$id_usuario;  
  //echo '<br>'.$url;
  header("Location: $url");


} elseif(isset($_POST['submit_hacer_packing'])){
  $id_usuario = $_POST['usuario'];
  echo $id_usuario;

  $token = Tools::getAdminTokenLite('AdminModules');
  $url_modulos = _MODULE_DIR_;

  $url = $url_modulos.'pickpack/controllers/admin/procesos/packing.php?token='.$token.'&id_empleado='.$id_usuario;  
  //echo '<br>'.$url;
  header("Location: $url");
} else {

  //creamos el array con los usuarios, pondremos nombre de empleado y como value el id_employee de Prestashop para poder guardar lo que hacen. Creamos 4 empleados auxiliares. Añadimos otro para usuarios invitados (becarios o lo que sea, que no tengan un usuario)
  $usuarios = array(
    'Alberto Álvarez' => '1',
    'Ana Resa' => '30', 
    'Ana Mateo' => '47', 
    'Andrea Alfaro' => '39', 
    'Beatriz Álvarez' => '5', 
    'Idoia Casalé' => '15', 
    'Lorena Ubierna' => '4', 
    'Nacho Martínez' => '17', 
    'Octavio Mariñán' => '18', 
    'Paula Marín' => '33', 
    'Sara El Bacali' => '38', 
    'Sergio Ortiz' => '22'
  );

  $invitados = array(  
    'Ellen Ripley' => '48',
    'Jason Voorhees' => '49',  
    'Michael Myers' => '50',
    'Sarah Connor' => '51'
  );

  //el usuario debe escoger un perfil de un SELECT, una vez escogido elegirá si hacer picking o packing. 
  echo '
      <div class="jumbotron jumbotron_login" style="padding-top:10px;">
        <h1 class="display-4">Hola</h1>
        <p class="lead">Escoge tu usuario y pulsa sobre la función que quieres realizar</p>
        <div class="container" style="margin-bottom:20px;">        
          <form action="pickpacklogin.php" method="post"> 
            <div class="form-group">
              <select class="mdb-select md-form form-control-lg" name="usuario">
                <option value="" disabled selected>Escoge Usuario</option>
                <optgroup label="Usuarios">';
                foreach($usuarios as $nombre => $id) {
                  echo '<option value="'.$id.'">'.$nombre.'</option>';                
                }                
                echo '</optgroup>
                <optgroup label="Invitados">';
                foreach($invitados as $nombre => $id) {
                  echo '<option value="'.$id.'">'.$nombre.'</option>';                
                } 
                echo'</optgroup>
              </select>
              <br><br>
              <button type="submit" name="submit_hacer_picking" class="btn boton_picking">Picking</button>
              <button type="submit" name="submit_hacer_packing" class="btn boton_packing">Packing</button>
            </div>
          </form>      
        </div>  
      </div>    
      ';
}


    

?>

</body>
</html>
  