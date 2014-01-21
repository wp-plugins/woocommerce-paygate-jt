<?php
class WC_Korea_Pack_options {
    
    function __construct(){
        $this->settings = array(
            array( 'title' => __( 'WooCommerce Paygate JT', 'wc_korea_pack' ), 'type' => 'title', 'desc' => '', 'id' => 'wckp_general_options' ),
            array(
                'title'     => __( '하나의이름', 'wc_korea_pack' ),
                'desc'      => __( '결제 정보 입력시 이름에 하나의 입력 값만을 적용합니다.', 'wc_korea_pack' ),
                'id'        => 'woocommerce_wckp_namefix',
                'default'   => 'no',
                'type'      => 'checkbox'
            ),
            array(
                'title'     => __( '회사이름삭제', 'wc_korea_pack' ),
                'desc'      => __( '회사 이름을 사용하지 않습니다.', 'wc_korea_pack' ),
                'id'        => 'woocommerce_wckp_companyfix',
                'default'   => 'no',
                'type'      => 'checkbox'
            ),
            array(
                'title'     => __( '우편번호검색', 'wc_korea_pack' ),
                'desc'      => __( '한국 우편번호 검색을 사용.', 'wc_korea_pack' ),
                'id'        => 'woocommerce_wckp_zip_code',
                'default'   => 'no',
                'desc_tip'  =>  __( '사용할 경우 도시 이름, 국가 선택 입력박스를 제거하며, 주소 입력 순서가 변경됩니다.', 'wc_korea_pack' ),
                'type'      => 'checkbox'
            ),
            array(
                'title'     => __( '우편번호검색형식', 'wc_korea_pack' ),
                'desc'      => __( '지번 기반 검색은 업데이트가 중단 됩니다. <br />도로명기반 사용시 반드시 테스트가 필요합니다.<br /> 일부 사이트에서 CSS 수정이 필요 할 수 있습니다.', 'wc_korea_pack' ),
                'id'        => 'woocommerce_wckp_search_zip_code_type',
                'options'   => array('road' => '도로명기반', 'jibun' => '지번기반'),
                'default'   => 'jibun',
                'type'      => 'radio'
            ),
            array(
                'title'     => __( '디버그 모드', 'wc_korea_pack' ),
                'desc'      => __( 'paygate JT의 디버그 모드 적용', 'wc_korea_pack' ),
                'id'        => 'woocommerce_wckp_mode_debug',
                'default'   => 'no',
                'desc_tip'  =>  __( '활성화시 woocommerce의 로그를 사용합니다.<br /> woocommerce의 로그는 woocommerce/logs/ 디렉토리에 txt 파일 형태로 저장 됩니다.', 'wc_korea_pack' ),
                'type'      => 'checkbox'
            ),

            array( 'type' => 'sectionend', 'id' => 'wckp_options')
        );
        
        //우커머스 관리자 탭추가
        add_filter('woocommerce_settings_tabs_array', array($this, 'settings_tabs_array') );
        //관리자 옵션 페이지
        add_action('woocommerce_settings_tabs_wckp', array($this, 'admin_option_page') );
        // 관리자 옵션 저장
        add_action('woocommerce_update_options_wckp', array($this, 'update_option') );
        //주소 필드 수정
        add_filter('woocommerce_default_address_fields', array($this, 'default_address_fields') );
        // 우편 번호 스크립트        
        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        // 우편 번호 필드 출력
        add_filter('woocommerce_form_field_search_postcode', array( $this, 'search_postcode_field'), 10, 4);
        add_filter('woocommerce_form_field_zip_code', array( $this, 'zip_code_field'), 10, 4);

        // 우편 번호 검색
        add_action('wp_ajax_wckp_json_search_zipcode', array($this, 'search_zipcode') );
        // 우편 번호 검색
        add_action('wp_ajax_nopriv_wckp_json_search_zipcode', array($this, 'search_zipcode') );
        // 주소 출력 
        add_filter('woocommerce_my_account_my_address_formatted_address', array($this, 'my_address_formatted_address') );
    }
    
    //우커머스 관리자 탭추가
    function settings_tabs_array($tabs){
        $tabs['wckp'] =  __( 'Paygate JT', 'wc_korea_pack' );
        return $tabs;
    }
    
