<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );
//EP pievienojam grozam visus produktus no upsell formas

add_filter("jet-form-builder/action/redirect_to_woo_checkout/add-to-cart", 'formbuilder_add_to_cart' , 20 , 5);    


function formbuilder_add_to_cart($params) {
    
 
    if (isset($params[4]['jfb_form_data']['dalibnieku_dati'])) {
        
             $cartdata['dalibnieki_serial'] = serialize( $params[4]['jfb_form_data']['dalibnieku_dati']);
             
             $dalibnieki = '';
             foreach($params[4]['jfb_form_data']['dalibnieku_dati'] as $k => $v) {
             
                   $dalibnieki .= implode(' ; ' , $v)."<br />";
             }
             
             $cartdata['dalibnieki'] = $dalibnieki;
    } 
    
   
    
    //EP pievienojam galveno produktu
    
    WC()->cart->add_to_cart($params[0], $params[4]['jfb_form_data']['skaits'] , 0, array(),$cartdata); 

    //EP pievienojam upsell produktus
    
    if ($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus']) {
        
        if (is_array($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus'])) {
      
            foreach($params[4]['jfb_form_data']['pievieno_citus_cikla_seminarus'] as $v) {
         
                            WC()->cart->add_to_cart($v, $params[4]['jfb_form_data']['skaits'] , 0, array(), $cartdata); 
            }  
        } else {
          
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
 return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'formbuilder_get_item_data', 10, 2 );


function formbuilder_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
 if( isset( $values['dalibnieki_serial'] ) ) {
 $item->add_meta_data(
    'Dalībnieki' ,
    $values['dalibnieki_serial'],
 true
 );
 }
}
add_action( 'woocommerce_checkout_create_order_line_item', 'formbuilder_checkout_create_order_line_item', 10, 4 );
