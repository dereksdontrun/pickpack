<?php

include('header.php');

?>
<!-- Cabecera con info sobre cliente y pedido -->
<div class="jumbotron jumbotron_packing">
    <h1 class="display-4"><span style="font-size: 50%;">PACKING</span> <strong><?= $pedido ?></strong> <span style="font-size: 50%;"><?= $fecha_pedido ?></span>  
        
        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
        <span class="caret"></span></button>
        <ul class="dropdown-menu">
            <li><a href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php'; ?>">  Cerrar Sesión</a></li>              
        </ul>
        
    </h1>
    <h5><span style="font-size: 80%;">Estado Pick Pack actual</span> <strong><?= $estado_pickpack ?></strong></h5>
    <?php if ($empleado_picking && $empleado_picking !== ''){ ?>
        <p class="empleado">Picking realizado por <?= $empleado_picking ?></p>
    <?php } else { ?>
        <p class="empleado">Picking no realizado o empleado desconocido</p>
    <?php }  ?>       
    <div class="row"> 
        <div class="panel datos_cliente col-md-4">
            <address>          
            <strong><?= $nombre_cliente ?></strong><br>
            <?= $direccion ?><br>
            <?= $codigo_postal ?> <?= $ciudad ?> - <?= $provincia ?> - <?= $pais ?><br>
            </address>
            <b>Nº Pedidos:</b>
            <?php if ($numero_pedidos > 4){ ?>
            <span class="badge badge-pill badge-warning"><?= $numero_pedidos ?></span><br>
            <?php } else { 
            $numero_pedidos ?><br>
            <?php } 
            if ($nota_sobre_cliente){ ?>
            <b>Notas sobre cliente:</b>              
            <blockquote class="blockquote padquote nota_cliente">  
            <p><?= $nota_sobre_cliente ?></p>
            </blockquote>
            <?php }  ?>         
        </div> </br>
        <div class="panel datos_cliente col-md-4">
            <b>Pagado:</b> <?= $metodo_pago ?><br>
            <?php if ($amazon){ ?>
            <h3><span class="badge badge-pill badge-info">AMAZON</span></h3>
            <?php } ?>       
            <b>Transporte:</b><br> <h3><span class="badge badge-pill badge-warning"><?= $transporte ?></span></h3>
            <!-- <b>Estado:</b> <= $estado_prestashop ?><br> -->
            <b>Envuelto para Regalo: </b><?php if ($regalo){ ?><h3><span class="badge badge-pill badge-danger"> SI</span></h3> <?php }else{ ?> No<br> <?php } 
            if ($regalo && $mensaje_regalo){ ?>
            <!-- ponemos nl2br() para respetar saltos de línea etc del mensaje -->
            <b>Mensaje regalo:</b> 
                <blockquote class="blockquote padquote nota_regalo">  
                <p><?= nl2br($mensaje_regalo) ?></p>
                </blockquote>
            <?php } ?> 
        </div>
    </div>
</div> <!-- fin jumbotron -->  

<!-- 03/11/2020 mostramos mensaje de warning avisando de que este pedido se encontraba en estado Paquete Enviado al abrir el packing y podría estar duplicado -->
<?php if ($estado_pickpack == 'Paquete Enviado'){ ?>

<div class="container container_mensajes">  
  <div class="panel">    
    <div class="list-group">
      <h3>¡ATENCIÓN!</h3>      
      <div class="list-group-item" style="color:white; background-color:#e34f4f; border:2px solid black;"> 
        <p>Este pedido se encuentra en estado de pickpack "Paquete Enviado" en este momento.</p> 
        <p>Asegúrate de que no está duplicado o tiene otra incidencia y se puede continuar con el packing.</p>       
      </div>      
    </div>
  </div>
</div>
<br>
<?php } ?>
    
