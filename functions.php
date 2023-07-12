<?php

//EP leibli priekš upsell checkbox uz jetformbuilder formas

add_filter( 'jet-forms-generate-from-query/label'  , 'modify_label_for_upsells' , 10, 3);


function modify_label_for_upsells($label, $obj, $args) {
	
	  //EP nolasam produktu un attēlojam datus
          	
          $product = wc_get_product( $obj->ID );
          $price = $product->get_price() ;
          $title = $product->get_title();
          
	  return $title.'  '.$price.' EUR';
}


//EP pievienojam grozam visus produktus no upsell formas

add_filter("jet-form-builder/action/redirect_to_woo_checkout/add-to-cart", 'formbuilder_add_to_cart' , 20 , 5);    


function formbuilder_add_to_cart($params) {
    
    $cartdata['heading'] = 'heading';
    $cartdata[] = array(
							'key'     => 'Dalībnieki',
							'display' => 'Dalībnieki'
        );
    
    
   
    
    //EP pievienojam galveno produktu
    
    WC()->cart->add_to_cart($params[0], $params[4]['jfb_form_data']['skaits'] , 0, array(),$params[4]); 

    //EP pievienojam upsell produktus
    
    if ($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus']) {
        
        foreach($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus'] as $v) {
         
            
                WC()->cart->add_to_cart($v, $params[4]['jfb_form_data']['skaits'] , 0, array(), $params[4]); 
        }
    }
    
    
    return array();

}



//EP izvēlamies lielāko cenu variable produktam

add_filter( 'woocommerce_get_price_html', 'change_variable_price_display', 10, 2 );

function change_variable_price_display( $price, $product_obj ) {
    global $product;

    if ( 'variable' !== $product->get_type() || 'product_variation' === $product_obj->post_type ) {
        return $price;
    }

    $price =  $product->get_variation_price( 'max', true ) ;
    // Translators: %s is the lowest variation price.
    $price =  wc_price( $price);

    return $price;
}

//EP pievienojam VAT lauku juridiskām personām

add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');

function custom_override_checkout_fields($fields) {

  // $billing_reg = ['billing_regnr' => [ 'placeholder'  => 'LV************' , 'label' => 'Reģistrācijas Numurs(PVN)' , 'required' => false ]];
      
  // array_($fields['billing'], $billing_reg);

   //print_r($fields);
   //exit();
   
   return $fields;
}

//EP month-breaker settingi

define('JET_ENGINE_BREAK_BY_FIELD' , true);
define('JET_ENGINE_BREAK_BY_QUERY_ID' , 'break_months');
define( 'JET_ENGINE_BREAK_BY_FIELD', 'datums' );
define('JET_ENGINE_BREAK_MONTH_FORMAT ' , 'M.Y');
