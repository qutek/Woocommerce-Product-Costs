<?php
/**
 * BD_Report_Profit_By_Date
 *
 * @author      lafif@astahdziq .in 
 * @category    Admin
 */
class WooCost_Report_Profit_By_Date extends WC_Admin_Report {

	public $chart_colours = array();

    protected function prepare_profit_chart_data( $orders, $costs ) {
        $profits = $orders;
        foreach(array_keys($profits) as $time) {
            $profits[$time][1] = $orders[$time][1] - $costs[$time][1];
            ($profits[$time][1] < 0)? $profits[$time][1] = 0 : 0;
        }

        return $profits;
    }
	
	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		$legend   = array();

		$total_orders = $this->get_order_report_data( array(
			'data' => array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				)
			),
            'query_type' => 'get_var',
			'filter_range' => true
		) );

        $total_sales = $this->get_order_report_data( array(
            'data' => array(
                '_line_subtotal' => array(
                    'type'            => 'order_item_meta',
                    'order_item_type' => 'line_item',
                    'function'        => 'SUM',
                    'name'            => 'total_item_sales'
                ),
            ),
            'query_type' => 'get_var',
            'filter_range' => true,
        ) );

		$total_items    = absint( $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				),
			),
            'query_type' => 'get_var',
			'filter_range' => true
		) ) );

		$total_cog = $this->get_order_report_data( array(
			'data' => array(
                '_line_funkwoocost_order' => array(
                    'type'            => 'order_item_meta',
                    'order_item_type' => 'line_item',
                    'function'        => 'SUM',
                    'name'            => 'total_cog'
                ),
			),
            'query_type' => 'get_var',
			'filter_range' => true,
            //'nocache'      => true
		) );



        $total_profit = $total_sales - $total_cog;
        ($total_profit < 0)? $total_profit = 0 : 0;
		$this->average_sales = $total_sales / ( $this->chart_interval + 1 );

		switch ( $this->chart_groupby ) {
			case 'day' :
				$average_sales_title = sprintf( __( '%s average daily sales', 'woocommerce' ), '<strong>' . wc_price( $this->average_sales ) . '</strong>' );
			break;
			case 'month' :
				$average_sales_title = sprintf( __( '%s average monthly sales', 'woocommerce' ), '<strong>' . wc_price( $this->average_sales ) . '</strong>' );
			break;
		}

		$legend[] = array(
			'title' => sprintf( __( '%s item sales in this period', 'funkwoocost' ), '<strong>' . wc_price( $total_sales ) . '</strong>' ),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and excluding shipping and taxes.', 'funkwoocost' ),
			'color' => $this->chart_colours['sales_amount'],
			'highlight_series' => 4
		);
        $legend[] = array(
            'title' => sprintf( __( '%s item profits in this period', 'funkwoocost' ), '<strong>' . wc_price( $total_profit ) . '</strong>' ),
            'placeholder'      => __( 'This is the sum of the order totals after deducting by cost of goods, any refunds and excluding shipping and taxes.', 'funkwoocost' ),
            'color' => $this->chart_colours['profit_amount'],
            'highlight_series' => 3
        );
        $legend[] = array(
			'title' => $average_sales_title,
			'color' => $this->chart_colours['average'],
			'highlight_series' => 2
		);
		$legend[] = array(
			'title' => sprintf( __( '%s orders placed', 'woocommerce' ), '<strong>' . $total_orders . '</strong>' ),
			'color' => $this->chart_colours['order_count'],
			'highlight_series' => 1
		);
		$legend[] = array(
			'title' => sprintf( __( '%s items purchased', 'woocommerce' ), '<strong>' . $total_items . '</strong>' ),
			'color' => $this->chart_colours['item_count'],
			'highlight_series' => 0
		);

		return $legend;
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		global $woocommerce, $wpdb, $wp_locale;

		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_month'   => __( 'Last Month', 'woocommerce' ),
			'month'        => __( 'This Month', 'woocommerce' ),
			'7day'         => __( 'Last 7 Days', 'woocommerce' )
		);

		$this->chart_colours = array(
			'sales_amount' => '#3498db',
			'average'      => '#75b9e7',
			'order_count'  => '#b8c0c5',
			'item_count'   => '#d4d9dc',
			'profit_amount' => '#1abc9c'
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) )
			$current_range = '7day';

		$this->calculate_current_range( $current_range );

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		do_action( 'funkwoocost_before_export_button', $current_range, $this );

		?>
		<a
			href="#"
			download="report-<?php echo $current_range; ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php _e( 'Date', 'woocommerce' ); ?>"
			data-exclude_series="2"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
		$orders = $this->get_order_report_data( array(
			'data' => array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders',
					'distinct' => true,
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

        $order_totals = $this->get_order_report_data( array(
            'data' => array(
                '_line_subtotal' => array(
                    'type'     => 'order_item_meta',
                    'order_item_type' => 'line_item',
                    'function' => 'SUM',
                    'name'     => 'total_sales'
                ),
                'post_date' => array(
                    'type'     => 'post_data',
                    'function' => '',
                    'name'     => 'post_date'
                ),
            ),
            'where' => array(
                array(
                    'key'      => 'order_items.order_item_type',
                    'value'    => 'line_item',
                    'operator' => '='
                )
            ),
            'group_by'     => $this->group_by_query,
            'order_by'     => 'post_date ASC',
            'query_type'   => 'get_results',
            'filter_range' => true
        ) );

		// Order items
		$order_items = $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'order_items.order_item_type',
					'value'    => 'line_item',
					'operator' => '='
				)
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

		// Cost items
		$cost_items = $this->get_order_report_data( array(
			'data' => array(
                '_line_funkwoocost_order' => array(
                    'type'              => 'order_item_meta',
                    'order_item_type'   => 'line_item',
                    'function'          => 'SUM',
                    'name'              => 'total_cog'
                ),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'order_items.order_item_type',
					'value'    => 'line_item',
					'operator' => '='
				)
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
            //'nocache'      => true
		) );

		// Prepare data for report
		$order_counts      = $this->prepare_chart_data( $orders, 'post_date', 'total_orders', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$order_item_counts = $this->prepare_chart_data( $order_items, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$order_amounts     = $this->prepare_chart_data( $order_totals, 'post_date', 'total_sales', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$cost_amounts      = $this->prepare_chart_data( $cost_items, 'post_date', 'total_cog', $this->chart_interval, $this->start_date, $this->chart_groupby );
        $profit_amounts    = $this->prepare_profit_chart_data( $order_amounts, $cost_amounts );

		// Encode in json format
		$chart_data = json_encode( array(
			'order_counts'      => array_values( $order_counts ),
			'order_item_counts' => array_values( $order_item_counts ),
			'order_amounts'     => array_values( $order_amounts ),
			'profit_amounts'    => array_values( $profit_amounts )
		) );
		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );
				var drawGraph = function( highlight ) {
					var series = [
						{
							label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
							data: order_data.order_item_counts,
							color: '<?php echo $this->chart_colours['item_count']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Number of orders', 'woocommerce' ) ) ?>",
							data: order_data.order_counts,
							color: '<?php echo $this->chart_colours['order_count']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colours['order_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average sales amount', 'woocommerce' ) ) ?>",
							data: [ [ <?php echo min( array_keys( $order_amounts ) ); ?>, <?php echo $this->average_sales; ?> ], [ <?php echo max( array_keys( $order_amounts ) ); ?>, <?php echo $this->average_sales; ?> ] ],
							yaxis: 2,
							color: '<?php echo $this->chart_colours['average']; ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Profit amount', 'woocommerce' ) ) ?>",
							data: order_data.profit_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['profit_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						},
						{
							label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
							data: order_data.order_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['sales_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						}
					];

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars )
							highlight_series.bars.fillColor = '#9c5d90';

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery('.chart-placeholder.main'),
						series,
						{
							legend: {
								show: false
							},
						    grid: {
						        color: '#aaa',
						        borderColor: 'transparent',
						        borderWidth: 0,
						        hoverable: true
						    },
						    xaxes: [ {
						    	color: '#aaa',
						    	position: "bottom",
						    	tickColor: 'transparent',
								mode: "time",
								timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
								monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
								tickLength: 1,
								minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
								font: {
						    		color: "#aaa"
						    	}
							} ],
						    yaxes: [
						    	{
						    		min: 0,
						    		minTickSize: 1,
						    		tickDecimals: 0,
						    		color: '#d4d9dc',
						    		font: { color: "#aaa" }
						    	},
						    	{
						    		position: "right",
						    		min: 0,
						    		tickDecimals: 2,
						    		alignTicksWithAxis: 1,
						    		color: 'transparent',
						    		font: { color: "#aaa" }
						    	}
						    ]
				 		}
				 	);

					jQuery('.chart-placeholder').resize();
				}

				drawGraph();

				jQuery('.highlight_series').hover(
					function() {
						drawGraph( jQuery(this).data('series') );
					},
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}
}
