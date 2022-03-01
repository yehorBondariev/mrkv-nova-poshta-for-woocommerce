<?php

use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\base\Options;
use plugins\NovaPoshta\classes\Checkout;
use plugins\NovaPoshta\classes\Customer;

/**
 * Class WC_NovaPoshta_Shipping_Method
 */
class WC_NovaPoshta_Shipping_Method extends WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @param int $instance_id
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);
        $this->id = NOVA_POSHTA_TTN_SHIPPING_METHOD;
        $this->method_title = __('Nova Poshta', NOVA_POSHTA_TTN_DOMAIN);
        $this->method_description = $this->getDescription();

        $this->init();

        // Get setting values
        $this->title = $this->settings['title'];
        $this->enabled = $this->settings['enabled'];
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
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
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
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', NOVA_POSHTA_TTN_DOMAIN),
                'label' => __('Enable NovaPoshta', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Nova Poshta', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', NOVA_POSHTA_TTN_DOMAIN),
                'default' => __('Nova Poshta', NOVA_POSHTA_TTN_DOMAIN)
            ),


            Options::USE_FIXED_PRICE_ON_DELIVERY => [
                'title' => __('Set Fixed Price for Delivery.', NOVA_POSHTA_TTN_DOMAIN),
                'label' => __('If checked, fixed price will be set for delivery.', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => '',
            ],
            Options::FIXED_PRICE => [
                'title' => __('Fixed price', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'text',
                'description' => __('Delivery Fixed price.', NOVA_POSHTA_TTN_DOMAIN),
                'default' => 0.00
            ],
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
            'cost' => 0,
            'calc_tax' => 'per_item'
        );

        $location = Checkout::instance()->getLocation();
        $cityRecipient = Customer::instance()->getMetadata('nova_poshta_city', $location)
            //for backward compatibility with woocommerce 2.x.x
            ?: Customer::instance()->getMetadata('nova_poshta_city', '');

        if (NPttn()->options->useFixedPriceOnDelivery) {
            $rate['cost'] = NPttn()->options->fixedPrice;
        } elseif ($cityRecipient) {
            $citySender = NPttn()->options->senderCity;
            $serviceType = 'WarehouseWarehouse';
            /** @noinspection PhpUndefinedFieldInspection */
            $cartWeight = max(1, WC()->cart->cart_contents_weight);
            /** @noinspection PhpUndefinedFieldInspection */
            $cartTotal = max(1, WC()->cart->cart_contents_total);
            try {
                $result = NPttn()->api->getDocumentPrice($citySender, $cityRecipient, $serviceType, $cartWeight, $cartTotal);
                $cost = array_shift($result);
                $rate['cost'] = ArrayHelper::getValue($cost, 'Cost', 0);
            } catch (Exception $e) {
                NPttn()->log->error($e->getMessage());
            }
        }
        $invoice_dpay = get_option('invoice_dpay');
        if( intval( $invoice_dpay ) < WC()->cart->cart_contents_total ){
          $rate['cost']=0;
        }
        // Register the rate
        $rate = apply_filters('woo_shipping_for_nova_poshta_before_add_rate', $rate, $cityRecipient);
        $this->add_rate($rate);
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