    //관리자 옵션 페이지
    function admin_option_page(){
        woocommerce_admin_fields( $this->settings );    
    }
    
    //관리자 옵션 저장
    function update_option(){
        woocommerce_update_options( $this->settings );   
    }
    
    //주소 필드 수정
    function default_address_fields($fields){
        $new_fields = array();
        
        if( get_option('woocommerce_wckp_namefix') == 'yes' ){
            $fields['first_name']['class'] = array( 'form-row-wide' );
            unset($fields['last_name']);
        }

        if( get_option('woocommerce_wckp_companyfix') == 'yes' ){
            unset($fields['company']);
        }

        if( get_option('woocommerce_wckp_zip_code') == 'yes' ){
            unset($fields['city']);
            unset($fields['country']);
            unset($fields['state']);
            
            $zip_code_type = get_option('woocommerce_wckp_search_zip_code_type');

            if( $zip_code_type == 'road' ){
                $fields['search_postcode']['placeholder'] = '엔터를 누르면 검색됩니다.';
                $fields['search_postcode']['label'] = '우편번호 검색';
                $fields['search_postcode']['class'] = array('form-row-wide');
                $fields['search_postcode']['type'] = 'search_postcode';
                $fields['search_postcode']['clear'] = false;
            } else {
                $fields['postcode']['type'] = 'zip_code';
            }
            $fields['postcode']['class'] = array('form-row-wide');
            $fields['postcode']['clear'] = false;
            
            $fields['address_1']['placeholder'] = '동(읍/면) 까지의 주소';
            $fields['address_1']['label'] = '동(읍/면) 까지의 주소';

            $fields['address_2']['placeholder'] = '동(읍/면) 이후의 주소';
            $fields['address_2']['label'] = '동(읍/면) 이후의 주소';
            $fields['address_2']['required'] = true;
            

            foreach($fields as $key => $val ){
                
                if( $key == 'address_1'){
                    if( $zip_code_type == 'road' ){
                        $new_fields['search_postcode'] = $fields['search_postcode'];
                    }
                    $new_fields['postcode'] = $fields['postcode'];    

                } else if( $key == 'postcode' ){
                    continue;
                }
                
                $new_fields[$key] = $val;
            }
            $fields = $new_fields;
        }
        
        return $fields;        
    }

    function my_address_formatted_address($fields){
        $new_fields = array();
        
        if( get_option('woocommerce_wckp_namefix') == 'yes' ){
            unset($fields['last_name']);
        }

        if( get_option('woocommerce_wckp_companyfix') == 'yes' ){
            unset($fields['company']);
        }

        if( get_option('woocommerce_wckp_zip_code') == 'yes' ){
            unset($fields['city']);
            unset($fields['country']);
            unset($fields['state']);
        }
        
        return $fields;        
    }

