<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\ArrayHelper;
use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\base\OptionsHelper;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;
use plugins\NovaPoshta\classes\City;
use plugins\NovaPoshta\classes\Warehouse;
use plugins\NovaPoshta\classes\Poshtomat;

/**
 * Class Checkout
 * @property bool isCheckout
 * @property Customer $customer
 * @package plugins\NovaPoshta\classes
 */
class Checkout extends Base
{

    /**
     * @var Checkout
     */
    private static $_instance;

    /**
     * @return Checkout
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
        add_filter('woocommerce_billing_fields', array($this, 'addNovaPoshtaBillingFields'));
        add_filter('woocommerce_shipping_fields', array($this, 'addNovaPoshtaShippingFields'));
        add_action('woocommerce_checkout_process', array($this, 'saveNovaPoshtaOptions'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'updateOrderMeta'));

        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'displayShippingPhoneInOrderMeta'), 10, 1);
        add_action('woocommerce_thankyou', array($this, 'displayShippingPhoneOnThankyou'), 20);

        add_filter('woocommerce_cart_shipping_packages', array($this, 'updatePackages'));

        add_filter('nova_poshta_disable_default_fields', array($this, 'disableDefaultFields'));
        add_filter('nova_poshta_disable_nova_poshta_fields', array($this, 'disableNovaPoshtaFields'));

        add_filter('default_checkout_billing_nova_poshta_region', array($this, 'getDefaultRegion'));
        add_filter('default_checkout_billing_nova_poshta_city', array($this, 'getDefaultCity'));
        add_filter('default_checkout_billing_nova_poshta_warehouse', array($this, 'getDefaultWarehouse'));
        add_filter('default_checkout_shipping_nova_poshta_region', array($this, 'getDefaultRegion'));
        add_filter('default_checkout_shipping_nova_poshta_city', array($this, 'getDefaultCity'));
        add_filter('default_checkout_shipping_nova_poshta_warehouse', array($this, 'getDefaultWarehouse'));

        add_action( 'wp_footer', array($this, 'np_ajax_fetch' ));
    }

    public function displayShippingPhoneInOrderMeta($order) {
        echo '<p><strong>' . __('Phone', 'woocommerce') .':</strong><br> ' . get_post_meta( $order->get_id(), 'shipping_phone', true ) . '</p>';
    }

    public function displayShippingPhoneOnThankyou($orderId) {
        $order = wc_get_order( $orderId );
        $order_item_shipping = $order->get_data()['shipping_lines'];
        foreach ($order_item_shipping as $key => $value) {
            $is_nova_poshta_shipping_method = ( 'nova_poshta_shipping_method' == $value->get_data()['method_id'] ) ? true: false;
        }
        $is_shipping_phone = ( ! empty( get_post_meta( $orderId, 'shipping_phone', true ) )
            ? sanitize_text_field( get_post_meta( $orderId, 'shipping_phone', true ) )
            : false );
        if ( $is_nova_poshta_shipping_method && $order->get_shipping_address_1() && $is_shipping_phone ) {
            echo '<address>Телефон для доставки на іншу адресу: ' . $is_shipping_phone . '</address>';
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function saveNovaPoshtaOptions()
    {
        if ( NPttn()->isPost() && NPttn()->isNPttn() && NPttn()->isCheckout() || NPttnPM()->isPost() && NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) {
            $location = $this->getLocation();

            $region = ArrayHelper::getValue($_POST, Region::key($location));
            $city = ArrayHelper::getValue($_POST, City::key($location));
            $warehouse = ArrayHelper::getValue($_POST, Warehouse::key($location));

            $this->customer->setMetadata('nova_poshta_region', $region, $location);
            $this->customer->setMetadata('nova_poshta_city', $city, $location);
            $this->customer->setMetadata('nova_poshta_warehouse', $warehouse, $location);
        }
    }

    /**
     * Filter for hook woocommerce_shipping_init
     * @param $fields
     * @return mixed
     */
    public function maybeDisableDefaultShippingMethods($fields)
    {
        if ( NPttn()->isPost() && NPttn()->isNPttn() && NPttn()->isCheckout() || NPttnPM()->isPost() && NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) {
            $fields = apply_filters('nova_poshta_disable_default_fields', $fields);
            $fields = apply_filters('nova_poshta_disable_nova_poshta_fields', $fields);
        } else {
            $location = $this->getLocation();
            $fields[$location][$location . '_state']['required'] = false;
            $fields[$location][$location . '_state']['required'] = false;
            $fields[$location][$location . '_postcode']['required'] = false;
        }
        return $fields;
    }

