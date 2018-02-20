<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
class noStockInCart extends Module
{
  public $redirect;


  public function __construct(){
    $this->name = 'nostockincart';
    $this->version = '1.0.0';
    $this->author = 'Jakub Biesek';
    $this->tab = 'checkout';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('NO Stock In Cart');
    $this->description = $this->l('If there is a product in cart with no quantity update the cart.');

    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

  }

  public function install(){

    if (Shop::isFeatureActive())
    Shop::setContext(Shop::CONTEXT_ALL);

    if(!parent::install())
      return false;

      if(!$this->registerHook('DisplayShoppingCart')||!$this->registerHook('displayHome') || !$this->registerHook('displayHeader'))
        return false;

      return true;


    }

    public function uninstall(){
      if (!parent::uninstall())
      return false;
    return true;
  }

  public function hookDisplayHeader($params)
  {
    $this->context->controller->addJS($this->_path .'views/js/no_qty_cart.js');
    $this->context->controller->addCSS($this->_path .'views/css/style_noQty.css');
  }

  public function hookDisplayShoppingCart($params){
    $this->redirect = false;
    $cart = $this->context->cart;
    $products_inCart = $cart->getProducts();
    if(!empty($products_inCart)){
      foreach ($products_inCart as $key => $cart_products) {
        $get_quantity = StockAvailable::getQuantityAvailableByProduct($cart_products['id_product'], $cart_products['id_product_attribute']);
        //check product overall quantity and how many prods. are in cart
        if($get_quantity < $cart_products['quantity']){
          if($get_quantity <= 0 ){
            //Updating quantity of products in cart
            $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
            SET quantity = '.$get_quantity.',`date_add` = NOW()
            WHERE `id_product` = '.(int) $cart_products['id_product'].'
            AND `id_cart` = '.(int) $cart->id.' AND id_product_attribute = '.$cart_products['id_product_attribute'];
            $upd_quantity = Db::getInstance()->execute($sql);
            //deleting whole cart id where updated quantity is 0
            if($upd_quantity){
            $this->redirect = true;
            $sql_del = 'DELETE FROM `'._DB_PREFIX_.'cart_product` where quantity = 0 AND `id_product` = '.(int) $cart_products['id_product'].' AND `id_cart` = '.(int) $cart->id;
            Db::getInstance()->execute($sql_del);
            Configuration::updateValue('Redirect_no_qty', true);
              }
            }
          }
        }
      if($this->redirect == true){
        Tools::redirect('index.php');
      }
    }
  }


  public function hookdisplayHome()
  {
    $checker = Configuration::get('Redirect_no_qty');

    if($checker > 0){

      $this->context->smarty->assign(array(
                'redirect'=>$checker,
            ));
      Configuration::updateValue('Redirect_no_qty', 0);
    }
    return $this->display(__FILE__, 'displayHome.tpl', $this->getCacheId('displayHome'));
  }
}

?>
