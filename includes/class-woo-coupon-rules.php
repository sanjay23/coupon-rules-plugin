<?php
/**
 * @package Coupon Rules
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'Woo_Coupon_Rules' ) ) {
	class Woo_Coupon_Rules {

		function __construct() {
			add_action('woocommerce_applied_coupon', array($this,'woo_coupon_rules_free_product_coupons'),10 );
			add_action('woocommerce_cart_item_removed', array($this, 'woo_coupon_rules_remove_cart_item'), 11, 2 );
			add_action('woocommerce_before_calculate_totals', array($this,'woo_coupon_rules_before_calculate_totals'), 10, 1 );
			add_filter('woocommerce_cart_item_price',array($this,'woo_coupon_rules_updated_item_price'),10,3);
			add_filter('woocommerce_cart_item_quantity',array($this,'woo_coupon_rules_qty_fields'),10,3);
			add_action('woocommerce_removed_coupon', array($this,'woo_coupon_rules_removed_coupon'), 10, 1 );
			add_filter('woocommerce_cart_item_remove_link',array($this,'woo_coupon_rules_remove_product_link'),10,2);
			add_action('woocommerce_before_checkout_process', array($this,'woo_coupon_rules_validate_cart_items'), 10, 0);
			add_filter('woocommerce_add_cart_item_data', array($this,'woo_coupon_rules_cart_item_data'), 10, 3);
			add_filter('woocommerce_get_item_data', array($this,'woo_coupon_rules_item_data'), 10, 2 );
			add_action('woocommerce_checkout_create_order_line_item', array($this,'woo_coupon_rules_order_line_item'), 10, 4 );
			add_filter('woocommerce_order_item_display_meta_key', array($this,'woo_coupon_rules_item_meta_key'), 20, 3 );
		}

		/** 
		 * Add custom free item meta into cart item
		 */		
		public function woo_coupon_rules_cart_item_data($cart_item_data, $product_id, $variation_id)
		{
			if(isset($_POST['free_coupon_item'])){
				$cart_item_data['free_coupon_item'] = true;
			}
			return $cart_item_data;
		}

		public function woo_coupon_rules_item_data($item_data, $cart_item_data)
		{
			if(isset( $cart_item_data['free_coupon_item'] )){
				$item_data[] = array(
					'key'   => __( 'Free Coupon Item', 'coupon-rules' ),
					'value' => wc_clean( $cart_item_data['free_coupon_item'] ),
				);
			}
			return $item_data;
		}

		/**
		 * Add coupon free item meta key into order and checkout
		 */
		public function woo_coupon_rules_order_line_item( $item, $cart_item_key, $values, $order ) 
		{
			if ( isset( $values['free_coupon_item'] ) ) {
				$item->add_meta_data(
					'free_coupon_item',
					$values['free_coupon_item'],
					true
				);
			}
		}

		/**
		 * Update coupon meta key name into admin order deatils page
		 */
		public function woo_coupon_rules_item_meta_key( $display_key, $meta, $item ) 
		{	
			if( $item->get_type() === 'line_item' && $meta->key === 'free_coupon_item' ) {
				$display_key = __("Free Coupon Item", "coupon-rules" );
			}
			return $display_key;
		}
		
		/**
		 * Validate checkout based coupon code 
		 */
		public function woo_coupon_rules_validate_cart_items()
		{
			if (is_checkout()) {

				$total = WC()->cart->cart_contents_total;
				foreach ( WC()->cart->get_coupons() as $code => $coupon ){
					$coupon_id = $this->get_coupon_id_by_name($code);
					
					if($discount_type == 'free_product'){
						$allowed_user_id = false;
						$allowed_user = get_field('allow_user_email_id',$coupon_id);
						if(!empty($allowed_user)){
							$user_ids = array();
							$allowed_user_arr = explode(',',$allowed_user);
							foreach($allowed_user_arr as $user_email){
								$user_data = get_user_by('email',trim($user_email));
								if(isset($user_data->ID) && !empty($user_data->ID)){
									$user_ids[] = $user_data->ID;
								}
							}
							if(is_user_logged_in() && !in_array(get_current_user_id(),$user_ids)) {
								$allowed_user_id = true;
							}
							if(!empty($allowed_user_id)){
								WC()->cart->remove_coupon( $code );
								throw new Exception(__('Coupon code is not valid.', 'coupon-rules'));
							}		
						}
					}	
				}
			}
		}
		
		  
	   	/**
		 * Applied custom coupon code functionality based on type
		*/
	   	public function woo_coupon_rules_free_product_coupons($coupon_code) 
		{
			$coupon_code = strtolower($coupon_code);
			$coupon_id = $this->get_coupon_id_by_name($coupon_code);
			$discount_type = get_post_meta($coupon_id, 'discount_type', true);
			$filter_by = get_post_meta($coupon_id,'_woo_coupon_rule_type',true);
			$filter_by = !empty($filter_by) ? strtolower($filter_by) : "";
			$filter_by_category_name = get_post_meta($coupon_id,'_woo_coupon_filter_by_category',true);
			$filter_by_product_name = get_post_meta($coupon_id,'_woo_coupon_filter_by_product',true);
			$discount_product = get_post_meta($coupon_id,'_woo_coupon_rule_discount_product',true);
			$fee_quantity = get_post_meta($coupon_id,'_woo_coupon_rule_free_qty',true);
			if($discount_type == 'free_product'){
				if($filter_by == '2'){
					$cat_in_cart = false;
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$product = $cart_item['data'];
						$product_id = $cart_item['product_id'];
						$variation_id = $cart_item['variation_id'];
						if(!empty($filter_by_category_name)){
							foreach($filter_by_category_name as $category){
								if ( has_term( $category, 'product_cat', $cart_item['product_id'] ) ) {
									$cat_in_cart = true;
								}
							}
						}
					}
					if(empty($cat_in_cart)){
						foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
							if($coupon->code == $coupon_code) {
								WC()->cart->remove_coupon( $coupon->code ); 
							}
						}
						$error = sprintf( __( 'Coupon code is not valid for this product category.', 'coupon-rules' ) );
						wc_clear_notices();
						// Show error
						wc_print_notice( $error, 'error' );
					}
				}
				if($filter_by == '1'){
					if(empty($filter_by_product_name)){
						$cat_in_cart[] = 1;
					} else {
						foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
							$product = $cart_item['data'];
							$product_id = $cart_item['product_id'];
							$variation_id = $cart_item['variation_id'];
							if ( in_array($cart_item['product_id'],$filter_by_product_name)) {
								$cat_in_cart[] = 1;
								break;
							}
						}
						
						if(empty($cat_in_cart)){
							foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
								if($coupon->code == $coupon_code) {
									WC()->cart->remove_coupon( $coupon->code ); 
								}
							}
							$error = sprintf( __( 'Coupon code is not valid for above products.', 'coupon-rules' ) );
							wc_clear_notices();
							wc_print_notice( $error, 'error' );
						}
					}
				}
			}
			
			if(isset($cat_in_cart) && !empty($cat_in_cart)){
				$product_exist = false;
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					
					if($cart_item['product_id'] == $discount_product || $cart_item['variation_id'] == $discount_product){
						$updated_qty = $cart_item['quantity'] - 1;
						WC()->cart->set_quantity($cart_item_key, $updated_qty);
					}
				}
				$discounted_product = wc_get_product($discount_product);
				if(!$discounted_product->is_in_stock()){
					$attr = '';
					if ($discounted_product->is_type('variation')) {   
						$attributes = $discounted_product->get_variation_attributes(); 
						foreach ($attributes as $attribute_name => $attribute_value) { 
							$attr .= $attribute_value; 
						} 
					}
					WC()->cart->remove_coupon($coupon_code);
					$error = sprintf(__('Coupons cannot be applied because the discount product is out of stock.', 'coupon-rules'));
					wc_clear_notices();
					wc_print_notice($error, 'error');
				} else {
					$cart_free_item_key = WC()->cart->add_to_cart( $discount_product, $fee_quantity, 0, array(), array( 'free_coupon_item' => 'yes' ) );
				}
			}
		}

		/**
		 * Check coupon code belong to category and then category product is removed from cart then remove coupon code
		*/
		public function woo_coupon_rules_remove_cart_item( $cart_item_key, $cart ) 
		{
			$product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];
			$cat_in_cart = false;
		
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( has_term( 'flower', 'product_cat', $cart_item['product_id'] ) ) {
					$cat_in_cart = true;
					break;
				}
			}
		}

		/**
		 * Update price based on discount type
		*/
		public function woo_coupon_rules_before_calculate_totals( $cart ) 
		{
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
			$coupon = false;
			$buy_coupon = false;
			$free_product = false;
			$per_item_price = false;
			foreach ( WC()->cart->get_applied_coupons() as $code ) {
				$coupons = new WC_Coupon( $code );
				$coupon_id = $coupons->id;
				if($coupons->type == 'free_product'){
					$free_product = true;
					$discount_product = get_post_meta($coupon_id,'_woo_coupon_rule_discount_product',true);
				}
				if ($coupons->type == 'buy_x_get_y'){
					$buy_coupon = true;
				}	
				$filter_product = get_post_meta($coupon_id,'_woo_coupon_filter_by_product',true);
				$filter_category = get_post_meta($coupon_id,'_woo_coupon_filter_by_category',true);
				$quantity = get_post_meta($coupon_id,'_woo_coupon_rule_quantity',true);
				$free_qty = get_post_meta($coupon_id,'_woo_coupon_rule_free_qty',true);
				$discount_amount = $coupons->amount;
				$coupon_type = $coupons->type;
			}
			
			
			if($buy_coupon){
				foreach ( $cart->get_cart() as $cart_item ) {
					$single_product_id = $cart_item['product_id'];
					$product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $single_product_id;
					$unit_price = $this->get_product_price_without_symbol( $product_id);
					$cat_in_cart = false;
					if(!empty($filter_category)){
						foreach($filter_category as $category){
							if ( has_term( $category, 'product_cat', $single_product_id ) ) {
								$cat_in_cart = true;
							}
						}
					}
					if((!empty($filter_product) && in_array($single_product_id,$filter_product)) || (!empty($filter_category) && $cat_in_cart )){
						
						if(($cart_item['quantity'] == $quantity) || ($cart_item['quantity'] % $quantity == 0)){
							$qty = $cart_item['quantity'] / $quantity;
							$remainingQty = $cart_item['quantity'] - $free_qty;
							$current_price = $unit_price = $cart_item['data']->price;
							$price = $unit_price * $remainingQty;
							$price = $price / $cart_item['quantity'];
							$cart_item['data']->custom_original_price = $current_price; 
							$cart_item['data']->set_price( $price ); 
							
						}
						if(($cart_item['quantity'] > $quantity) && ($cart_item['quantity'] % $quantity != 0)){
						
							$qty = floor($cart_item['quantity'] / $quantity);
							$remainingQty = $cart_item['quantity'] - $free_qty;
							$price = $unit_price * $remainingQty;
							$price = $price / $cart_item['quantity'];
							$cart_item['data']->custom_original_price = $unit_price; 
							$cart_item['data']->set_price( $price ); 
						}
					}
				}
			}
			
			if($free_product){
				
				foreach ( $cart->get_cart() as $cart_item ) {
					$product_id = $cart_item['product_id'];
					
					if(isset($discount_product) && ($product_id == $discount_product || $cart_item['variation_id'] == $discount_product)){
						
						$product_variation_id_price_exist = $cart_item['variation_id'];
						$product_id_price_exist = $cart_item['product_id'];
						if(isset($cart_item['free_coupon_item'])){
							$cart_item['data']->set_price( 0 ); 
						}
					}
				}
			}
		}

		/**
		 * Update single price and add extra price html into it.
		*/
		public function woo_coupon_rules_updated_item_price($price, $cart_item,$cart_item_key)
		{
			$coupon = false;
			$buy_coupon = false;
			$per_item_price = false;
			$coupon_type = '';
			$filter_product = array();
			foreach ( WC()->cart->get_applied_coupons() as $code ) {
				$coupons = new WC_Coupon( $code );
			
				if ($coupons->type == 'buy_x_get_y'){
					$buy_coupon = true;
				}	  
				
				$coupon_id = $coupons->id;
				$filter_product = get_post_meta($coupon_id,'_woo_coupon_filter_by_product',true);
				$rule = get_post_meta($coupon_id,'_woo_coupon_rule_type',true);
				$quantity = get_post_meta($coupon_id,'_woo_coupon_rule_quantity',true);
				$free_qty = get_post_meta($coupon_id,'_woo_coupon_rule_free_qty',true);
				$filter_category = get_post_meta($coupon_id,'_woo_coupon_filter_by_category',true);
				$per_product_price = get_field('per_product_price',$coupon_id);
				$discount_amount = $coupons->amount;
				$coupon_type = $coupons->type;
			}
			
			if($buy_coupon){
				
				$filter_product_id = $cart_item['product_id'];
				$cat_in_cart = false;
				if(!empty($filter_category)){
					foreach($filter_category as $category){
						if ( has_term( $category, 'product_cat', $filter_product_id ) ) {
							$cat_in_cart = true;
						}
					}
				}
				$product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $filter_product_id;
				$unit_price = $this->get_product_price_without_symbol( $product_id);
				if((!empty($filter_product) && in_array($filter_product_id,$filter_product)) || (!empty($filter_category) && $cat_in_cart )){
					if(($cart_item['quantity'] == $quantity) || ($cart_item['quantity'] % $quantity == 0)){
		
						$qty = $cart_item['quantity'] / $quantity;
						$remainingQty = $cart_item['quantity'] - $free_qty;
						$price = "<div class='strikeprice'>".wc_price($cart_item['data']->custom_original_price)."</div>";
						$price .= "<div class='extra_price'>".wc_price(0)." X ".$free_qty."</div>";	
						$price .= "<div class='extra_price'>".wc_price($cart_item['data']->custom_original_price)." X ".$remainingQty."</div>";	
					}
					if(($cart_item['quantity'] > $quantity) && ($cart_item['quantity'] % $quantity != 0)){
						
						$qty = floor($cart_item['quantity'] / $quantity);
						$price = wc_price($unit_price) . " X ".$quantity;
						$remainingQty = $cart_item['quantity'] - $free_qty;
						$price = "<div class='strikeprice'>".wc_price($cart_item['data']->custom_original_price)."</div>";
						$price .= "<div class='extra_price'>".wc_price(0)." X ".$free_qty."</div>";	
						$price .= "<div class='extra_price'>".wc_price($cart_item['data']->custom_original_price)." X ".$remainingQty."</div>";
					}
				}
			}
			return $price;
		}

		/** 
		 * Update cart item qty based on free discount
		 */  
		public function woo_coupon_rules_qty_fields($product_quantity, $cart_item_key, $cart_item )
		{
			foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
				$coupon_code = $coupon->code;
			}
			$coupon_id = $this->get_coupon_id_by_name($coupon_code);
			$discount_product = get_post_meta($coupon_id,'_woo_coupon_rule_discount_product',true);
			$fee_quantity = get_post_meta($coupon_id,'_woo_coupon_rule_free_qty',true);
			if(isset($discount_product) && !empty($discount_product)){
				if($cart_item['product_id'] == $discount_product || $cart_item['variation_id'] == $discount_product){
					if(isset($cart_item['free_coupon_item'])){
						return $fee_quantity;
					}
				}
			}
			return $product_quantity;
		}

		/**
		 * On remove coupon remove free product
		 */
		public function woo_coupon_rules_removed_coupon( $coupon_code ) 
		{
			// Settings
			$coupon_code = strtolower($coupon_code);
			$coupon_id = $this->get_coupon_id_by_name($coupon_code);
			$discount_product = get_post_meta($coupon_id,'_woo_coupon_rule_discount_product',true);
			// Compare
			if(isset($discount_product) && !empty($discount_product)){
				// Loop through cart contents
				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {
					// When product in cart
					if ( $cart_item['product_id'] == $discount_product || $cart_item['variation_id'] == $discount_product) {
						// Remove cart item
						if(isset($cart_item['free_coupon_item'])){
							WC()->cart->remove_cart_item( $cart_item_key );
							break;
						}
						
					}
				}
			}
		}

		/**
		 * Update or remove item link from cart
		 */
		public function woo_coupon_rules_remove_product_link($link, $cart_item_single_key)
		{
			foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
				$coupon_code = $coupon->code;
			}
			$coupon_code = strtolower($coupon_code);
			$coupon_id = $this->get_coupon_id_by_name($coupon_code);
			$discount_product = get_post_meta($coupon_id,'_woo_coupon_rule_discount_product',true);
			if(isset($discount_product) && !empty($discount_product)){
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if($cart_item_single_key == $cart_item_key){
						if($cart_item['product_id'] == $discount_product || $cart_item['variation_id'] == $discount_product){
							if(isset($cart_item['free_coupon_item'])){
								return '';
							}
						}
					}
				}
			}
			return $link;
		}

		

		public function get_product_price_without_symbol($id)
		{
			$product = wc_get_product($id);
			return $product->get_price(); 
		}

		public function get_coupon_id_by_name($coupon_code)
		{
			$posts = new WC_Coupon($coupon_code);
			$coupon_id = $posts->id;
			return $coupon_id;
		}
	}
}