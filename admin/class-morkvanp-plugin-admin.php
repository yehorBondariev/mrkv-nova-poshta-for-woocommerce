<?php

class MNP_Plugin_Admin {

	private $plugin_name;

	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
		add_action( 'admin_notices', array($this, 'morkvanp_admin_notice' ) );
	}

	public function enqueue_styles() {
 		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mystyle.css', array(), $this->version, 'all' );
	}


	public function enqueue_scripts() {
		wp_enqueue_script( 'np-script-admin', PLUGIN_URL .'public/js/script.js', array(), MNP_PLUGIN_VERSION , true );
	}

	function morkvanp_admin_notice() {
        global $pagenow;
        $screen = get_current_screen();
        $all_zones = WC_Shipping_Zones::get_zones();
        $all_zones_count = count( $all_zones );

        if ( ( 'toplevel_page_morkvanp_plugin' === $screen->id || 'woocommerce_page_wc-settings' === $screen->id ) && ! $all_zones_count ) {
             echo '<div style="margin-left: 2px;" class="notice notice-info is-dismissible">
                <p style="font-size:14px;">
                     <img src="' . NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL . '/includes/nova_poshta_25px.png"
					 	style="height:25px;width:25px; margin-right:7px; margin-top:2px;">
                     <span>Для налаштування способів доставки плагіну MORKVA <strong>Shipping for Nova Poshta</strong> ввімкніть </span>
                     <a href="admin.php?page=wc-settings&tab=shipping"> <span> Зони доставки</span></a> <span> WooCommerce.</span>
                </p></div>';
        }

		if ( ! get_option( 'text_example' ) ) {
        	echo '<div style="margin-left: 2px;" class="notice notice-error is-dismissible">
                <p style="font-size:14px;">
                     <img src="' . NOVA_POSHTA_TTN_SHIPPING_PLUGIN_URL . '/includes/nova_poshta_25px.png"
					 	style="height:25px;width:25px; margin-right:7px; margin-top:2px;">
                     <span>Для початку роботи з плагіном MORKVA <strong>Shipping for Nova Poshta</strong> отримайте ключ API </span>
                     <a href="https://my.novaposhta.ua/auth#apikeys"> <span> https://my.novaposhta.ua/auth#apikeys</span></a>. <span> Це забере 2 хв.</span>
                </p></div>';
        }
    }

}
