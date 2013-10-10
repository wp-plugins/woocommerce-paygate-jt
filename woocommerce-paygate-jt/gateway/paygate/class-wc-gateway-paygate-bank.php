<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 /**
 * PayGate .
 *
 * @class			WC_Gateway_paygate_bank
 * @extends             WC_Payment_Gateway
 * @version             	0.1.0
 * @author              	studio-jt
 */
if ( !class_exists( 'WC_Gateway_PayGate_bank' ) ) :
	
class WC_Gateway_PayGate_bank extends WC_Gateway_PayGate {
        
        var $access_key;
        
        function __construct(){
                
                $this->id					= 'paygate-bank';
                $this->method                  	= '4';
                $this->class_name			= str_replace('-', '_', __CLASS__);
                $this->icon                             	= '';
                $this->method_title			= 'PayGate [계좌이체]';
                $this->method_description       = 'paygate_bank';
                
                parent::__construct();
        }

        public function init_form_fields() {
                parent::init_form_fields();
                
                $this->form_fields = array_merge( $this->form_fields, array(
                        'title' => array(
                                'title' => __('Title', 'woocommerce'),
                                'type' => 'text',
                                'description' => __('사용자가 체크 아웃하는 동안 제목을 제어합니다.', 'woocommerce'),
                                'default' => __('실시간 계좌이체', 'woocommerce'),
                                'desc_tip' => true,
                        ),
                ));
        }
        
        public function get_paygate_args( ) {
                $args = array(
                        'socialnumber'		=> '',
                        'receipttoname'	=> '',
                );

                
                return $args;
        }
}
endif;