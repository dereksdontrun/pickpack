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
        
                //si no_ok vale 1 mostrar mensaje de confirmación
                if (no_ok == 1){
                    //si no se pulsa cancelar, continua, si no, hace event.prevendefault()
                    if(!confirm("¿Quieres finalizar el Picking como incidencia?")){
                    event.preventDefault();
                    }
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