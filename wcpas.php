<?php
	/*
	Plugin Name:  WooCommerce Product Addons Skus
	Plugin URI: https://rayflores.com/plugins/wcpas/
	Description: Add skus to product addons ( backend options )
	Version: 0.3.3
	Author: Ray Flores
	Author URI: http://rayflores.com
	*/
	/**
	 * Add sku addon field
	 */
	add_action('admin_enqueue_scripts','wcpas_enqueue_styles');
	function wcpas_enqueue_styles(){
		wp_enqueue_style( 'wcpascss', plugins_url('/css/wcpas.css', __FILE__), array('woocommerce_product_addons_css') );
	}
	
	add_filter( 'woocommerce_product_addons_new_addon_option', 'rf_add_new_addon_option', 0 );
	function rf_add_new_addon_option( ){
		$new_addon_options = array(
			'sku' => ''
		);
		
		return $new_addon_options;
	}
//	{s:5:"label";s:1:"M";s:5:"price";s:0:"";s:5:"image";s:0:"";s:10:"price_type";s:8:"flat_fee";}}}}
	add_action('woocommerce_product_addons_panel_option_row', 'apg_add_checkbox_sku_field', 10, 4);
	function apg_add_checkbox_sku_field($post, $product_addons, $loop, $option) {
		$value = !empty( $option['sku'] ) ? $option['sku']  : '';
		?>
		<div class="wc-pao-addon-content-sku">
			<input type="text" class="sku" name="product_addon_option_sku[<?php echo $loop; ?>][]" value="<?php echo $value; ?>" placeholder="<?php esc_html_e( 'Enter a Sku', 'woocommerce-product-addons' ); ?>"/>
		</div>
		<?php
		
	}
	
	/**
	 * Add checkbox headings to addon fields
	 */
	add_action('woocommerce_product_addons_panel_option_heading', 'rf_add_checkbox_heading_fields', 10, 3);
	function rf_add_checkbox_heading_fields($post, $addon, $loop) {
		?>
        <div class="wc-pao-addon-content-sku-header">Sku</div>
		<?php
	}
	
		
	/**
	 * Generate a filterable default new addon option.
	 *
	 * @return array
	 */
	function get_new_addon_option() {
		$new_addon_option = array(
			'label'      => '',
			'image'      => '',
			'price'      => '',
			'price_type' => 'flat_fee',
			'sku' => '',
		);
	
		return apply_filters( 'woocommerce_product_addons_new_addon_option', $new_addon_option );
	}
	
	/**
	 * Save sku addon field
	 */
	add_filter('woocommerce_product_addons_save_data', 'apg_save_checkbox_sku_field', 10, 2);
	function apg_save_checkbox_sku_field($data, $i) {
		$addon_name               = $_POST['product_addon_name'];
		$addon_option_sku         = $_POST['product_addon_option_sku'];
		$addon_option_label       = $_POST['product_addon_option_label'];
		$addon_option_price       = $_POST['product_addon_option_price'];
		$addon_option_image       = $_POST['product_addon_option_image'];
		
		
		
		$addon_options = array();
		
		if ( isset( $addon_option_label[ $i ] ) ) {
			$option_label      = $addon_option_label[ $i ];
			$option_price      = $addon_option_price[ $i ];
			$option_price_type = $addon_option_price_type[ $i ];
			$option_image      = $addon_option_image[ $i ];
			$option_sku     = $addon_option_sku[ $i ];
			
			for ( $ii = 0; $ii < count( $option_label ); $ii++ ) {
				$label      = sanitize_text_field( stripslashes( $option_label[ $ii ] ) );
				$price      = wc_format_decimal( sanitize_text_field( stripslashes( $option_price[ $ii ] ) ) );
				$image      = sanitize_text_field( stripslashes( $option_image[ $ii ] ) );
				$price_type = sanitize_text_field( stripslashes( $option_price_type[ $ii ] ) );
				$sku = sanitize_text_field( stripslashes( $option_sku[ $ii ] ) );
				
				$addon_options[] = array(
					'label'      => $label,
					'price'      => $price,
					'image'      => $image,
					'price_type' => $price_type,
					'sku' => $sku,
				);
			}
		}
		$data['options'] = $addon_options;
		
		return $data;
	}
	/**
	 * Sort addons.
	 *
	 * @param  array $a First item to compare.
	 * @param  array $b Second item to compare.
	 * @return bool
	 */
	function addons_sku_cmp( $a, $b ) {
	if ( $a['position'] == $b['position'] ) {
		return 0;
	}
	
	return ( $a['position'] < $b['position'] ) ? -1 : 1;
}
	/**
	 * Add Sku to Cart Item Meta
	 * Also saves in order meta
	 */
	add_filter( 'woocommerce_product_addon_cart_item_data', 'apg_save_cart_item_data', 10, 4);
	function apg_save_cart_item_data( $data, $addon, $product_id, $post_data ){
		
		$product_addons = get_product_addons( $product_id );
		
		$value = isset( $post_data[ 'addon-' . $addon['field-name'] ] ) ? $post_data[ 'addon-' . $addon['field-name'] ] : '';
		if ( is_array( $value ) ) {
			$value = array_map( 'stripslashes', $value );
		} else {
			$value = stripslashes( $value );
		}
		foreach ( $addon['options'] as $option ) {
			if ( in_array( strtolower( sanitize_title( $option['label'] ) ), array_map( 'strtolower', array_values( $value ) ) ) ) {
				$cart_item_data[] = array(
					'name'  => $addon['name'],
					'value' => $option['label'] . ': Sku:' . $option['sku'],
					'price' => $option['price'],
					'sku' => $option['sku']
				);
			}
		}
		return $cart_item_data;
	}