<!-- mostramos los mensajes sobre el pedido si los hay. -->
<?php if ($todos_mensajes_pedido){ ?>
<div class="container container_mensajes">  
    <div class="panel">
        <h3>Mensajes de pedido:</h3>
        <div class="list-group">
        <?php foreach ($todos_mensajes_pedido AS $cabecera => $mensaje){ ?>
            <div class="list-group-item">        
                <footer class="blockquote-footer"><?= $cabecera ?></footer>
                <blockquote class="blockquote padquote">
                    <p class="mb-0"><?= $mensaje ?></p>
                </blockquote>
            </div>
        <?php } ?>
        </div>
    </div>
</div>
<?php } ?>

<!-- mostramos los mensajes del cliente sobre las cajas del pedido si los hay. -->
<?php if ($mensajes_cliente_cajas){ ?>
<br>
<div class="container container_mensajes">  
    <div class="panel">
        <h3>Cajas Sorpresa:</h3>
        <div class="list-group">
        <?php foreach ($mensajes_cliente_cajas AS $id_pedido_caja_s => $mensaje_caja){ ?>
            <div class="list-group-item">        
                <h4><?= $id_pedido_caja_s ?></h4>
                <blockquote class="blockquote padquote">
                    <p class="mb-0"><?= $mensaje_caja ?></p>
                </blockquote>
            </div>
        <?php } ?>
        </div>
    </div>
</div>
<?php } ?>

<!-- mostramos los comentarios de picking si los hay. -->
<?php if ($comentario_picking){ ?>
<br>
<div class="container container_comentario">  
    <div class="panel">
    <h3>Comentario de picking:</h3>
    <blockquote class="blockquote padquote">
    <p><?= $comentario_picking ?></p>        
    </blockquote>
    </div>
</div>
<?php } ?>


<!-- necesito saber el número de productos del pedido cuando envíe el formulario, de modo que usamos una variable $numero_productos que se insertará en un input hidden y se recogerá después en procesa Packing para sacar bien los id de producto y su estado al hacer packing. También creamos una variable donde guardar los id de pedido que correspondan a cajas sorpresa, si las hay, cada vez que encontremos un producto cuyo id_order no corresponde al general del pedido del que estamos haciendo el packing. Este array irá a un input hidden que procesaremos en packing.php para hacer update en lafrips_pickpack -->

<?php $numero_productos = count($productos_pedido);
      $ids_cajas = array();
?>

<!-- líneas de productos -->

