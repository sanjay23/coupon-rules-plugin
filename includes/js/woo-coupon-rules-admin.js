jQuery(document).ready(function($) {
	// $('.post-type-shop_coupon .woo_coupon_inner').each(function(){
	// 	$(this).hide();
	// })
    console.log("filter "+ coupon_rule_obj.filter_product_ids);
    console.log("discount "+coupon_rule_obj.discount_product_id);
    // $('#woo_coupon_filter_by_product').val(coupon_rule_obj.filter_product_ids).trigger('change');
    // $('#woo_coupon_discount_product').val(coupon_rule_obj.discount_product_id).trigger('change');
	jQuery(document).on("change","#discount_type",function(e) {
	
		var currentObj = $(this);
        $('.post-type-shop_coupon .woo_coupon_inner').each(function(){
            $(this).hide();
        });
        $('.woo_coupon_filter_by_category').hide();
		if(currentObj.val() == 'free_product'){
			$('.coupon_rules_for').show();
            $('.woo_coupon_rule_data, .woo_coupon_rule_discounted_product, .woo_coupon_rule_qty, .free_qty_wrap').show();
            $('.qty_wrap').hide();
            
		}
        if(currentObj.val() == 'buy_x_get_y'){
			$('.coupon_rules_for').show();
            $('.woo_coupon_rule_data, .woo_coupon_rule_qty, .qty_wrap').show();
		}
		$(".woo_coupon_rule_filter").trigger('change');
	});
    jQuery(document).on("change",".woo_coupon_rule_filter",function(e) {
		var currentObj = $(this);
		if(currentObj.val() == '1'){
			$('.woo_coupon_filter_by_category').hide();
            $('.woo_coupon_filter_by_product').show();   
		}
        if(currentObj.val() == '2'){
			$('.woo_coupon_filter_by_category').show();
            $('.woo_coupon_filter_by_product').hide();
		}
	});
    initailizeProductSelect2();
    initailizeDiscountProductSelect2();
    function initailizeProductSelect2(){
		$(".wc-filter-product-search").select2({
			minimumInputLength: 3,
			minimumResultsForSearch: 7,
			allowClear:  false,
			ajax: {
			  url: coupon_rule_obj.ajaxurl,
			  dataType: 'json',
			  delay: 250,
			  data: function (params) {
				 return {
					term: params.term,
					exclude_type:'variable',
					action: 'get_product_search_data',
				 };
			  },
			  processResults: function( data ) {
					var terms = [];
					
					if ( data ) {
						var textArr;
						$.each( data, function( id, text ) {
							textArr = text.split('||');
							terms.push({
								id: id,
								text: textArr[0],
								price: textArr[1],
								cat: textArr[2]
							});
						});
					}
					return {
						results: terms
					};
				},
				cache: true
			},
		 });
	}

    function initailizeDiscountProductSelect2(){
        $(".wc-discount-product-search").select2({
			minimumInputLength: 3,
			minimumResultsForSearch: 7,
			allowClear:  false,
			ajax: {
			  url: coupon_rule_obj.ajaxurl,
			  dataType: 'json',
			  delay: 250,
			  data: function (params) {
				 return {
					term: params.term,
					exclude_type:'variable',
					action: 'get_product_search_data',
				 };
			  },
			  processResults: function( data ) {
					var terms = [];
					
					if ( data ) {
						var textArr;
						$.each( data, function( id, text ) {
							textArr = text.split('||');
							terms.push({
								id: id,
								text: textArr[0],
								price: textArr[1],
							});
						});
					}
					return {
						results: terms
					};
				},
				cache: true
			},
		});
    }
	
	$.each(coupon_rule_obj.filter_product_ids, function(k, v) {
		let newOption = new Option(v, k, true, true);
		$('#woo_coupon_filter_by_product').append(newOption).trigger('change');
    });
	$.each(coupon_rule_obj.discount_product_id, function(k, v) {
		let newOption = new Option(v, k, true, true);
		$('#woo_coupon_discount_product').append(newOption).trigger('change');
    });
	
});