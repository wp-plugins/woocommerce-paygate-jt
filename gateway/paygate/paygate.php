<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'WC_Gateway_PayGate' ) ) :

define( WCKP_PAYGATE_PLUGIN_DIR,  plugin_dir_path( __FILE__ ) );	
define( WCKP_PAYGATE_PLUGIN_URL,  plugin_dir_url ( __FILE__ ) );
define( WCKP_PAYGATE_TEMPLATES_PATH,  trailingslashit( WCKP_PAYGATE_PLUGIN_DIR.'templates') );

class WC_Gateway_PayGate extends WC_Payment_Gateway {

	function __construct() {
		global $woocommerce;
		
		$this->has_fields 			= false;
		$this->templates_path 		= WCKP_PAYGATE_TEMPLATES_PATH;

		// Define user set variables
		$this->debug 				= false;
		$this->title 				= $this->get_option('title');
		$this->enabled 				= $this->get_option('enabled');
		$this->description 			= $this->get_option('description');
		$this->order_description 	= $this->get_option('order_description');
		$this->pg_skin 				= $this->get_option('pg_skin');
		//$this->use_escrow			= $this->get_option('use_escrow');
		
		$this->access_key 			= $this->get_option('access_key');
		$this->api_key 				= $this->get_option('api_key');
		$this->supported_currencies = array('KRW');
		
		$this->notify_url   		= add_query_arg( 'wc-api', strtolower($this->class_name), home_url( '/' ) ) ;
		// 결제 모듈 ajax 사용이 가능해질 경우.
		//$this->notify_url   		= admin_url( 'admin-ajax.php?action=wpkp_paygate_response' );
		
		// load form fields.
		$this->init_form_fields();
		
		// load settings (via WC_Settings_API)
		$this->init_settings();
		
		// Logs
		if ( 'true' == $this->debug )
			$this->log = $woocommerce->logger();

		if ( ! $this->is_valid_for_use() ) $this->enabled = false;

		//add pay script
		add_action('wp_enqueue_scripts', array( $this, 'script' ) );
		
		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_'.$this->id, array( $this, 'receipt_page' ) );
		
		// 결제 모듈 ajax 사용이 가능해질 경우.
		//add_action( 'wp_ajax_wpkp_paygate_response', array( $this, 'process_payment_response' ) );
		
		// Payment listener/API hook
		add_action( 'woocommerce_api_'.strtolower($this->class_name), array( $this, 'process_payment_response' ) );
		
		// add email fields
		//add_filter('woocommerce_email_order_meta_keys', array( $this, 'filterWooEmailOrderMetaKeys' ) );
	}

