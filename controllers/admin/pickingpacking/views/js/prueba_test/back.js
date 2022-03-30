/**
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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

$(function(){
    //al pulsar los botones de envuelto para regalo y obsequio por número de pedidos quitamos y ponemos la clase del botón checkbox para que cambie de warning a success, también el texto, y marcamos el checkbox como checked o no checked
    
    $('#micheckbox_regalo').toggle(
        function(){
            $('#micheckbox_regalo').removeClass("btn-warning");
            $('#micheckbox_regalo').addClass("btn-success");
            $('#span_regalo_no').hide();
            $('#span_regalo_si').show();  
            $('#checkbox_regalo').prop("checked", true);          
        },
        function(){
            $('#micheckbox_regalo').removeClass("btn-success");
            $('#micheckbox_regalo').addClass("btn-warning");
            $('#span_regalo_si').hide();
            $('#span_regalo_no').show();  
            $('#checkbox_regalo').prop("checked", false); 
        });

    $('#micheckbox_obsequio').toggle(
        function(){
            $('#micheckbox_obsequio').removeClass("btn-warning");
            $('#micheckbox_obsequio').addClass("btn-success");
            $('#span_obsequio_no').hide();
            $('#span_obsequio_si').show();  
            $('#checkbox_obsequio').prop("checked", true);          
        },
        function(){
            $('#micheckbox_obsequio').removeClass("btn-success");
            $('#micheckbox_obsequio').addClass("btn-warning");
            $('#span_obsequio_si').hide();
            $('#span_obsequio_no').show(); 
            $('#checkbox_obsequio').prop("checked", false);  
        });

    //cuando se intente enviar el formulario de un picking o packing con un producto o más cuyo radio button checado sea 'no ok' se mostrará un mensaje de confirmación preguntando si se quiere cerrar el proceso como incidencia
    //al hacer submit en el formulario de picking, primero hay que saber si se ha hecho submit con el botón de enviar o el de cancelar, ambos son submit en el formulario pero con cancelar no hace falta mostrar la confirmación. Sacamos el name del botón de clase submit_button_picking que ha sido pulsado y si es el de envío, permitimos ejecutar la función de mostrar confirmación.
    
    $('.submit_boton_picking').click(function(event) {      
        //si se ha pulsado submit_finpicking revisamos si necesita confirmación    
        if ($(this).attr('name') == 'submit_finpicking'){
                
            // $('#formulario_picking').submit(function(event){
                //esto me saca los radio button marcados, con su nombre y value
                // var radioValues = ''; 
                // $('input[type="radio"]:checked').each(function() {
                //     radioValues += $(this).attr('name')+' = '+$(this).val()+'\n';
                // });        
                // alert(radioValues);
        
                //variable para poner a 1 si algún value de radio button es 0, es decir, marcada no OK
                var no_ok = 0;
                $('input[type="radio"]:checked').each(function() {
                    if ($(this).val() == 0){
                        no_ok = 1;
                    }
                });
        
                //si no_ok vale 1 mostrar mensaje de confirmación
                if (no_ok == 1){
                    //si no se pulsa cancelar, continua, si no, hace event.prevendefault()
                    if(!confirm("¿Quieres finalizar el Picking como incidencia?")){
                        event.preventDefault();
                    }
                }        
            // });
            
        }
    });

    //lo mismo para los submit de packing
    //23/10/2020 En el packing, además se comprueba que ningún producto esté en radio button warning, si lo hay tiene que pasarse a ok o a no ok para poder cerrar
    $('.submit_boton_packing').click(function(event) {      
        //si se ha pulsado submit_finpacking revisamos si necesita confirmación    
        if ($(this).attr('name') == 'submit_finpacking'){
            console.log('pulsado '+$(this).attr('name'));
            // $('#formulario_packing').submit(function(event){

                //comprobamos si algún value de radio button es 2, es decir, marcado warning, si es así, mostramos ek div con el warning y hacemos event.prevent_default, ya que no se puede cerrar packing con warning   
                var warning = 0;             
                $('input[type="radio"]:checked').each(function() {
                    if ($(this).val() == 2){
                        warning = 1;
                    }
                });

                if (warning) {
                    console.log('muestra alert warning');
                    // alert('Revisa los productos, no puedes cerrar un packing con un producto en alerta');
                    $('#warning_radio_button').show();
                    // $('#warning_radio_button').fadeIn(300);
                    event.preventDefault();
                    
                } else {
                    //si no hay warnings procesamos los no ok
                    $('#warning_radio_button').hide();
                    // $('#warning_radio_button').fadeOut(300);
                    //variable para poner a 1 si algún value de radio button es 0, es decir, marcada no OK
                    var no_ok = 0;
                    $('input[type="radio"]:checked').each(function() {
                        if ($(this).val() == 0){
                            no_ok = 1;
                        }
                    });
            
                    //si no_ok vale 1 mostrar mensaje de confirmación
                    if (no_ok == 1){
                        //si NO se pulsa cancelar, continua, si no, hace event.prevendefault()
                        if( !confirm("¿Quieres finalizar el Packing como incidencia?")){
                            event.preventDefault();
                            // event.stopImmediatePropagation();
                        } 
                    }      
                }
            //si no había warning ni no-ok seguimos el flujo del click en el submit ejecutando el packing   
                  
            // });
            
        }
        //si no se ha pulsado el botón de finalizar es que se ha pulsado del de cancelar, continuamos con el submit, que irá a packing.php donde cancelará y volverá atrás
        console.log('pulsado '+$(this).attr('name'));            
        
    });
    

    // 21/10/2020 Código para manejar el input flotante para el ean
    $('#sticky_form_ean').on('submit',function(event){
        //paramos la ejecución del formulario
        event.preventDefault();
        console.log('pulsado ok ean');
        var ean13 = $('#input_ean').val();
        console.log('ean13 introducido '+ean13);
        //limpiamos de espacios en blanco (por si se han introducido solo espacios en blanco, aunque no pasa nada)
        ean13 = ean13.trim();

        //vaciamos el input del ean
        $('#input_ean').val('');
        // console.log('ean13 length '+ean13.length);
        //si no se ha introducido nada no hacemos nada
        if (!ean13 || ean13 == '') {
            console.log('input ean13 vacio');
            return;
        }
        //contamos los elementos con esa clase
        var num_input_hidden = $('.'+ean13).length;
        console.log('num input hidden con class ean = '+num_input_hidden);
        //si num_input_hidden es 0 es que no se encuentra el ean en el packing (producto erróneo o ean mal puesto en producto) Mostramos error.
        if (!num_input_hidden) {
            //no encontramos el ean, mostramos un mensaje de error sobre el input del ean
            $("#sticky_producto_escaneado").fadeIn(500);
            $("#escaneo_danger").fadeIn(500);
            setTimeout(function() { 
                $("#sticky_producto_escaneado").fadeOut(400);
                $("#escaneo_danger").fadeOut(400); 
            }, 3500);


            console.log('Producto no encontrado con ese ean13');
            // alert('El ean '+ean13+' no corresponde a ningún producto en la lista.');

        } else {
            //se ha encontrado al menos un input hidden con class = ean, procedemos.
            //sacamos todos los input hidden class=ean13 del packing
            $('input[type="hidden"].'+ean13).each(function() {
                console.log('val del input hidden (unidades) '+$(this).val());
                console.log('id del input hidden '+$(this).attr('id'));
                //restamos 1 al número de inputs hidden con esa clase ean. Cuando llegue a 0 es que estamos en el último loop del each. num_input_hidden indicará cuantas vueltas quedan
                num_input_hidden = num_input_hidden - 1;
                //sacamos el value del input hidden (unidades del producto en el pedido, si es caja, en el pedido de esa caja)
                var unidades = $(this).val();
                //si unidades es 0 es que ya se ha escaneado ese producto hasta restar hasta 0 el value del input hidden, comprobamos si quedan más input hidden con esa class. Si quedan, continuamos el loop each con return; , si no quedan hay que mostrar un warning (demasiadas unidades cogidas en el picking o producto repetido) y paramos el loop con return false;
                //sacamos el name del radio button para buscar cada radio button por su value (0 es no ok, 1 es ok, 2 es warning). Para ello tenemos que buscar el que tenga como name el id del input hidden sin el ean al final, y hacemos substring desde 0 hasta la posición de _ean13 para sacar name.
                var input_hidden_id = $(this).attr('id');
                var radio_name = input_hidden_id.substring(0, input_hidden_id.search('_'+ean13));
                console.log('radio_name = '+radio_name);

                //con radio_name tenemos también la clase que hemos asignado a las imagenes de cada producto (id producto+id_atributo+id_order), buscamos la url de imagen que tenga dicha clase, para mostrarla si el ean es correcto:
                // var url_imagen_scan = $("img."+radio_name).attr("src");
                //por problemas con las comillas, es mejor asignar directamente a src el valor $("img."+radio_name).attr("src")
                
                if (!unidades && !num_input_hidden) {
                    //ya se han marcado todos los productos, esta unidad escaneada es un error (exceso de picking). Salimos de todo el loop y mostramos error-warning
                    console.log('No quedan productos por marcar con ese ean13');
                    //marcamos el radio button de warning (value 2)
                    $("input[name="+radio_name+"]:radio[value=2]").prop("checked", true);

                    //añadimos src de imagen al img con id imagen_sobran
                    $("#imagen_sobran").attr("src", $("img."+radio_name).attr("src"));
                    //mostramos mensaje
                    $("#sticky_producto_escaneado").fadeIn(500);
                    $("#escaneo_warning_sobran").fadeIn(500);
                    setTimeout(function() { 
                        $("#sticky_producto_escaneado").fadeOut(400);
                        $("#escaneo_warning_sobran").fadeOut(400);
                    }, 3500);

                    //mostramos ventana avisando del exceso
                    // alert('El ean '+ean13+' corresponde a algún producto en la lista pero están todos marcados.\nQuizás se ha hecho picking de demasiadas unidades.');

                    return false;
                } else if (!unidades && num_input_hidden) {
                    //en este input ya se han marcado las unidades, pero quedan más por revisar, salimos de este loop al siguiente
                    return;
                }

                //comprobamos si el radio button de ok (el que tiene val=1) está checked. 
                //buscamos el radio button con el name correcto y con value = 1, comprobando si está checked
                if (!$("input[name="+radio_name+"]:radio[value=1]").is(':checked')) {
                    console.log('input OK NO checado');
                    //comprobamos que no esté checado el warning
                    if ($("input[name="+radio_name+"]:radio[value=2]").is(':checked') && unidades < 1) {
                        console.log('radio Warning está checado');  
                        
                        //añadimos src de imagen al img con id imagen_sobran
                        $("#imagen_sobran").attr("src", $("img."+radio_name).attr("src"));
                        //mostramos mensaje                         
                        $("#sticky_producto_escaneado").fadeIn(500);
                        $("#escaneo_warning_sobran").fadeIn(500);
                        setTimeout(function() { 
                            $("#sticky_producto_escaneado").fadeOut(400);
                            $("#escaneo_warning_sobran").fadeOut(400);
                        }, 3500);
                        //mostramos el error de exceso de producto y salimos
                        // alert('El ean '+ean13+' corresponde a algún producto en la lista pero están todos marcados.\nQuizás se ha hecho picking de demasiadas unidades o se ha marcado OK a mano.');
    
                        return false;
                    }
                    //si este radio button no está checado y tampoco el warning, comprobamos el valor de unidades y restamos 1, si queda 0 checamos el radio button y salimos del loop each con return false; Si queda más que 0  marcamos como warning.
                    unidades = unidades - 1;
                    if (unidades > 0) {
                        //ponemos el nuevo valor al value del input y marcamos el radio button como warning o aviso de que aún faltan unidades.
                        console.log('Marcamos Warning, quedan unidades');
                        $("input[name="+radio_name+"]:radio[value=2]").prop("checked", true);
                        $(this).val(unidades);

                        //añadimos src de imagen al img con id imagen_faltan
                        $("#imagen_faltan").attr("src", $("img."+radio_name).attr("src"));

                        $("#sticky_producto_escaneado").fadeIn(500);
                        $("#escaneo_warning_faltan").fadeIn(500);
                        setTimeout(function() { 
                            $("#sticky_producto_escaneado").fadeOut(400);
                            $("#escaneo_warning_faltan").fadeOut(400);
                        }, 3500);

                        //salimos del each loop
                        return false;
                    } else {
                        //no quedan más unidades que marcar en este input, ponemos checked a su radio button correspondiente y ponemos el value del input a 0
                        console.log('Marcamos OK, producto finalizado');
                        $("input[name="+radio_name+"]:radio[value=1]").prop("checked", true);
                        $(this).val(unidades);

                        //añadimos src de imagen al img con id imagen_success
                        $("#imagen_success").attr("src", $("img."+radio_name).attr("src"));

                        $("#sticky_producto_escaneado").fadeIn(500);
                        $("#escaneo_success").fadeIn(500);
                        setTimeout(function() { 
                            $("#sticky_producto_escaneado").fadeOut(400);
                            $("#escaneo_success").fadeOut(400);
                        }, 3500);

                        //salimos del each loop
                        return false;
                    }

                    //PEDIDOS PRUEBAS 187603, 187595

                } else {
                    console.log('input OK checado');
                    //si este radio button ya está checado, comprobamos que queden más input hidden de ese ean y pasamos al siguiente loop del each con continue;. Si no quedan más hay que mostrar warning. En todo caso, esto sería un error ya que si está checado debería estar el value del input hidden a 0, condición con la que no habría llegado aquí, pero quizás lo han marcado a mano. Especificamos en mensaje.
                    if (!num_input_hidden) {
                        //ya se han marcado todos los productos, esta unidad escaneada es un error (exceso de picking)o hay un error al marcar los productos. Salimos de todo el loop y mostramos error-warning
                        console.log('No quedan productos por marcar con ese ean13 o están mal marcados (a mano?)');
                        $("input[name="+radio_name+"]:radio[value=2]").prop("checked", true);

                        //añadimos src de imagen al img con id imagen_sobran
                        $("#imagen_sobran").attr("src", $("img."+radio_name).attr("src"));

                        $("#sticky_producto_escaneado").fadeIn(500);
                        $("#escaneo_warning_sobran").fadeIn(500);
                        setTimeout(function() { 
                            $("#sticky_producto_escaneado").fadeOut(400);
                            $("#escaneo_warning_sobran").fadeOut(400); 
                        }, 3500);

                        // alert('El ean '+ean13+' corresponde a algún producto en la lista pero están todos marcados.\nQuizás se ha hecho picking de demasiadas unidades o se ha marcado OK a mano.');
    
                        return false;
                    } else {
                        //está marcado, pasamos al siguiente loop ya que quedan inputs hidden (esto sería un error probablemente ya que unidades aquí debería ser > 0)
                        console.log('radio OK checado, quedan más');
                        return;
                    }
                }                
                
            });
        }   
    });

    //23/10/2020 Si se marca manualmente el radio button de warning o el rojo, se resetea ese producto o línea de producto (puede haber más si se encuentra repetido en una caja) y se quita el check de ok o no ok y se devuelve el value del input hidden al valor original. El check de este radio button para que no quede en warning no se puede quitar una vez marcado alguno (habría que generar de nuevo el html probablemente)
    //05/11/2020 añadimos que si se pulsa cualquier radio button, seguido se haga focus de nuevo en el input, también con el check de obsequio.
    $("input[type='radio']:radio[value=2]").on('click', function(){
        console.log('pulsado warning manualmente');
        // $(this).addClass("warning_manual");
        //sacamos el name de este grupo de radio button
        var this_radio_name = $(this).attr('name');
        console.log('this_radio_name pulsado = '+this_radio_name);
        //buscamos el valor original de cantidad de producto, que está dentro del span con el id igual que el name que los radio button
        var unidades_producto = $("span[id="+this_radio_name+"]").text();
        console.log('unidades_producto = '+unidades_producto);
        //buscamos el input hidden que corresponde a este radio button y le asignamos unidades_producto
        // ​var this_input_hidden_cantidad = $('input[id^="'+this_radio_name+'_"]').val();   CON ESTAS comillas no funciona¿?
        $("input[id^='"+this_radio_name+"_']").val(unidades_producto);       
        console.log('reasignada unidades producto a input hidden '+unidades_producto);        
    });

    //04/11/2020 Lo mismo si se marca el radio button rojo (value = 0)
    $("input[type='radio']:radio[value=0]").on('click', function(){
        console.log('pulsado no ok (rojo) manualmente');
        // $(this).addClass("warning_manual");
        //sacamos el name de este grupo de radio button
        var this_radio_name = $(this).attr('name');
        console.log('this_radio_name pulsado = '+this_radio_name);
        //buscamos el valor original de cantidad de producto, que está dentro del span con el id igual que el name que los radio button
        var unidades_producto = $("span[id="+this_radio_name+"]").text();
        console.log('unidades_producto = '+unidades_producto);
        //buscamos el input hidden que corresponde a este radio button y le asignamos unidades_producto
        // ​var this_input_hidden_cantidad = $('input[id^="'+this_radio_name+'_"]').val();   CON ESTAS comillas no funciona¿?
        $("input[id^='"+this_radio_name+"_']").val(unidades_producto);       
        console.log('reasignada unidades producto a input hidden '+unidades_producto);        
    });


    // $("input[type='radio']:radio[value=1]").on('click', function(){
    //     console.log('pulsado OK (verde) manualmente');   
        
    // });

    //para no perder el foco del input para que el scanner siempre este colocado sobre él, tengo que hacer que cada vez que se pulsa sobre algo en el documento, el foco vuelva. Añado excepciones como dentro del text area, cuyo id es 'comentario'. 
    $(document).click(function (event) {        
        console.log('clicado elemento con id = '+event.target.id);
        if (event.target.id !== 'comentario') {
            $("#input_ean").focus();
        }        
    });



    
    // $('#micheckbox_picking_obsequio').toggle(
    //     function(){
    //         $('#micheckbox_picking_obsequio').removeClass("btn-warning");
    //         $('#micheckbox_picking_obsequio').addClass("btn-success");
    //         $('#span_picking_obsequio_no').hide();
    //         $('#span_picking_obsequio_si').show();  
    //         $('#checkbox_picking_obsequio').prop("checked", true);          
    //     },
    //     function(){
    //         $('#micheckbox_picking_obsequio').removeClass("btn-success");
    //         $('#micheckbox_picking_obsequio').addClass("btn-warning");
    //         $('#span_picking_obsequio_si').hide();
    //         $('#span_picking_obsequio_no').show(); 
    //         $('#checkbox_picking_obsequio').prop("checked", false);  
    //     });

});


// $(function(){


//     var id_empleado = id_employee;

//     //Si el usuario pulsa sobre botón Picking
//     $('#proceso_picking').on('click', function(e){
//         e.preventDefault();
        
//         var url = url_base+'controllers/admin/procesos/picking.php?token='+token+'&id_empleado='+id_empleado;    
    
//         openInNewTab(url);
//     });


    
    
//     //openInNewTab(url_base+'gestionpedidos.php?token='+token+'&fecha_desde='+fecha_desde+'&fecha_hasta='+fecha_hasta+'&estados_pedido='+estados_pedido);
//     //Si el usuario pulsa sobre botón Packing
//     $('#proceso_packing').on('click', function(e){
//         e.preventDefault();
        
//         var url = url_base+'controllers/admin/procesos/packing.php?token='+token+'&id_empleado='+id_empleado;    
    
//         openInNewTab(url);
//     });


//     //Si el usuario pulsa sobre botón Gestión
//     $('#proceso_gestion').on('click', function(e){
//         e.preventDefault();

//         var url = url_base+'controllers/admin/procesos/gestion.php?token='+token+'&id_empleado='+id_empleado;     
    
//         openInNewTab(url);
//     });


//     //función para abrir página en nueva pestaña
//     function openInNewTab(url) {
//         window.open(url, '_blank').focus();
//     }

// });