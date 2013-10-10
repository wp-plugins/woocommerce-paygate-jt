<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 /**
 * PayGate .
 *
 * @class 		WC_Gateway_paygate_card
 * @extends		WC_Payment_Gateway
 * @version		0.1.0
 * @author 		studio-jt
 */
 if ( !class_exists( 'WC_Gateway_PayGate_card' ) ) :
	 
class WC_Gateway_PayGate_card extends WC_Gateway_PayGate {
	
	var $access_key;
	
	function __construct(){
		
		$this->id 					= 'paygate-card';
		$this->method 				= 'card';
		$this->class_name			= str_replace('-', '_', __CLASS__);
		$this->icon 				= '';
		$this->method_title 			= 'PayGate [Card]';
		$this->method_description	= 'paygate_card';
		
		parent::__construct();
	}

	public function init_form_fields() {
		parent::init_form_fields();
		
		$this->form_fields = array_merge( $this->form_fields, array(
			'title' => array(
				'title' => __('Title', 'woocommerce'),
				'type' => 'text',
				'description' => __('사용자가 체크 아웃하는 동안 제목을 제어합니다.', 'woocommerce'),
				'default' => __('카드 결제', 'woocommerce'),
				'desc_tip' => true,
			),
		));
	}
	
	public function get_paygate_args( ) {
		
		$args = array(
			'cardquota'			=> '',
			'cardexpiremonth' 	=> '',
			'cardexpireyear' 		=> '',
			'cardsecretnumber' 	=> '',
			'cardownernumber' 	=> '',
			'cardtype' 			=> '',
			'cardnumber' 		=> '',
			'cardauthcode' 		=> '',
		);

		
		return $args;
	}
}
endif;