    //지번 기반 우편번호
    function zip_code_field( $blank, $key, $args, $value ) {
        if ( $args['required'] ) {
            $required = ' <abbr class="required" title="' . esc_attr__( 'required', 'wc_korea_pack'  ) . '">*</abbr>';
        } else {
            $required = '';
        }
        
        // Custom attribute handling
        $custom_attributes = array();

        if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) )
            foreach ( $args['custom_attributes'] as $attribute => $attribute_value )
                $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

        if ( ! empty( $args['validate'] ) )
            foreach( $args['validate'] as $validate )
                $args['class'][] = 'validate-' . $validate;

        if ( ( ! empty( $args['clear'] ) ) ) $after = '<div class="clear"></div>'; else $after = '';
        
        $field = '
        <style type="text/css">.woocommerce-account .form-row .chzn-container-single .chzn-search input {line-height: 13px;width: 100%!important;}</style>
        <p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) .'" id="' . esc_attr( $key ) . '_field">
                    <label for="' . esc_attr( $key ) . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label']. $required  . '</label>
                    <select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="" ' . implode( ' ', $custom_attributes ) . '>';
        if( $value != '') {
            $field .='<option value="' . esc_attr( $value ) . '" ' . selected( 1, 1, false ) . '>' . esc_html( $value ) .'</option>';
        } else {
            $field .='<option value="">'.__( '지역명을 입력하세요', 'wc_korea_pack' ) .'</option>';
        }
        $field .= '</select>';

        $field .= '</p>' . $after;
        echo $field;
        
    }

    //도로명 기반 우편번
    function search_postcode_field( $blank, $key, $args, $value ) {
        // autocomplete="off">
        // Custom attribute handling
        $custom_attributes = array();

        if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) )
            foreach ( $args['custom_attributes'] as $attribute => $attribute_value )
                $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

        if ( ( ! empty( $args['clear'] ) ) ) $after = '<div class="clear"></div>'; else $after = '';
        
        $field = '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) .'" id="' . esc_attr( $key ) . '_field">';

        if ( $args['label'] )
            $field .= '<label for="' . esc_attr( $key ) . '" class="' . implode( ' ', $args['label_class'] ) .'">' . $args['label'] . $required . '<span class="road_api_link">(주소검색 API 제공 : <a href="http://xenosi.de/roadzip" target="_blank">송효진</a>)</span>';

        $field .= '<input type="text" class="input-text XenoFindZip ui-autocomplete-input" id="' . esc_attr( $key ) . '" placeholder="' . $args['placeholder'] . '" '.$args['maxlength'] .' ' . implode( ' ', $custom_attributes ) . ' />
            <span class="XenoFindZipLabel"></span></p></label>' . $after;
        echo $field;
    }
    
    function scripts(){
        global $woocommerce;
        if( get_option('woocommerce_wckp_zip_code') == 'yes' ){
            if ( is_checkout() || is_account_page() ) {

                $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                
                if( get_option('woocommerce_wckp_search_zip_code_type') == 'road' ){
                    wp_enqueue_style( 'wckp-roadzip-css', plugin_dir_url ( __FILE__ ) . 'checkout.css', array('wp-jquery-ui-dialog') );
                    wp_enqueue_script( 'wckp-roadzip', '//xenosi.de/load/roadzip/roadzip.min.js', array( 'jquery', 'jquery-ui-dialog' ), wc_korea_pack()->version, true );
                    wp_enqueue_script( 'wckp-checkout', plugin_dir_url ( __FILE__ ) . 'checkout.js', array( 'jquery', 'jquery-ui-dialog' ), wc_korea_pack()->version, true );
                } else {
                    $frontend_script_path   = $woocommerce->plugin_url() . '/assets/js/frontend/';
                    wp_enqueue_style( 'woocommerce_chosen_styles', $woocommerce->plugin_url() . '/assets/css/chosen.css' );
                    wp_enqueue_script( 'wc-chosen', $frontend_script_path . 'chosen-frontend' . $suffix . '.js', array( 'chosen' ), $woocommerce->version, true );
                    wp_register_script( 'ajax-chosen', $woocommerce->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery'.$suffix.'.js', array('jquery', 'chosen'), $woocommerce->version, true );
                    wp_enqueue_script( 'wckp-checkout', plugin_dir_url ( __FILE__ ) . 'checkout.js', array( 'jquery', 'woocommerce', 'chosen', 'ajax-chosen' ), wc_korea_pack()->version, true );
                }
            }
        }
    }
    
    function search_zipcode(){
        //check_ajax_referer( 'search-customers', 'security' );

        $term = urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
    
        if ( empty( $term ) )
            die();

        $limit_cnt = 0;
        $zipcode_arr = $result_zip_code = array();
        $handle  = fopen( wc_korea_pack()->includes_dir."zip.db", "r");
        
        while( !feof($handle) ){
            $zipcode_arr[] = fgets($handle, 4096);
        }
        fclose($handle);
        
        foreach( $zipcode_arr as $zipcode ) {
            
            $zipcode = explode(',', $zipcode);
            
            if( strstr( str_replace(' ', '', $zipcode[1]), str_replace(' ', '', $term) ) ) {
                $zipcode[0] = substr($zipcode[0],0,3).'-'.substr($zipcode[0],3);
                $result_zip_code[$zipcode[0]] = '('.$zipcode[0].') '.$zipcode[1];
                
                if( --$limit_cnt == 0 ) break;
            }
        }

        header( 'Content-Type: application/json; charset=utf-8' );
    
        echo json_encode( $result_zip_code );
        die(1);
    }
}
