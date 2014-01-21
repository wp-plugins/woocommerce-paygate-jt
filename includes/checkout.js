jQuery(document).ready(function($) {
    /* ���� ��� �����ȣ �˻� ���� */
    if( $.fn.ajaxChosen ){
        $('select#billing_postcode, select#shipping_postcode').ajaxChosen({
            method:         'GET',
            url:            woocommerce_params.ajax_url,
            dataType:       'json',
            afterTypeDelay: 700,
            minTermLength:  2,
            data:       {
                action:     'wckp_json_search_zipcode',
            }
        }, function (data) {

            var terms = {};

            $.each(data, function (i, val) {
                terms[i] = val;
            });

            return terms;
        });
        
        $('select#billing_postcode').change(function (){
            
            var arr_addr = $( 'select#billing_postcode option:selected' ).text().split(" ");
            arr_addr.shift();
            if( arr_addr[arr_addr.length -1].search('~') > 0 ){
                arr_addr.pop();
            }
            
            $('#billing_address_1').val( arr_addr.join(' ') );

        });

        $('select#shipping_postcode').change(function (){
            
            var arr_addr = $( 'select#shipping_postcode option:selected' ).text().split(" ");
            arr_addr.shift();
            if( arr_addr[arr_addr.length -1].search('~') > 0 ){
                arr_addr.pop();
            }
            
            $('#shipping_address_1').val( arr_addr.join(' ') );

        });
    }
    /* ���� ��� �����ȣ �˻� �� */

    /* ���θ� ��� �����ȣ �˻�*/
    if( typeof XenoZipFinder == 'function' ){
        var $billing_search_postcode = $('#billing_search_postcode');
        var $shipping_search_postcode = $('#shipping_search_postcode');
        if( $billing_search_postcode ){
            $billing_search_postcode.data({
                z : 'billing_postcode', 
                a : 'billing_address_1', 
                r : 'billing_address_2'
            });
        }
        if( $shipping_search_postcode ){
            $shipping_search_postcode.data({
                z : 'shipping_postcode', 
                a : 'shipping_address_1', 
                r : 'shipping_address_2'
            });
        }
        //data-z="zip" data-a="ad" data-r="adr" data-e="ade" data-n="n"
        $('input.XenoFindZip').each(XenoZipFinder); // input �� �˻���ũ��Ʈ ����
    }
});