<div class="container container_productos">  
    <h3>Productos - <?= $numero_productos ?></h3>
    <form id="formulario_packing" action="packing.php" method="post"> 

    <!-- iniciamos la variable donde iremos guardando el id de pedido del producto actual del foreach para comparar  y saber si es de otro y por tanto de una caja sorpresa, y también la variable $estilo, que indicará la clase a añadir a row si es caja. Iniciamos con cajaB porque en la primera comprobación pondrá cajaA -->
    <?php $id_caja_actual = 0;  
            $estilo = 'cajaB';
    ?>

    <?php foreach ($productos_pedido as $producto) {          
        //stocks
        $stock_online = $producto['stock_online'];
        if (!$stock_online){
        $stock_online = 0;
        }
        $stock_tienda = $producto['stock_tienda'];
        if (!$stock_tienda){
        $stock_tienda = 0;
        }
        //imagen, revisamos campo 'existe_imagen', si no contiene id ponemos logo de tienda. 
        if (empty($producto['existe_imagen'])) {
        $imagen = 'https://lafrikileria.com/img/logo_producto_medium_default.jpg';
        } else {
        $imagen = $producto['imagen'];
        }
        ?>
        

    <!-- Para distinguir los productos de las cajas sorpresa, si las hay, vamos a poner a row una clase diferente que cambiara el color de fondo si es producto caja.  Comparamos el id de pedido con el del pedido padre, si no coincide es caja, guardamos el id y lo volvemos a comparar con cada producto, si aparece otro id pedido diferente sería otra caja y ponemos la segunda clase de color base, si hay varias cajas las vamos intercambiando   -->      
    <?php if ($producto['id_order'] == $pedido){  ?>
      <!-- Si el id pedido del producto coincide con el pedido padre, no es de caja, no añadimos clase para color de fondo  -->
    <div class="row">

        <!-- Si el id pedido del producto NO coincide con el pedido padre, es de caja, añadimos clase para color de fondo, pero primero comparamos el id de pedido del producto con id_caja_actual, si no coincide hay que cambiar la variable estilo ,si es una , cajaA, ponemos la otra, cajaB, y viceversa, y después guardamos el id en la variable caja_actual -->
    <?php } elseif ($producto['id_order'] != $pedido) {  
            if ($producto['id_order'] != $id_caja_actual) { //la priemra vez será diferente, al ser 0 el valor inicial de id_caja_actual
                $id_caja_actual = $producto['id_order'];
                //cambiamos el estilo, si hay uno ponemos el otro
                if ($estilo == 'cajaB') {
                    $estilo = 'cajaA';
                } else {
                    $estilo = 'cajaB';
                }
            } ?>
    <div class="row <?= $estilo ?>">        
        
    <?php }?>

        <!-- mostramos imagen, tamaño de imagen fijado, con un 25% menos del tamaño de home_default -->
        <div class="col-sm-5 col-xs-4">            
            <img src="<?= $imagen ?>"  width="218" height="289"/>    
        </div>
     

        <!-- datos del producto --> 
        
        <div class="col-sm-5 col-xs-6">
            <p class="h5"><?= $producto['nombre_completo'] ?></p>
            REF: <?= $producto['referencia_producto'] ?><br/>
            <!-- Si no tiene ean no lo ponemos -->
            <?php if ($producto['ean'] && $producto['ean'] !== ''){ ?>
            <span>EAN: <?= $producto['ean'] ?></span><br/>
            <?php } ?>
            <!-- Si no tiene localización no la ponemos -->
            <?php if ($producto['localizacion'] && $producto['localizacion'] !== ''){  ?>
            <span style="font-size:17px; font-weight:bold;"><?= $producto['localizacion'] ?></span><br/>
            <?php } ?>
            <!-- Si no tiene localización de repo no la  ponemos -->
            <?php if ($producto['localizacion_repo'] && $producto['localizacion_repo'] !== ''){  ?>
            <span style="font-size:14px;">Repo: <?= $producto['localizacion_repo'] ?></span><br/>
            <?php } ?>
            <!-- Stock en ambos almacenes -->
            <span style="font-size:14px;">Stock: Online ( <?= $stock_online ?> ) - Tienda ( <?= $stock_tienda ?> )</span>  
            <!-- 23/12/2020 Si el producto es carta hogwarts queremos mostrar el o los nombres a poner en la carta para que se aseguren. El id de producto será 19578, sacamos el campo customizable_data, que contiene un concat con el o los nombres -->
            <?php if ($producto['id_producto'] == 19578){ ?>
            <br><br>
            <span style="font-size:14px;">Nombre/s carta personalizada:</span><br/> 
            <div class="list-group-item" style="background-color:#ffcca9; border:1px solid black; border-radius: 10px;">
                <p><span style="font-size:16px;"><?= $producto['customizable_data'] ?></span></p>
            </div> 
            <?php } ?>
            <!-- Si el producto es de una caja sorpresa, su id_order no coincidirá con $pedido, que es el pedido base, mostramos mensaje e imagen de caja sopresa, y guardaremos el id_order en array -->
            <?php if ($producto['id_order'] != $pedido){  
                $ids_cajas[] = $producto['id_order'];
            ?>
            <br><br>
            <div class="row">
                <div class="col-sm-4 col-xs-5">
                    <img src="https://lafrikileria.com/img/cajasorpresa.jpg"  width="80" height="80"/>
                </div>
                <div class="col-sm-5 col-xs-6">
                    <br>
                    <span style="font-size:25px; font-weight:bold; color:red;">   <?= $producto['id_order'] ?></span>
                </div>  
            </div>
            <?php } ?>        
        </div>
        
        <!-- Unidades de producto en pedido, si son más de una se señala -->
        <div class="col-sm-1 col-xs-1">
        <h4 class="alert-heading">Ud:</h4>
        <?php if ($producto['cantidad'] != 1) {  ?>        
            <div class="alert alert-danger">          
                <p class="h2"><?= $producto['cantidad'] ?></p>
            </div> 
            <?php }else{ ?>
            <div class="alert alert-light">          
                <?= $producto['cantidad'] ?>
            </div>
            <?php }  ?>  
        </div>
        
        <!-- botones de ok para cada producto -->
      <div class="col-sm-1 col-xs-1" style="padding-top:5%;">
        <div class="btn-group" data-toggle="buttons">            
          <label class="btn btn-success active">
            <input type="radio" name="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" id="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" value="1" autocomplete="off" required>
            <span class="fa fa-check"></span>
          </label>		

          <label class="btn btn-danger">
          <input type="radio" name="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" id="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" value="0" autocomplete="off" >
            <span class="fa fa-times"></span>
          </label>          
        </div>     
      </div>
    </div> <!-- Fin row de producto-->
    <hr>


<?php  } //fin foreach producto  ?>

