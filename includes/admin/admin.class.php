<?php
/**
 * WooCost_Admin Class.
 *
 * @class       WooCost_Admin
 * @version		1.0
 * @author lafif <lafif@astahdziq.in>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCost_Admin class.
 */
class WooCost_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->includes();

		add_action( 'woocommerce_product_options_general_product_data', array($this, 'add_field_funkwoocost') );
		add_action( 'woocommerce_process_product_meta', array($this, 'save_field_funkwoocost') );
		add_filter( 'woocommerce_hidden_order_itemmeta', array($this, 'hide_line_funkwoocost'), 10, 1 );

		// report
		add_action( 'woocommerce_admin_reports', array($this, 'funkwoocost_reports') );
		add_action( 'funkwoocost_before_export_button', array($this, 'add_recalculate_button'), 10, 2 );// ajax recalculate profit on report
		add_action( 'wp_ajax_recalculate_profit', array($this, 'recalculate_profit_callback') );// clear our custom report class transient when woocommerce did
		add_action( 'woocommerce_delete_shop_order_transients', array($this, 'funkwoocost_clear_shop_order_transient') );
		add_action( 'admin_head', array($this, 'add_custom_report_styles') );

		// report by date
		// add_filter( 'wc_admin_reports_path', array($this, 'funkwoocost_sales_by_date_report'), 10, 2 );
	}

	public function add_field_funkwoocost(){
		global $woocommerce, $post;
  
		echo '<div class="options_group">';
  
		woocommerce_wp_text_input( 
			array( 
				'id'                => '_funkwoocost', 
				'label'             => sprintf( __( 'Product Cost (%s)', 'funkwoocost' ), get_woocommerce_currency_symbol() ), 
				'placeholder'       => '', 
				'description'       => __( 'Add product cost for profit report.', 'funkwoocost' ),
				'type'              => 'number', 
				// 'custom_attributes' => array(
				// 		'step' 	=> 'any',
				// 		'min'	=> '0'
				// 	) 
			)
		);
  
		echo '</div>';
	}

	public function save_field_funkwoocost($post_id){
		$woocommerce_text_field = (float) $_POST['_funkwoocost'];

		if( !empty( $woocommerce_text_field ) )
			update_post_meta( $post_id, '_funkwoocost', wc_format_decimal( $woocommerce_text_field ) );
	}



    public function funkwoocost_reports($reports) {
        $reports['profit'] = array(
            'title'  => __( 'Profit', 'funkwoocost' ),
            'reports' => array(
                "profit_by_date"    => array(
                    'title'       => __( 'Profit by date', 'funkwoocost' ),
                    'description' => '',
                    'hide_title'  => true,
                    'callback'    => array( __CLASS__, 'get_report' )
                ),
                "profit_by_product"     => array(
                    'title'       => __( 'Profit by product', 'funkwoocost' ),
                    'description' => '',
                    'hide_title'  => true,
                    'callback'    => array( __CLASS__, 'get_report' )
                ),
                "profit_by_category" => array(
                    'title'       => __( 'Profit by category', 'funkwoocost' ),
                    'description' => '',
                    'hide_title'  => true,
                    'callback'    => array( __CLASS__, 'get_report' )
                ),
            )
        );

        return $reports;
    }

    public static function get_report( $name ) {
        $name  = sanitize_title( str_replace( '_', '-', $name ) );
        $class = 'WooCost_Report_' . str_replace( '-', '_', $name );

        include_once( apply_filters( 'funkwoocost_reports_path', 'reports/report-' . $name . '.class.php', $name, $class ) );

		if ( ! class_exists( $class ) )
			return;

		$report = new $class();
		$report->output_report();
    }

    public function add_recalculate_button($current_range, $report){
    	?>
		<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=recalculate_profit' ), 'funkwoocost-recalculate-profit', 'token' ); ?>" class="funkwoocost-report-button"><i class="dashicons dashicons-image-rotate"></i> Recalculate Profit</a>
    	<?php
    }


	public function recalculate_profit_callback(){
		$redirect = ( wp_get_referer() ) ? wp_get_referer() : admin_url( 'admin.php?page=wc-reports&tab=profit' );
		
		if(!isset($_REQUEST['token']) || !wp_verify_nonce( $_REQUEST['token'], 'funkwoocost-recalculate-profit' )){
			wp_redirect( add_query_arg(array('status' => 'failed'), $redirect) );
			die();
		}

		global $wpdb;

		$order_items = $wpdb->get_results("
            SELECT order_items.order_item_id,
                   meta_product_id.meta_value AS product_id,
                   meta_variation_id.meta_value AS variation_id
            FROM {$wpdb->prefix}woocommerce_order_items AS order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id ON order_items.order_item_id=meta_product_id.order_item_id AND meta_product_id.meta_key='_product_id'
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_variation_id ON order_items.order_item_id=meta_variation_id.order_item_id AND meta_variation_id.meta_key='_variation_id'
            WHERE order_items.order_item_type='line_item'
        ");

        // echo "<pre>";
        // print_r($order_items);
        // echo "</pre>";
        // exit();

        if(!empty($order_items)){
        	foreach($order_items as $order_item) {
                $cost_of_good = (!empty($order_item->variation_id)) ?
                    get_post_meta($order_item->variation_id, '_funkwoocost', true) :
                    get_post_meta($order_item->product_id, '_funkwoocost', true);

                // echo $cost_of_good; exit();

                $order_item_qty = wc_get_order_item_meta($order_item->order_item_id, '_qty', true);

                wc_update_order_item_meta( $order_item->order_item_id, '_line_funkwoocost_order', wc_format_decimal( $cost_of_good * $order_item_qty ) );
            }
        }

        // clear transient
        delete_transient( strtolower('WooCost_Report_Profit_By_Date') );

        wp_redirect( add_query_arg(array('status' => 'success'), $redirect) );
		die();
	}

	public function funkwoocost_clear_shop_order_transient(){

		// delete our custom report transient when woocoommerce run it
		delete_transient( strtolower('WooCost_Report_Profit_By_Date') );
	}

	public function add_custom_report_styles(){
		global $current_screen;

		if($current_screen->id != 'woocommerce_page_wc-reports')
			return;

		?>
		<style>
		/* Our custom button beside export csv */
		.stats_range .funkwoocost-report-button {
		  	float: right;
		    line-height: 26px;
		    border-left: 1px solid #dfdfdf;
		    padding: 10px;
		    display: block;
		    text-decoration: none;
		}
		.stats_range .funkwoocost-report-button .dashicons {
		  	font-size: 15px;
		    margin-top: 5px;
		}
		</style>
		<?php
	}

	public function hide_line_funkwoocost($itemmeta){
    	$itemmeta[] = '_line_funkwoocost_order';
    	return $itemmeta;
    }

	// public function funkwoocost_sales_by_date_report($name, $class){
	// 	if($class == 'sales-by-date')
	// 		$name = 'dasd';

	// 	return $name;
	// }

	public function includes(){
		
	}

}

return new WooCost_Admin();