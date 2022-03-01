<?php

/**
 * Fired during plugin activation
 *
 * @link       http://morkva.co.ua/
 * @since      1.0.0
 *
 * @package    morkvanp-plugin
 * @subpackage morkvanp-plugin/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    morkvanp-plugin
 * @subpackage morkvanp-plugin/includes
 * @author     MORKVA <hello@morkva.co.ua>
 */


class MNP_Plugin_Activator {
	/**
	 * The code that runs during plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

			if ( is_plugin_active ( 'woo-shipping-for-nova-poshta/woo-shipping-for-nova-poshta.php' ) ) {
					$plugins = 'woo-shipping-for-nova-poshta/woo-shipping-for-nova-poshta.php';
					//deactivate_plugins( $plugins, $silent = false, $network_wide = null );
			}





       global $wpdb;

       $table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';

       if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
       		// if table not exists, create this table in DB
       		$charset_collate = $wpdb->get_charset_collate();

       		$sql = "CREATE TABLE $table_name (
       			id int(11) AUTO_INCREMENT,
       			order_id int(11) NOT NULL,
       			order_invoice varchar(255) NOT NULL,
                        invoice_ref varchar(255) NOT NULL,
       			PRIMARY KEY(id)
       		) $charset_collate;";
       		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
       		dbDelta( $sql );
       } else {

       }

       flush_rewrite_rules();
    }
}
