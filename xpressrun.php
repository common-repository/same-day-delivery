<?php
/**
	* @package WooCommerce
	* Plugin Name: XpressRun-Local-Delivery
	* Description: Your shipping method plugin
	* Version: 1.0.0
	* Author: XpressRun
    * Developer: Mamadou Soko, Souleymane Ouattara
	* Author URI: https://xpressrun.com
**/

if ( ! defined( 'WPINC' ) ){
	die('security by preventing any direct access to your plugin file');
}

if(!defined('WP_DEBUG')){
	define('WP_DEBUG', true);
}

define( 'XPRESSRUN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'XPRESSRUN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! class_exists( 'Xpressrun_db_config' ) ) {
	include_once XPRESSRUN_PLUGIN_PATH . 'src/access/xpressrun_db_config.php';
}
if ( ! class_exists( 'Xpressrun_Service' ) ) {
	include_once XPRESSRUN_PLUGIN_PATH . 'src/api/xpressrun_service.php';
}
if ( ! class_exists( 'Xpressrun_order_entity' ) ) {
	include_once XPRESSRUN_PLUGIN_PATH . 'src/entities/xpressrun_order_entity.php';
}

class WC_Integration_Xpressrun
{   
	public function __construct()
	{
		add_action('init', array($this, 'createApiConnectors'));
		add_action('init', array($this, 'createDatabaseXp'));
		add_action('woocommerce_shipping_init', array( $this, 'init' ) );
		add_filter('plugin_action_links_' . XPRESSRUN_PLUGIN_BASENAME, array($this, 'xpressrun_plugin_action_links'));
		add_action('wp_ajax_xpr_oauth_es', array($this, 'xpressrun_registration_request'));
	}

	/**
	 * Initialize the plugin.
	 */
	public function init()
	{
		add_filter('woocommerce_shipping_methods', array($this, 'add_integration'));
		add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
	}

	/** 
	 *
	 */
	public function createApiConnectors(){
		$xp_access_endpoints = new Xpressrun_access_endpoints();
	}
	
	/**
	 * Add a new integration to WooCommerce.
	 * @param $methods
	 *
	 * @return mixed
	 */
	function add_integration($methods)
	{
		$methods[] = 'Xpressrun_Shipping_Method';
		return $methods;
	}
	/**
	 * 
	 */
	public function add_shipping_method($methods)
	{
		if (is_array($methods)) {
			$methods['xpressrun'] = 'Xpressrun_Shipping_Method';
		}
		return $methods;
	}
	
	/**
	 *  
	 */
	public function xpressrun_plugin_action_links($links)
	{
		return array_merge(
			$links,
			array('<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=xpressrun') . '"> ' . __('Settings', 'xpressrun-shipping') . '</a>')
		);
	}

	public function xpressrun_registration_request()
	{
		$registration = new Xpressrun_Registration();
		$registration->sendRegistrationRequest();
	} 

	public function createDatabaseXp(){
		$configdb = new Xpressrun_db_config();
		$configdb->createOrderTable();
	}

}

function xpressrun_loading() {
	if ( class_exists( 'woocommerce' ) ) {
		if (class_exists('WC_Shipping_Method')){
			if ( ! class_exists( 'Xpressrun_Registration' ) ) {
				include_once XPRESSRUN_PLUGIN_PATH . 'src/xpressrun_Registration.php'; 
			}
			if ( ! class_exists( 'Xpressrun_access_endpoints' ) ) {
				include_once XPRESSRUN_PLUGIN_PATH . 'src/access/xpressrun_access_endpoints.php';
			}
			if ( ! class_exists( 'Xpressrun_Shipping_Method' ) ) {
				include_once XPRESSRUN_PLUGIN_PATH . 'src/xpressrun_shipping_method.php';
			}
			$WC_Integration_Xpressrun = new WC_Integration_Xpressrun();
		} 
	}
}
add_action( 'plugins_loaded', 'xpressrun_loading' );

function my_oauth_action_button_es()
{
	wp_enqueue_script(
		'my_oauth_action_button_es',
		plugin_dir_url(__FILE__) . 'assets/js/admin/ajax_oauth_es.js',
		array('jquery'),
		'5.0.4');
}
add_action('admin_enqueue_scripts', 'my_oauth_action_button_es');

function order_status_has_changed_xp( $order_id, $old_status, $new_status ) {
	if($old_status === 'pending' && $new_status === 'processing'){
			$order_service = new Xpressrun_Service();
			$response = $order_service->createXpressRunOrder($order_id);
			/*if($response->error){
			}*/
	}
}
add_action('woocommerce_order_status_changed','order_status_has_changed_xp', 20, 3);