<!-- Si el cliente ha pedido envuelto para regalo -->
<?php if ($regalo){ ?>    
    <div class="row">
        <div class="form-group form-check" style="padding-left:30%;">
            <label class="btn btn-lg btn-warning">Envuelto regalo / nota: 
                <input type="checkbox" name="checkbox_regalo" id="checkbox_regalo" required>
            </label>
        </div>
    </div>
    <hr>      
<?php } ?>   

<!-- Si el cliente lleva 5 o más pedidos ponemos un check para marcar si se coge el regalo, salvo que el pedido vaya en vuelto para regalo, en cuyo caso suponemos que no se le envía al comprador y no queremos meter obsequio  -->
<?php if (($numero_pedidos > 4) && (!$regalo)){ ?>       
<div class="row">
    <div class="form-group form-check" style="padding-left:30%;">
      <label class="btn btn-lg btn-warning">Obsequio: 
        <input type="checkbox" name="obsequio" id="obsequio" required>
      </label>
    </div>
</div>
<hr>      
<?php } ?>

<!-- ponemos un textarea para comentarios sobre el packing -->
<div class="row"> 
    <div class="form-group col-sm-12  col-xs-12">
      <label for="comentario">Comentarios:</label>
      <textarea class="form-control" rows="5" id="comentario" name="comentario"></textarea>
    </div>
  </div>
</div> <!--div final de container -->

  <!-- coloco el número de productos en un input hidden para el proceso del Packing -->
  <input type="hidden" name="numero_productos" value="<?= $numero_productos ?>">
  <!-- coloco el número de pedido en un input hidden para el proceso del Packing -->
  <input type="hidden" name="id_pedido" value="<?= $pedido ?>">
  <!-- coloco los ids de pedido que correspondan a pedidos de caja sorpresa, si los hay, en un input hidden para el proceso de Picking. Primero hacemos array_unique del array donde se han guardado, y para enviar el array por post hay que serializarlo aquí y unserializarlo en el destino, usamos comillas simples porque serialize añade comillas dobles -->
  <?php  $ids_cajas = array_unique($ids_cajas);  ?>
  <input type="hidden" name="ids_cajas" value='<?= serialize($ids_cajas) ?>'>

<!-- Botón de submit para el formulario. formnovalidate en el botón de cancelar permite hacer submit sin marcar los required etc -->
<div class="row" style="padding-left:30%; margin-bottom:30px;"> 
    <input class="btn btn-lg btn-success submit_boton_packing" type="submit" id="submit_finpacking" name="submit_finpacking" value="Finalizar" />
    <button type="submit" name="submit_volver" class="btn btn-danger submit_boton_packing"  formnovalidate  style="margin-left:10px;">Cancelar</button>
    </div>
</form>

</body>
</html>