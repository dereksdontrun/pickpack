<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/
//Controlador para gestión del proceso de picking y packing donde se puede ver el estado de todos los pedidos almacenados en la tabla lafrips_pick_pack para seleccionar con cual queremos trabajar


if (!defined('_PS_VERSION_'))
    exit;

class AdminGestionPickpackController extends ModuleAdminController {

  public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->identifier = 'id_pickpack';
        $this->table = 'pick_pack';
        //$this->list_id = 'inventory';
        $this->className = 'PickPackOrder';
        $this->lang = false;
        //$this->multishop_context = Shop::CONTEXT_ALL;

        $this->_select = '
        a.id_pickpack AS idpickpack,
        a.id_pickpack_order AS idorder,
        IF (a.comenzado, "Si", "No") AS comenzado,
        IF (a.finalizado, "Si", "No") AS finalizado, 
        ppe.nombre_estado AS nombre_estado,
        oca.tracking_number AS tracking,
        car.name AS transportista,
        osl.name AS estado_prestashop,
        ors.color AS color_presta,
        ppe.color AS color_pickpack';

        $this->_join = '
        LEFT JOIN lafrips_pick_pack_estados ppe ON ppe.id_pickpack_estados = a.id_estado_order
        LEFT JOIN lafrips_orders ord ON a.id_pickpack_order = ord.id_order        
        LEFT JOIN lafrips_carrier car ON ord.id_carrier = car.id_carrier
        LEFT JOIN lafrips_order_carrier oca ON oca.id_order = ord.id_order
        LEFT JOIN lafrips_order_state ors ON ors.id_order_state = ord.current_state
        LEFT JOIN lafrips_order_state_lang osl ON ors.id_order_state = osl.id_order_state AND osl.id_lang = 1';
        $this->_orderBy = 'id_pickpack_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        //metemos los estados de pedido en un array
        $sql_estados = 'SELECT id_pickpack_estados, nombre_estado FROM lafrips_pick_pack_estados';
        $estados = Db::getInstance()->ExecuteS($sql_estados);
        foreach ($estados as $estado) {
            $this->estados_array[$estado['id_pickpack_estados']] = $estado['nombre_estado'];
        }

        //sacamos los estados de pedido de prestashop
        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        //para el SELECT de finalizado
        // $this->finalizado_array[0] = 'No';
        // $this->finalizado_array[1] = 'Si';

        $this->fields_list = array(
            'tracking' => array(
                'title' => $this->l('Tracking'),
                'align' => 'text-center',
                'width' => 'auto',
                'type' => 'text',
                'filter_key' => 'oca!tracking_number',                
            ),       
            'idorder' => array(
                'title' => $this->l('Pedido'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'type' => 'text',
                'filter_key' => 'a!id_pickpack_order',
                'filter_type' => 'int',
            ),            
            'nombre_estado' => array(
                'title' => $this->l('Estado PickPack'),
                'type' => 'select',
                'color' => 'color_pickpack',
                'list' => $this->estados_array,
                'filter_key' => 'ppe!id_pickpack_estados',
                'filter_type' => 'int',
                'order_key' => 'nombre_estado'
            ),
            // 'pais' => array(
            //     'title' => $this->l('País'),
            //     'type' => 'text',
            //     'align' => 'text-center',
            //     //'class' => 'fixed-width-xl',
            //     'filter_key' => 'col!name',
            //     'filter_type' => 'text',
            //     'order_key' => 'col!name'
            // ),
            'transportista' => array(
                'title' => $this->l('Transporte'),
                'type' => 'text',
                'align' => 'text-center',
                //'class' => 'fixed-width-xl',
                'filter_key' => 'car!name',
                'filter_type' => 'text',
                'order_key' => 'car!name'
            ),
            'estado_prestashop' => array(
                'title' => $this->l('Estado Prestashop'),
                'type' => 'select',
                'color' => 'color_presta',
                'class' => 'fixed-width-xs',
                'list' => $this->statuses_array,
                'filter_key' => 'ors!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osl!name'
            ),
            // 'nombre_employee_picking' => array(
            //     'title' => $this->l('Empleado Picking'),
            //     'class' => 'fixed-width-xs',
            //     'type' => 'text',
            // ),
            // 'nombre_employee_packing' => array(
            //     'title' => $this->l('Empleado Packing'),
            //     'class' => 'fixed-width-xs',
            //     'type' => 'text',
            // ),
            'date_add' => array(
                'title' => $this->l('Añadido'),
                'width' => 40,
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'filter_type' => 'datetime',
            ),
            // 'date_fin_picking' => array(
            //     'title' => $this->l('Fin Picking'),
            //     'width' => 40,
            //     'type' => 'datetime',
            //     'filter_key' => 'a!date_fin_picking',
            //     'filter_type' => 'datetime',
            // ),
            // 'date_fin_packing' => array(
            //     'title' => $this->l('Fin Packing'),
            //     'width' => 40,
            //     'type' => 'datetime',
            //     'filter_key' => 'a!date_fin_packing',
            //     'filter_type' => 'datetime',
            // ),
            'comenzado' => array(
                'title' => $this->l('Comenzado'),
                'type' => 'select',
                'class' => 'fixed-width-xs',
                'list' => array(0 => 'No', 1 => 'Si'),
                'filter_key' => 'a!comenzado',                               
            ),
            'finalizado' => array(
                'title' => $this->l('Finalizado'),
                'type' => 'select',
                'class' => 'fixed-width-xs',                 
                // 'list' => $this->finalizado_array,
                'list' => array(0 => 'No', 1 => 'Si'),
                'filter_key' => 'a!finalizado',                               
            ),
            // 'finalizado' => array(
            //     'title' => $this->l('Finalizado'),
            //     'width' => 10,
            //     'type' => 'bool',
            //     'active' => 'status',
            //     'filter_key' => 'a!finalizado',
            //     'orderby' => false,
            // ),
        );
        $this->actions = array('view');
        //$this->actions = array('view');
        //15/09/2020 Añadimos otra acción que nos permita ir al pedido en admin orders desde aquí, y si existe tracking para el pedido, otra acción para ir directamente al seguimiento
        // $this->addRowAction('view'); 
        $this->addRowAction('verpedido');
        $this->addRowAction('seguimiento'); 

        //añadimos posibilidad de cambiar estado de pickpack a varios pedidos a la vez. Dos botones, uno para pasar a Picking finalizado y otro apacking finalizado -> Pauqete enviado
        $this->bulk_actions = array(
            'updateIdEstadoOrderFinPicking' => array('text' => $this->l('Cambiar Estado a Picking Finalizado'), 'icon' => 'icon-refresh'),
            'updateIdEstadoOrderFinPacking' => array('text' => $this->l('Cambiar Estado a Paquete Enviado'), 'icon' => 'icon-refresh'),
        );     

        parent::__construct();
    }

    /*
     * Hace accesible js y css desde la página de controller/AdminGestionPickpack
     */
    public function setMedia(){
        parent::setMedia();
        $this->addJs($this->module->getPathUri().'views/js/back.js');
        //añadimos la dirección para el css
        $this->addCss($this->module->getPathUri().'views/css/back.css');
    }

