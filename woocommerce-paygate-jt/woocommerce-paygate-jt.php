<?php
 /**
 * Plugin Name: WooCommerce Paygate JT
 * Plugin URI: http://www.studio-jt.co.kr
 * Description: woocommerce paygate 결제모듈
 * Version: 0.3.1
 * Author: 스튜디오 제이티 (support@studio-jt.co.kr)
 * Author URI: studio-jt.co.kr
 *
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'WC_Korea_Pack' ) ) :

class WC_Korea_Pack {

	private static $instance;
	//public $gateway_items = array( 'openxpay', 'paygate' );
	public $gateway_items = array( 'paygate' );
	public $shipping_items = array( 'condition-on-free' );
        
	private function __construct() { /* Do nothing here */ }
        
	public static function getInstance() {
    	if( !class_exists( 'Woocommerce' ) ) {
        	return null;
		} else if( ! isset( self::$instance ) ) {
			self::$instance = new WC_Korea_Pack;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}
	
	private function setup_globals() {
		
		$this->file       		= __FILE__;
		$this->plugin_dir      	= apply_filters( 'wc_korea_pack_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url		= apply_filters( 'wc_korea_pack_plugin_dir_url',   plugin_dir_url ( $this->file ) );
		
		// Includes
		$this->includes_dir 	= apply_filters( 'wc_korea_pack_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url 	= apply_filters( 'wc_korea_pack_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );
		
		//gateway
		$this->gateway_dir 		= apply_filters( 'wc_korea_pack_gateway_dir', trailingslashit( $this->plugin_dir . 'gateway'  ) );
		$this->gateway_url 		= apply_filters( 'wc_korea_pack_gateway_url', trailingslashit( $this->plugin_url . 'gateway'  ) );
		
		//Gateway list item
		$this->gateway_items	= apply_filters( 'wc_korea_pack_gateway', $this->gateway_items );
		
		//shipping
		$this->shipping_dir 	= apply_filters( 'wc_korea_pack_shipping_dir', trailingslashit( $this->plugin_dir . 'shipping'  ) );
		$this->shipping_url 	= apply_filters( 'wc_korea_pack_shipping_url', trailingslashit( $this->plugin_url . 'shipping'  ) );
		
		// Languages
		$this->lang_dir     	= apply_filters( 'wc_korea_pack_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );
		
	}
	
	private function includes() {
		
		require_once( $this->includes_dir . 'functions.php' );
		
		//gateway load
		foreach( $this->gateway_items as $gateway_item ) {
			require_once( $this->gateway_dir . $gateway_item .'/'. $gateway_item .'.php' );	
		}

		//shipping load
		foreach( $this->shipping_items as $shipping_item ){
			require_once( $this->shipping_dir . $shipping_item .'.php' );
		}
	}
	
	private function setup_actions() {
		
		/*$actions = array(
			
		);
		
		// Add the actions
		foreach( $actions as $class_action )
			add_action( 'wc_korea_pack_'.$class_action, array( $this, $class_action ), 5 );
		*/
	}	
        
        
}

function wc_korea_pack() {
        return WC_Korea_Pack::getInstance();
}

add_action( 'plugins_loaded', 'wc_korea_pack', 0 );
endif;

