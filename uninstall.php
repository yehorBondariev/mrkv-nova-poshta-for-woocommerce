<?php
/**
 * Fired when the plugin is uninstalled.
 *
 *
 * @link       http://morkva.co.ua/
 * @since      1.0.0
 *
 * @package    morkvanp-plugin
 */
// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'novaposhta_ttn_invoices';
$sql = "DROP TABLE IF EXISTS $table_name;";
$wpdb->query($sql);