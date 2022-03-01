<?php

 use plugins\NovaPoshta\classes\base\ArrayHelper;
 use plugins\NovaPoshta\classes\base\Options;
 use plugins\NovaPoshta\classes\Checkout;
 use plugins\NovaPoshta\classes\Customer;

function request_npaddress_shipping_quote_init() {
    if ( ! class_exists( 'WC_NovaPoshtaAddress_Shipping_Method' ) ) {

        class WC_NovaPoshtaAddress_Shipping_Method2 extends WC_Shipping_Method {

            public function __construct( $instance_id = 0 ) {
                $this->id = 'npttn_address_shipping_method';
                $this->instance_id = absint( $instance_id );
                $this->method_title = __( "Адресна доставка Нова пошта ", NOVA_POSHTA_TTN_DOMAIN );
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                    );
                $this->init();
            }

            public function init() {

            $this->init_form_fields();
            $this->init_settings();

            //add_filter('woocommerce_checkout_fields', array($this, 'maybeDisableDefaultShippingMethods'));
            //add_filter('nova_poshta_disable_default_fields', array($this, 'disableDefaultFields'));
            // add_filter('woocommerce_checkout_fields', [ $this, 'removeDefaultFieldsFromValidation' ]);
            // add_filter('woocommerce_checkout_posted_data', [ $this, 'processCheckoutPostedData' ]);

            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option('desc');

            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }


            private function maybeDisableDefaultFields()
            {
                return isset($_POST['shipping_method']) &&
                    preg_match('/^' . 'npttn_address_shipping_method' . '.*/i', $_POST['shipping_method'][0]);

            }

            public function removeDefaultFieldsFromValidation($fields)
            {
              if ($this->maybeDisableDefaultFields()) {
                unset($fields['billing']['billing_postcode']);
              }
              return $fields;
            }

            public function processCheckoutPostedData($data)
            {
              if (isset($data['shipping_method'])) {
                if (
                  preg_match('/^' . 'npttn_address_shipping_method' . '.*/i', $data['shipping_method'][0]) &&
                  isset($data['ship_to_different_address'])
                ) {

                  unset($data['shipping_postcode']);
                  unset($data['billing_postcode']);
                }
              }

              return $data;
            }

            public function maybeDisableDefaultShippingMethods($fields)
            {
              echo '<script>console.log("'.$_POST['shipping_method'].'")</script>';
              if(isset ($_POST)){
                  echo '<script>console.log("'.$_POST['shipping_method'].'")</script>';
                if (strpos($_POST['shipping_method'][0], 'npttn_address') !== false) {
                //if(  preg_match('/^npttn_address*/i', $_POST['shipping_method'][0])  ){
                  unset($fields['billing']['billing_postcode']);
                  unset($fields['shipping']['shipping_postcode']);
              }
            }
            return $fields;
            }
            public function disableDefaultFields($fields)
            {
              $fields['billing']['billing_postcode']['required'] = false;
               $fields['shipping']['shipping_postcode']['required'] = false;
              unset($fields['billing']['billing_postcode']);
              unset($fields['shipping']['shipping_postcode']);
                return $fields;
            }

            /**
            * Calculate custom shipping method.
            *
            * @param array $package
            *
            * @return void
            */
            public function calculate_shipping( $package = array() ) {
                $this->add_rate( array(
                    'label' => $this->title,
                    'package' => $package,
                ) );
            }

            /**
            * Init form fields.
            */
            public function init_form_fields() {
                $this->instance_form_fields = array(

                    'title' => array(
                        'title' => __( 'Заголовок', NOVA_POSHTA_TTN_DOMAIN ),
                        'type' => 'text',
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                        'default' => __( ' Адресна доставка Нова пошта  ', NOVA_POSHTA_TTN_DOMAIN ),
                        'desc_tip' => true,
                    ),
                    'desc' => array(
                        'title' => __( 'опис ', NOVA_POSHTA_TTN_DOMAIN ),
                        'type' => 'text',
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                        'default' => __( ' Адресна доставка Нова пошта  ', NOVA_POSHTA_TTN_DOMAIN ),
                        'desc_tip' => true,
                        ),
                    Options::USE_FIXED_PRICE_ON_DELIVERY => array(
                        'title' => __('Set Fixed Price for Delivery.', NOVA_POSHTA_TTN_DOMAIN),
                        'label' => __('If checked, fixed price will be set for delivery.', NOVA_POSHTA_TTN_DOMAIN),
                        'type' => 'checkbox',
                        'default' => 'no',
                        'description' => 'Увага: мінімальна сума для безкоштовної доставки не буде враховуватися',
                    ),
                    Options::FIXED_PRICE => array(
                        'title' => __('Fixed price', NOVA_POSHTA_TTN_DOMAIN),
                        'type' => 'text',
                        'description' => __('Delivery Fixed price.', NOVA_POSHTA_TTN_DOMAIN),
                        'default' => 0.00
                    ),

                    Options::FREE_SHIPPING_MIN_SUM => array(
                        'title' => __('Мінімальна сума для безкоштовної доставки', NOVA_POSHTA_TTN_DOMAIN),
                        'type' => 'text',
                        'placeholder' => 'Вкажіть суму цифрами',
                        'description' => __('Введіть суму, при досягненні якої, доставка для покупця буде безкоштовною', NOVA_POSHTA_TTN_DOMAIN),
                    ),
                    Options::FREE_SHIPPING_TEXT => array(
                        'title' => __('Текст при безкоштовній доставці', NOVA_POSHTA_TTN_DOMAIN),
                        'type' => 'text',
                        'placeholder' => 'Ваш текст',
                        'description' => __('Введіть текст, який замінить назву способу доставки при досягненні мінімальної суми замовлення<br>Наприклад: "БЕЗКОШТОВНО на відділення Нової Пошти".', NOVA_POSHTA_TTN_DOMAIN),
                    ),            

                    'settings' => array(
                        'title' => __('', NOVA_POSHTA_TTN_DOMAIN),
                        'type' => 'hidden',
                        'description' => __('Решта налаштувань доступні за <a href="admin.php?page=morkvanp_plugin">посиланям</a>.', NOVA_POSHTA_TTN_DOMAIN),
                        'default' => __(' ', NOVA_POSHTA_TTN_DOMAIN)
                    ),
                );
            }
        }
    }
}
add_action( 'woocommerce_shipping_init', 'request_npaddress_shipping_quote_init' );

function request_npaddress_shipping_quote_shipping_method( $methods ) {
    $methods['npttn_address_shipping_method'] = 'WC_NovaPoshtaAddress_Shipping_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'request_npaddress_shipping_quote_shipping_method' );