    //añadimos esta función para gestionar el botón acción añadido al controlador para ir directamente al pedido en admin orders vieworder
    public function displayVerpedidoLink($token = null, $id, $name = null)
    {
        // $token will contain token variable
        // $id will hold id_identifier value
        // $name will hold display name

        //generamos el destino del link, el pedido en admin orders. Para ello, a partir de $id, que es el id_pickpack de la tabla lafrips_pickpack, sacamos el id_pickpack_order   
        //instanciamos el pedido pickpack
        $pickpack_order = new PickPackOrder((int)$id); 
        $id_order = $pickpack_order->id_pickpack_order;
        //https://lafrikileria.com/lfadminia/index.php?controller=AdminOrders&token=b192540700c383eeb6b26f1da43998da&id_order=225726&vieworder
        $token_adminorders = Tools::getAdminTokenLite('AdminOrders');
        $url_base = Tools::getHttpHost(true).__PS_BASE_URI__;
        $href = $url_base.'lfadminia/index.php?controller=AdminOrders&token='.$token_adminorders.'&id_order='.$id_order.'&vieworder';

        //hay que crear esta plantilla para el botón
        //$tpl = $this->createTemplate('helpers/list/list_action_verpedido.tpl'); 
        //me daba problemas así que usamos esta forma de createTemplate con la url absoluta
        $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_.'pickpack/views/templates/admin/pick_pack/helpers/list/list_action_verpedido.tpl'); 
        
