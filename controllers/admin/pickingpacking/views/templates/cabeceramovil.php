
<!-- Cabecera para ubicaciones y recepciones. En session funcionalidad tenemos guar dado ubicaciones o recepciones -->

<nav class="navbar bg-body-tertiary jumbotron_<?= $_SESSION["funcionalidad"] ?>">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1 capitalizar span_<?= $_SESSION["funcionalidad"] ?>"><?= $_SESSION["funcionalidad"] ?></span>    
    <div class="dropdown-center">
      <button class="btn btn-primary dropdown-toggle btn_<?= $_SESSION["funcionalidad"] ?>_logout" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
      <span class="caret"></span></button>
      <ul class="dropdown-menu">        
        <li><a class="dropdown-item" href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?cerrar_sesion=1'; ?>">  Cerrar Sesión</a></li>              
      </ul>
    </div>
  </div>
</nav>
