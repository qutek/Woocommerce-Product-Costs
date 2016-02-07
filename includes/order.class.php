<?php
/**
 * WooCost_Order Class.
 *
 * @class       WooCost_Order
 * @version		1.0
 * @author lafif <lafif@astahdziq.in>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCost_Order class.
 */
class WooCost_Order {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->includes();

		add_action( 'woocommerce_add_order_item_meta', array($this, 'save_funkwoocost_on_checkout'), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array($this, 'hide_funkwoocost'), 10, 1 );
	}

    public function save_funkwoocost_on_checkout($item_id, $values) {
        $cost_of_good = ($values['variation_id'] != '') ?
        get_post_meta($values['variation_id'], '_funkwoocost', true) :
        get_post_meta($values['product_id'], '_funkwoocost', true);

        wc_add_order_item_meta( $item_id, '_line_funkwoocost_order', wc_format_decimal( $cost_of_good * $values['quantity'] ) );
    }

    public function hide_funkwoocost($itemmeta){
    	$itemmeta[] = '_line_funkwoocost_order';
    	return $itemmeta;
    }
	

	public function includes(){
		
	}

}

return new WooCost_Order();