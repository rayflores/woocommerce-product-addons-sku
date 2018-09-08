<?php 
/*
Plugin Name:  WooCommerce Product Addons Skus
Plugin URI: https://rayflores.com/plugins/wcpas/
Description: Add skus to product addons ( backend options )
Version: 0.1.0
Author: Ray Flores
Author URI: http://rayflores.com
*********************
INSPRIRATION: https://echo5digital.com/blog/how-to-add-images-to-your-woocommerce-product-addons/
*********************
*/
/**
 * Add sku addon field
 */
add_action('woocommerce_product_addons_panel_option_row', 'apg_add_checkbox_sku_field', 10, 4);
function apg_add_checkbox_sku_field($post, $product_addons, $loop, $option) {
    wp_enqueue_media();
    ob_start();
	
	$value = isset( $option['sku'] ) ? esc_attr( wc_format_localized_price( $option['sku'] ) ) : '';
    ?>
    <td class="checkbox_column">
        <input type="text" class="sku" name="product_addon_option_sku[<?php echo $loop; ?>][]" value="<?php echo $value; ?>"/>
    </td>
    <?php
    $output = ob_get_clean();
    echo $output;

}

/**
 * Add checkbox headings to addon fields
 */
add_action('woocommerce_product_addons_panel_option_heading', 'apg_add_checkbox_heading_fields', 10, 3);
function apg_add_checkbox_heading_fields($post, $addon, $loop) {
    echo '<th class="checkbox_column"><span class="column-title">Sku</span></th>';
}

/**
 * Save sku addon field
 */
add_filter('woocommerce_product_addons_save_data', 'apg_save_checkbox_sku_field', 10, 2);
function apg_save_checkbox_sku_field($data, $i) {
	
    $addon_option_sku = $_POST['product_addon_option_sku'];
	$addon_name         = $_POST['product_addon_name'];
	$addon_option_label = $_POST['product_addon_option_label'];
	
	//print_r(sizeof( $addon_name )); die('here');
	
	for ( $i = 0; $i < sizeof( $addon_name ); $i++ ) {

		if ( ! isset( $addon_name[ $i ] ) || ( '' == $addon_name[ $i ] ) ) {
			continue;
		}

		$addon_options 	= array();
		$option_label  	= $addon_option_label[ $i ];
		$option_sku  	= $addon_option_sku[ $i ];

		for ( $ii = 0; $ii < sizeof( $option_label ); $ii++ ) {
			$sku    = sanitize_text_field( $option_sku[ $ii ] );
			$data['options'][$ii]['sku'] = $sku;
		}
	}
				

    return $data;
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