    /**
     * Hook for adding nova poshta billing fields
     * @param array $fields
     * @return array
     */
    public function addNovaPoshtaBillingFields($fields)
    {
        return $this->addNovaPoshtaFields($fields, Area::BILLING);
    }

    /**
     * Hook for adding nova poshta shipping fields
     * @param array $fields
     * @return array
     */
    public function addNovaPoshtaShippingFields($fields)
    {
        return $this->addNovaPoshtaFields($fields, Area::SHIPPING);
    }

    /**
     * Update the order meta with field value
     * @param int $orderId
     */
    public function updateOrderMeta($orderId)
    {

        //address shipping method address_trigger

        $billing_city = "";

        if (isset($_POST['billing_city'])) {
            $billing_city = $_POST['billing_city'];
        }

        $billing_address = "";

        if (isset($_POST['billing_address_1'])) {
            $billing_address = $_POST['billing_address_1'];
        }

        if (!get_post_meta($orderId, '_billing_city')) {
            update_post_meta($orderId, '_billing_city', $billing_city);
            update_post_meta($orderId, '_billing_address_1', $billing_address);
            update_post_meta($orderId, '_shipping_city', $billing_city);
            update_post_meta($orderId, '_shipping_address_1', $billing_address);
        }

        //if not address shipping method:
        if ( NPttn()->isNPttn() && NPttn()->isCheckout() || NPttnPM()->isPost() && NPttnPM()->isNPttnPM() && NPttnPM()->isCheckoutPoshtomat() ) {
            $fieldGroup = $this->getLocation();

            $regionKey = Region::key($fieldGroup);
            $regionRef = isset( $_POST[$regionKey] ) ? sanitize_text_field($_POST[$regionKey]) : '';
            $area = new Region($regionRef);
            update_post_meta($orderId, '_' . $fieldGroup . '_state', $area->description);

            $cityKey = City::key($fieldGroup);

            $cityRef = isset($_POST['npcityref']) ? sanitize_text_field($_POST['npcityref']) : sanitize_text_field($_POST[$cityKey]);
            $city = new City($cityRef);
            update_post_meta($orderId, '_' . $fieldGroup . '_city', $city->description);

            $warehouseKey = Warehouse::key($fieldGroup);
            $warehouseRef = isset($_POST['npwhref']) ? sanitize_text_field($_POST['npwhref']) : sanitize_text_field($_POST[$warehouseKey]);
            $warehouse = new Warehouse($warehouseRef);
            update_post_meta($orderId, '_' . $fieldGroup . '_address_1', $warehouse->description);

            //TODO this part should be refactored
            $shippingFieldGroup = Area::SHIPPING;
            if ($this->shipToDifferentAddress()) {
                update_post_meta($orderId, '_' . Region::key($shippingFieldGroup), $area->ref);
                update_post_meta($orderId, '_' . City::key($shippingFieldGroup), $city->ref);
                update_post_meta($orderId, '_' . Warehouse::key($shippingFieldGroup), $warehouse->ref);
            } else {
                update_post_meta($orderId, '_' . $shippingFieldGroup . '_state', $area->description);
                update_post_meta($orderId, '_' . $shippingFieldGroup . '_city', $city->description);
                update_post_meta($orderId, '_' . $shippingFieldGroup . '_address_1', $warehouse->description);
            }

            $deliveryprice = isset( $_POST['deliveryprice'] ) ? $_POST['deliveryprice'] : '';
            update_post_meta( $orderId, 'deliveryprice', $deliveryprice );

            $shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
            update_post_meta( $orderId, 'shipping_phone', $shipping_phone );
        }
    }

