{*
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

  
  {*Panel con información del pedido y el cliente *}
  <div class="panel clearfix">
    <h3>Hola {Context::getContext()->employee->firstname} - Información del pedido <span style="font-size: 150%;"><strong><a href="{$url_pedido}" target="_blank" title="Ver Pedido" class="link_order">{$idpedido}</a></strong></span> del {$fecha_pedido} {if $seguimiento}<span style="font-size: 150%;"><strong><a href="{$url_tracking}" target="_blank" title="Ver Seguimiento" class="link_order"> Tracking</a></strong></span>{/if} {if $caja_sorpresa}<span class="badge badge-pill badge-warning">Contiene Caja Sorpresa</span>{/if}</h3>

    {* Para mostrar el estado buscamos en el array de pickpack_estados la posición de estado pickpack menos 1, ya que el array empieza desde 0, y sacamos el nombre y la clase para el badge de bootstrap, que hemos creado los colores en el css back.css. El nombre lo sacamos uniendo el nombre de estado y pasando a minúsculas *}
    {$estado_pickpack = $pickpack_estados[($objeto_pick_pack->id_estado_order - 1)]['nombre_estado'] }
    {$badge_class = 'badge-'|cat:$estado_pickpack|replace:' ':''|lower } {* unimos 'badge-' al nombre de estado sustituyendo espacio por nada y pasando a minúsculas *}
    
    <p>Estado actual PickPack  <span class="badge badge-pill {$badge_class}">{$estado_pickpack}</span>
        {* Si el cambio de estado tiene exito redirigimos al tpl desde el cotrolador con una variable get en la url llamada success, si no, mostramos error *}
        {if $smarty.get.success == 1}     
          <span class="alert alert-success"  style="margin-left: 50px;" role="alert">
            Estado cambiado correctamente
          </span>          
        {/if}
        {if $smarty.get.success == 2}     
          <span class="alert alert-danger"  style="margin-left: 50px;" role="alert">
            El pedido ya se encuentra en ese estado
          </span>          
        {/if}
    </p><br>
    <div class="container">  
      <div class="row"> 
        <div class="panel col-md-4"> 
          {* Información del cliente *}
          <div class="panel"> 
            <h3>Cliente:</h3>
            <address>          
              <strong>{$nombre_cliente}</strong><br>
              {$direccion}<br>
              {if $codigo_postal}{$codigo_postal}{/if}  {if $ciudad}{$ciudad}{/if} - {if $provincia}{$provincia}{/if} - {if $pais}{$pais}{/if}<br>
              {$telefono}
            </address>
            <b>Nº Pedidos:</b> {if $numero_pedidos > 4}<span class="badge badge-pill badge-warning">{$numero_pedidos}</span>{else}{$numero_pedidos}{/if}<br> 
            {if $nota_sobre_cliente}<b>Notas:</b>              
              <blockquote>  
                <p>{$nota_sobre_cliente}</p>
              </blockquote>
            {/if}            
          </div>
          {* Información sobre el pedido *}
          <div class="panel">
            <h3>Info:</h3>
            {if $amazon}
            <span class="badge badge-pill badge-info">Amazon</span><br>
            <b>ID Amazon:</b> {$amazon_id}<br>
            <b>Marketplace:</b> {$amazon_marketplace}<br>
            {/if}           
            <b>Pagado:</b> {$metodo_pago}<br>
            <b>Transporte:</b><br> <span class="badge badge-pill badge-info">{$transporte}</span><br>
            <b>Estado:</b> {$estado_prestashop} <i>{$fecha_estado_prestashop|date_format:"%d-%m-%Y %H:%M:%S"}</i><br> 
            {if $pedido_webservice}<span class="badge badge-pill badge-warning">Pedido WebService</span><br>{/if}           
            {if $pedido_dropshipping}<span class="badge badge-pill badge-warning">Contiene Dropshipping</span><br>{/if}
            <b>Envuelto para Regalo:</b> {if $regalo}<span class="badge badge-pill badge-danger">SI</span>{else}No{/if}<br> 
            {if $regalo}<b>Mensaje regalo:</b> 
              <blockquote>  
                <p>{nl2br($mensaje_regalo)}</p> {* usamos nl2br() para mostrar saltos de línea etc del mensaje *}
              </blockquote>
            {/if}  
          </div>    
        </div>
        {* Panel con los posibles mensajes del pedido, peticiones del cliente y comentarios de empleados *}
        <div class="panel col-md-8">
          <h3>Mensajes de pedido:</h3>
          {if !$todos_mensajes_pedido}
            <h4><b>SIN MENSAJES</b></h4>
          {else}
            <div class="list-group">
              {foreach from=$todos_mensajes_pedido key=cabecera item=mensaje }
                <div class="list-group-item">        
                  <h4 class="list-group-item-heading">{$cabecera}</h4>
                  <blockquote><p class="list-group-item-text">{$mensaje}</p></blockquote>
                </div>
              {/foreach}
            </div>
          {/if}    
        </div>
      </div>
      
    </div>
  </div>

  {*Panel con información sobre cajas sorpresa, si las hay *}
  {if $mensajes_cliente_cajas}
  <div class="panel clearfix">
    <h3>Cajas Sorpresa</h3>            
    <div class="panel col-md-8">      
        <div class="list-group">
          {foreach from=$mensajes_cliente_cajas key=cabecera item=mensaje }
            <div class="list-group-item">        
              <h4 class="list-group-item-heading">{$cabecera}</h4>
              <blockquote><p class="list-group-item-text">{$mensaje}</p></blockquote>
            </div>
          {/foreach}
        </div>         
    </div>      
  </div>
  {/if} 

  {* panel con el formulario para cambiar de estado el pedido pickpack *}
  {* $smarty.get.token también recoge el token del controlador sin necesidad de tarerlo como variable *}
  {* <form action="{$url_base}index.php?controller=AdminPacking&token={$smarty.get.token}" method="post"> *}
  <div class="panel clearfix">
    <h3>Cambiar Estado Pickpack</h3>
    <form action="{$url_base}index.php?controller=AdminGestionPickpack&token={$token}" method="post">
      <div class="form-row align-items-center">
        <div class="col-lg-2 col-md-2 col-sm-2">        
          <select class="form-control form-control-lg" id="nuevo_estado_pickpack" name="nuevo_estado_pickpack">
            <option value="0" selected>Cambiar Estado</option>
            {* Sacamos los estados del array $pickpack_estados, poniendo la clase de badge para el color de fondo *}
            {foreach $pickpack_estados as $estado}
              {* No queremos que se pueda cambiar a No comenzado, ni a picking abierto ni a packing abierto ,luego se debe saltar los estados *}
              {if ($estado['nombre_estado'] != 'No Comenzado') && ($estado['nombre_estado'] != 'Picking Abierto') && ($estado['nombre_estado'] != 'Packing Abierto')}
              <option value="{$estado['id_pickpack_estados']}" class="badge-{$estado['nombre_estado']|replace:' ':''|lower}">{$estado['nombre_estado']}</option>
              {/if}
            {/foreach} 
          </select>
        </div>  
        <input type="hidden" name="id_pedido" value="{$idpedido}">    
        <input class="btn btn-success" type="submit" id="cambia_estado_pickpack" name="cambia_estado_pickpack" value="Cambiar" />  
          {* Si el cambio de estado tiene exito redirigimos aquí desde el cotrolador con una variable get en la url llamada success, si no, mostramos error
           *}
        {if $smarty.get.success == 1}          
          <br><br>
          <div class="alert alert-success col-lg-3 col-md-3 col-sm-3" role="alert">
            Estado cambiado correctamente
          </div>          
        {/if}
        {if $smarty.get.success == 2}          
          <br><br>
          <div class="alert alert-danger col-lg-3 col-md-3 col-sm-3" role="alert">
            El pedido ya se encuentra en ese estado
          </div>          
        {/if}
      </div>
    </form>
  </div>

  {* Panel con info de picking *}
  <div class="panel clearfix">
    <h3>Picking</h3>
    {if $objeto_pick_pack->nombre_employee_picking}
      <p>Picking realizado por <strong>{$objeto_pick_pack->nombre_employee_picking}</strong></p>
    {else} 
      <p>Picking NO realizado</p>
    {/if}
    <hr>
    {* Horarios *}
    {if $objeto_pick_pack->date_inicio_picking != "0000-00-00 00:00:00"}
      <p>Comenzado <strong>{$objeto_pick_pack->date_inicio_picking|date_format:"%d-%m-%Y %H:%M:%S"}</strong></p>
    {else} 
      <p>Picking NO comenzado</p>
    {/if}
    {if $objeto_pick_pack->date_fin_picking != '0000-00-00 00:00:00'}
      <p>Finalizado <strong>{$objeto_pick_pack->date_fin_picking|date_format:"%d-%m-%Y %H:%M:%S"}</strong></p>
      {* Tiempo que ha llevado el picking *}
      {$date_diff_secs = strtotime($objeto_pick_pack->date_fin_picking) - strtotime($objeto_pick_pack->date_inicio_picking)}
      {$days = floor(($date_diff_secs / 3600) / 24)}
      {$hours = floor(($date_diff_secs / 3600) % 24)}
      {$minutes = floor(($date_diff_secs / 60) % 60)}
      {$seconds = ($date_diff_secs % 60)}
      <p>Duración: <strong>{if $days}{$days} dias {/if}{if $hours}{$hours} horas {/if}{if $minutes}{$minutes} minutos {/if}{$seconds} segundos</strong></p>
    {else} 
      <p>Picking NO finalizado</p>
    {/if}
     
     {* Mensaje si el picking tuvo incidencia  *}
    {if $objeto_pick_pack->incidencia_picking}
      <hr>
      <span class="badge badge-pill badge-warning">Picking con Incidencia</span>
      <br>      
    {/if} 

    {*Mensajes desde picking si lo hay *}
    {if $objeto_pick_pack->comentario_picking}
    <br><br>
    <h3>Comentarios</h3>
    <blockquote><!-- |nl2br interpreta \n como salto de línea -->
      <p>{$objeto_pick_pack->comentario_picking|nl2br}</p>
      {* <footer>{$objeto_pick_pack->nombre_employee_picking}</footer> *}
    </blockquote>
    {/if}
  </div>

  
  {* Panel con info de packing *}
  <div class="panel clearfix">
    <h3>Packing</h3>
    {if $objeto_pick_pack->nombre_employee_packing}
      <p>Packing realizado por <strong>{$objeto_pick_pack->nombre_employee_packing}</strong></p>
    {else} 
      <p>Packing NO realizado</p>
    {/if}
    <hr>
    {* Horarios *}
    {if $objeto_pick_pack->date_inicio_packing != "0000-00-00 00:00:00"}
      <p>Comenzado <strong>{$objeto_pick_pack->date_inicio_packing|date_format:"%d-%m-%Y %H:%M:%S"}</strong></p>
    {else} 
      <p>Packing NO comenzado</p>
    {/if}
    {if $objeto_pick_pack->date_fin_packing != '0000-00-00 00:00:00'}
      <p>Finalizado <strong>{$objeto_pick_pack->date_fin_packing|date_format:"%d-%m-%Y %H:%M:%S"}</strong></p>
      {* Tiempo que ha llevado el packing *}
      {$date_diff_secs = strtotime($objeto_pick_pack->date_fin_packing) - strtotime($objeto_pick_pack->date_inicio_packing)}
      {$days = floor(($date_diff_secs / 3600) / 24)}
      {$hours = floor(($date_diff_secs / 3600) % 24)}
      {$minutes = floor(($date_diff_secs / 60) % 60)}
      {$seconds = ($date_diff_secs % 60)}
      <p>Duración: <strong>{if $days}{$days} dias {/if}{if $hours}{$hours} horas {/if}{if $minutes}{$minutes} minutos {/if}{$seconds} segundos</strong></p>
    {else} 
      <p>Packing NO finalizado</p>
    {/if}
     
     {* Mensaje si el packing tuvo incidencia  *}
    {if $objeto_pick_pack->incidencia_packing}
      <hr>
      <span class="badge badge-pill badge-warning">Packing con Incidencia</span> 
      <br>     
    {/if} 

    {*Mensajes desde packing si lo hay *}
    {if $objeto_pick_pack->comentario_packing}
    <br><br>
    <h3>Comentarios</h3>
    <blockquote><!-- |nl2br interpreta \n como salto de línea -->
      <p>{$objeto_pick_pack->comentario_packing|nl2br}</p>
      {* <footer>{$objeto_pick_pack->nombre_employee_picking}</footer> *}
    </blockquote>
    {/if}
  </div>
  
  {*Panel con lista de productos del pedido *}
  <div class="panel clearfix">
    <h3>Productos - {$productos_pedido|@count} </h3>
    {* Sacamos una línea por cada producto del carrito *}
    {foreach from=$productos_pedido item=producto}
      <div class="row">
        {* Imagen de producto *}
        <div class="col-lg-3 col-md-4 col-sm-5">
          <img 
            {if !$producto['existe_imagen'] }
              src="https://lafrikileria.com/img/logo_producto_medium_default.jpg"
            {else}
              src="{$producto['imagen']}"
            {/if}  
          />    
        </div>
        {* Información sobre el producto *}
        <div class="col-lg-3 col-md-3 col-sm-4" style="padding-top:2%;">
          <div class="list-group">
            <div class="list-group-item"> 
              <h4 class="list-group-item-heading">Nombre</h4>
              <p class="list-group-item-text h3">{$producto['nombre_completo']}</p>
            </div>
            <div class="list-group-item"> 
              <h4 class="list-group-item-heading">Referencia</h4>
              <p class="list-group-item-text">{$producto['referencia_producto']}</p>
            </div>
            <!-- Si no tiene ean no lo ponemos -->
            {if $producto['ean'] && $producto['ean'] !== ''}
              <div class="list-group-item"> 
                <h4 class="list-group-item-heading">Ean</h4>
                <p class="list-group-item-text">{$producto['ean']}</p>
              </div>
            {/if}  
            <!-- Si no tiene localización no la  ponemos -->
            {if $producto['localizacion'] && $producto['localizacion'] !== ''}          
            <div class="list-group-item"> 
              <h4 class="list-group-item-heading">Localización</h4>
              <p class="list-group-item-text h3">{$producto['localizacion']}</p>
            </div>
            {/if}
            <!-- Si no tiene localización de repo no la  ponemos -->
            {if $producto['localizacion_reposicion'] && $producto['localizacion_reposicion'] !== ''}
              <div class="list-group-item">               
                <h4 class="list-group-item-heading">Reposición</h4>
                <p class="list-group-item-text">{$producto['localizacion_reposicion']}</p>
              </div>
            {/if}            
            <div class="list-group-item"> 
              <h4 class="list-group-item-heading">Stock</h4>
              <p class="list-group-item-text">
                <span style="font-size:12px;">Online ( {if !$producto['stock_online']}0{else}{$producto['stock_online']}{/if} ) - Tienda ( {if !$producto['stock_tienda']}0{else}{$producto['stock_tienda']}{/if} )</span>
                {* metemos un mensaje indicando que el producto es dropshipping si lo es *}
                {if $producto['dropshipping']}<span style="font-size: 150%;" class="badge badge-pill badge-warning">Dropshipping</span>{/if}
              </p> 
            </div>    
          </div>
        </div>
        {* Precio del producto que ha pagado el cliente *}
        <div class="col-lg-1 col-md-1 col-sm-1" style="padding-top:10%;">
          <div class="alert alert-light">
            <h4 class="alert-heading">PVP:</h4>
            <p>{$producto['precio_producto']|string_format:"%.2f"} €</p>
          </div>      
        </div>
        {* Unidades compradas, se resalta si es más de una *}
        <div class="col-lg-1 col-md-1 col-sm-2" style="padding-top:10%;">
          {if $producto['cantidad'] != 1}
          <div class="alert alert-danger">
            <h4 class="alert-heading">Ud:</h4>
            <p class="h2">{$producto['cantidad']}</p>
          </div> 
          {else} 
          <div class="alert alert-light">
            <h4 class="alert-heading">Ud:</h4>
            {$producto['cantidad']}
          </div>
          {/if}    
        </div>
        {* Estado de producto en Picking, sin procesar, correcto o incidencia, o correcto pero tuvo incidencia *}
        <div class="col-lg-2 col-md-2 col-sm-2" style="padding-top:10%;">
          {if $producto['ok_picking'] && !$producto['incidencia_picking']}
            <div class="alert alert-success" role="alert">
              Picking Correcto
            </div>
          {elseif $producto['ok_picking'] && $producto['incidencia_picking']}
            <div class="alert alert-warning" role="alert">
              Picking Correcto<br>Tuvo Incidencia
            </div>
          {elseif !$producto['ok_picking'] && $producto['incidencia_picking']}
            <div class="alert alert-danger" role="alert">
              Incidencia Picking
            </div>
          {elseif !$producto['ok_picking'] && !$producto['incidencia_picking']}
            <div class="alert alert-info" role="alert">
              Sin Procesar
            </div>
          {/if}
        </div>
        {* Estado de producto en Packing, sin procesar, correcto o incidencia, o correcto pero tuvo incidencia *}
        <div class="col-lg-2 col-md-2 col-sm-2" style="padding-top:10%;">
          {if $producto['ok_packing'] && !$producto['incidencia_packing']}
            <div class="alert alert-success" role="alert">
              Packing Correcto
            </div>
          {elseif $producto['ok_packing'] && $producto['incidencia_packing']}
            <div class="alert alert-warning" role="alert">
              Packing Correcto<br>Tuvo Incidencia
            </div>
          {elseif !$producto['ok_packing'] && $producto['incidencia_packing']}
            <div class="alert alert-danger" role="alert">
              Incidencia Packing
            </div>
          {elseif !$producto['ok_packing'] && !$producto['incidencia_packing']}
            <div class="alert alert-info" role="alert">
              Sin Procesar
            </div>
          {/if}
        </div>

        {* Radio buttons para indicar si está correcto o no el producto *} 
        {* <div class="col-lg-2 col-md-2 col-sm-2" style="padding-top:10%;">
          <div class="btn-group" data-toggle="buttons">
            <div class="form-group form-check">
              <label class="btn btn-lg btn-success active">                
                <input type="radio" name="{$producto['id_producto']}_{$producto['id_atributo']}" value="1"  autocomplete="off" required>                
              </label>	
            </div>	
            <div class="form-group form-check">
              <label class="btn btn-lg btn-danger">                
                <input type="radio" name="{$producto['id_producto']}_{$producto['id_atributo']}"  autocomplete="off" value="0">                
              </label>
            </div>
          </div>     
        </div> *}
      </div>
      <hr>      
    {/foreach}    
  </div>

  {* panel con las opciones de si ha envuelto para regalo y si se le envía un obsequio por número de pedidos del cliente *}
  {if $regalo || $numero_pedidos > 4}
  <div class="panel clearfix">
    <h3>Extras</h3>
    {* Opción envolver para regalo *}
    {if $regalo}
    <div class="row">
      <div class="col"  style="padding-left:30%;">
        <div class="alert alert-success col-lg-2 col-md-2 col-sm-2" role="alert">
          Envuelto para regalo
        </div>
      </div>
    </div>
    {/if}
    {* Mostramos línea separadora si existen ambas opciones *}
    {if $regalo && $numero_pedidos > 4}
      <hr>
    {/if}
    {* Si el número de pedidos del cliente es mayor de 4 se le envía un obsequio *}
    {if $numero_pedidos > 4}
    <div class="row">
      <div class="col" style="padding-left:30%;">        
        <div class="alert alert-success col-lg-2 col-md-2 col-sm-2" role="alert">
          Obsequio por compras
        </div>        
      </div>
    </div>
    {/if}
  </div>
  {/if}
  {* panel con textarea para dejar comentarios sobre el packing *}
  {* <div class="panel clearfix">
    <h3>Comentarios</h3>
    <div class="row">      
      <div class="form-group col">        
        <textarea class="form-control" rows="5" id="comentario_packing" name="comentario_packing" ></textarea>
      </div>      
    </div>
  </div> *}
 {* Metemos el número de productos para procesar en AdminPacking los radio button con $_POST *}
  {* <input type="hidden" name="numero_productos" value="{$productos_pedido|@count}">  

  {* Metemos el id de pedido para procesar en AdminPacking *}
  {* <input type="hidden" name="id_pedido" value="{$idpedido}">   *} 


{* Botón para volver a panel de Gestión pickpack *}
<form action="{$url_base}index.php?controller=AdminGestionPickpack&token={$token}" method="post">
  <div class="panel-footer">
    <input class="btn btn-lg btn-success center-block" type="submit" id="volver_gestion" name="volver_gestion" value="Volver" />
  </div>
</form>
