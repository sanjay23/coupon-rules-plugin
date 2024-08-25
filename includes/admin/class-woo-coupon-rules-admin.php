<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Admin Class
 * 
 * Manage Admin Panel Class 
 *
 * @package Coupon Rules admin
 * @since 1.0.0
 */

if( !class_exists( 'Woo_Coupon_Rules_Admin' ) ) {

	class Woo_Coupon_Rules_Admin {

		function __construct() {
			add_filter( 'woocommerce_coupon_discount_types', array($this,'woo_coupon_rules_discount_type'));
			add_action('admin_enqueue_scripts', array($this,'woo_coupon_rules_admin_enqueue'));
			add_action( 'add_meta_boxes', array($this,'woo_coupon_rules_add_custom_box'));
			add_action( 'wp_ajax_get_product_search_data', array($this,'woo_coupon_rules_product_search'));
			add_action( 'save_post', array($this,'woo_coupon_rules_save_meta_box_data'));
		}
		
		/**
		* Add custom discount type
		*/
		public function woo_coupon_rules_discount_type( $discount_types ) 
		{
		   $discount_types['free_product'] = __( 'Free product', 'coupon-rules' );
		   $discount_types['buy_x_get_y'] = __( 'Buy X Get Y', 'coupon-rules' );
		   return $discount_types;
	    }

		public function woo_coupon_rules_admin_enqueue()
		{
			global $post;
			$woo_coupon_filter_by_product = get_post_meta( $post->ID, '_woo_coupon_filter_by_product', true );
			$woo_coupon_rule_discount_product = get_post_meta( $post->ID, '_woo_coupon_rule_discount_product', true );
			$filter_data = $this->get_filter_product_data($woo_coupon_filter_by_product);
			$discount_product = $this->get_discount_product_data($woo_coupon_rule_discount_product);
			wp_enqueue_script('woo_coupon_rules_admin_js', WOO_COUPON_RULES_INCLUDE_URL . '/js/woo-coupon-rules-admin.js',array('jquery'),WOO_COUPON_RULES_VERSION);
			$localize = array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'filter_product_ids' => $filter_data,
				'discount_product_id' => $discount_product,
			);
			wp_localize_script('woo_coupon_rules_admin_js', 'coupon_rule_obj', $localize);

			wp_enqueue_style('woo_coupon_rules_admin_css', WOO_COUPON_RULES_INCLUDE_URL . '/css/woo-coupon-rules-admin.css', false, WOO_COUPON_RULES_VERSION);
		}

		public function get_discount_product_data($id)
		{
			$products = array();
			if(!empty($id)){
				$product_object = wc_get_product( $id);
				$formatted_name = $product_object->get_formatted_name();
				$products[$id] = $formatted_name;
			}
			return $products;
		}

		public function get_filter_product_data($ids)
		{
			$products = array();
			if(!empty($ids)){
				foreach($ids as $id){
					$product_object = wc_get_product( $id);
					$formatted_name = $product_object->get_formatted_name();
					$products[$id] = $formatted_name;
				}
			}
			return $products;
		}

		public function woo_coupon_rules_add_custom_box() 
		{
			add_meta_box(
				'woo_coupon_rules',
				__( 'Coupon Rules', 'coupon-rules' ),
				array($this,'woo_coupon_rules_box_html'),
				'shop_coupon'
			);
		}

		public function woo_coupon_rules_box_html($post )
		{
			wp_nonce_field( 'woo_coupon_save_meta_box_data', 'woo_coupon_fields_meta_box_nonce' );
			$woo_coupon_rule_type = get_post_meta( $post->ID, '_woo_coupon_rule_type', true );
			$woo_coupon_filter_by_category = get_post_meta( $post->ID, '_woo_coupon_filter_by_category', true);
			$woo_coupon_filter_by_product = get_post_meta( $post->ID, '_woo_coupon_filter_by_product', true );
			$woo_coupon_rule_free_qty = get_post_meta( $post->ID, '_woo_coupon_rule_free_qty', true );
			$woo_coupon_rule_quantity = get_post_meta( $post->ID, '_woo_coupon_rule_quantity', true );
			$woo_coupon_rule_discount_product = get_post_meta( $post->ID, '_woo_coupon_rule_discount_product', true );
			?>
			<div class="woo_coupon_rule_wrap">
				<div class="coupon_rules_for woo_coupon_inner">
					<p><?php esc_html_e( 'Filter Product Or Category', 'coupon-rules' );?></p>
					<div class="woo_coupon_rule_type">
						<select name="woo_coupon_rule_type" class="woo_coupon_rule_filter">
							<option value="1" <?php echo ($woo_coupon_rule_type == '1') ? "selected" : "";?>><?php esc_html_e( 'Product', 'coupon-rules' );?></option>
							<option value="2" <?php echo ($woo_coupon_rule_type == '2') ? "selected" : "";?>><?php esc_html_e( 'Category', 'coupon-rules' );?></option>
						</select>
					</div>
				</div>
				<div class="woo_coupon_rule_product_wrap">
					<div class="woo_coupon_rule_data woo_coupon_inner">
						<div class="woo_coupon_filter_by_category">
							<p><?php esc_html_e( 'Filter Category List', 'coupon-rules' );?></p>
							<select name="woo_coupon_filter_by_category[]" multiple class="woo_coupon_filter_by_category">
							<?php 
							$category = $this->get_all_product_category();
							if(!empty($category)){
								?>
								<option value=""><?php esc_html_e( '-- Select Category ---', 'coupon-rules' )?></option>
								<?php 
								foreach($category as $category_val){
									$selected = !empty($woo_coupon_filter_by_category) && in_array($category_val->slug,$woo_coupon_filter_by_category) ? "selected" : "";
									?>
									<option value="<?php echo esc_html( $category_val->slug)?>" <?php echo esc_html( $selected )?>><?php echo esc_html( $category_val->name )?></option>
									<?php
								}
								
							}
							?>
							</select>
						</div>
						<div class="woo_coupon_filter_by_product">
							<p><?php esc_html_e( 'Filter Product List', 'coupon-rules' );?></p>
							<select id="woo_coupon_filter_by_product" name="woo_coupon_filter_by_product[]" multiple class="woo_coupon_filter_by_product wc-filter-product-search product-search product-field">
								<option value=""><?php esc_html_e( '-- Select Product ---', 'coupon-rules' )?></option>
							</select>
						</div>
					</div>
					<div class="woo_coupon_rule_discounted_product woo_coupon_inner">
						<p><?php esc_html_e( 'Discount Product', 'coupon-rules' );?></p>
						<select id="woo_coupon_discount_product" name="woo_coupon_discount_product" class="wc-discount-product-search product-field">
							<option value=""><?php esc_html_e( '-- Select Product ---', 'coupon-rules' )?></option>
						</select>
					</div>
				</div>
				<div class="woo_coupon_rule_qty woo_coupon_inner">
					<div class="qty_wrap">
						<p><?php esc_html_e('Quantity', 'coupon-rules' );?></p>
						<input type="number" name="woo_coupon_rule_quantity" class="woo_coupon_rule_quantity" min="1" value="<?php echo $woo_coupon_rule_quantity ? esc_attr($woo_coupon_rule_quantity) : '1';?>">
					</div>
					<div class="free_qty_wrap">
						<p><?php esc_html_e('Free Quantity', 'coupon-rules' );?></p>
						<input type="number" name="woo_coupon_rule_free_qty" class="woo_coupon_rule_free_qty" min="1" value="<?php echo $woo_coupon_rule_free_qty ? esc_attr($woo_coupon_rule_free_qty) : '1';?>">
					</div>
				</div>
			</div>
			<?php
		}

		public function woo_coupon_rules_save_meta_box_data($post_id)
		{
			if ( ! isset( $_POST['woo_coupon_fields_meta_box_nonce'] ) )
				return;
			if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['woo_coupon_fields_meta_box_nonce'])), 'woo_coupon_save_meta_box_data' ) )
				return;
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;

			if(!$_POST['woo_coupon_rule_type'])
				return;
			if(isset($_POST['discount_type']) && $_POST['discount_type'] == 'free_product'){
				
				$woo_coupon_discount_product = sanitize_text_field( $_POST['woo_coupon_discount_product'] );
				update_post_meta( $post_id, '_woo_coupon_rule_discount_product', $woo_coupon_discount_product );
			}
			if(isset($_POST['discount_type']) && $_POST['discount_type'] == 'buy_x_get_y'){
				$discount_qty = sanitize_text_field( $_POST['woo_coupon_rule_quantity'] );
				update_post_meta( $post_id, '_woo_coupon_rule_quantity', $discount_qty );
			}
			if($_POST['discount_type'] == 'free_product' || $_POST['discount_type'] == 'buy_x_get_y'){
				$woo_coupon_rule_type = sanitize_text_field( $_POST['woo_coupon_rule_type'] );
				$free_qty = sanitize_text_field( $_POST['woo_coupon_rule_free_qty'] );
				update_post_meta( $post_id, '_woo_coupon_rule_free_qty', $free_qty );
				update_post_meta( $post_id, '_woo_coupon_rule_type', $woo_coupon_rule_type );
				if($woo_coupon_rule_type == '1'){
					if(isset($_POST[ 'woo_coupon_filter_by_product' ]) && is_array( $_POST[ 'woo_coupon_filter_by_product' ] )){
						$sanitized_product_data = $this->woo_coupon_rules_sanitize_array($_POST['woo_coupon_filter_by_product']);
						update_post_meta( $post_id, '_woo_coupon_filter_by_product', $sanitized_product_data );
					}
				}
				if($woo_coupon_rule_type == '2'){
					if(isset($_POST[ 'woo_coupon_filter_by_category' ]) && is_array( $_POST[ 'woo_coupon_filter_by_category' ] )){
						$sanitized_data = $this->woo_coupon_rules_sanitize_array($_POST['woo_coupon_filter_by_category']);
						update_post_meta( $post_id, '_woo_coupon_filter_by_category', $sanitized_data );
					}
				}
			}
			
		}

		public function woo_coupon_rules_sanitize_array($input)
		{
			if ( is_array( $input ) ) {
				foreach ( $input as $key => $value ) {
					$input[ $key ] = $this->woo_coupon_rules_sanitize_array( $value );
				}
			} else {
				$input = sanitize_text_field( $input );
			}
			return $input;
		} 

		public function get_all_product_category()
		{
			$args = array(
				'number'     => $number,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => 0,
			);
			$product_categories = get_terms( 'product_cat', $args );
			return $product_categories;
		}	
		
		
		public function woo_coupon_rules_product_search()
		{
			global $woocommerce;
			$include_variations = true;
			if ( empty( $term ) && isset( $_GET['term'] ) ) {
				$term = (string) wc_clean( wp_unslash( $_GET['term'] ) );
			}

			if ( empty( $term ) ) {
				wp_die();
			}

			if ( ! empty( $_GET['limit'] ) ) {
				$limit = absint( $_GET['limit'] );
			} else {
				$limit = absint( 500 );
			}

			$include_ids = ! empty( $_GET['include'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['include'] ) ) : array();
			$exclude_ids = ! empty( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : array();

			$exclude_types = array();
			if ( ! empty( $_GET['exclude_type'] ) ) {
				$exclude_types = sanitize_text_field(wp_unslash( $_GET['exclude_type'] ));
				if ( ! is_array( $exclude_types ) ) {
					$exclude_types = explode( ',', $exclude_types );
				}

				foreach ( $exclude_types as &$exclude_type ) {
					$exclude_type = strtolower( trim( $exclude_type ) );
				}
				$exclude_types = array_intersect(
					array_merge( array( 'variation' ), array_keys( wc_get_product_types() ) ),
					$exclude_types
				);
			}

			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit, $include_ids, $exclude_ids );

			$products = array();
			foreach ( $ids as $id ) {
				if(empty($id)){
					continue;
				}
				$product_object = wc_get_product( $id );

				$formatted_name = $product_object->get_formatted_name();
				$managing_stock = $product_object->managing_stock();
				$price = $product_object->get_regular_price();
				$sell_price = $product_object->get_sale_price();
				if(!empty($sell_price)){
					$price = $sell_price;
				}
				if ( in_array( $product_object->get_type(), $exclude_types, true ) ) {
					continue;
				}
				
				$stock_amount = $product_object->is_in_stock();
				if($stock_amount == 0){
					continue;
				}
			
				$has_term = '';

				$formatted_name .= ' – ' . sprintf( __( 'Stock: %d', 'coupon-rules' ), wc_format_stock_quantity_for_display( $stock_amount, $product_object ) );	
				$products[ $product_object->get_id() ] = rawurldecode( wp_strip_all_tags( $formatted_name ) );
			}

			wp_send_json( $products );
		}

	}
} 