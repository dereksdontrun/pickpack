<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/
//clase para lafrips_pick_pack

class PickPackOrder extends ObjectModel
{
    /**
     * Id tabla pick_pack
     *
     * @var int
     */
    public $id_pickpack;
    /**
     * Id pedido de prestashop
     *
     * @var int
     */
    public $id_pickpack_order;
    /**
     * Estado pedido
     *
     * @var int
     */
    public $id_estado_order;

    /**
     * Empleado picking
     *
     * @var int
     */
    public $id_employee_picking;

    /**
     * Empleado packing
     *
     * @var int
     */
    public $id_employee_packing;

    /**
     * Nombre empleado picking
     *
     * @var string
     */
    public $nombre_employee_picking;

    /**
     * Nombre empleado packing
     *
     * @var string
     */
    public $nombre_employee_packing;

    /**
     * Comentario sobre picking
     *
     * @var string
     */
    public $comentario_picking;

    /**
     * Comentario sobre packing
     *
     * @var string
     */
    public $comentario_packing;

    /**
     * Fecha de "entrada" de pedido (primera vez que se guarda el picking)
     *
     * @var date
     */
    public $date_add;

    /**
     * Fecha de cambio (cada vez que se guarde algo del pedido)
     *
     * @var date
     */
    public $date_upd;

    /**
     * Fecha de inicio de picking 
     *
     * @var date
     */
    public $date_inicio_picking;

    /**
     * Fecha de inicio de packing 
     *
     * @var date
     */
    public $date_inicio_packing;

    /**
     * Fecha de cierre de picking (guardar como terminado)
     *
     * @var date
     */
    public $date_fin_picking;

    /**
     * Fecha de cierre de packing (guardar como terminado/enviado)
     *
     * @var date
     */
    public $date_fin_packing;
    
    /**
     * Pedido finalizado / enviado
     *
     * @var bool
     */
    public $finalizado;

    /**
     * Pedido comenzado, es decir, se ha guardado el picking o el packing (si no solo ha entrado en Prestashop)
     *
     * @var bool
     */
    public $comenzado;

    /**
     * Obsequio, si por número de pedidos al cliente se le envía un obsequio
     *
     * @var bool
     */
    public $obsequio;

    /**
     * Regalo, si va envuelto para regalo
     *
     * @var bool
     */
    public $regalo;

    /**
     * comenzado_picking
     *
     * @var bool
     */
    public $comenzado_picking;
    
    /**
     * comenzado_packing
     *
     * @var bool
     */
    public $comenzado_packing;

    /**
     * incidencia_picking
     *
     * @var bool
     */
    public $incidencia_picking;

    /**
     * incidencia_packing
     *
     * @var bool
     */
    public $incidencia_packing;

    /**
     * picking finalizado desde gestión con botón bulk
     *
     * @var bool
     */
    public $picking_finalizado_bulk;

    /**
     * packing finalizado desde gestión con botón bulk
     *
     * @var bool
     */
    public $packing_finalizado_bulk;

    //23/11/2020 Añado variable ara saber si el pedido contiene una caja sorpresa (vale 1) o es una caja sorpresa (vale 2)
    /**
     * caja sorpresa
     *
     * @var int
     */
    public $caja_sorpresa;
    

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'pick_pack',
        'primary' => 'id_pickpack',
        //'multishop' => false,
        'multilang' => false,
        'fields' => array(
            'id_pickpack_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_estado_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_fin_picking' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_fin_packing' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_inicio_picking' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_inicio_packing' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'nombre_employee_picking' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50),
            'nombre_employee_packing' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50),
            'comentario_picking' => array('type' => self::TYPE_STRING, 'size' => 500),
            'comentario_packing' => array('type' => self::TYPE_STRING, 'size' => 500),
            'id_employee_picking' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_employee_packing' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'comenzado' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'regalo' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'obsequio' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),            
            'finalizado' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'comenzado_picking' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'comenzado_packing' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'incidencia_picking' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'incidencia_packing' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'picking_finalizado_bulk' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'packing_finalizado_bulk' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'caja_sorpresa' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
        ),
    );

    // public function save($null_values = false, $auto_date = true)
    // {
    //     // $res = parent::save($null_values ,$autodate );

    //     // // $this->comentario_picking = $this->comentario_picking.' idpickpack '.$this->id_pickpack.' idorder '.$this->id_pickpack_order;
    //     // $this->comentario_picking = 'okokokok';

    //     if ($this->date_fin_picking > '0000-00-00 00:00:00'){
    //         $sql_update = 'UPDATE lafrips_pick_pack
    //         SET
    //         comenzado = '.$this->comenzado.',
    //         id_estado_order = '.$this->id_estado_order.',
    //         id_employee_picking = '.$this->id_employee_picking.',
    //         nombre_employee_picking = '.$this->nombre_employee_picking.',
    //         comentario_picking = '.$this->comentario_picking.',
    //         obsequio = '.$this->obsequio.',
    //         date_fin_picking = '.$this->date_fin_picking.'
    //         WHERE id_pickpack ='.$this->id_pickpack.';';            
    //     } else {
    //         $sql_update = 'UPDATE lafrips_pick_pack
    //         SET
    //         comenzado = '.$this->comenzado.',
    //         id_estado_order = '.$this->id_estado_order.',
    //         id_employee_picking = '.$this->id_employee_picking.',
    //         nombre_employee_picking = '.$this->nombre_employee_picking.',
    //         comentario_picking = '.$this->comentario_picking.',
    //         obsequio = '.$this->obsequio.'            
    //         WHERE id_pickpack ='.$this->id_pickpack.';';   
    //     }

    //     $res = Db::getInstance()->execute($sql_update);
        

    // }

    // public function __construct($id = null, $id_lang = null, $id_shop = null) {
    //     Shop::addTableAssociation(self::$definition['table'], array('type' => 'shop'));
    //     parent::__construct($id, $id_lang, $id_shop);
    // }

    // public function update($null_values = false){
    //     parent::update($null_values);
    // }
    // public function add($autodate = true, $null_values = true)
    // {
    //     parent::add($autodate, $null_values);
    // }
    // public function save($null_values = false){
    //     parent::save($null_values);
    // }
    
    public static function getIdPickPackByIdOrder($id_order)
    {
        $id_pickpack = Db::getInstance()->getValue('
            SELECT id_pickpack
            FROM lafrips_pick_pack
            WHERE id_pickpack_order ='.$id_order.';'
            );
        return $id_pickpack;
    }

    // public function __construct($id = null) {
    //     Shop::addTableAssociation(self::$definition['table'], array('type' => 'shop'));
    //     parent::__construct($id);
    // }

    

    

    // public static function getNumeroPedidos()
    //     {
    //     $numero_pedidos = Db::getInstance()->getValue('
    //     SELECT COUNT(`id_pickpack_order`)
    //     FROM lafrips_pick_pack'
    //     );
    //     return $numero_pedidos;
    //     }
    //    
    //        y podemos usar la función llamando desde otro sitio:
    //     $numero_pedidos = PickPackOrder::getNumeroPedidos();
    
}





?>