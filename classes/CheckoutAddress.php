<?php

namespace plugins\NovaPoshta\classes;


/**
 * Class Calculator
 * @property bool isCheckout
 * @property Customer $customer
 * @package plugins\NovaPoshta\classes
 */



class CheckoutAddress extends Base
{

    /**
     * @var CheckoutAddress
     */
    private static $_instance;

    /**
     * @return CheckoutAddress
     */
    public static function instance()
    {
        if (static::$_instance == null) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * @return void
     */
     public function init()
     {
         add_filter('woocommerce_checkout_fields', array($this, 'maybeDisableDefaultShippingMethods'));
         add_filter('nova_poshta_disable_default_fields', array($this, 'disableDefaultFields'));

     }

     public function maybeDisableDefaultShippingMethods($fields)
     {
         if (NPttn()->isPost() && NPttn()->isNPttn() && NPttn()->isCheckout()) {
             $fields = apply_filters('nova_poshta_disable_default_fields', $fields);
         }
         return $fields;
     }

     public function disableDefaultFields($fields)
     {
       $fields['billing']['billing_postcode']['required'] = false;
       $fields['shipping']['shipping_postcode']['required'] = false;

         return $fields;
     }
     public function getLocation()
     {
         return $this->shipToDifferentAddress() ? Area::SHIPPING : Area::BILLING;
     }

     /**
      * @return bool
      */
     public function shipToDifferentAddress()
     {
         $shipToDifferentAddress = isset($_POST['ship_to_different_address']);

         if (isset($_POST['shiptobilling'])) {
             _deprecated_argument('WC_Checkout::process_checkout()', '2.1', 'The "shiptobilling" field is deprecated. The template files are out of date');
             $shipToDifferentAddress = !$_POST['shiptobilling'];
         }

         // Ship to billing option only
         if (wc_ship_to_billing_address_only()) {
             $shipToDifferentAddress = false;
         }
         return $shipToDifferentAddress;
     }

    /**
     * NovaPoshta constructor.
     *
     * @access private
     */
    private function __construct()
    {
    }

    /**
     * @access private
     */
    private function __clone()
    {
    }

}
