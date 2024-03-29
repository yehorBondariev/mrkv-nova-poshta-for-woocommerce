<?php

 use plugins\NovaPoshta\classes\base\ArrayHelper;
 use plugins\NovaPoshta\classes\base\Options;
 use plugins\NovaPoshta\classes\Poshtomat;
 use plugins\NovaPoshta\classes\Checkout;
 use plugins\NovaPoshta\classes\CheckoutPoshtomat;
 use plugins\NovaPoshta\classes\Customer;

/**
 * Class WC_NovaPoshta_Shipping_Method_Poshtomat
 */
if ( ! class_exists( 'WC_NovaPoshta_Shipping_Method_Poshtomat' ) ) :
    class WC_NovaPoshta_Shipping_Method_Poshtomat extends WC_Shipping_Method
    {
         public function __construct($instance_id = 0)
         {
            $this->instance_id = absint( $instance_id );
            parent::__construct( $instance_id );
            $this->id = NOVA_POSHTA_TTN_SHIPPING_METHOD_POSHTOMAT;
            $this->method_title = __( 'Nova Poshta Poshtomat', NOVA_POSHTA_TTN_DOMAIN );
            $this->method_description = $this->getDescription();
            $this->rate = 0.00;

            $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );

            $this->init();

            // Get setting values
            $this->title = $this->get_option( 'title' );
            $this->enabled = true;

            $this->enabled = $this->get_option( 'enabled' );
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init()
        {
            $this->init_form_fields();
            $this->init_settings();
            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function test($packages)
        {

            return $packages;
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        public function init_form_fields()
        {
            $this->instance_form_fields = array(
                'title' => array(
                    'title' => __( 'Nova Poshta Poshtomat', NOVA_POSHTA_TTN_DOMAIN ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', NOVA_POSHTA_TTN_DOMAIN ),
                    'default' => __( 'Nova Poshta Poshtomat', NOVA_POSHTA_TTN_DOMAIN )
                ),
                Options::USE_FIXED_PRICE_ON_DELIVERY => array(
                    'title' => __( 'Set Fixed Price for Delivery.', NOVA_POSHTA_TTN_DOMAIN ),
                    'label' => __( 'If checked, fixed price will be set for delivery.', NOVA_POSHTA_TTN_DOMAIN ),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'description' => 'Увага: мінімальна сума для безкоштовної доставки не буде враховуватися',
                ),
                Options::FIXED_PRICE => array(
                    'title' => __( 'Fixed price', NOVA_POSHTA_TTN_DOMAIN ),
                    'type' => 'text',
                    'description' => __( 'Delivery Fixed price.', NOVA_POSHTA_TTN_DOMAIN ),
                    'default' => 0.00
                ),
                Options::FREE_SHIPPING_MIN_SUM => array(
                    'title' => __( 'Мінімальна сума для безкоштовної доставки', NOVA_POSHTA_TTN_DOMAIN ),
                    'type' => 'text',
                    'placeholder' => 'Вкажіть суму цифрами',
                    'description' => __( 'Введіть суму, при досягненні якої, доставка для покупця буде безкоштовною', NOVA_POSHTA_TTN_DOMAIN ),
                ),
                Options::FREE_SHIPPING_TEXT => array(
                    'title' => __( 'Текст при безкоштовній доставці', NOVA_POSHTA_TTN_DOMAIN ),
                    'type' => 'text',
                    'placeholder' => 'Ваш текст',
                    'description' => __( 'Введіть текст, який замінить назву способу доставки при досягненні мінімальної суми замовлення<br>Наприклад: "БЕЗКОШТОВНО на відділення Нової Пошти".', NOVA_POSHTA_TTN_DOMAIN ),
                ),

                'settings' => array(
                    'title' => __( '', NOVA_POSHTA_TTN_DOMAIN ),
                    'type' => 'hidden',
                    'description' => __( 'Решта налаштувань доступні за <a href="admin.php?page=morkvanp_plugin">посиланям</a>.', NOVA_POSHTA_TTN_DOMAIN ),
                    'default' => __( ' ', NOVA_POSHTA_TTN_DOMAIN )
                ),
            );
        }


        /**
         * calculate_shipping function.
         *
         * @access public
         *
         * @param array $package
         */
        public function calculate_shipping($package = array())
        {
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => 0.00,
                'calc_tax' => 'per_item'
            );

            $location = Checkout::instance()->getLocation();
            $cityRecipient = Customer::instance()->getMetadata('nova_poshta_city', $location)
                //for backward compatibility with woocommerce 2.x.x
                ?: Customer::instance()->getMetadata('nova_poshta_city', '');

                // Розрахунок вартості доставки через API Нової Пошти (початок)
                $cityRecipient =  isset($_COOKIE['city']) ? $_COOKIE['city'] : "fc5f1e3c-928e-11e9-898c-005056b24375"; //sorry but Abazivka
                $rate['cost'] = 0;
                $citySender = NPttn()->options->senderCity;
                $serviceType = 'WarehouseWarehouse';
                if ( get_option( 'woocommerce_nova_poshta_sender_address_type' ) ) {
                    $serviceType = 'DoorsWarehouse';
                }
                $items = WC()->cart->get_cart(); // Розрахунок Об'ємної ваги Відправлення
                $volumeWeight = 0.00;
                foreach($items as $item => $values) {
                    $product[$item] = $values['data'];
                    $item_length = ( null !== $product[$item]->get_length() ) ? floatval( $product[$item]->get_length() ) : 0.00;
                    $length = $item_length;
                    $item_width = ( null !== $product[$item]->get_width() ) ? floatval( $product[$item]->get_width() ) : 0.00;
                    $width = $item_width;
                    $item_height = ( null !== $product[$item]->get_height() ) ? floatval( $product[$item]->get_height() ) : 0.00;
                    $height = $item_height;
                    $volumeWeight += $length * $width * $height / 4000;
                }
                $weight_coef = $this->convert_weight_unit();
                $actualWeight = ( WC()->cart->cart_contents_weight > 0 ) ? WC()->cart->cart_contents_weight * $weight_coef : 0.5;
                $cartWeight = max( $actualWeight, $volumeWeight );  // Береться більше значення між Фактичною вагою і Об'ємною вагою
                $cartTotal = WC()->cart->cart_contents_total;
                try {
                    $result = NPttn()->api->getDocumentPrice($citySender, $cityRecipient, $serviceType, $cartWeight, $cartTotal);
                    $cost = is_array( $result ) ? array_shift($result) : 0;
                    $rate['cost'] = ArrayHelper::getValue($cost, 'Cost', 0);
                    NPttn()->log->error('calculated citySender-'.$citySender." cityRecipient-". $cityRecipient. " serviceType-". $serviceType." cartWeight-". $cartWeight." cartTotal-". $cartTotal);
                } catch (Exception $e) {
                    NPttn()->log->error($e->getMessage());
                    NPttn()->log->error($cityRecipient);
                }
                $rate = apply_filters('woo_shipping_for_nova_poshta_before_add_rate', $rate, $cityRecipient);
                // Розрахунок вартості доставки через API Нової Пошти (кінець)
            if ( ! $this->get_option( Options::FREE_SHIPPING_MIN_SUM ) && ! ( 'no' == $this->get_option( Options::USE_FIXED_PRICE_ON_DELIVERY ) ) ||
                $this->get_option( Options::FREE_SHIPPING_MIN_SUM ) && ! ( 'no' == $this->get_option( Options::USE_FIXED_PRICE_ON_DELIVERY ) ) ) {
                // Мінімальна сума для безкоштовної доставки не визначена і встановлена фіксована вартість доставки
                if ( get_option( 'show_calc' ) ) {
                    // Show
                    if ( get_option('plus_calc')) {
                        $rate['cost'] = $this->get_option( Options::FIXED_PRICE );
                    } else {
                        $rate['cost'] = 0.00;
                        add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_display_fixed_shipping_cost' ), 10, 2 );
                    }
                } else {
                    // Not show
                    add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_no_display_shipping_cost' ), 10, 2 );
                    $rate['cost'] = $this->get_option( Options::FIXED_PRICE );
                }
            } elseif ( $this->get_option( Options::FREE_SHIPPING_MIN_SUM ) && ( 'no' == $this->get_option( Options::USE_FIXED_PRICE_ON_DELIVERY ) ) ) {
                // Мінімальна сума для безкоштовної доставки визначена і не встановлена фіксована вартість доставки

                if ( get_option( 'show_calc' ) ) {
                    // Show
                    if ( $this->get_option( Options::FREE_SHIPPING_MIN_SUM ) <= $cartTotal ) {
                        // Вартість кошику більше Мінімальної суми для безкоштовної доставки
                        $rate['label'] = ( null != $this->get_option( Options::FREE_SHIPPING_TEXT ) ) ? $this->get_option( Options::FREE_SHIPPING_TEXT ) : $this->title;
                        $rate['cost'] = 0.00;
                            add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_display_zero_shipping_cost' ), 10, 2 );
                    } else {
                        // Вартість кошику менше Мінімальної суми для безкоштовної доставки
                        if ( get_option('plus_calc')) {
                            $rate = apply_filters('woo_shipping_for_nova_poshta_before_add_rate', $rate, $cityRecipient);
                        } else {
                            add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_display_custom_shipping_cost' ), 10, 2 );
                            $rate = apply_filters('woo_shipping_for_nova_poshta_before_add_rate', $rate, $cityRecipient);
                        }
                    }
                } else {
                    // Not show
                    if ( $this->get_option( Options::FREE_SHIPPING_MIN_SUM ) <= $cartTotal ) {
                        // Вартість кошику більше Мінімальної суми для безкоштовної доставки
                        $rate['label'] = ( null != $this->get_option( Options::FREE_SHIPPING_TEXT ) ) ? $this->get_option( Options::FREE_SHIPPING_TEXT ) : $this->title;
                        if ( get_option('plus_calc')) {
                            add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_no_display_shipping_cost' ), 10, 2 );
                            // $rate = apply_filters('woo_shipping_for_nova_poshta_before_add_rate', $rate, $cityRecipient);
                            $rate['cost'] = 0.00;
                        } else {
                            $rate['cost'] = 0.00;
                        }
                    } else {
                        // Вартість кошику менше Мінімальної суми для безкоштовної доставки
                        add_filter( 'woocommerce_cart_shipping_method_full_label', array($this, 'mrkv_no_display_shipping_cost' ), 10, 2 );
                        $rate = apply_filters('woo_shipping_for_nova_poshta_before_add_rate', $rate, $cityRecipient);
                    }
                }
            }
            $this->add_rate($rate);
        }

        public function convert_weight_unit() {

            $weight_unit  =  get_option('woocommerce_weight_unit');

            if ( 'g' == $weight_unit ) return 0.001;
            if ( 'kg' == $weight_unit ) return 1;
            if ( 'lbs' == $weight_unit ) return 0.45359;
            if ( 'oz' == $weight_unit ) return 0.02834;
        }

        /**
        * Changes shipping label on '₴0.00', when rate cost is equal 0.00.
        */
        public function mrkv_display_zero_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method_poshtomat' == $method->get_id() ) {
                if( $method->cost == 0.00 ) {
                    $currency_symbol = get_woocommerce_currency_symbol();
                    $label  = $method->get_label() . ': ' . $currency_symbol . '0.00';
                }
            }
            return $label;
        }

        /**
        * Changes shipping label on fixed price value.
        */
        public function mrkv_display_fixed_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method_poshtomat' == $method->get_id() ) {
                $currency_symbol = get_woocommerce_currency_symbol();
                $cost = $this->get_option( Options::FIXED_PRICE );
                $label  = $method->get_label() . ': ' . $currency_symbol . $cost;
            }
            return $label;
        }

        /**
        * Changes shipping label on current rate cost value.
        */
        public function mrkv_display_custom_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method_poshtomat' == $method->get_id() ) {
                $currency_symbol = get_woocommerce_currency_symbol();
                $cost = $this->rate['cost'];
                $label  = $method->get_label() . ': ' . $currency_symbol . $cost;
            }
            return $label;
        }

        /**
        * Removes rate cost value/
        */
        public function mrkv_no_display_shipping_cost($label, $method) {
            if ( 'nova_poshta_shipping_method_poshtomat' == $method->get_id() ) {
                $label = $method->get_label();
            }
            return $label;
        }

        /**
         * Is this method available?
         * @param array $package
         * @return bool
         */
        public function is_available($package)
        {
            return $this->is_enabled();
        }

        /**
         * @return string
         */
        private function getDescription()
        {
            $href = "https://wordpress.org/support/view/plugin-reviews/nova-poshta-ttn?filter=5#postform";
            $link = '<a href="' . $href . '" target="_blank" class="np-rating-link">&#9733;&#9733;&#9733;&#9733;&#9733;</a>';

            $descriptions = array();
            $descriptions[] = __('Shipping with popular Ukrainian logistic company Nova Poshta', NOVA_POSHTA_TTN_DOMAIN);
            if (NPttn()->options->pluginRated) {
                $descriptions[] = __('Thank you for encouraging us!', NOVA_POSHTA_TTN_DOMAIN);
            } else {
                $descriptions[] = sprintf(__("If you like our work, please leave us a %s rating!", NOVA_POSHTA_TTN_DOMAIN), $link);
            }
            return implode('<br>', $descriptions);
        }
    }
endif;