        $tpl->assign(array(
            'id' => $id,
            'href' => $href,        
            'action' => 'Ir a pedido'
        ));
        return $tpl->fetch();
    }

    //para el botón acción que envía al seguimeinto si hay tracking
    public function displaySeguimientoLink($token = null, $id, $name = null)
    {
        // $token will contain token variable
        // $id will hold id_identifier value
        // $name will hold display name

        //generamos el destino del link, el seguimiento del pedido si existe tracking. Para ello, a partir de $id, que es el id_pickpack de la tabla lafrips_pickpack, sacamos el id_pickpack_order, desde ahí el transportista y si hay tracking, dependiendo de este sacamos la url  
        //instanciamos el pedido pickpack
        $pickpack_order = new PickPackOrder((int)$id); 
        $id_order = $pickpack_order->id_pickpack_order;

        $sql_tracking = 'SELECT car.name AS name, oca.tracking_number AS tracking
        FROM lafrips_order_carrier oca
        JOIN lafrips_carrier car ON car.id_carrier = oca.id_carrier
        WHERE id_order = '.$id_order;
        $tracking = Db::getInstance()->ExecuteS($sql_tracking);

        if ($tracking) {            
            $transportista = $tracking[0]['name'];
            $tracking = $tracking[0]['tracking'];
            //si hay número de tracking montamos la url del seguimiento según el transportista, al cual diferenciamos por nombre ya que el id puede cambiar si se elimina y crea el transporte, etc. Habría que actualizarlo con nuevos transportistas
            if (preg_match('/GLS/i',$transportista )) {
                //si es GLS buscamos la url en la tabla lafrips_asm_envios
                $sql_tracking_gls = 'SELECT url_track FROM lafrips_asm_envios WHERE codigo_envio = "'.$tracking.'" AND id_envio_order = '.$id_order;
                $url_tracking = Db::getInstance()->ExecuteS($sql_tracking_gls)[0]['url_track'];
            } elseif (preg_match('/correos/i',$transportista ) && preg_match('/^((?!expres).)*$/i',$transportista )) {
                //si el nombre contiene correos pero no express
                //la url de seguiimiento para correos es tal que http://www.correos.es/comun/localizador/track.asp?numero=PS4S2T0720500920110800Q
                $url_tracking = 'http://www.correos.es/comun/localizador/track.asp?numero='.$tracking;
            } elseif (preg_match('/spring/i',$transportista )) {
                //si el nombre contiene spring 
                //la url de seguimiento para spring es tal que https://mailingtechnology.com/tracking/?tn=3SDOKC0476849
                $url_tracking = 'https://mailingtechnology.com/tracking/?tn='.$tracking;
            } else { //mientras no utilicemos otros transportistas queda así
                $url_tracking = '';
            }
        }

        if ($url_tracking && $url_tracking != '') {
            $href = $url_tracking;
            $seguimiento = 1;
        } else {
            $href = '';
            $seguimiento = 0;
        }

        //hay que crear esta plantilla para el botón        
        //me daba problemas así que usamos esta forma de createTemplate con la url absoluta
        $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_.'pickpack/views/templates/admin/pick_pack/helpers/list/list_action_seguimiento.tpl'); 
        
        $tpl->assign(array(
            'id' => $id,
            'href' => $href, 
            'seguimiento' => $seguimiento,      
            'action' => 'Seguimiento'
        ));
        return $tpl->fetch();
    }

    public function renderView(){
        //cuando se pulse sobre "ver" en algún pedido de la lista, el proceso viene por aquí para renderizar la vista. Por defecto se recoge el objeto $this->object con los datos del pedido que tenemos del objeto PickPackOrder.php. Con esos datos, que incluyen el id de pedido de prestashop, sacamos todoos los datos necesarios y los asignamos a variables smarty para mostrarlo en el tpl que asignamos también más abajo, que será gestionpickpack.tpl

        $id_pedido = (int)$this->object->id_pickpack_order;

        //sacamos info del cliente y pedido, LEFT JOIN para state ya que muchos de amazon no tienen
        //30/03/2022 añadimos campo para averiguar si el pedido está en lafrips_dropshipping e indicarlo en la vista
        //24/05/2022 obtenemos un indicador pedido_webservice si el pedido está en la tabla lafrips_webservice_orders para mostrar mensaje en packing 
        $sql_info_pedido = "SELECT ord.id_customer AS id_cliente, CONCAT(cus.firstname,' ', cus.lastname) AS nombre_cliente, CONCAT(adr.address1,' ', adr.address2) AS direccion, adr.postcode AS codigo_postal, ord.payment AS metodo_pago, osl.name AS estado_prestashop, ohi.date_add AS fecha_estado_prestashop, ord.module AS amazon,
        adr.city AS ciudad, sta.name AS provincia, col.name AS pais, ord.date_add AS fecha_pedido, car.name AS transporte, ord.gift AS regalo, 
        ord.gift_message AS mensaje_regalo, cus.note AS nota_sobre_cliente, adr.phone_mobile AS tlfno1, adr.phone AS tlfno2,
        IF((SELECT COUNT(id_dropshipping) FROM lafrips_dropshipping WHERE id_order = ord.id_order) < 1, 0, 1) AS pedido_dropshipping,
        IF((SELECT COUNT(id_webservice_order) FROM lafrips_webservice_orders WHERE id_order = ord.id_order) < 1, 0, 1) AS pedido_webservice   
        FROM lafrips_customer cus
        JOIN lafrips_orders ord ON ord.id_customer = cus.id_customer
        JOIN lafrips_address adr ON ord.id_address_delivery = adr.id_address
        JOIN lafrips_country_lang col ON adr.id_country = col.id_country
        LEFT JOIN lafrips_state sta ON sta.id_state = adr.id_state
        JOIN lafrips_carrier car ON car.id_carrier = ord.id_carrier
        JOIN lafrips_order_state ors ON ors.id_order_state = ord.current_state
        JOIN lafrips_order_state_lang osl ON ors.id_order_state = osl.id_order_state 
        AND osl.id_lang = 1
        JOIN lafrips_order_history ohi ON ohi.id_order_state = ord.current_state AND ohi.id_order = ord.id_order
        WHERE col.id_lang = 1
        AND ord.id_order = ".$id_pedido.";";

        $info_pedido = Db::getInstance()->ExecuteS($sql_info_pedido);
        //asignamos los datos a variables
        $id_cliente = $info_pedido[0]['id_cliente'];
        $nombre_cliente = $info_pedido[0]['nombre_cliente'];
        $direccion = $info_pedido[0]['direccion'];
        $codigo_postal = $info_pedido[0]['codigo_postal'];
        $ciudad = $info_pedido[0]['ciudad'];
        $provincia = $info_pedido[0]['provincia'];
        $pais = $info_pedido[0]['pais'];
        $fecha_pedido = date('d-m-Y', strtotime($info_pedido[0]['fecha_pedido']));
        $transporte = $info_pedido[0]['transporte'];
        $metodo_pago = $info_pedido[0]['metodo_pago'];
        $amazon = $info_pedido[0]['amazon'];
        $estado_prestashop = $info_pedido[0]['estado_prestashop'];
        $fecha_estado_prestashop = $info_pedido[0]['fecha_estado_prestashop'];
        $regalo = $info_pedido[0]['regalo'];
        $mensaje_regalo = $info_pedido[0]['mensaje_regalo'];
        $nota_sobre_cliente = $info_pedido[0]['nota_sobre_cliente'];
        $pedido_dropshipping = $info_pedido[0]['pedido_dropshipping'];
        $pedido_webservice = $info_pedido[0]['pedido_webservice'];
        if ($info_pedido[0]['tlfno1'] != "") {
            $telefono = $info_pedido[0]['tlfno1'];
        } else {
            $telefono = $info_pedido[0]['tlfno2'];
        }
        if ($info_pedido[0]['amazon'] == "amazon") {
            $amazon = 1;
        } else {
            $amazon = 0;
        }
        //Si es pedido amazon sacamos su marketplace y el id de pedido de amazon
        if ($amazon){
            $sql_amazon_order = "SELECT mp_order_id AS amazon_id, sales_channel AS marketplace FROM lafrips_marketplace_orders WHERE id_order = ".$id_pedido.";";
            $amazon_order = Db::getInstance()->ExecuteS($sql_amazon_order);
            $amazon_marketplace = $amazon_order[0]['marketplace'];
            $amazon_id = $amazon_order[0]['amazon_id'];
        } else {
            $amazon_marketplace = '';
            $amazon_id = 0;
        }

        //obtenemos el campo caja_sorpresa almacenado al crear el pickpack del pedido y que indica si contiene caja sorpresa
        $caja_sorpresa = (int)$this->object->caja_sorpresa;

        // Número de pedidos del cliente
        $sql_numero_pedidos = "SELECT COUNT(id_order) AS num_pedidos FROM lafrips_orders WHERE id_customer = ".$id_cliente." AND valid = 1;";
        $numero_pedidos = Db::getInstance()->ExecuteS($sql_numero_pedidos);
        $numero_pedidos = $numero_pedidos[0]['num_pedidos'];  
                
        

        // Sacamos los mensajes del pedido.         
        // Mensajes de "Pedido manual" si lo hay por ser pedido creado a mano por empleado, este solo se encuentra en lafrips_message
        $sql_mensajes_pedido_manual = "SELECT date_add, message FROM lafrips_message WHERE id_order = ".$id_pedido." AND message LIKE '%Pedido manual --%';";
        $mensajes_pedido_manual = Db::getInstance()->ExecuteS($sql_mensajes_pedido_manual);        

        // Mensajes sobre pedido de clientes y empleados. Son tanto el mensaje que puede dejar el cliente en el carro de compra como los posteriores que puede dejar sobre el pedido en el área de cliente/pedidos y además los mensajes tanto privados como públicos que dejan los empleados sobre el pedido dentro de la ficha de pedido.
        $sql_mensajes_sobre_pedido = "SELECT cum.id_employee AS id_empleado, CONCAT(emp.firstname,' ',emp.lastname) AS nombre_empleado, cum.message AS mensaje, cum.private AS privado, cum.date_add AS fecha
        FROM lafrips_customer_message cum
        JOIN lafrips_customer_thread cut ON cut.id_customer_thread = cum.id_customer_thread
        LEFT JOIN lafrips_employee emp ON emp.id_employee = cum.id_employee
        WHERE cut.id_order = ".$id_pedido."
        ORDER BY cum.date_add DESC;";
        $mensajes_sobre_pedido = Db::getInstance()->ExecuteS($sql_mensajes_sobre_pedido);
        
        //vamos a crear un array key=>value que contenga todos los mensajes que haya que mostrar sobre el pedido (no sobre cliente o mensaje de envoltorio regalo). Key será la parte que contiene la fecha y persona que creó el mensaje y value el mensaje en si. Primero sacamos si existe mensaje de pedido manual para que vaya el primero (quedaría más abajo) y después el resto por fecha de creación
        $todos_mensajes_pedido = array();

        //si hay mensaje de pedido manual:
        if($mensajes_pedido_manual){
            $fecha = date_create($mensajes_pedido_manual[0]['date_add']); 
            $fecha = date_format($fecha, 'd-m-Y H:i:s');
            $todos_mensajes_pedido[$fecha] = $mensajes_pedido_manual[0]['message'];
        }

        //si hay otros mensajes los vamos introduciendo al array
        if($mensajes_sobre_pedido){
            foreach ($mensajes_sobre_pedido AS $mensaje){
                //primero montamos la cabecera del mensaje, fecha con creador de mensaje
                $fecha = date_create($mensaje['fecha']); 
                $fecha = date_format($fecha, 'd-m-Y H:i:s');
                //si el id de empleado es 0 es un mensaje del cliente
                if ($mensaje['id_empleado'] != 0){
                    $mensajeador = $mensaje['nombre_empleado'];
                } else {
                    $mensajeador = 'CLIENTE: '.$nombre_cliente;
                }
                //si el mensaje es privado lo indicamos
                if ($mensaje['privado'] != 0){
                    $privado = '<b>PRIVADO</b>';
                } else {
                    $privado = '';
                }
                $cabecera = $fecha.'<b> '.$mensajeador.'</b>  '.$privado;

                //ahora sacamos el mensaje
                $mensaje = $mensaje['mensaje'];

                //introducimos todo al array de mensajes
                $todos_mensajes_pedido[$cabecera] = $mensaje;
            }           

        }
   
        //info de productos en pedido, sacamos también si tuvo incidencia en picking y packing desde la tabla lafrips_pick_pack_productos
        //30/03/2022 Añadimos left join a productos_vendidos_sin_stock para sacar si el producto era sin stock y dropshipping y poder mostrar un mensaje en la vista del contenido del pedido
        $sql_productos_pedido = "SELECT ode.product_id AS id_producto, ode.product_attribute_id AS id_atributo, ode.product_name AS nombre_completo,    
        ode.product_reference AS referencia_producto, ode.product_ean13 AS ean, ode.product_quantity AS cantidad, ode.unit_price_tax_incl AS precio_producto,  
        CONCAT( 'http://lafrikileria.com', '/', img.id_image, '-home_default/', 
        pla.link_rewrite, '.', 'jpg') AS 'imagen', img.id_image AS 'existe_imagen', 
        CASE
        WHEN pro.cache_is_pack = 0 THEN wpl.location
        WHEN pro.cache_is_pack = 1 THEN CONCAT(
            (CASE
            WHEN wpl.location IS NULL THEN 'NO'
            WHEN wpl.location = '' THEN 'NO'
            ELSE CONCAT('P',wpl.location)
            END)
            ,' / ',(SELECT GROUP_CONCAT(location SEPARATOR ' / ') FROM lafrips_warehouse_product_location WHERE id_product IN (SELECT id_product_item FROM lafrips_pack WHERE id_product_pack = ode.product_id) AND id_warehouse = 1))
        END
        AS 'localizacion',
        loc.r_location AS 'localizacion_repo',
        (SELECT SUM(physical_quantity) FROM lafrips_stock 
        WHERE id_product = ode.product_id
        AND id_product_attribute = ode.product_attribute_id 
        AND id_warehouse = 1) AS 'stock_online',
        (SELECT SUM(physical_quantity) FROM lafrips_stock 
        WHERE id_product = ode.product_id
        AND id_product_attribute = ode.product_attribute_id  
        AND id_warehouse = 4) AS 'stock_tienda',
        ppp.ok_picking AS ok_picking, ppp.ok_packing AS ok_packing, ppp.incidencia_picking AS incidencia_picking, ppp.incidencia_packing AS incidencia_packing,
        IFNULL(pvs.dropshipping, 0) AS dropshipping
        FROM lafrips_product pro      
        JOIN lafrips_order_detail ode ON pro.id_product = ode.product_id   
        JOIN lafrips_product_lang pla ON pla.id_product = ode.product_id AND pla.id_lang = 1  
        LEFT JOIN lafrips_image img ON ode.product_id = img.id_product
            AND img.cover = 1
        LEFT JOIN lafrips_warehouse_product_location wpl ON wpl.id_product = ode.product_id AND wpl.id_product_attribute = ode.product_attribute_id     
        LEFT JOIN lafrips_localizaciones loc ON loc.id_product = ode.product_id AND loc.id_product_attribute = ode.product_attribute_id
        LEFT JOIN lafrips_pick_pack_productos ppp ON ppp.id_pickpack_order = ode.id_order AND ppp.id_product = ode.product_id AND ppp.id_product_attribute = ode.product_attribute_id
        LEFT JOIN lafrips_productos_vendidos_sin_stock pvs ON pvs.id_order = ode.id_order 
			AND pvs.id_product = ode.product_id AND pvs.id_product_attribute = ode.product_attribute_id
        WHERE ode.id_order = ".$id_pedido." 
        AND wpl.id_warehouse = 1 
        GROUP BY ode.product_name
        ORDER BY wpl.location;";

        $productos_pedido = Db::getInstance()->ExecuteS($sql_productos_pedido);

        //sacamos la info de la tabla pick_pack_estados para pasarla al tpl
        $sql_pickpack_estados = 'SELECT id_pickpack_estados, nombre_estado, color FROM lafrips_pick_pack_estados;';
        $pickpack_estados = Db::getInstance()->ExecuteS($sql_pickpack_estados);

        //queremos mostrar en gestion pickpack cuales son las cajas sorpresa, si las hay, que corresponden al pedido padre. Buscamos el id_order en frik_cajas_sorpresa y si saca resultados guardamos tanto el id del pedido que contiene la caja, como su nombre de caja completo y el mensaje del cliente para mostrarlo en la visión del pedido pickpack
        $sql_pedidos_cajas = 'SELECT id_order_caja, id_customization, id_product, id_product_attribute 
        FROM frik_cajas_sorpresa
        WHERE id_order = '.$id_pedido;
        $pedidos_cajas = Db::getInstance()->ExecuteS($sql_pedidos_cajas);

        $mensajes_cliente_cajas = array();

        if (count($pedidos_cajas) > 0) {
            foreach ($pedidos_cajas AS $pedido_caja) {
                //obtenemos el nombre completo de la caja vendida en la tabla order_detail
                $sql_nombre_caja = 'SELECT product_name FROM lafrips_order_detail 
                WHERE id_order = '.$id_pedido.'
                AND product_id = '.$pedido_caja['id_product'].'
                AND product_attribute_id = '.$pedido_caja['id_product_attribute'];
                
                if (Db::getInstance()->ExecuteS($sql_nombre_caja)) {
                    $nombre_caja = '<b>'.Db::getInstance()->ExecuteS($sql_nombre_caja)[0]['product_name'].'</b><br><br>'; 
                } else {
                    $nombre_caja = ''; 
                }    

                $id_customization_caja_sorpresa = $pedido_caja['id_customization'];

                //buscamos el mensaje de cliente
                $sql_mensaje_cliente_caja = 'SELECT value FROM lafrips_customized_data
                WHERE id_customization = '.$id_customization_caja_sorpresa;
                if ($mensaje_cliente_caja = Db::getInstance()->ExecuteS($sql_mensaje_cliente_caja)) {
                    $mensaje = $nombre_caja.$mensaje_cliente_caja[0]['value'];
                } else {
                    $mensaje = 'Error con mensaje de cliente / No hay mensaje';
                }

                $mensajes_cliente_cajas[$pedido_caja['id_order_caja']] = $mensaje;
            }            
        }     
        
        //15/09/2020 queremos añadir un enlace al pedido en admin orders
        //https://lafrikileria.com/lfadminia/index.php?controller=AdminOrders&token=b192540700c383eeb6b26f1da43998da&id_order=225726&vieworder
        $token_adminorders = Tools::getAdminTokenLite('AdminOrders');
        $url_base = Tools::getHttpHost(true).__PS_BASE_URI__;
        $url_pedido = $url_base.'lfadminia/index.php?controller=AdminOrders&token='.$token_adminorders.'&id_order='.$id_pedido.'&vieworder';

        //queremos añadir enlace a seguimiento si lo hay
        $sql_tracking = 'SELECT car.name AS name, oca.tracking_number AS tracking
        FROM lafrips_order_carrier oca
        JOIN lafrips_carrier car ON car.id_carrier = oca.id_carrier
        WHERE id_order = '.$id_pedido;
        $tracking = Db::getInstance()->ExecuteS($sql_tracking);

        if ($tracking) {            
            $nombre_transportista = $tracking[0]['name'];
            $tracking = $tracking[0]['tracking'];
            //si hay número de tracking montamos la url del seguimiento según el transportista, al cual diferenciamos por nombre ya que el id puede cambiar si se elimina y crea el transporte, etc. Habría que actualizarlo con nuevos transportistas
            if (preg_match('/GLS/i',$nombre_transportista )) {
                //si es GLS buscamos la url en la tabla lafrips_asm_envios - 23/02/2021 cambiada tabla a gls_envios
                $sql_tracking_gls = 'SELECT url_track FROM lafrips_gls_envios WHERE codigo_envio = "'.$tracking.'" AND id_envio_order = '.$id_pedido;
                $url_tracking = Db::getInstance()->ExecuteS($sql_tracking_gls)[0]['url_track'];
            } elseif (preg_match('/correos/i',$nombre_transportista ) && preg_match('/^((?!expres).)*$/i',$nombre_transportista )) {
                //si el nombre contiene correos pero no express
                //la url de seguiimiento para correos es tal que http://www.correos.es/comun/localizador/track.asp?numero=PS4S2T0720500920110800Q
                $url_tracking = 'http://www.correos.es/comun/localizador/track.asp?numero='.$tracking;
            } elseif (preg_match('/spring/i',$nombre_transportista )) {
                //si el nombre contiene spring 
                //la url de seguimiento para spring es tal que https://mailingtechnology.com/tracking/?tn=3SDOKC0476849
                $url_tracking = 'https://mailingtechnology.com/tracking/?tn='.$tracking;
            } else { //mientras no utilicemos otros transportistas queda así
                $url_tracking = '';
            }
        }

        if ($url_tracking && $url_tracking != '') {            
            $seguimiento = 1;
        } else {
            $url_tracking = '';
            $seguimiento = 0;
        }

        //asignamos la plantilla packing a esta vista
        $tpl = $this->context->smarty->createTemplate(dirname(__FILE__).'/../../views/templates/admin/gestionpickpack.tpl');

        // $tpl->assign('pick_pack', $this->object);  
        // $tpl->assign('nombre', $nombre);   
        // $tpl->assign('pedido', $id_pedido);  

        //asignamos a smarty la info de cliente y pedido y productos en pedido
        $tpl->assign(
            array(
                'objeto_pick_pack' => $this->object,             
                'idpedido' => $id_pedido,
                'id_cliente' => $id_cliente,
                'nombre_cliente' => $nombre_cliente,
                'direccion' => $direccion,
                'codigo_postal' => $codigo_postal,
                'ciudad' => $ciudad,
                'provincia' => $provincia,
                'pais' => $pais,
                'fecha_pedido' => $fecha_pedido,
                'pais' => $pais,
                'transporte' => $transporte,
                'estado_prestashop' => $estado_prestashop,
                'fecha_estado_prestashop' => $fecha_estado_prestashop,
                'metodo_pago' => $metodo_pago,
                'amazon' => $amazon,
                'amazon_marketplace' => $amazon_marketplace,
                'amazon_id' => $amazon_id,
                'telefono' => $telefono,
                'regalo' => $regalo,
                'mensaje_regalo' => $mensaje_regalo,
                'nota_sobre_cliente' => $nota_sobre_cliente,
                'numero_pedidos' => $numero_pedidos,
                'todos_mensajes_pedido' => $todos_mensajes_pedido,
                'productos_pedido' => $productos_pedido,
                'pickpack_estados' => $pickpack_estados,
                'mensajes_cliente_cajas' => $mensajes_cliente_cajas,
                'url_pedido' => $url_pedido,
                'url_tracking' => $url_tracking,
                'seguimiento' => $seguimiento,
                'caja_sorpresa' => $caja_sorpresa,
                'pedido_dropshipping' => $pedido_dropshipping,
                'pedido_webservice' => $pedido_webservice,
                'token' => Tools::getAdminTokenLite('AdminGestionPickpack'),
                'url_base' => Tools::getHttpHost(true).__PS_BASE_URI__.'lfadminia/',
            )
        );    
            
        return $tpl->fetch();
    }


    public function postProcess()
    {
        //para que cargue bien el controlador y se pueda filtrar
        parent::postProcess();

        //si se pulsa el botón de cambiar estado dentro de la ficha de gestión de un producto
        if (Tools::isSubmit('cambia_estado_pickpack')) {
            if ($id_nuevo_estado = Tools::getValue('nuevo_estado_pickpack')){
                $id_pedido = Tools::getValue('id_pedido');
                //obtenemos el id_pickpack desde el id_order con el método de clase
                $id_pickpack = PickPackOrder::getIdPickPackByIdOrder($id_pedido);
                //$this->errors[] = sprintf(Tools::displayError('El pedido #%d no se puede cargar'), $id_pickpack);

                //instanciamos el pedido pickpack
                $pickpack_order = new PickPackOrder((int)$id_pickpack);
                if (!Validate::isLoadedObject($pickpack_order)) {
                    $this->errors[] = sprintf(Tools::displayError('El pedido #%d no se puede cargar'), $id_pickpack);
                } else {
                    //comprobamos que el pedido no esté ya en estado al que queremos cambiar $id_nuevo_estado
                    if ($pickpack_order->id_estado_order == $id_nuevo_estado){
                        //$this->errors[] = sprintf(Tools::displayError('El pedido #%d ya se encuentra en el estado al que lo quieres cambiar'), $pickpack_order->id_pickpack_order);
                        //si estamos intentando cambiar al estado que ya tiene, redirigimos de nuevo al pedido en gestión, mostrando un mensaje de error con una variable success=2 en la url que leeremos con $smarty.get
                        $token = Tools::getAdminTokenLite('AdminGestionPickpack');
                        $url_base = Tools::getHttpHost(true).__PS_BASE_URI__.'lfadminia/';
                        $link = $url_base.'index.php?controller=AdminGestionPickpack&id_pickpack='.$id_pickpack.'&viewpick_pack&token='.$token.'&success=2';                            
                        Tools::redirectAdmin($link);
                    } else {
                        //en función de a qué estado queremos cambiar actualizaremos las fechas y el id. También actualizamos el usuario. No se puede cambiar a No comenzado, Picking abierto o packing abierto.
                        $id_empleado = Context::getContext()->employee->id;
                        $nombre_empleado = Context::getContext()->employee->firstname; 

                        //sacamos la info de la tabla pick_pack_estados
                        $sql_pickpack_estados = 'SELECT id_pickpack_estados, nombre_estado FROM lafrips_pick_pack_estados;';
                        $pickpack_estados = Db::getInstance()->ExecuteS($sql_pickpack_estados);
                        
                        //los estados disponibles de la tabla son 3 - incidencia picking, 4 - picking finalizado, 5 - incidencia packing, 6 - paquete enviado, 7- cancelado
                        if ($id_nuevo_estado == 3){ //incidencia picking
                            $tipo = 'picking';
                            $finalizado = '0';
                            $finalizado_bulk = '';
                            $fecha_fin = '';
                            $comenzadopickpacking = 'comenzado_picking = 1,';
                            $incidenciapickpacking = 'incidencia_picking = 1,';
                        } elseif ($id_nuevo_estado == 4){ //picking finalizado
                            $tipo = 'picking';
                            $finalizado = '0';
                            $finalizado_bulk = 'picking_finalizado_bulk = 1,';
                            $fecha_fin = 'date_fin_picking = NOW(),';
                            $comenzadopickpacking = 'comenzado_picking = 1,';
                            $incidenciapickpacking = '';
                        } elseif ($id_nuevo_estado == 5){ //incidencia packing
                            $tipo = 'packing';
                            $finalizado = '0';
                            $finalizado_bulk = '';
                            $fecha_fin = '';
                            $comenzadopickpacking = 'comenzado_packing = 1,';
                            $incidenciapickpacking = 'incidencia_packing = 1,';
                        } elseif ($id_nuevo_estado == 6){ //paquete enviado
                            $tipo = 'packing';
                            $finalizado = '1';
                            $finalizado_bulk = 'packing_finalizado_bulk = 1,';
                            $fecha_fin = 'date_fin_packing = NOW(),';
                            $comenzadopickpacking = 'comenzado_packing = 1,';
                            $incidenciapickpacking = '';
                        } elseif ($id_nuevo_estado == 7){ //cancelado
                            //el mensaje de cancelado y el usuario serán como el de packing
                            $tipo = 'packing';
                            $finalizado = '0';  
                            $finalizado_bulk = '';                          
                            $fecha_fin = '';
                            $comenzadopickpacking = '';
                            $incidenciapickpacking = '';
                        } else {
                            $this->errors[] = sprintf(Tools::displayError('Se produjo un error al cambiar el estado del pedido #%d'), $pickpack_order->id_pickpack_order);
                        } 
                        
                        $nombre_estado = $pickpack_estados[$id_nuevo_estado-1]['nombre_estado'];
                        //desde aquí no puedo poner el span por las dobles comillas, prestashop parece que no admite \"
                        $comentario = '- Pedido pasado desde Gestión a estado '.$nombre_estado.' por <i> '.$nombre_empleado.' </i>';      
                        $comentario_cambio = 'comentario_'.$tipo.' = CONCAT(comentario_'.$tipo.',"'.$comentario.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"<br>"),';
                                                
                        $sql_update_pickpack_estado_pedido = 'UPDATE lafrips_pick_pack
                                SET 
                                comenzado = 1,                       
                                id_estado_order = '.$id_nuevo_estado.',
                                id_employee_'.$tipo.' = '.$id_empleado.',
                                nombre_employee_'.$tipo.' = "'.$nombre_empleado.'",
                                finalizado = '.$finalizado.',
                                '.$incidenciapickpacking.'
                                '.$comenzadopickpacking.'
                                '.$comentario_cambio.'
                                '.$finalizado_bulk.'                       
                                '.$fecha_fin.'
                                date_upd = NOW()
                                WHERE id_pickpack = '.$id_pickpack.';';

                        //$this->errors[] = sprintf(Tools::displayError($sql_update_pickpack_estado_pedido));
                        $ejecucion = Db::getInstance()->execute($sql_update_pickpack_estado_pedido);
                        if (!$ejecucion){
                            $this->errors[] = sprintf(Tools::displayError('No se pudo cambiar el estado del pedido #%d'), $pickpack_order->id_pickpack_order);
                        } else {
                            //23/02/2021 Si el cambio ha sido a Paquete Enviado (Packing finalizado) queremos introducir el pedido en la tabla de cambios de estado automáticos para pasarlo a enviado (y enviar email de seguimiento si es GLS)
                            //01/03/2021 Si es de ClickCanarias lo ignoramos para el cambio, y si es virtual, lo marcamos para pasar a entregado en lugar de enviado
                            if ($id_nuevo_estado == 6){ 
                                //primero nos aseguramos de que el pedido no esté en estado Enviado, Cancelado, Entregado, Error pago , virtual(cajas)       
                                $id_estado_enviado = (int)Configuration::get(PS_OS_SHIPPING);
                                $id_estado_entregado = (int)Configuration::get(PS_OS_DELIVERED);
                                $id_estado_cancelado = (int)Configuration::get(PS_OS_CANCELED);
                                $id_estado_errorpago = (int)Configuration::get(PS_OS_ERROR);
                                $id_estado_canarias = (int)Configuration::get(CLICKCANARIAS_STATE);
                                //sacamos id_status de Pedido virtual
                                $sql_id_pedido_virtual = "SELECT ost.id_order_state as id_pedido_virtual
                                FROM lafrips_order_state ost
                                JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
                                WHERE osl.name = 'Pedido Virtual'
                                AND ost.deleted = 0";
                                $id_pedido_virtual = (int)Db::getInstance()->executeS($sql_id_pedido_virtual)[0]['id_pedido_virtual'];

                                //sacamos id_status de Pendiente envío fuera península.
                                $sql_id_pendiente_fuera = "SELECT ost.id_order_state as id_pendiente_fuera
                                FROM lafrips_order_state ost
                                JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
                                WHERE osl.name = 'Pendiente envío fuera península.'
                                AND ost.deleted = 0";
                                $id_pendiente_fuera = (int)Db::getInstance()->executeS($sql_id_pendiente_fuera)[0]['id_pendiente_fuera'];

                                //instanciamos pedido
                                $order = new Order($id_pedido);
                                $current_state = (int)$order->current_state;
                                
                                //si el estado actual no es Enviado, Cancelado, Entregado, Error pago, canarias, o si es virtual enviamos a la tabla. Si es virtual lo pasaremos a entregado
                                if (($current_state !== $id_estado_enviado) && ($current_state !== $id_estado_entregado) && ($current_state !== $id_estado_cancelado) && ($current_state !== $id_estado_errorpago) && ($current_state !== $id_estado_canarias) && ($current_state !== $id_pendiente_fuera) || ($current_state == $id_pedido_virtual)) {
                                    if ($current_state == $id_pedido_virtual) {
                                        $cambio = 'entregado';
                                    } else {
                                        $cambio = 'enviado';
                                    }
                                    //usamos esta sql para asegurarnos de no insertar duplicados de un order si por lo que sea se cierra un packing varias veces
                                    $insert_frik_cambio_enviado = "INSERT INTO frik_cambio_enviado 
                                        (cambio, id_order, id_order_status, id_empleado, date_add) 
                                        SELECT '".$cambio."',
                                        ".$id_pedido." ,
                                        ".$current_state." ,				
                                        ".$id_empleado." ,             
                                        NOW()
                                        FROM dual
                                        WHERE NOT EXISTS (SELECT id_order FROM frik_cambio_enviado WHERE id_order = ".$id_pedido.")LIMIT 1";

                                    Db::getInstance()->Execute($insert_frik_cambio_enviado);
                                }
                            }
                            //si el cambio tiene exito redirigimos a la url del pedido pickpack con una variable get success=1 en la url, que se sacará si está mediante smarty para mostrar mensaje desde tpl
                            $token = Tools::getAdminTokenLite('AdminGestionPickpack');
                            $url_base = Tools::getHttpHost(true).__PS_BASE_URI__.'lfadminia/';
                            $link = $url_base.'index.php?controller=AdminGestionPickpack&id_pickpack='.$id_pickpack.'&viewpick_pack&token='.$token.'&success=1';                            
                            Tools::redirectAdmin($link);
                        }             
                    }
                }




            }

        }

        //Si llegamos trás pulsar el botón Finalizar en la pantalla de Packing, recogemos los valores - NO VÁLIDO, hacemos packing aparte!!       
        // if (Tools::isSubmit('fin_packing')) {
        //     if (Tools::getValue('comentario_packing')){
        //         $comentario_packing = Tools::getValue('comentario_packing');
        //     } else {
        //         $comentario_packing = '';
        //     }

        //     if (Tools::getValue('numero_productos')){
        //         $numero_productos = Tools::getValue('numero_productos');
        //     } 

        //     if (Tools::getValue('id_pedido')){
        //         $id_pedido = Tools::getValue('id_pedido');
        //     } 
            
        //     if (Tools::getValue('checkbox_obsequio') && Tools::getValue('checkbox_obsequio') == 'on'){
        //         $obsequio = 1;
        //     } else {
        //         $obsequio = 0;
        //     }

        //     if (Tools::getValue('checkbox_regalo') && Tools::getValue('checkbox_regalo') == 'on'){
        //         $regalo = 1;
        //     } else {
        //         $regalo = 0;
        //     }
        //     //sacamos los id_product y id_product_atribute de cada producto. Como $_POST contiene los valores del formulario en el orden de este, saco el contenido desde el primero hasta $numero_productos y los almaceno en dos variables, $id_product y $id_product_atribute después de separar la cadena que llega (tipo 12323_2345)
        //     $contador = 1;
        //     foreach($_POST as $key => $value) {
        //         if ($contador <= $numero_productos){
        //             //$key contiene el id unido al id atributo con barra baja
        //             $ids = explode('_', $key);
        //             $id_product = $ids[0];
        //             $id_product_attribute = $ids[1];
        //             //$value contiene 1 o 0 si el radio button estaba en ok o no
        //             $correcto = $value;
        //             if (!$correcto){
        //                 $incidencia = 1;
        //             }
        //             //Creamos la sql para hacer update de los productos en picking comprobando producto a producto si está en lafrips_pick_pack_productos (si el update da resultado) y si no lo está lo metemos con un insert, ya que puede darse el caso de que se cambie o añada algún producto si hay incidencia. 
        //             $sql_update_producto = 'UPDATE lafrips_pick_pack_productos 
        //             SET ok_packing = '.$correcto.'
        //             WHERE id_product = '.$id_product.'
        //             AND id_product_attribute = '.$id_product_attribute.'
        //             AND id_pickpack_order = '.$id_pedido.';';
        //             //si no funciona el update, hacemos insert del producto
        //             if (!Db::getInstance()->Execute($sql_update_producto)) {
        //             Db::getInstance()->Execute("INSERT INTO lafrips_pick_pack_productos (id_pickpack_order, id_product, id_product_attribute, ok_packing) VALUES (".$id_pedido." ,".$id_product." ,".$id_product_attribute." ,".$correcto.")");
        //             }
        //             //Si algún producto que estaba en un packing anterior ya no se recibe en este packing, pondremos a 1 "eliminado". SIN HACERRRRR!!        

        //             $contador++;
        //         }
        //     }
        //     $id_pickpack = PickPackOrder::getIdPickPackByIdOrder($id_pedido);
        //     if ($incidencia){
        //     //si hay incidencia ponemos o dejamos el estado Incidencia Packing
        //     $id_estado_order = 5;
        //     $date_fin_packing = '0000-00-00 00:00:00';
        //     $finalizado = 0;
        //     } else {
        //     //si no hay incidencia ponemos el estado Enviado (Packing Finalizado) y metemos la fecha y finalizado 1
        //     $id_estado_order = 6;
        //     $date_fin_packing = date("Y-m-d H:i:s");
        //     $finalizado = 1;
        //     }
            
        //     $id_empleado = Context::getContext()->employee->id;
        //     $nombre_empleado = Context::getContext()->employee->firstname;
            
        //     $sql_update_pickpack_pedido = 'UPDATE lafrips_pick_pack
        //     SET
        //     comenzado = 1,
        //     id_estado_order = '.$id_estado_order.',
        //     id_employee_packing = '.$id_empleado.',
        //     nombre_employee_packing = "'.$nombre_empleado.'",
        //     comentario_packing = "'.$comentario_packing.'",
        //     regalo = '.$regalo.',
        //     obsequio = '.$obsequio.',
        //     finalizado = '.$finalizado.',
        //     date_fin_packing = "'.$date_fin_packing.'",
        //     date_upd = NOW()
        //     WHERE id_pickpack = '.$id_pickpack.';';

        //     Db::getInstance()->execute($sql_update_pickpack_pedido);

        // }

    }

    //Cambiar estado pickpack a varios pedidos al mismo tiempo, la función se llama como en el array de bulkactions declarado arriba, esta cambiara a estado picking finalizado
    public function processBulkUpdateIdEstadoOrderFinPicking()
    {
        
        if (Tools::isSubmit('submitBulkupdateIdEstadoOrderFinPicking'.$this->table)){ 
            //sacamos los pedidos de pickpack que tienen el check de bulkactions marcado. pick_packBox es el nombre que se pone por defecto al crear el controlador (nombre tabla + Box[]), lo que se recoge es el id_pickpack, identificador en tabla y no id de pedido
            foreach (Tools::getValue('pick_packBox') as $id_pickpack) {
                //instanciamos cada pedido pickpack
                $pickpack_order = new PickPackOrder((int)$id_pickpack);
                if (!Validate::isLoadedObject($pickpack_order)) {
                    $this->errors[] = sprintf(Tools::displayError('El pedido #%d no se puede cargar'), $id_pickpack);
                } else {
                    //comprobamos que el pedido no esté ya en estado Picking Finalizado id_estado_order = 4
                    if ($pickpack_order->id_estado_order == 4){
                        $this->errors[] = sprintf(Tools::displayError('El pedido #%d ya se encuentra en estado Picking Finalizado'), $pickpack_order->id_pickpack_order);
                    } else {
                        //cambiamos el estado del pedido, poniendo en 1 el campo picking_finalizado_bulk para indicar que se ha hecho así, y poniendo la fecha de fin_picking y date_upd. También actualizamos el usuario
                        $id_empleado = Context::getContext()->employee->id;
                        $nombre_empleado = Context::getContext()->employee->firstname;  
                        
                        //desde aquí no puedo poner el span por las dobles comillas, prestashop parece que no admite \"
                        $comentario = '- Pedido pasado desde Gestión a Picking Finalizado por <i> '.$nombre_empleado.' </i>';      
                        $comentario_picking = 'comentario_picking = CONCAT(comentario_picking,"'.$comentario.'",NOW(),"<br>"),';
                                                
                        $sql_update_pickpack_estado_pedido = 'UPDATE lafrips_pick_pack
                                SET 
                                comenzado = 1,                       
                                id_estado_order = 4,
                                id_employee_picking = '.$id_empleado.',
                                nombre_employee_picking = "'.$nombre_empleado.'",
                                '.$comentario_picking.'
                                picking_finalizado_bulk = 1,                       
                                date_fin_picking = NOW(),
                                date_upd = NOW()
                                WHERE id_pickpack = '.$id_pickpack.';';

                        //$this->errors[] = sprintf(Tools::displayError($sql_update_pickpack_estado_pedido));
                        $ejecucion = Db::getInstance()->execute($sql_update_pickpack_estado_pedido);
                        if (!$ejecucion){
                            $this->errors[] = sprintf(Tools::displayError('No se pudo cambiar el estado del pedido #%d'), $pickpack_order->id_pickpack_order);
                        }                        
                    }
                }
            }
        }
    }

    //Cambiar estado pickpack a varios pedidos al mismo tiempo, la función se llama como en el array de bulkactions declarado arriba, esta cambiara a estado packing finalizado, es decir, paquete enviado
    public function processBulkUpdateIdEstadoOrderFinPacking()
    {
        
        if (Tools::isSubmit('submitBulkupdateIdEstadoOrderFinPacking'.$this->table)){ 
            //sacamos los pedidos de pickpack que tienen el check de bulkactions marcado. pick_packBox es el nombre que se pone por defecto al crear el controlador (nombre tabla + Box[]), lo que se recoge es el id_pickpack, identificador en tabla y no id de pedido
            foreach (Tools::getValue('pick_packBox') as $id_pickpack) {
                //instanciamos cada pedido pickpack
                $pickpack_order = new PickPackOrder((int)$id_pickpack);
                if (!Validate::isLoadedObject($pickpack_order)) {
                    $this->errors[] = sprintf(Tools::displayError('El pedido #%d no se puede cargar'), $id_pickpack);
                } else {
                    //comprobamos que el pedido no esté ya en estado Packing Finalizado o Paquete Enviado id_estado_order = 6
                    if ($pickpack_order->id_estado_order == 6){
                        $this->errors[] = sprintf(Tools::displayError('El pedido #%d ya se encuentra en estado Paquete Enviado'), $pickpack_order->id_pickpack_order);
                    } else {
                        //cambiamos el estado del pedido, poniendo en 1 el campo packing_finalizado_bulk para indicar que se ha hecho así, y poniendo la fecha de fin_packing y date_upd. También actualizamos el usuario
                        $id_empleado = Context::getContext()->employee->id;
                        $nombre_empleado = Context::getContext()->employee->firstname;  
                        
                        //desde aquí no puedo poner el span por las dobles comillas, prestashop parece que no admite \"
                        $comentario = '- Pedido pasado desde Gestión a Paquete Enviado por <i> '.$nombre_empleado.' </i>';      
                        $comentario_packing = 'comentario_packing = CONCAT(comentario_packing,"'.$comentario.'",NOW(),"<br>"),';
                                                
                        $sql_update_pickpack_estado_pedido = 'UPDATE lafrips_pick_pack
                                SET 
                                comenzado = 1,                       
                                id_estado_order = 6,
                                id_employee_packing = '.$id_empleado.',
                                nombre_employee_packing = "'.$nombre_empleado.'",
                                finalizado = 1,
                                '.$comentario_packing.'
                                packing_finalizado_bulk = 1,                       
                                date_fin_packing = NOW(),
                                date_upd = NOW()
                                WHERE id_pickpack = '.$id_pickpack.';';

                        //$this->errors[] = sprintf(Tools::displayError($sql_update_pickpack_estado_pedido));
                        $ejecucion = Db::getInstance()->execute($sql_update_pickpack_estado_pedido);
                        if (!$ejecucion){
                            $this->errors[] = sprintf(Tools::displayError('No se pudo cambiar el estado del pedido #%d'), $pickpack_order->id_pickpack_order);
                        } else {
                            //23/02/2021 Si el cambio ha sido a Paquete Enviado (Packing finalizado) queremos introducir el pedido en la tabla de cambios de estado automáticos para pasarlo a enviado (y enviar email de seguimiento si es GLS)
                            //01/03/2021 Si es de ClickCanarias lo ignoramos para el cambio, y si es virtual, lo marcamos para pasar a entregado en lugar de enviado                             
                            //primero nos aseguramos de que el pedido no esté en estado Enviado, Cancelado, Entregado, Error pago, virtual(cajas), los pedidos de ClickCanarias, estados "Pendiente envío fuera península." y "Pagado con ClickCanarias"            
                            $id_estado_enviado = (int)Configuration::get(PS_OS_SHIPPING);
                            $id_estado_entregado = (int)Configuration::get(PS_OS_DELIVERED);
                            $id_estado_cancelado = (int)Configuration::get(PS_OS_CANCELED);
                            $id_estado_errorpago = (int)Configuration::get(PS_OS_ERROR);
                            $id_estado_canarias = (int)Configuration::get(CLICKCANARIAS_STATE);
                            //sacamos id_status de Pedido virtual
                            $sql_id_pedido_virtual = "SELECT ost.id_order_state as id_pedido_virtual
                            FROM lafrips_order_state ost
                            JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
                            WHERE osl.name = 'Pedido Virtual'
                            AND ost.deleted = 0";
                            $id_pedido_virtual = (int)Db::getInstance()->executeS($sql_id_pedido_virtual)[0]['id_pedido_virtual'];

                            //sacamos id_status de Pendiente envío fuera península.
                            $sql_id_pendiente_fuera = "SELECT ost.id_order_state as id_pendiente_fuera
                            FROM lafrips_order_state ost
                            JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
                            WHERE osl.name = 'Pendiente envío fuera península.'
                            AND ost.deleted = 0";
                            $id_pendiente_fuera = (int)Db::getInstance()->executeS($sql_id_pendiente_fuera)[0]['id_pendiente_fuera'];

                            //instanciamos pedido
                            $id_pedido = $pickpack_order->id_pickpack_order;
                            $order = new Order($id_pedido);
                            $current_state = (int)$order->current_state;
                            
                            //si el estado actual no es Enviado, Cancelado, Entregado, Error pago, enviamos a la tabla
                            //añadimos virtual y los de Canarias
                            if (($current_state !== $id_estado_enviado) && ($current_state !== $id_estado_entregado) && ($current_state !== $id_estado_cancelado) && ($current_state !== $id_estado_errorpago) && ($current_state !== $id_estado_canarias) && ($current_state !== $id_pendiente_fuera) || ($current_state == $id_pedido_virtual)) {
                                if ($current_state == $id_pedido_virtual) {
                                    $cambio = 'entregado';
                                } else {
                                    $cambio = 'enviado';
                                }
                                //usamos esta sql para asegurarnos de no insertar duplicados de un order si por lo que sea se cierra un packing varias veces
                                $insert_frik_cambio_enviado = "INSERT INTO frik_cambio_enviado 
                                    (cambio, id_order, id_order_status, id_empleado, date_add) 
                                    SELECT '".$cambio."',
                                    ".$id_pedido." ,
                                    ".$current_state." ,				
                                    ".$id_empleado." ,             
                                    NOW()
                                    FROM dual
                                    WHERE NOT EXISTS (SELECT id_order FROM frik_cambio_enviado WHERE id_order = ".$id_pedido.")LIMIT 1";

                                Db::getInstance()->Execute($insert_frik_cambio_enviado);
                                
                            }
                        }              
                    }
                }
            }
        }
    }

    // public function renderList()
    // {
    //     // 'submitBulkupdateIdOrderStatepick_pack' nombre creado automáticamente para el submit
    //     if (Tools::isSubmit('submitBulkupdateIdEstadoOrder'.$this->table)) {
    //         if (Tools::getIsset('cancel')) {
    //             Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
    //         }

    //         $this->tpl_list_vars['updateOrderStatus_mode'] = true;
    //         $this->tpl_list_vars['order_statuses'] = $this->statuses_array;
    //         $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
    //         $this->tpl_list_vars['POST'] = $_POST;
    //     }

    //     return parent::renderList();
    // }

    // public function getContent()
    // {
        /**
         * If values have been submitted in the form, process.
         */
        // if (((bool)Tools::isSubmit('fin_packing')) == true) {
        //     $comentario = Tools::getValue('comentario');
        //     echo '<h1>hey '.$comentario.'</h1>';
        //     $this->postProcess();            
        // }
        

        // $this->context->smarty->assign('module_dir', $this->_path);

        // $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        // return $output.$this->renderForm();
    //}

    

}

?>

