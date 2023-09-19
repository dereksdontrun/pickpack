<?php

require_once(dirname(__FILE__).'/../../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../../init.php');

//https://lafrikileria.com/modules/pickpack/controllers/admin/pickingpacking/classes/Recepciones.php
//https://lafrikileria.com/test/modules/pickpack/controllers/admin/pickingpacking/classes/Recepciones.php

//13/07/2023 Proceso programado que recogerá los nuevos datos de la tabla lafrips_recepciones y los gestionará para recibir productos a pedidos pendientes de materiales.

//obtendremos línea a línea las líneas de la tabla que estén con procesando = 0 y finalizado = 0 (y error = 0) e inmediatamente marcaremos procesando 1 para evitar una posible ejecución inmediata de este mismo proceso que podría romper los pedidos. Se procesará una línea cada vez, recibiendo las unidades que ponga de ese producto para ese pedido, comprobando el estado de pedido y si está finalizado, para marcar como terminado y pasar a otra línea, en lugar de agrupar todas las líneas de un pedido.

$a = new Recepciones();

class Recepciones
{
    //almacenamos max_execution_time para saber cuando parar si hemos puesto un limit muy alto. El valor que almacenamos es el 90% de la variable php, de modo que si lo superamos no continuaremos con más productos
    public $my_max_execution_time;
    //momento de inicio del script, en segundos, para comparar con max_execution_time
    public $inicio;   
    //un segundo max execution time definido a x minutos para programarlo dos/tres veces por hora sin que se solapen. Pongo 25 minutos para dos veces (1500 sec) o 18 minutos para 3 veces ()
    //18 minutos 1080 segundos
    //8 minutos (480 sec) para ejecutarlo cada 10, a ver que tal, ya que parece que siempre se para a los 10 productos o más o menos 5 mintuos
    public $max_execution_time_x_minutos = 480;

    //directorio para log    
    public $log_file = _PS_ROOT_DIR_.'/modules/pickpack/log/recepciones.txt';
    public $error = 0;

    public $linea;

    public $contador_lineas = 0;
    public $contador_errores = 0;
    public $contador_correctos = 0;   

    public function __construct() {
        $this->inicio = time();
        $this->my_max_execution_time = ini_get('max_execution_time')*0.9; //90% de max_execution_time   

        //llamamos a función para que analice productos atascados en "procesando"
        $this->checkProcesando();
 
        $this->start();
    }

    public function start() {
        $exit = 0;

        do {
            $this->linea = null;       

            if ($this->getLinea()) {

                $this->contador_lineas++;

                if ($this->contador_lineas == 1) {
                    file_put_contents($this->log_file, date('Y-m-d H:i:s').' --------------------------------------------------'.PHP_EOL, FILE_APPEND);
                    file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Comienzo proceso Recepciones'.PHP_EOL, FILE_APPEND);
                    file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Tiempo máximo ejecución - my_max_execution_time = '.$this->my_max_execution_time.PHP_EOL, FILE_APPEND);
                }

                file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Procesando línea '.$this->linea['id_recepciones'].' , producto '.$this->linea['id_product'].'_'.$this->linea['id_product_attribute'].PHP_EOL, FILE_APPEND);

                //comienza el proceso real de la línea
                if ($this->procesaLinea()) {
                    $this->contador_correctos++;
                    $this->setFinalizado();
                } else {
                    $this->contador_errores++;
                } 

            } else {
                //no hay líneas para trabajar en la tabla, interrumpimos el proceso, salimos del do - while
                break;
            }
            
            if (((time() - $this->inicio) >= $this->my_max_execution_time) || ((time() - $this->inicio) >= $this->max_execution_time_x_minutos)) {
                $exit = 1;

                file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Tiempo ejecución alcanzando límite'.PHP_EOL, FILE_APPEND);
            }
        } while (!$exit);

        if ($this->contador_lineas > 0) {
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Fin proceso. Líneas procesadas = '.$this->contador_lineas.PHP_EOL, FILE_APPEND);
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Procesos correctos = '.$this->contador_correctos.PHP_EOL, FILE_APPEND);
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Procesos con error = '.$this->contador_errores.PHP_EOL, FILE_APPEND);
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Tiempo ejecución - '.(time() - $this->inicio).PHP_EOL, FILE_APPEND);
        }

        if ($this->error) {
            $this->enviaEmail();
        }  

        exit;
    }

