<?php
//EP pievienojam grozam visus produktus no upsell formas

add_filter("jet-form-builder/action/redirect_to_woo_checkout/add-to-cart", 'formbuilder_add_to_cart' , 20 , 5);    


function filter_normal($a) { 
    
    if  ($a['attributes']['attribute_pa_upsell'] == 'normal-2'){
        return true;    
    }
    
}

function formbuilder_add_to_cart($params) {
    

    
    
    if ($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus']) {
		
		$cartdata['cikls'] = true;
		
	}
 
    if (isset($params[4]['jfb_form_data']['dalibnieku_dati'])) {
        
             $cartdata['dalibnieki_serial'] = json_encode( $params[4]['jfb_form_data']['dalibnieku_dati']);
             
             $dalibnieki = '';
             foreach($params[4]['jfb_form_data']['dalibnieku_dati'] as $k => $v) {
             
                   //$dalibnieki .= implode('; ' , $v)."<br />";
                     $dalibnieki .= $v['vards_uzvards']."<br />";
             }
             
             $cartdata['dalibnieki'] = $dalibnieki;
    } 
    
    
    
    //EP pievienojam galveno produktu
    $product = wc_get_product($params[0]);
	$datums = $product->get_meta('datums_');
    $cartdata['datums'] = date("d.m.Y", (int)$datums ); 
   
    if (get_post_type($params[0]) == 'product') {
		
 
		

    //EP ja pamatprodukts ir ar variācijām izvēlamies normal		
	 	
      
      
	    if ($product->get_type() == 'variable') {	
		    
        	$variations = $product->get_available_variations();
        	$normal = array_filter( $variations, "filter_normal" );
        	$normal = current($normal);
		 
            WC()->cart->add_to_cart( $product->id, $params[4]['jfb_form_data']['skaits'] ,  $normal["variation_id"], array(),$cartdata); 
		} else		
			WC()->cart->add_to_cart($params[0], $params[4]['jfb_form_data']['skaits'] , 0 , array(),$cartdata);         
    } else {
   
        WC()->cart->add_to_cart($params[0], $params[4]['jfb_form_data']['skaits'] , 0 , array(),$cartdata); 
    } 

    //EP pievienojam upsell produktus
    
    if ($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus']) {
        
        if (is_array($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus'])) {
      
            foreach($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus'] as $v) {
         
                
                        $variation = wc_get_product($v);
                        $parent_id  = $variation->get_parent_id();
                        $parent =  wc_get_product($parent_id);
                        $datums = $parent->get_meta('datums_');
                        $cartdata['datums'] = date("d.m.Y", (int)$datums );
                        WC()->cart->add_to_cart($v, $params[4]['jfb_form_data']['skaits'] , 0, array(), $cartdata); 
            }  
        } else {
                $product = wc_get_product($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus']);
                $datums = $product->get_meta('datums_');
                $cartdata['datums'] = date("d.m.Y", (int)$datums );
                WC()->cart->add_to_cart($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus'], $params[4]['jfb_form_data']['skaits'] , 0, array(), $cartdata); 
        }  
      }
    
    
    return array();

}

function formbuilder_get_item_data( $item_data, $cart_item_data ) {
 if( isset( $cart_item_data['dalibnieki'] ) ) {
     $item_data[] = array(
     'key' => 'Dalībnieki',
     'value' =>  $cart_item_data['dalibnieki'] 
 );
 }
 
 if( isset( $cart_item_data['datums'] ) ) {
     $item_data[] = array(
     'key' => 'Datums',
     'value' =>  $cart_item_data['datums'] 
 );
 }
 if( isset( $cart_item_data['cikls'] ) ) {
     $item_data[] = array(
     'key' => 'Cikls',
     'value' =>  $cart_item_data['cikls'] 
 );
 }	
	
 return $item_data;
}

add_filter( 'woocommerce_get_item_data', 'formbuilder_get_item_data', 10, 2 );


function formbuilder_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
 if( isset( $values['dalibnieki_serial'] ) ) {
 $item->add_meta_data(
    'Dalībnieki' ,
    $values['dalibnieki_serial'], true );
 
 }
 if( isset( $values['cikls'] ) ) {
 $item->add_meta_data(
    'Cikls' ,
    $values['cikls'], true );
 
 }	
  //$item->add_meta_data(    'Datums' ,    $values['datums'], true ); 
}
add_action( 'woocommerce_checkout_create_order_line_item', 'formbuilder_checkout_create_order_line_item', 10, 4 );

function ir_webhook_http_args($http_args , $arg, $id){
  
  return array_merge($http_args, array('sslverify'   => false));
}

add_action( 'woocommerce_webhook_http_args', 'ir_webhook_http_args', 10, 3 );

add_action('wp' , 'product_view_counter');
function product_view_counter() {
 
  global $post;
 if ( is_product() ) {
     $meta = get_post_meta( $post->ID, 'skatits', TRUE );
     $meta = ($meta) ? $meta + 1 : 1; 
     update_post_meta( $post->ID, 'skatits', $meta );
 }
}

function allow_unsafe_urls ( $args ) {
       $args['reject_unsafe_urls'] = false;
       return $args;
    } ;

add_filter( 'http_request_args', 'allow_unsafe_urls' );


function register_invoiced_order_status() {
   register_post_status( 'wc-invoiced', array(
       'label'                     => 'Invoiced',
       'public'                    => true,
       'show_in_admin_status_list' => true,
       'show_in_admin_all_list'    => true,
       'exclude_from_search'       => false
       
   ) );
}
add_action( 'init', 'register_invoiced_order_status' );


function add_invoiced_to_order_statuses( $order_statuses ) {
   $new_order_statuses = array();
   foreach ( $order_statuses as $key => $status ) {
       $new_order_statuses[ $key ] = $status;
       if ( 'wc-processing' === $key ) {
           $new_order_statuses['wc-invoiced'] = 'Invoiced';
       }
   }
   return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_invoiced_to_order_statuses' );


//add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );

/**
 * Removes the attribute from the product title, in the cart.
 * 
 * @return string
 */
function remove_variation_from_product_title( $title, $cart_item, $cart_item_key ) {
	$_product = $cart_item['data'];
	$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

	if ( $_product->is_type( 'variation' ) ) {
		if ( ! $product_permalink ) {
			return $_product->get_title();
		} else {
			return sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() );
		}
	}

	return $title;
}
//add_filter( 'woocommerce_cart_item_name', 'remove_variation_from_product_title', 10, 3 );


function ir_after_order_complete( $order_id ) {
	
	
   $order = new WC_Order( $order_id );
   $payment_title = $order->get_payment_method();	
   
   if ($payment_title == 'bacs') {
	
	
      // URL of the web service
      $url = 'https://n8n.m50.lv:5678/webhook/5aa23911-6370-40bc-b97e-e8812b5c8459';

	  //echo($url)  ;
	  //exit;
      // Data to send in the POST request
      $data = array(
       'order_nr' => $order_id,
   
       );

      // Initialize cURL session
      $ch = curl_init($url);

      // Set cURL options for the POST request
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
      // Disable SSL verification
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

      // Execute the cURL session and store the response in $response
      $response = curl_exec($ch);

      // Check for cURL errors
      if (curl_errno($ch)) {
         echo 'cURL error: ' . curl_error($ch);
      }

      // Close cURL session
      curl_close($ch);
   } elseif ( $payment_title == 'Everypay'  ||  $payment_title == 'everypay'  ) {
	   
	     // URL of the web service
      $url = 'https://n8n.m50.lv:5678/webhook/01c7f401-2fc3-475c-add3-3c3158ffdf1a';

      // Data to send in the POST request
      $data = array(
       'id' => $order_id,
   
       );

      // Initialize cURL session
      $ch = curl_init($url);

      // Set cURL options for the POST request
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
      // Disable SSL verification
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

      // Execute the cURL session and store the response in $response
      $response = curl_exec($ch);

      // Check for cURL errors
      if (curl_errno($ch)) {
         echo 'cURL error: ' . curl_error($ch);
      }

      // Close cURL session
      curl_close($ch); 
   }	   
   
   return true;
}

add_action( 'woocommerce_order_status_completed', 'ir_after_order_complete'  );

/**
 * exclude a product from an coupon by attribute value
 */
add_filter('woocommerce_coupon_is_valid_for_product', 'exclude_product_from_coupon_by_attribute', 12, 4);
function exclude_product_from_coupon_by_attribute($valid, $product, $coupon, $values ){
    
	
	$valid = true;
    
    //$product = wc_get_product($product);
    
   // $variations = $product->get_available_variations();
   // $variations_id = wp_list_pluck( $variations, 'variation_id' );
    
    
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    
       
    foreach ($items as $k => $v) {
        
        if ($v['cikls'] == true && ($v['variation_id'] == $product->id))   return false;
	    if ($v['cikls'] == true && ($v['product_id'] == $product->id))   return false;
     //   if ($product->is_type('variable') ||  $product->is_type('simple') ) {
     //         if ($v['cikls'] == true && $v['product_id'] == $product->id)   return false;
     //   } elseif ($product->is_type('variation'))
            
             
    }
    
    
    

    return $valid;
}

add_action( 'jet-form-builder/custom-action/disable-checkout-redirect', function() {

	//load cart before adding product to cart
	add_action( 'jet-form-builder/action/redirect_to_woo_checkout/before-add', function() {
		WC()->cart->get_cart();
	}, 0 );

	//do not remove product from cart
	remove_action(
		'jet-form-builder/action/redirect_to_woo_checkout/before-add',
		array( \Jet_FB_Woo\Plugin::instance()->wc, 'on_before_add_to_cart' ),
		10
	);

	

} );


function remove_product_check( $cart_item_key, $cart ) {

    //print "<pre>";
    $product_id = $cart->cart_contents[ $cart_item_key ]['product_id']; 
	$product = wc_get_product( $product_id );
    $atttr =   $product->get_attribute('pa_upsell');

    
};
add_action( 'woocommerce_remove_cart_item', 'remove_product_check', 10, 2 );

// Hook to modify the remove link for products in the cart
add_filter('woocommerce_cart_item_remove_link', 'custom_cart_item_remove_link', 10, 2);

function custom_cart_item_remove_link($link, $cart_item_key) {
 
    $cart = WC()->cart;

    // Get cart item
    $cart_item = $cart->get_cart_item($cart_item_key);

    // Check if the product can be removed
    
    // Check if the product can be removed
    $product = $cart_item['data'];
	
	$product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];
 
    if ($variation = $cart_item['variation_id']){
		$product_id = $variation;
	}
	
	
	
	$product = wc_get_product( $product_id );
	
	//echo($product->get_type());
	
	$normal = false;
    
	//EP count cikls 
	$cikls = 0;
	foreach ( $cart->get_cart() as $key => $cart_item_cikls ) {
		if ($cart_item_cikls["cikls"] == true) $cikls++;
	}	
	
	//EP check for attribute p
	if ($product) {
        // Get the product attributes
        $attributes = $product->get_attributes();
		if ($attributes['pa_upsell'] === 'normal-2' ) $normal = true;
        //echo($normal);
        
    }
	
    if (isset($cart_item['cikls']) && ($product->get_type() == "simple"  ||  $normal   )  && $cikls != 1 )  return '';
	return $link;
}



// Remove product base from product permalinks
function custom_remove_product_base($permalink, $post, $leavename) {
    if ($post->post_type == 'product' && 'publish' == $post->post_status) {
        $permalink = str_replace('/notikums/', '/', $permalink); // Change 'product' to your desired base
    }
    return $permalink;
}
//add_filter('post_type_link', 'custom_remove_product_base', 10, 3);

// Flush rewrite rules to apply changes
function custom_flush_rewrite_rules() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'custom_flush_rewrite_rules');

