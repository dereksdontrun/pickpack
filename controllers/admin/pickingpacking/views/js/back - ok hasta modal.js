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
    
    $('.submit_boton_picking').click(function() {      
        //si se ha pulsado submit_finpicking revisamos si necesita confirmación    
        if ($(this).attr('name') == 'submit_finpicking'){
                
            //17/01/2024 Cuando cerremos un picking con incidencia, en lugar de pedir solo confirmación vamos a mostrar un prompt para meter un comentario, que debería ser el código de la gaveta donde se dejen los productos que si se han encontrado. El contenido se asignará a un input hidden y luego se creará en el controlador un mensaje de pedido con dicho código para verlo cuando vuelvan a hacer el picking. Hay que contar con la posibilidad de que solo haya un producto pero varias unidades y falte alguna, y como no sabemos cuantas han encontrado no sabemos si la incidencia es que no hay ninguna o no están todas, de modo que mostramos siempre el prompt. El usuario decidirá si meter o no algo en el comentario dependiendo de si ha recogido algún producto.
            $('#formulario_picking').submit(function(event){
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
        
                //si no_ok vale 1 mostrar prompt para meter código de gaveta si hay algún producto recogido
                if (no_ok == 1){
                    //mostramos prompt para escanear gaveta de incidencias, y guardamos lo introducido en input hidden id "gaveta_incidencias"
                    var codigo_gaveta_incidencias = prompt("Vas a cerrar Picking como incidencia, si vas a reservar algún producto introduce el código de la gaveta para incidencias:");
                    console.log("codigo gaveta:", codigo_gaveta_incidencias);

                    //codigo_gaveta_incidencia será el valor introducido. Si no se introduce nada pero se pulsa Aceptar será <empty string>, y si se pulsa Cancelar, será null. Si es null no permitimos continuar, si está vacío continuamos sin más, y si tiene valor lo guardamos en input hidden gaveta_incidencias
                    if (codigo_gaveta_incidencias === null) {
                        //pulsado Cancelar
                        event.preventDefault();
                    } else if (codigo_gaveta_incidencias !== "") {                        
                        //el prompt contiene algo, si es un número lo guardamos en input hidden, si no mostramos error. Como el contenido del prompt llega aquí como string, pasamos un regex que asegure que solo contiene números. d digits
                        var number_regex = /^\d{1,8}$/;
                        if (number_regex.test(codigo_gaveta_incidencias)) {
                            console.log("es un número : "+codigo_gaveta_incidencias);
                            $("#gaveta_incidencias").val(codigo_gaveta_incidencias);
                            // event.preventDefault();
                        } else {
                            console.log("no es un número : "+codigo_gaveta_incidencias);
                            alert('Error: El código de la gaveta debe ser un número entero de hasta 8 cifras');
                            event.preventDefault(); 
                        }                                          
                       
                    } 
                    //si no había nada sigue el proceso de submit
                    

                    //si ningún producto fue marcado ok, ya sea porque no hay más o porque todos son no ok solo se muestra mensaje de confirmación
                    // if (si_ok == 0){
                    //     //si no se pulsa cancelar, continua, si no, hace event.prevendefault()
                    //     if(!confirm("¿Quieres finalizar el Picking como incidencia?")){
                    //         event.preventDefault();
                    //     }
                    // } else {
                    //     //si hay algún producto no ok y alguno si ok, mostramos prompt para escanear gaveta de incidencias, y guardamos lo introducido en input hidden id "gaveta_incidencias"
                    //     var codigo_gaveta_incidencias = prompt("Vas a cerrar Picking como incidencia, introduce el código de la gaveta para incidencias:");
                    //     console.log("codigo gaveta:", codigo_gaveta_incidencias);
                    // }
                }        
            });
            
        }
    });

    //lo mismo para los submit de packing
    $('.submit_boton_packing').click(function() {      
        //si se ha pulsado submit_finpacking revisamos si necesita confirmación    
        if ($(this).attr('name') == 'submit_finpacking'){
                
            $('#formulario_packing').submit(function(event){
                
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
                    if(!confirm("¿Quieres finalizar el Packing como incidencia?")){
                    event.preventDefault();
                    }
                }        
            });
            
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

$(document).ready(function() {
    if ($("#es_recepcion").val() == 1) {
        //ponemos el color de fondo del input en función de si el valor es 0 o superior
        if ($("#input_unidades_esperadas").val() < 1) {
            $("#input_unidades_esperadas").css("background-color","#F8A1A4");
        }

        //24/10/2023 Comprobamos si hay un pedido seleccionado, si lo hay buscamos su mensaje de pedido y si este existe, hacemos visible el botón de mostrar mensaje
        if ($("#select_pedido_materiales").val() == 0) {
            //nos aseguramos de que botón mensaje sea hidden
            $("#submit_supply_order_message").hide();
        } else {
            //sacamos el id de pedido para comprobar el value del input hidden que guarda su mensaje.         
            var select_value = $("#select_pedido_materiales").val().split("_");
            var id_supply_order = select_value[0];
            console.log('mensaje pedido ' + $("#supply_order_message_"+id_supply_order).val());
            $("#submit_supply_order_message").attr("data-value", id_supply_order);
            //comprobamos si en el input hidden del pedido hay mensaje o venía vacío. Si estaba vacío no mostramos botón
            if ($("#supply_order_message_"+id_supply_order).val() != '') {
                $("#submit_supply_order_message").show();
            } else {
                $("#submit_supply_order_message").hide();
            }          
        }
        
        //11/07/2023 Para Rececpcionador. Cuando el select de pedido de materiales cambie, obtenemos el value de la opción elegida y sacamos la cantidad y la ponemos en el input de unidades esperadas. Además ponemos en el span de unidades recibidas / esperadas los valores necesarios también.
        //recibimos 'idsupplier_unidadesesperadas_unidadesrecibidas_unidadesesperadasreales'    
        $("#select_pedido_materiales").change(function(){          
            $("#input_unidades_esperadas").val(this.value.split("_")[3]);
            $("#span_esperadas_recibidas").text(this.value.split("_")[2]+' / '+this.value.split("_")[1]);
            
            //ponemos el color de fondo del input en función de si el valor es 0 o superior
            if (this.value.split("_")[3] < 1) {
                $("#input_unidades_esperadas").css("background-color","#F8A1A4");
            } else {
                $("#input_unidades_esperadas").css("background-color","#FFFFFF");
            }

            //24/10/2023 cambiamos el atributo data-value del botón de mensaje por el id de pedido. Nos aseguramos de mostrar el botón de mensaje, a menos que id_supply_order sea 0, lo que significa que el select está en "Selecciona pedido"
            // document.getElementById("submit_supply_order_message").setAttribute("data-value", this.value.split("_")[0]);
            // mostrar o esconde con javascript 
            // document.getElementById("myButton").style.display = "block";  o "none"
            $("#submit_supply_order_message").attr("data-value", this.value.split("_")[0]);
            if (this.value.split("_")[0] == 0) {
                $("#submit_supply_order_message").hide();
            } else {
                //comprobamos si en el input hidden del pedido hay mensaje o venía vacío. Si estaba vacío no mostramos botón
                if ($("#supply_order_message_"+this.value.split("_")[0]).val() != '') {
                    $("#submit_supply_order_message").show();
                } else {
                    $("#submit_supply_order_message").hide();
                }            
            }        
        }); 
    }   

    //quiero impedir que se lance el formulario pulsando Enter
    // NOOOO se puede, ya que el trigger que hace el scanner se interpreta como pulsar enter, y se para también. Quizás el scanner sea configurable, pero supongo que habría que configurar todos y esto será por defecto.
    // $('#formulario_ubicaciones').on('keydown', function(event) {
    //     //Enter (key code 13)
    //     if (event.keyCode === 13) {
    //         //paramos la ejecución
    //         event.preventDefault();
    //         console.log('Enter pulsado, paramos formulario.');
    //     }
    // });

    //revisamos que se reciban cantidades positivas, y avisamos cuando se supone que ya se ha recibido una cantidad superior a la esperada
    //además también avisamos si la cantidad es superior a 100, por si se escanea la gaveta sobre el input de stock
    //para evitar dobles clicks que envíen el formulario varias veces, tenemos la variable isFormSubmitted por defecto en false. Al entrar en el $('#formulario_ubicaciones').submit, si ha sido pulsando 'submit_producto_ok', comprobamos esa variable, si es la primera vez tendrá valor false y dejamos pasar en el if, poniéndola inmediantamente en true, de modo que si se vuelve a pulsar no podrá pasar. Este if(!isFormSubmitted) { envía a eventpreventDefault si no se cumple, de modo que el form no se ejecuta a partir del primer pulsado del submit. Si se trata de un valor 0 o alto etc y se da la opción a cancelar, ponemos la variable de nuevo en isFormSubmitted = false. 
    let isFormSubmitted = false;

    $('#formulario_ubicaciones').submit(function(event) {       
        console.log('formulario submit');     
        //event.preventDefault();   

        //27/09/2023 Tenemos 3 botones submit, por defecto, si pulsamos Enter, o en nuestro caso el equivalente que es el escanner que lo dispara, el submit que "ejecuta" el formulario por defecto es el primero por orden en el html, es decir, submit_producto_ok. Aquí tenemos el problema de que si pulsamos Volver o Incidencia, también pasa por aquí y en esos casos no queremos que mire ni cantidades a recibir ni nada, solo que continue con el submit. Como hemos puesto esa medida de seguridad para caundo se pulsa dos veces muy rápido OK y después ejecutamos las comprobaciones, tengo que poner un if antes, que si el submittedButton.attr('name') es vovler o incidencia, no ejecute nada de eso y prosiga con eventdefault, es decir, nada dentro del if de esa condición, y el else es todo lo demás       

        var submittedButton = $(document.activeElement);
        // console.log('submitted button name: '+submittedButton.attr('name'));
        if ((submittedButton.attr('name') == 'submit_volver') || (submittedButton.attr('name') == 'submit_producto_incidencia')) {
            //nada, quiero que el formulario se envíe

        } else {
            //seguimos si se ha hecho submit de ok comprobando el atributo name del botón de submit, volver o incidencia no cuentan
            if (submittedButton.attr('name') == 'submit_producto_ok') {
                // console.log('formulario submit_producto_ok');
                //comprobamos el valor de isFormSubmitted, si no es false es que acabamos de pasar por aquí y enviamos a event.preventDefault
                if (!isFormSubmitted) {
                    //pasamos la variable a true
                    isFormSubmitted = true;                           
                    
                } else {
                    // console.log('formulario preventDefault');
                    event.preventDefault();
                }
            } 

            //04/01/2024 Comprobamos el input de stock para saber si se ha modificado y en ese caso, si se pone una cantidad superior a 100 mostrar mensaje de alerta. Impedimos valores negativos.
            //obtenemos el value del input id input_stock con el de input_stock_hidden. Si son iguales no se ha modificado stock
            if ($("#input_stock").val() != $("#input_stock_hidden").val()) {
                console.log('stock_fisico de '+$("#input_stock_hidden").val()+' a '+$("#input_stock").val());                
                
                //comprobamos que el número en input_stock es entero, no sea negativo, y si es mayor de 99 que se confirme
                if (!$.isNumeric($("#input_stock").val()) || $("#input_stock").val() % 1 !== 0) {
                    alert('Error: El stock físico debe ser un número entero');
                    //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK   
                    isFormSubmitted = false;
                    event.preventDefault();  
                } else if ($("#input_stock").val() < 0) {
                    alert('Error: El stock físico no puede ser negativo');
                    //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK   
                    isFormSubmitted = false;
                    event.preventDefault();                 
                } else if ($("#input_stock").val() > 99) {
                    //si no se pulsa cancelar, continua, si no, hace event.prevendefault()
                    if(!confirm("Atención, ¿Estas segur@ de querer modificar el stock a "+$("#input_stock").val()+" unidades?")){
                        //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK                         
                        isFormSubmitted = false;    
                        event.preventDefault();  
                    }
                }
            }
                        
            //22/08/2023 Primero comprobamos el value del select, si es 0 es que el producto está en más de un pedido y no lo han seleccionado, mostramos error y no pueden continuar
            //27/09/2023 Sacamos todo este if else de if (submittedButton.attr('name') == 'submit_producto_ok') porque si se usa el scanner nunca entra ahí al no ser 'submit_product_ok', de modo que aquí debajo, por si acaso, pasamos isFormSubmitted a false en caso de que sea necesario, aunque quizás no hayamos entrado por 'submit_product_ok'
            //en el front en el formulario de ubicaciones/recepciones tenemos un input hidden de id "es_recepcion" que vale 1 si lo es y 0  si no. Para evitar procesar todo esto si estamos en ubicaciones, comprobamos ese value, y si es 0 no entramos aquí.
            // console.log("es_recepcion: "+$("#es_recepcion").val());
            if ($("#es_recepcion").val() == 1) {
                console.log("es_recepcion");
                if ($("#select_pedido_materiales").val() == 0) {
                    alert('Atención: este producto se encuentra en más de un pedido de materiales, selecciona uno para continuar');
                    //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK  
                    isFormSubmitted = false;
                    event.preventDefault(); 
                } else {
                    //sacamos las unidades a recibir, las unidades esperadas en pedido de materiales y las supuestamente ya recibidas (guardadas en el value de la opción del select separadas por "_")
                    //para asegurarnos de operar con números hacemos parseInt, en base 10, decimal
                    var unidades_a_recibir = parseInt($("#input_unidades_esperadas").val(), 10);
                    var select_value = $("#select_pedido_materiales").val().split("_");
                    var unidades_esperadas = parseInt(select_value[1], 10);
                    var unidades_ya_recibidas = parseInt(select_value[2], 10);
                    // console.log ('-unidades_a_recibir:'+unidades_a_recibir+' -unidades_esperadas:'+unidades_esperadas+' -unidades_ya_recibidas:'+unidades_ya_recibidas);
                    //si unidades a recibir es menor que 1 mostramos alert y no permitimos seguir. 
                    if (unidades_a_recibir < 1) {
                        alert('Error: La cantidad a recibir ha de ser positiva');
                        //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK   
                        isFormSubmitted = false;
                        event.preventDefault();                 
                    } else if (unidades_a_recibir > 100) {
                        //si no se pulsa cancelar, continua, si no, hace event.prevendefault()
                        if(!confirm("Atención, ¿Estas segur@ de querer recepcionar tantas unidades?")){
                            //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK                         
                            isFormSubmitted = false;    
                            event.preventDefault();  
                        }
                    } else if ((unidades_a_recibir + unidades_ya_recibidas) > unidades_esperadas) {
                        //si no se pulsa cancelar, continua, si no, hace event.prevendefault()
                        if(!confirm("Atención, ¿Quieres recepcionar más unidades de las esperadas?")){
                            //por si acaso "reseteamos" la variable para poder entrar al envío del formulario, en caso de que se haya entrado pulsando OK                    
                            isFormSubmitted = false;   
                            event.preventDefault();    
                        }
                    }
                }   
            }
              
        }        
        
        // console.log('formulario submit fin');
                    
    });
    

});

//24/10/2023 Función que se llama al hacer click sobre el botón Mensaje de pedido de materiales
function muestraMensajePedido() {
    var button = document.getElementById("submit_supply_order_message");
    var id_supply_order = button.getAttribute("data-value");
    var message = document.getElementById("supply_order_message_"+id_supply_order).value;

    alert(message);
}


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