    //obtiene la primera línea sin procesar de la tabla lafrips_recepciones y devuelve false o true
    public function getLinea() {
        //obtenemos la primera línea sin finalizar de lafrips_recepciones
        $sql_get_linea = "SELECT * 
        FROM lafrips_recepciones 
        WHERE finalizado = 0
        AND procesando = 0
        AND error = 0
        ORDER BY date_add ASC";        

        if ($this->linea = Db::getInstance()->getRow($sql_get_linea)) {
            //con los datos de la línea hacemos update para poner procesando a 1 y devolvemos la info.
            $sql_procesando = "UPDATE lafrips_recepciones
            SET
            procesando = 1, 
            date_procesando = NOW()
            WHERE id_recepciones = ".$this->linea['id_recepciones'];

            Db::getInstance()->executeS($sql_procesando);              

            return true;
        } else {
            //para evitar escribir sobre el archivo log cada vez aunque no haya líneas lo haremos solo si contador es mayor que 0
            if ($this->contador_lineas > 0) {
                file_put_contents($this->log_file, date('Y-m-d H:i:s').' - No encontradas líneas a gestionar - Finalizando proceso'.PHP_EOL, FILE_APPEND);
            }

            return false;
        }
    }


    //procesa la línea en $this->linea buscando el pedido de materiales y "recibiendo" las unidades de producto. En caso de que después no quede más producto para recibir llamará a cambiar el pedido de estado
    public function procesaLinea() {
        //los datos de $linea que interesan ahora son los ids de producto, (no vamos a comprobar ean otra vez si el proceso pasa a menudo, pero podría cambiar por error) el id de pedido de materiales, el id de línea de pedido de materiales correspondiente al producto y la cantidad recibida con el ubicador.
        //además, el proceso de recepción de pedidos necesita id de empleado, nombre y apellido para el log que monta. Pondremos el id y nombre de empleado, pero el apellido será Recepcionador.
        $id_product = $this->linea['id_product'];
        $id_product_attribute = $this->linea['id_product_attribute'];
        $id_supply_order = $this->linea['id_supply_order'];
        $id_supply_order_detail = $this->linea['id_supply_order_detail'];
        $quantity = $this->linea['cantidad_recibida'];
        $id_employee = $this->linea['id_employee'];
        $nombre_employee = $this->linea['nombre_employee'];
        
        //este código está replicado y adaptado de AdminSupplyOrderController.php
        $supply_order = new SupplyOrder((int)$id_supply_order);

        //nos aseguramos de que el pedido a procesar está o bien en Pendiente de Recepción 3 o Recibido parcialmente 4
        //19/09/2023 Ya no recepcionamos pedidos en estado 3 pendiente de recpción, deben estar en 7 - pedido entregado, o ya en recibido parcialmente, luego 4 y 7
        if (!in_array((int)$supply_order->id_supply_order_state, array(7, 4))) {
            $this->error = 1;
            $this->mensajes[] = 'Error - El pedido de materiales '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].' no se encuentra en estado correcto de recepción de materiales ('.(int)$supply_order->id_supply_order_state.')';
                
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Error - El pedido de materiales '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].' no se encuentra en estado correcto de recepción de materiales ('.(int)$supply_order->id_supply_order_state.')'.PHP_EOL, FILE_APPEND);

            $this->setError('Pedido no se encuentra en estado correcto para recepción ('.(int)$supply_order->id_supply_order_state.')');

            return false;
        }
        
        $supply_order_detail = new SupplyOrderDetail($id_supply_order_detail);

