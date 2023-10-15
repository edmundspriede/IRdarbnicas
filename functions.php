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
        
             $cartdata['dalibnieki_serial'] = json_encode( $params[4]['jfb_form_data']['dalibnieku_dati']);
             
             $dalibnieki = '';
             foreach($params[4]['jfb_form_data']['dalibnieku_dati'] as $k => $v) {
             
                   $dalibnieki .= implode('; ' , $v)."<br />";
             }
             
             $cartdata['dalibnieki'] = $dalibnieki;
    } 
    
   
    
    //EP pievienojam galveno produktu
  
    $product = wc_get_product($params[0]);
    if ($product->is_type('variable')) {
      
        $variations = $product->get_available_variations();
        WC()->cart->add_to_cart($params[0], $params[4]['jfb_form_data']['skaits'] , $variations[0]["variation_id"], array(),$cartdata); 
    } else {
   
        WC()->cart->add_to_cart($params[0], $params[4]['jfb_form_data']['skaits'] , 0 , array(),$cartdata); 
    } 

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
add_filter( 'woocommerce_cart_item_name', 'remove_variation_from_product_title', 10, 3 );