	public function init_form_fields() {
		global $openxpay;
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Enable ', 'woocommerce'),
				'default' => 'yes'
			),
			'access_key' => array(
				'title' => __('상점 아이디', 'woocommerce-koreapack'),
				'type' => 'text',
				'description' => __('상점아이디 paygate로 부터 발급받으신 상점 아이디(로그인 아이디)를 입력하세요)', 'woocommerce-koreapack'),
				'default' => __('', 'woocommerce'),
				'desc_tip' => true,
			),
			'description' => array(
				'title' => __('Customer Message', 'woocommerce'),
				'type' => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default' => ''
			),
			'api_key' => array(
				'title' => __('API 인증값', 'woocommerce'),
				'type' => 'text',
				'description' => __( '상점관리자 로그인후 맴버관리 > 자기정보관리 > API인증값 <br/> <a href="https://km.paygate.net/display/CS/Transaction+Hash+Verification%28SHA-256%29">참조</a>', 'woocommerce-koreapack' ),
				'default' => '',
				'desc_tip' => true,
			),
			'pg_skin' => array(
				'title' => __('결제창 스킨', 'woocommerce-koreapack'),
				'type' => 'select',
				'options' => array('0' => 'style0', '1' => 'style1', '2' => 'style2', '3' => 'style3', '4' => 'style4', '5' => 'style5'),
				'default' => '5',
				'description' => 'paygate 에서 제공하는 스킨 입니다. <br/> 마음에 드는 스킨이 없을 경우 별도 css 를 추가 하시기 바랍니다.',
				'desc_tip' => true,
			),
			/*'use_escrow' => array(
				'title' => __('에스크로 강제', 'woocommerce-koreapack'),
				'type' => 'checkbox',
				'default' => 'no',
				'description' => '서비스 옵션에서 매매보호 이용함으로 설정된 경우 10만원 이상 현금거래시 유저가 매매보호 거래를 선택할 수 있는 화면이 제시됩니다. ',
				'desc_tip' => true,
			),*/
		);
	}

	public function is_valid_for_use() {

		if ( !in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_openxpay_supported_currencies', $this->supported_currencies ) ) ) {
			return false;
		}
		
		return true;
	}

	/* *
	 * 	script
	 * */

	public function script() {
		
		$order_id  = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		$order_key = isset( $_GET['key'] ) ? woocommerce_clean( $_GET['key'] ) : '';
		
		$order = new WC_Order( $order_id );
		
		$thanks_url = get_permalink( woocommerce_get_page_id( 'thanks' ) );
		$thanks_url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order->id, $thanks_url ) );

		if ($this->enabled == 'yes' && is_page( woocommerce_get_page_id( 'pay' ) ) == true) {
			wp_enqueue_script( 'wc_paygate_remote', 'https://api.paygate.net/ajax/common/OpenPayAPI.js',null, null,true);
			#ie 7 지원
			//echo '<script language="javascript" src="https://api.paygate.net/ajax/common/OpenPayAPI.js" charset="utf-8">';

			wp_enqueue_script( 'wc_paygate_main', WCKP_PAYGATE_PLUGIN_URL.'assets/paygate.js', null, null,true);
				
			wp_localize_script( 'wc_paygate_main', 'wckp', array(
				'notify_url' => add_query_arg('order', $order->id, add_query_arg( 'key', $order->order_key, $this->notify_url ) ),
				'thanks_url' => $thanks_url
			) ); 	
			wp_register_style( 'wc_paygate_main', WCKP_PAYGATE_PLUGIN_URL.'assets/style.css' );
			wp_enqueue_style( 'wc_paygate_main' );
		}
	}
	
	public function format_settings($value) {
		return ( is_array($value)) ? $value : html_entity_decode($value);
	}

	/* *
	 * 	admin option page
	 * */
	 
	public function admin_options() {
		require $this->templates_path . 'admin-woocommerce-' . $this->id . '.php';
	}

	/* *
	 * 	paygate 결제 요청 페이지
	 * */
	 
	public function receipt_page( $order_id ) {
		global $woocommerce;
		
		$order = new WC_Order( $order_id );
		
		$item_names = array();

		if ( sizeof( $order->get_items() ) > 0 )
			foreach ( $order->get_items() as $item )
				if ( $item['qty'] )
					$item_names[] = $item['name'] . ' x ' . $item['qty'];
		
		//echo '<p>'.__( '주문해 주셔서 감사합니다.(paygate) ', 'woocommerce-koreapack' ).'</p>';

		$paygate_args = $this->get_paygate_args( $order );
		$paygate_args = array_merge( array(
				'charset'		=> 'UTF-8',
				'mid' 		=> $this->access_key,
				'paymethod' 	=> $this->method,
				'goodname' 	=> sprintf( __( 'Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode( ', ', $item_names ),
				'unitprice' 	=> $order->get_total() + $order->get_order_discount(),
				'replycode' 	=> '',
				'replyMsg'	=> '',
				'mb_serial_no' => $order_id,
				'kindcss'		=> $this->pg_skin
			), $paygate_args 
		);

		if ( 'true' == $this->debug ) {
			$paygate_args['unitprice'] = 100;
		}
		
		if( $this->api_key ){
			$paygate_args['hashresult'] = '';
			$paygate_args['tid'] = '';
			$paygate_args['goodcurrency'] = '';
		}
		
		/*if( $this->use_escrow == 'yes' ){
			$paygate_args['loanSt'] = 'escrow';
		}*/
		
		$paygate_args_array = array();

		foreach( $paygate_args as $key => $value ) {
			$paygate_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" id="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
		}
		
		$output = '
			<div id="PGIOscreen"></div>
			<form method="post" name ="PGIOForm" id="PGIOForm">' .$woocommerce->nonce_field('process_payment_response', true, false). implode( '', $paygate_args_array) . '
			<a class="button alt" href="#" id="submit_paygate_payment_form">' . __( '결제', 'woocommerce' ) . '</a>
			</form>
		';
		
		echo $output;
	}

	/**
	 * add the successful transaction ID to WooCommerce order emails
	 * @param array $keys
	 * @return array
	 */
	public function filterWooEmailOrderMetaKeys($keys) {

		//$keys[] = 'Transaction ID';

		return $keys;
	}

	public function validate_fields() {
		return true;
	}

	public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
		);
		
	}

	public function process_payment_response() {
		global $woocommerce;


		// nonce check!
		$woocommerce->verify_nonce( 'process_payment_response' );
		
		$order_id  = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		$order_key = isset( $_GET['key'] ) ? woocommerce_clean( $_GET['key'] ) : '';
		
		$order = new WC_Order( $order_id );
		#check order key!! 
		if ( $order_id > 0 ) {
			if ( $order->order_key != $order_key ) {
				$woocommerce->add_error( '주문번호 검증 실패' );				
			}
		} else {
			$woocommerce->add_error( '주문번호 검증 실패' );
		}
		
		if ( $woocommerce->error_count() == 0 ) {
			
			#check SHA256!!!
			if( $this->check_salt( $order_id ) === true ) {
				
				$order->payment_complete();
				$woocommerce->cart->empty_cart();
				
			} else {
				$woocommerce->add_error( '주문번호 검증 실패' );
			}
		}

		if ( $woocommerce->error_count() == 0 ) {
			
			/* 결제 모듈 ajax 사용이 가능해질 경우.
			echo '<!--WCKP_START-->' . json_encode(
				array(
					'result'	=> 'success'
				)
			) . '<!--WCKP_END-->';*/
			
		} else {
			
			/* 결제 모듈 ajax 사용이 가능해질 경우.
			ob_start();
			$woocommerce->show_messages();
			$messages = ob_get_clean();
			
			echo '<!--WCKP_START-->' . json_encode(
				array(
					'result'	=> 'failure',
					'messages' 	=> $messages,
				)
			) . '<!--WCKP_END-->';*/
			
		}
		
		wp_redirect( $this->get_return_url( $order ) );

		
		die();
	}
	
	//결제검증
	public function check_salt( $order_id ) {
		global $woocommerce;
		
		if ( 'true' == $this->debug ) {
			$_POST['unitprice'] = 100;	
		}
		
		if( $this->api_key ) {
			$order 	= new WC_Order( $order_id );
			
			//$data = $_POST['replycode'].$_POST['tid'].$order_id.$_POST['unitprice'].$_POST['goodcurrency'];
			$data = $_POST['replycode'].$_POST['tid'].$order_id.$_POST['unitprice'].'KRW';
			
			$hashReuslt = hash('sha256',$this->api_key.$data);
						
			if( $hashReuslt != $_POST['hashresult'] ) {
				$woocommerce->add_error( '비정상적인 결제 시도' );
				return false;
			} 
			
		}
		
		return true;
	}
		
}

class wckp_PayGate {
	
	private $methods;
	
	
	function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}
	
	private function setup_globals() {
		$this->methods		= array(
			'alipay', 
			'ars', 
			'bank', 
			'cup', 
			'mobile', 
			'phonebill', 
			'card', 
		);
	}
	
	private function includes() {
		foreach( $this->methods as $method ) {
			require_once WCKP_PAYGATE_PLUGIN_DIR.'class-wc-gateway-paygate-'.$method.'.php';
		}
	}
	
	public function setup_actions() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_paygate_class' ) );
	}
	
	public function add_paygate_class( $methods ) {
		
		foreach( $this->methods as $method ) {
			array_unshift( $methods, 'WC_Gateway_PayGate_'.$method );
		}

		return $methods;
	}
}

$GLOBALS['paygate'] = new wckp_PayGate();

endif;