        if (Validate::isLoadedObject($supply_order_detail) && Validate::isLoadedObject($supply_order)) {
            // checks if quantity is valid
            // It's possible to receive more quantity than expected in case of a shipping error from the supplier
            if (!Validate::isInt($quantity) || $quantity <= 0) {
                $this->error = 1;
                $this->mensajes[] = 'Error - La cantidad recibida es errónea, no es un entero o es menor o igual a cero ('.$quantity.') para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'];
                 
                file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Error - La cantidad recibida es errónea, no es un entero o es menor o igual a cero ('.$quantity.') para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].PHP_EOL, FILE_APPEND);

                $this->setError('Cantidad recibida errónea (no entero o menor o igual a cero)');
                  
                return false;
            } else {                
                // everything is valid :  updates

                // creates the history
                $supplier_receipt_history = new SupplyOrderReceiptHistory();
                $supplier_receipt_history->id_supply_order_detail = (int)$id_supply_order_detail;
                $supplier_receipt_history->id_employee = (int)$id_employee;
                $supplier_receipt_history->employee_firstname = $nombre_employee;
                $supplier_receipt_history->employee_lastname = 'Recepcionador';
                $supplier_receipt_history->id_supply_order_state = (int)$supply_order->id_supply_order_state;
                $supplier_receipt_history->quantity = (int)$quantity;
                
                // updates quantity received
                $supply_order_detail->quantity_received += (int)$quantity;

                // if current state is "Pending receipt", then we sets it to "Order received in part"
                //19/09/2023 En este punto y al añadir el nuevo estado de pedido de materiales 7, entregado (en almacén) el pedido que se ha comenzado a recepcionar estaría en estado 7 y no 3. Esto habrá que tenerlo en cuenta para el proceso nativo, ya que si usan el esatdo 7, no pasará a 4 sin hacer un override de AdminSupplyOrderController.php, que es de donde sale todo este código. 
                if (7 == $supply_order->id_supply_order_state) {
                    $supply_order->id_supply_order_state = 4;
                }
                
                // Adds to stock
                $warehouse = new Warehouse($supply_order->id_warehouse);
                if (!Validate::isLoadedObject($warehouse)) {
                    $this->error = 1;
                    $this->mensajes[] = 'Error - El almacén no pudo cargarse para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'];
                    
                    file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Error - El almacén no pudo cargarse para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].PHP_EOL, FILE_APPEND);

                    $this->setError('Almacén no pudo cargarse');
                    
                    return false;
                }
                
                $price = $supply_order_detail->unit_price_te;
                // converts the unit price to the warehouse currency if needed
                if ($supply_order->id_currency != $warehouse->id_currency) {
                    // first, converts the price to the default currency
                    $price_converted_to_default_currency = Tools::convertPrice($supply_order_detail->unit_price_te,
                        $supply_order->id_currency, false);

                    // then, converts the newly calculated pri-ce from the default currency to the needed currency
                    $price = Tools::ps_round(Tools::convertPrice($price_converted_to_default_currency,
                        $warehouse->id_currency, true), 6);
                }
                
                $manager = StockManagerFactory::getManager();
                //En lugar de Configuration::get('PS_STOCK_MVT_SUPPLY_ORDER') ponemos 12 que es la etiqueta creada para identificar movimientos de stock "Recepcionador" 
                //creamos además el objeto empleado para poder llamar a $manager->addProduct() añadiendolo como último parámetro, ya que esta función de StockManager necesita el $context o en su defecto un $employee para generar los movimientos de stok
                $objeto_empleado = new stdClass(); 
                $objeto_empleado->id = (int)$id_employee;
                $objeto_empleado->firstname = $nombre_employee;
                $objeto_empleado->lastname = 'Recepcionador';

                $res = $manager->addProduct($supply_order_detail->id_product,
                    $supply_order_detail->id_product_attribute,    $warehouse, (int)$quantity,
                    12, $price, true, $supply_order->id, $objeto_empleado); //hemos añadido $objeto_empleado que en AdminSupplyOrdersCpontroller no se incluye ya que está dentro del marco de Context

                $location = Warehouse::getProductLocation($supply_order_detail->id_product,
                    $supply_order_detail->id_product_attribute, $warehouse->id);

                $res = Warehouse::setProductlocation($supply_order_detail->id_product,
                    $supply_order_detail->id_product_attribute, $warehouse->id, $location ? $location : '');

                
                if ($res) {
                    $supplier_receipt_history->add();
                    $supply_order_detail->save();
                    StockAvailable::synchronize($supply_order_detail->id_product);
                } else {
                    $this->error = 1;
                    $this->mensajes[] = 'Error - Algo se rompió poniendo la localización de almacén para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'];
                    
                    file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Error - Algo se rompió poniendo la localización de almacén para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].PHP_EOL, FILE_APPEND);

                    $this->setError('Error poniendo localización de almacén');
                    
                    return false;
                }
            }
        } else {
            $this->error = 1;
            $this->mensajes[] = 'Error - El objeto SupplyOrder o SupplyOrderDetail no pudo cargarse para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'];
            
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Error - El objeto SupplyOrder o SupplyOrderDetail no pudo cargarse para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].PHP_EOL, FILE_APPEND);

            $this->setError('SupplyOrder o SupplyOrderDetail no pudo cargarse');
            
            return false;
        }        
        
        $supply_order->id_supply_order_state = ($supply_order->id_supply_order_state == 4 && $supply_order->getAllPendingQuantity() > 0) ? 4 : 5;
        $supply_order->save();

        file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Correcto - Línea para producto '.$id_product.'_'.$id_product_attribute.' pedido '.$this->linea['supply_order_reference'].' ('.$id_supply_order.') en id_recepciones '.$this->linea['id_recepciones'].PHP_EOL, FILE_APPEND);

        return true;
    }

    //función que marca error en la línea actual que estamos revisando
    public function setError($mensaje_error) {
        Db::getInstance()->Execute("UPDATE lafrips_recepciones
        SET
        procesando = 0, 
        date_procesando = '0000-00-00 00:00:00', 
        error = 1,                                           
        mensaje_error = CONCAT(mensaje_error, ' | $mensaje_error ', DATE_FORMAT(NOW(),'%d-%m-%Y %H:%i:%s')),                              
        date_upd = NOW()
        WHERE id_recepciones = ".$this->linea['id_recepciones']);

        return;
    }

    //función que marca finalizado y quita procesando en la línea actual que estamos revisando
    public function setFinalizado() {
        Db::getInstance()->Execute("UPDATE lafrips_recepciones
        SET
        procesando = 0,         
        finalizado = 1,                                           
        date_finalizado = NOW(),                             
        date_upd = NOW()
        WHERE id_recepciones = ".$this->linea['id_recepciones']);

        return;
    }    

    //función que busca lineas con procesando = 1 y si date_procesando es superior a x tiempo, los deja en procesando = 0 para que se procesen en otra pasada.
    public function checkProcesando() {
        //sacamos productos con procesando = 1
        $sql_productos_procesando = 'SELECT id_recepciones, id_product, id_product_attribute, id_supply_order, supply_order_reference, date_procesando, error
        FROM lafrips_recepciones 
        WHERE procesando = 1';
        $productos_procesando = Db::getInstance()->executeS($sql_productos_procesando);

        $contador = 0;

        if (count($productos_procesando) > 0) { 
            foreach ($productos_procesando AS $producto) {
                $id_recepciones = $producto['id_recepciones']; 
                $id_product = $producto['id_product'];    
                $id_product_attribute = $producto['id_product_attribute'];
                $id_supply_order = $producto['id_supply_order'];
                $supply_order_reference = $producto['supply_order_reference'];
                $date_procesando = $producto['date_procesando'];
                $error = $producto['error'];

                //comprobamos cuanto tiempo lleva procesando y si es más de 10 minutos (se ha quedado bloqueado por lo que sea) lo volvemos a poner en procesando 0 para que la siguiente pasada del proceso lo vuelva a intentar. Metemos mensaje en error_message
                //dividimos entre 60 para sacar cuantos minutos son la diferencia de segundos       
                $diferencia_minutos =  round((strtotime("now") - strtotime($date_procesando))/60, 1);

                //si hubiera error = 1 lo vamos a dejar como está para revisar manualmente, si no lo reseteamos
                if (($diferencia_minutos >= 10) && ($error == 0)) {          
                    $contador++;         
                    
                    Db::getInstance()->Execute("UPDATE lafrips_recepciones
                    SET
                    procesando = 0, 
                    date_procesando = '0000-00-00 00:00:00',                                            
                    mensaje_error = CONCAT(mensaje_error, ' | Proceso reiniciado - ', DATE_FORMAT(NOW(),'%d-%m-%Y %H:%i:%s')),                              
                    date_upd = NOW()
                    WHERE id_recepciones = ".$id_recepciones );          
                    
                    if ($contador == 1) {
                        file_put_contents($this->log_file, date('Y-m-d H:i:s').' --------------------------------------------------'.PHP_EOL, FILE_APPEND);
                        file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Reseteo línea de productos "procesando"'.PHP_EOL, FILE_APPEND);
                    }

                    file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Reseteado "procesando" para producto '.$id_product.'_'.$id_product_attribute.' de pedido '.$supply_order_reference.' ('.$id_supply_order.')'.PHP_EOL, FILE_APPEND);
                    
                    continue;
                } else {
                    //lleva menos de X tiempo procesando o tiene error, lo ignoramos de momento
                    if ($error == 1) {
                        $contador++;

                        if ($contador == 1) {
                            file_put_contents($this->log_file, date('Y-m-d H:i:s').' --------------------------------------------------'.PHP_EOL, FILE_APPEND);
                            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Reseteo línea de productos "procesando"'.PHP_EOL, FILE_APPEND);
                        }

                        file_put_contents($this->log_file, date('Y-m-d H:i:s').' - No Reseteado "procesando" producto '.$id_product.'_'.$id_product_attribute.' de pedido '.$supply_order_reference.' ('.$id_supply_order.')'.' - Tiene error = 1'.PHP_EOL, FILE_APPEND);
                    }

                    continue;
                }
            }
        }

        if ($contador > 0) {
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Fin Reseteo línea de productos "procesando"'.PHP_EOL, FILE_APPEND);
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' --------------------------------------------------'.PHP_EOL, FILE_APPEND);            
        }

        return;
    }

    public function enviaEmail() {
        if (empty($this->mensajes)) {
            $this->mensajes = "todo OK";
        }
        // echo '<br>En enviaEmail()';
        // echo '<pre>';
        // print_r($this->mensajes);
        // echo '</pre>';

        if ($this->log) {
            file_put_contents($this->log_file, date('Y-m-d H:i:s').' - Fin del proceso, dentro de enviaEmail '.PHP_EOL, FILE_APPEND);  
        }            

        $cuentas = 'sergio@lafrikileria.com';

        $asunto = 'ERROR con proceso de Recepcionado Automático '.date("Y-m-d H:i:s");
        $info = [];                
        $info['{employee_name}'] = 'Usuario';
        $info['{order_date}'] = date("Y-m-d H:i:s");
        $info['{seller}'] = "";
        $info['{order_data}'] = "";
        $info['{messages}'] = '<pre>'.print_r($this->mensajes, true).'</pre>';
        
        @Mail::Send(
            1,
            'aviso_pedido_webservice', //plantilla
            Mail::l($asunto, 1),
            $info,
            $cuentas,
            'Usuario',
            null,
            null,
            null,
            null,
            _PS_MAIL_DIR_,
            true,
            1
        );

        exit;
    }

    //copia de función addProduct() de StockManager. La función utiliza el Context que aquí no tenemos, de modo que reproduzco de momento la función completa y la llamo sin instanciar StockManager, modificando las partes de uso de Context
    /**
     * @see StockManagerInterface::addProduct()
     *
     * @param int           $id_product
     * @param int           $id_product_attribute
     * @param Warehouse     $warehouse
     * @param int           $quantity
     * @param int           $id_stock_mvt_reason
     * @param float         $price_te
     * @param bool          $is_usable
     * @param int|null      $id_supply_order
     * @param Employee|null $employee
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function addProduct(
        $id_product,
        $id_product_attribute = 0,
        Warehouse $warehouse,
        $quantity,
        $id_stock_mvt_reason,
        $price_te,
        $is_usable = true,
        $id_supply_order = null,
        $employee = null
    ) {
        if (!Validate::isLoadedObject($warehouse) || !$quantity || !$id_product) {
            return false;
        }

        $price_te = round((float)$price_te, 6);
        if ($price_te <= 0.0) {
            return false;
        }

        if (!StockMvtReason::exists($id_stock_mvt_reason)) {
            $id_stock_mvt_reason = Configuration::get('PS_STOCK_MVT_INC_REASON_DEFAULT');
        }

        // $context = Context::getContext();
        //Aquí modificamos y creamos un pequeño objeto Context para esta función
        // $context = new stdClass();        
        // $context->employee = new stdClass();
        // $context->employee->id = 44;

        

        $mvt_params = array(
            'id_stock' => null,
            'physical_quantity' => $quantity,
            'id_stock_mvt_reason' => $id_stock_mvt_reason,
            'id_supply_order' => $id_supply_order,
            'price_te' => $price_te,
            'last_wa' => null,
            'current_wa' => null,
            'id_employee' => (int)$context->employee->id ? (int)$context->employee->id : $employee->id,
            'employee_firstname' => $context->employee->firstname ? $context->employee->firstname : $employee->firstname,
            'employee_lastname' => $context->employee->lastname ? $context->employee->lastname : $employee->lastname,
            'sign' => 1
        );

        $stock_exists = false;

        // switch on MANAGEMENT_TYPE
        switch ($warehouse->management_type) {
            // case CUMP mode
            case 'WA':

                $stock_collection = $this->getStockCollection($id_product, $id_product_attribute, $warehouse->id);

                // if this product is already in stock
                if (count($stock_collection) > 0) {
                    $stock_exists = true;

                    /** @var Stock $stock */
                    // for a warehouse using WA, there is one and only one stock for a given product
                    $stock = $stock_collection->current();

                    // calculates WA price
                    $last_wa = $stock->price_te;
                    $current_wa = $this->calculateWA($stock, $quantity, $price_te);

                    $mvt_params['id_stock'] = $stock->id;
                    $mvt_params['last_wa'] = $last_wa;
                    $mvt_params['current_wa'] = $current_wa;

                    $stock_params = array(
                        'physical_quantity' => ($stock->physical_quantity + $quantity),
                        'price_te' => $current_wa,
                        'usable_quantity' => ($is_usable ? ($stock->usable_quantity + $quantity) : $stock->usable_quantity),
                        'id_warehouse' => $warehouse->id,
                    );

                    // saves stock in warehouse
                    $stock->hydrate($stock_params);
                    $stock->update();
                } else {
                    // else, the product is not in sock

                    $mvt_params['last_wa'] = 0;
                    $mvt_params['current_wa'] = $price_te;
                }
            break;

            // case FIFO / LIFO mode
            case 'FIFO':
            case 'LIFO':

                $stock_collection = $this->getStockCollection($id_product, $id_product_attribute, $warehouse->id, $price_te);

                // if this product is already in stock
                if (count($stock_collection) > 0) {
                    $stock_exists = true;

                    /** @var Stock $stock */
                    // there is one and only one stock for a given product in a warehouse and at the current unit price
                    $stock = $stock_collection->current();

                    $stock_params = array(
                        'physical_quantity' => ($stock->physical_quantity + $quantity),
                        'usable_quantity' => ($is_usable ? ($stock->usable_quantity + $quantity) : $stock->usable_quantity),
                    );

                    // updates stock in warehouse
                    $stock->hydrate($stock_params);
                    $stock->update();

                    // sets mvt_params
                    $mvt_params['id_stock'] = $stock->id;
                }

            break;

            default:
                return false;
            break;
        }

        if (!$stock_exists) {
            $stock = new Stock();

            $stock_params = array(
                'id_product_attribute' => $id_product_attribute,
                'id_product' => $id_product,
                'physical_quantity' => $quantity,
                'price_te' => $price_te,
                'usable_quantity' => ($is_usable ? $quantity : 0),
                'id_warehouse' => $warehouse->id
            );

            // saves stock in warehouse
            $stock->hydrate($stock_params);
            $stock->add();
            $mvt_params['id_stock'] = $stock->id;
        }

        // saves stock mvt
        $stock_mvt = new StockMvt();
        $stock_mvt->hydrate($mvt_params);
        $stock_mvt->add();

        return true;
    }

}

?>