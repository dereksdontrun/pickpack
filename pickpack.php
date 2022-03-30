<?php
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
*  @author    Sergio™ <sergio@lafrikileria.com>
*    
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

//include con la clase para la tabla de pedidos
//include dirname(__FILE__).'/classes/PickPackOrder.php';
require_once(dirname(__FILE__).'/classes/PickPackOrder.php');

class Pickpack extends Module
{
    protected $config_form = false;
    //creamos la variable para asignar link en pestaña lateral de pedidos
    protected $admin_tab = array();

    public function __construct()
    {
        $this->name = 'pickpack';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Sergio';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        //colocamos el link al módulo en la pestaña lateral de Pedidos        
        $this->admin_tab[] = array('classname' => 'AdminPickPackLogin', 'parent' => 'AdminOrders', 'displayname' => 'Inicia PickPack');
        //$this->admin_tab[] = array('classname' => 'AdminPacking', 'parent' => 'AdminOrders', 'displayname' => 'Packing');
        $this->admin_tab[] = array('classname' => 'AdminGestionPickpack', 'parent' => 'AdminOrders', 'displayname' => 'Gestión PickPack');

        $this->displayName = $this->l('Picking y Packing sin papel');
        $this->description = $this->l('Módulo que permite hacer picking y packing sin necesidad de imprimir las hojas de pedido');

        $this->confirmUninstall = $this->l('¿Quieres desinstalar este módulo?');
        //creamos la variable para javascript con la url del módulo. No se utiliza- OJO, al genrar así las variables quedan como variables globales y se accede a ellas desde cualquier parte del front
        // Media::addJsDef(array('url_base' => $this->_path));        
        // Media::addJsDef(array('id_employee' => Context::getContext()->employee->id));        

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('PICKPACK_LIVE_MODE', false);        

        //añadimos link en pestaña de pedidos llamando a installTab
        foreach ($this->admin_tab as $tab)
            $this->installTab($tab['classname'], $tab['parent'], $this->name, $tab['displayname']);

        return parent::install() &&
            //$this->initSQL() && Si decidimos crear la tabla en la instalación del módulo llamamos a la función initSQL() que la creará
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader')&& 
            //añadimos un hook para que cada vez que entre un pedido a Prestashop, el id_order se introduzca en nuestra tabla lafrips_pick_pack como No comenzado, de modo que se pueda acceder a el desde el controlador de Packing y poder hacer el packing sin que se haya pasado por picking (si el pedido no está en la tabla no podemos acceder a su packing)           
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PICKPACK_LIVE_MODE');

        //desinstalar el link de la pestaña lateral de pedidos llamando a unistallTab
        foreach ($this->admin_tab as $tab)
            $this->unInstallTab($tab['classname']);

        return parent::uninstall()
            //&& $this->uninstallSQL()  De momento prefiero no eliminar las tablas al desinstalar el módulo
            ;
    }

    /*
     * Crear el link en pestaña de menú lateral, dentro de Pedidos
     */    
    protected function installTab($classname = false, $parent = false, $module = false, $displayname = false) {
        if (!$classname)
            return true;

        $tab = new Tab();
        $tab->class_name = $classname;
        if ($parent)
            if (!is_int($parent))
                $tab->id_parent = (int) Tab::getIdFromClassName($parent);
            else
                $tab->id_parent = (int) $parent;
        if (!$module)
            $module = $this->name;
        $tab->module = $module;
        $tab->active = true;
        if (!$displayname)
            $displayname = $this->displayName;
        $tab->name[(int) (Configuration::get('PS_LANG_DEFAULT'))] = $displayname;

        if (!$tab->add())
            return false;

        return true;
    }

