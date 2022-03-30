
<?php

include('header.php');

//25/10/2021 Quitamos la separación de usuarios e invitados ya que ahora obtenemos los usuarios etc diectamente de la base de datos donde no se difreencian
?>
<div class="jumbotron jumbotron_login" style="padding-top:10px;">
    <h1 class="display-4">Hola</h1>
    <p class="lead">Escoge tu usuario y pulsa sobre la función que quieres realizar</p>
    <div class="container" style="margin-bottom:20px;">        
        <form action="../pickpackindex.php" method="post"> 
        <div class="form-group">
            <select class="mdb-select md-form form-control-lg" name="usuario">
            <option value="" disabled selected>Escoge Usuario</option>
            <!-- quitados invitados/usuarios -->
            <?php
            foreach($usuarios as $usuario) {
            ?>
                <option value="<?= $usuario['id_employee'] ?>"><?= $usuario['nombre'] ?></option>
            <?php               
            }  
            ?>    
            
            </select>
            <br><br>
            <button type="submit" name="submit_hacer_picking" class="btn boton_picking">Picking</button>
            <button type="submit" name="submit_hacer_packing" class="btn boton_packing">Packing</button>
        </div>
        </form>      
    </div>  
</div>    