//register number validation

function wpdesk_fcf_validate_regnumber( $field_label, $value ) {
	
	$patternvat = '/^(AT|BE|BG|CY|CZ|DE|DK|EE|EL|ES|FI|FR|GB|HR|HU|IE|IT|LT|LU|LV|MT|NL|PL|PT|RO|SE|SI|SK)[0-9A-Z]{11}$/';
	$patternur = '/^[0-9]{11}$/';
    
		
    // Perform the regex match
    if (preg_match($patternvat, $value)) {
        $vatnr = true; // VAT number is valid
    } else {
        $vatnr = false; // VAT number is not valid
    }
	
    if (preg_match($patternur, $value)) {
        $urnr = true; // VAT number is valid
    } else {
        $urnr = false; // VAT number is not valid
    }

	
    if ( !$vatnr  && !$urnr ) {
        wc_add_notice( sprintf( '%s ir nederīgs.', '<strong>' . $field_label . '</strong>' ), 'error' );
    }
}

add_filter( 'flexible_checkout_fields_custom_validation', 'wpdesk_fcf_custom_validation_regnumber' );
/**
 * Add custom number validation
 *
 */
function wpdesk_fcf_custom_validation_regnumber( $custom_validation ) {
    $custom_validation['regnumber'] = array(
        'label'     => 'Reģistrācijas numurs',
        'callback'  => 'wpdesk_fcf_validate_regnumber'
    );

    return $custom_validation;
}