    /**
     * @param array $packages
     * @return array
     */
    public function updatePackages(array $packages)
    {
        if (false) {
            $location = $this->getLocation();
            $warehouse = $this->customer->getMetadata('nova_poshta_warehouse', $location);
            $city = $this->customer->getMetadata('nova_poshta_city', $location);

            $cii = new City($city);

            if (get_locale() == 'ru_RU') {
                $desc1 = $cii->content->description_ru;
            } else {
                if (isset($cii->content->description)) {
                    $desc1 = $cii->content->description;
                } else {
                    $desc1 = '';
                }
            }

            $region = $this->customer->getMetadata('nova_poshta_region', $location);
            $wai = new Warehouse($warehouse);
            if (get_locale() == 'ru_RU') {
                $desc2 = $wai->content->description_ru;
            } else {
                if (isset($wai->content->description)) {
                    $desc2 = $wai->content->description;
                } else {
                    $desc2 = '';
                }
            }
            foreach ($packages as &$package) {
                $package['destination']['address_1'] = $desc2;//$warehouse;
                $package['destination']['city'] = $desc1;//$city;
                //$package['destination']['state'] = '888';//$region;
            }
        }
        return $packages;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function disableNovaPoshtaFields($fields)
    {
        $location = $this->shipToDifferentAddress() ? Area::BILLING : Area::SHIPPING;

        $fields[$location][$location . '_state']['required'] = false;

        $fields[$location][$location . '_state']['required'] = false;
        $fields[$location][$location . '_postcode']['required'] = false;
        $fields[$location][$location.'_address_1']['required'] = false;
        $fields[$location][$location.'_city']['required'] = false;

        $fields[$location][City::key($location)]['required'] = false;
        $fields[$location][Warehouse::key($location)]['required'] = false;
        $fields[$location][Region::key($location)]['required'] = false;

        return $fields;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function disableDefaultFields($fields)
    {
        $location = $this->getLocation();
        if (array_key_exists($location . '_state', $fields[$location])) {
            $fields[$location][$location . '_state']['required'] = false;
        }
        if (array_key_exists($location . '_city', $fields[$location])) {
            $fields[$location][$location . '_city']['required'] = false;
        }
        if (array_key_exists($location . '_address_1', $fields[$location])) {
            $fields[$location][$location . '_address_1']['required'] = false;
        }
        if (array_key_exists($location . '_postcode', $fields[$location])) {
            $fields[$location][$location . '_postcode']['required'] = false;
        }
        return $fields;
    }

    /**
     * Get address type which stores nova poshta options: either shipping or billing
     * @return string
     */
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
     * Check Woocommerce version, does it satisfy code requirements
     * @param string $version minimum version, lower versions of Woocommerce are legacy
     * @return bool
     */
    public function isLegacyWoocommerce(/** @noinspection PhpUnusedParameterInspection */
        $version = '3.0'
    )
    {
        //TODO compare with woocommerce version
        return !method_exists(WC()->customer, 'set_billing_address_1');
    }

    /**
     * @return string
     */
    public function getDefaultRegion()
    {
        return $this->customer->getMetadata('nova_poshta_region', Area::SHIPPING);
    }

    /**
     * @return string
     */
    public function getDefaultCity()
    {
        return $this->customer->getMetadata('nova_poshta_city', Area::SHIPPING);
    }

    /**
     * @return string
     */
    public function getDefaultWarehouse()
    {
        return $this->customer->getMetadata('nova_poshta_warehouse', Area::SHIPPING);
    }

    /**
     * @return string
     */
    public function getDefaultPoshtomat()
    {
        return $this->customer->getMetadata('nova_poshta_poshtomat', Area::SHIPPING);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function getIsCheckout()
    {
        if (function_exists('is_checkout')) {
            return is_checkout();
        } else {
            //for backward compatibility with woocommerce 2.x.x
            global $post;
            $checkoutPageId = get_option('woocommerce_checkout_page_id');
            $pageId = ArrayHelper::getValue($post, 'ID', null);
            return $pageId && $checkoutPageId && ($pageId == $checkoutPageId);
        }
    }

    /**
     * @return Customer
     */
    protected function getCustomer()
    {
        return Customer::instance();
    }

    /**
     * @param array $fields
     * @param string $location
     * @return array
     */
    private function addNovaPoshtaFields($fields, $location)
    {
        $factory = AreaRepositoryFactory::instance();
        $area = $this->customer->getMetadata('nova_poshta_region', $location);
        $city = $this->customer->getMetadata('nova_poshta_city', $location);
        $required = NPttn()->isGet() ?: (NPttn()->isNPttn() && NPttn()->isCheckout());
        $current_shipping_method = WC()->session->get( 'chosen_shipping_methods' );

        $value_for_checkout_selects = esc_attr(get_option('morkvanp_checkout_count'));

        $warehouse_label = __( 'Nova Poshta Warehouse (#)', NOVA_POSHTA_TTN_DOMAIN );
        $warehouse_options = OptionsHelper::getList( $factory->warehouseRepo()->findByParentRefAndNameSuggestion($city) );
        if ( 'nova_poshta_shipping_method_poshtomat' == $current_shipping_method[0] ) {
            // $warehouse_label = __( 'Nova Poshta Poshtomat (#)', NOVA_POSHTA_TTN_DOMAIN );
            $warehouse_options = OptionsHelper::getList( $factory->poshtomatRepo()->findByParentRefAndNameSuggestion($city) );
        }
        $fields['shipping_phone'] = array(
            'label'        => __('Phone', 'woocommerce'),
            'type'         => 'text',
            'required'     => false,
            'class'        => array('form-row-wide'),
            'priority'     => 25,
            'clear'        => true
        );

        if ( $value_for_checkout_selects == '3fields' ) {
            $fields[Region::key($location)] = [
                'label' => __('Region', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'select',
                'required' => $required,
                'default' => '',
                'options' => OptionsHelper::getList($factory->regionRepo()->findAll()),
                'class' => array(),
                'custom_attributes' => array(),
            ];

            $fields[City::key($location)] = [
                'label' => __('City', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'select',
                'required' => $required,
                'options' => OptionsHelper::getList($factory->cityRepo()->findByParentRefAndNameSuggestion($area)),
                'class' => array(),
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __('Choose city', NOVA_POSHTA_TTN_DOMAIN),
            ];
            $fields[Warehouse::key($location)] = [
                'label' => $warehouse_label,
                'type' => 'select',
                'required' => $required,
                'options' => $warehouse_options,
                'class' => array(),
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __('Choose warehouse', NOVA_POSHTA_TTN_DOMAIN),
            ];
        } elseif ( $value_for_checkout_selects == '2fields' ) {
              $fields[City::key($location)] = [
                'label' => __('City', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'select',
                'required' => $required,
                'options' => OptionsHelper::getList($factory->cityRepo()->findAll()),
                'class' => array(),
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __('Choose city', NOVA_POSHTA_TTN_DOMAIN),
            ];
            $fields[Warehouse::key($location)] = [
                'label' => __('Nova Poshta Warehouse (#)', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'select',
                'required' => $required,
                'options' => OptionsHelper::getList($factory->warehouseRepo()->findByParentRefAndNameSuggestion($city)),
                'class' => array(),
                'value' => '',
                'custom_attributes' => array(),
                'placeholder' => __('Choose warehouse', NOVA_POSHTA_TTN_DOMAIN),
            ];
        } elseif ( $value_for_checkout_selects == '2fieldsdb' ) {
            $fields[CityNP::key($location)] = [
              'label' => __('City', NOVA_POSHTA_TTN_DOMAIN),
              'type' => 'text',
              'required' => $required,
              'class' => array(),
              'id' => 'billing_mrk_nova_poshta_city',
              'value' => '',
              'custom_attributes' => array('onkeyup' => "fetchCities()"),
              'placeholder' => __('Choose city', NOVA_POSHTA_TTN_DOMAIN),
              'autocomplete' => "off"
            ];
            $fields[WarehouseNP::key($location)] = [
                'label' => __('Nova Poshta Warehouse (#)', NOVA_POSHTA_TTN_DOMAIN),
                'type' => 'text',
                'required' => $required,
                'class' => array(),
                'id' => 'billing_mrk_nova_poshta_warehouse',
                'value' => '',
                'custom_attributes' => array('onkeyup' => "fetchWarehouses()"),
                'placeholder' => __('Choose warehouse', NOVA_POSHTA_TTN_DOMAIN),
                'autocomplete' => "off"
            ];
        }

        return $fields;
    }

    public function np_ajax_fetch() {
		?>
		<script>
        // Set $cityRef as value in 'Місто' input element.
        var formcheckoutcity = document.querySelector("form[name=checkout");
        if(formcheckoutcity){
            formcheckoutcity.addEventListener('submit', setCityValRef);
        }
        // Set $warehouseRef as value in 'Склад(№)' input element.
        var formcheckoutwh = document.querySelector("form[name=checkout");
        if(formcheckoutwh){
            formcheckoutwh.addEventListener('submit', setWarehouseValRef);
        }
        function setCityValRef(event) {
            var datacityref = document.getElementById("billing_mrk_nova_poshta_city")
            if(datacityref){
                datacityref.getAttribute("data-ref");
                document.getElementById("billing_mrk_nova_poshta_city").style.color = "transparent";
                document.getElementById("billing_mrk_nova_poshta_city").value = datacityref;
            }
        }
        function setWarehouseValRef(event) {
            var datawhref = document.getElementById("billing_mrk_nova_poshta_warehouse")
            if(datawhref){
                datawhref.getAttribute("data-ref");
                document.getElementById("billing_mrk_nova_poshta_warehouse").style.color = "transparent";
                document.getElementById("billing_mrk_nova_poshta_warehouse").value = datawhref;
            }
        }
        // Put chosen City name to City field on Checkout page and close search dropdown.
        function selectCity(val,ref) {
            var billingMrkNpCity = jQuery("#billing_mrk_nova_poshta_city");
            var elcityref = '<input type="hidden" name="npcityref" value="' + ref +'"></input>';
            if ( jQuery( "input[name=npcityref]" ).length == 0 ) {
                jQuery('form').append(elcityref);
            }
            billingMrkNpCity.addClass('border-radius-zero');
            billingMrkNpCity.val(val);
            billingMrkNpCity.attr('data-ref',ref);
            jQuery("#npdatafetch").css('display','none');
        }
        // Put chosen Warehouse name to Warehouse field on Checkout page and close search dropdown.
        function selectWarehouse(val,ref) {
            var billingMrkNpWarehouse = jQuery("#billing_mrk_nova_poshta_warehouse");
            var elwhref = '<input type="hidden" name="npwhref" value="' + ref +'"></input>';
            if ( jQuery( "input[name=npwhref]" ).length == 0 ) {
                jQuery('form').append(elwhref);
            }
            jQuery("#billing_mrk_nova_poshta_warehouse_field").removeClass("woocommerce-invalid woocommerce-invalid-required-field");
            jQuery("#billing_mrk_nova_poshta_warehouse_field").addClass("woocommerce-validated");
            billingMrkNpWarehouse.addClass('border-radius-zero');
            billingMrkNpWarehouse.val(val);
            billingMrkNpWarehouse.attr('data-ref',ref);
            jQuery("#npdatafetchwh").css('display','none');
        }
        // АJAX get City names from "$wpdb->prefix . 'nova_poshta_city'" table of site Database.
		function fetchCities(){
		    jQuery.ajax({
		        url: '<?php echo admin_url('admin-ajax.php'); ?>',
		        type: 'post',
		        data: { action: 'npdata_fetch', npcityname: jQuery('#billing_mrk_nova_poshta_city').val(), npcityref: jQuery('#billing_mrk_nova_poshta_city').data('ref') },
		        success: function(data) {
		        	if ( data.length > 2 ) {
                        jQuery('#npdatafetch').show();
			            jQuery('#npdatafetch').html( data );
			    	  }
		        }
		    });
		}
        jQuery('input[name^=shipping_method]').on("change", function() {
          //console.log('update');
          jQuery('body').trigger('update_checkout'); // TODO
          // disableNovaPoshtaOptions();
          // enableNovaPoshtaShippingOptions();
        });
        // jQuery(document.body).on('updated_checkout', function() {
        //   disableNovaPoshtaOptions();
        //   enableNovaPoshtaShippingOptions();
        // });
        // console.log('Shipping Method = '+ JSON.stringify(jQuery(".shipping_method:checked").val()));
		// console.dir('Shipping Method = '+ jQuery(".shipping_method:checked").val());
        var curShipMethod = jQuery(".shipping_method:checked").val();
        // АJAX get Warehouses names of chosen City from "$wpdb->prefix . 'nova_poshta_warehouse'" table of site Database.
        function fetchWarehouses(){
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'post',
                data: { action: 'npdata_fetchwh',
                        npwhref: jQuery('#billing_mrk_nova_poshta_warehouse').val(),
                        npcityref: jQuery('#billing_mrk_nova_poshta_city').data('ref'),
                        shipping_method: curShipMethod },
                success: function(data) {
                  if ( data.length > 2 ) {
                      jQuery('#npdatafetchwh').show();
                      jQuery('#npdatafetchwh').html( data );
                  }
                }
            });
        }
		</script>

		<?php
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

/**
 * Class CityNP
 * @package plugins\NovaPoshta\classes
 */
class CityNP extends City
{

    /**
     * @return string
     */
    protected static function _key()
    {
        return 'mrk_nova_poshta_city';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->cityRepo();
    }

}

/**
 * Class WarehouseNP
 * @package plugins\NovaPoshta\classes
 */
class WarehouseNP extends Warehouse
{

    /**
     * @return string
     */
    protected static function _key()
    {
        //return '_warehouse';
        return 'mrk_nova_poshta_warehouse';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->warehouseRepo();
    }
}