    /*
     * Quitar el link en pestaña de menú lateral, dentro de Pedidos
     */
    protected function unInstallTab($classname = false) {
        if (!$classname)
            return true;

        $idTab = Tab::getIdFromClassName($classname);
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
            ;
        }
        return true;
    }    

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPickpackModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        //$helper->submit_action = 'submitPickpackModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'PICKPACK_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'PICKPACK_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'PICKPACK_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PICKPACK_LIVE_MODE' => Configuration::get('PICKPACK_LIVE_MODE', true),
            'PICKPACK_ACCOUNT_EMAIL' => Configuration::get('PICKPACK_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'PICKPACK_ACCOUNT_PASSWORD' => Configuration::get('PICKPACK_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * Crear la tabla pickpack en la BD
     */
    protected function initSQL()
    {
        //la tabla tendrá nombre  lafrips_pickpack si hacemos => CREATE TABLE IF NOT EXISTS `'.pSQL(_DB_PREFIX_.$this->name).'` (
        //tuve que llamar a la clase y la tabla pick_pack porque pickpack es el nombre del módulo y no puedo generar dos clases con el mismo nombre
        // el primery key va a ser el id_order, si no no podría usar la gestión con clases tipo new Pick_Pack($id_order)
        
        Db::getInstance()->Execute('
	        CREATE TABLE IF NOT EXISTS `'.pSQL(_DB_PREFIX_.'pick_pack').'` (				                
                `id_pickpack_order` int(10) NOT NULL,
                `comenzado` tinyint(1) NOT NULL,
                `id_estado_order` int(10) NOT NULL,
                `id_employee_picking` int(10) NOT NULL,
                `id_employee_packing` int(10) NOT NULL,
                `nombre_employee_picking` varchar(50) NOT NULL,
                `nombre_employee_packing` varchar(50) NOT NULL,                  
                `comentario_picking` varchar(2000) NOT NULL, 
                `comentario_packing` varchar(2000) NOT NULL,
                `regalo` tinyint(1) NOT NULL,
                `obsequio` tinyint(1) NOT NULL,
                `caja_sorpresa` int(10) NOT NULL,
                `finalizado` tinyint(1) NOT NULL,
                `comenzado_picking` tinyint(1) NOT NULL,
                `comenzado_packing` tinyint(1) NOT NULL,
                `incidencia_picking` tinyint(1) NOT NULL,  
                `incidencia_packing` tinyint(1) NOT NULL, 
                `picking_finalizado_bulk` tinyint(1) NOT NULL,
                `packing_finalizado_bulk` tinyint(1) NOT NULL,                                   
                `date_add` datetime NOT NULL,
                `date_inicio_picking` datetime NOT NULL,
                `date_fin_picking` datetime NOT NULL,
                `date_inicio_packing` datetime NOT NULL,
                `date_fin_packing` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id_pickpack_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `lafrips_pick_pack_estados` (
                `id_pickpack_estados` int(10) NOT NULL,
                `nombre_estado` varchar(50) NOT NULL,
                `color` varchar(32),                
                PRIMARY KEY (`id_pickpack_estados`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        //Meto los nombres de los estados con sus colores
        Db::getInstance()->Execute("INSERT INTO lafrips_pick_pack_estados (id_pickpack_estados, nombre_estado, color) VALUES 
                ( 1, 'No Comenzado', '#e1ff59'),
                ( 2, 'Picking Abierto', '#32CD32'), 
                ( 3, 'Incidencia Picking', '#FF8C00'),
                ( 4, 'Picking Finalizado', '#bcb1ff'),
                ( 5, 'Incidencia Packing', '#ffc334'),
                ( 6, 'Enviado', '#8A2BE2'), 
                ( 7, 'Cancelado', '#DC143C'), 
                ( 8, 'Packing Abierto', '#32cd97');"); 

        Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `lafrips_pick_pack_productos` (
                `id_pickpack_productos` int(10) NOT NULL AUTO_INCREMENT,
                `id_pickpack_order` int(10) NOT NULL,
                `id_product` int(10) NOT NULL,
                `id_product_attribute` int(10) NOT NULL,
                `ok_picking` tinyint(1) NOT NULL,
                `ok_packing` tinyint(1) NOT NULL, 
                `incidencia_picking` tinyint(1) NOT NULL,
                `incidencia_packing` tinyint(1) NOT NULL,               
                PRIMARY KEY (`id_pickpack_productos`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

		return true;
    }

    /**
     * Eliminar las tablas pickpack en la BD
     */
    protected function uninstallSQL()
    {
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `lafrips_pick_pack_estados`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `lafrips_pick_pack`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `lafrips_pick_pack_productos`');

        return true;
    }

    // Con el Hook metemos cada nuevo pedido en la tabla lafrips_pick_pack, solo el id_order como id_pickpack_order
    // Hook recibe:
    // array(
    //     'cart' => (object) Cart,
    //     'order' => (object) Order,
    //     'customer' => (object) Customer,
    //     'currency' => (object) Currency,
    //     'orderStatus' => (object) OrderState
    //   );
    public function hookActionValidateOrder($params)
    {
        //El hook funcionará cada vez que entra un nuevo pedido.
        if ($params) {
            //sacamos el id_order del pedido
            $order = $params['order'];
            if (Validate::isLoadedObject($order))
            {  
                $id_order = (int)$order->id;
                //metemos en la tabla el id_order, el estado de pickpack, que será No comenzado, id 1 y la fecha. El resto de campos se llenan si es necesario al ser NOT NULL
                //Db::getInstance()->Execute("INSERT INTO lafrips_pick_pack (id_pickpack_order, id_estado_order, date_add) VALUES (".$id_order." , 1, NOW())");
                $order_pickpack = new PickPackOrder();
                $order_pickpack->id_pickpack_order = (int)$id_order;
                //$order_pickpack->comentario_picking = 'comentarion';
                $order_pickpack->id_estado_order = 1;
                //$order_pickpack->date_fin_picking = date("Y-m-d H:i:s");    //campos date_add y date_upd se llenan automáticamente            
                

                //ahora obtenemos los productos que hay en el pedido y los metemos en la tabla lafrips_pick_pack_productos
                //con el id sacamos los productos del pedido
                $pedido = new Order($id_order);
                $productos = $pedido->getProducts();   
                $caja = 0;             
                //sacamos el id e id_product_attribute de cada producto en el pedido y los metemos en la tabla
                foreach ($productos as $producto){
                    $id_producto = $producto['product_id'];
                    //comprobamos si hay alguna caja sorpresa en el pedido
                    if ($id_producto == 5344) {
                        $caja = 1;
                    }
                    $id_atributo_producto = $producto['product_attribute_id'];
                    Db::getInstance()->Execute("INSERT INTO lafrips_pick_pack_productos (id_pickpack_order, id_product, id_product_attribute) VALUES (".$id_order." ,".$id_producto." ,".$id_atributo_producto.")");                    
                }

                //23/11/2020 Si en el pedido hay alguna caja sorpresa id 5344 ponemos a la varable de pickpack caja_sorpresa valor 1
                if ($caja) {
                    $order_pickpack->caja_sorpresa = 1;
                }                

                $order_pickpack->add();

                return;
            }
        }
        return;
    }
}
