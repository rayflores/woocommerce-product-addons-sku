<?php
/**
 * WooCommerce Product Addons SKUs
 *
 * Plugin Name:  WooCommerce Product Addons Skus
 * Plugin URI: https://rayflores.com/plugins/wcpas/
 * Description: Add skus to product addons ( backend options )
 * Version: 0.4.6
 * Author: Ray Flores
 * Author URI: http://rayflores.com
 *
 * @package WooCommerceProductAddonsSKUs
 */

/**
 * Enqueue styles for WooCommerce Product Addons SKUs.
 *
 * @param string $hook The current admin page hook.
 * @return void
 */
function wcpas_enqueue_styles( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wcpascss', plugins_url( '/css/wcpas.css', __FILE__ ), array( 'woocommerce_product_addons_css' ) );
}
add_action( 'admin_enqueue_scripts', 'wcpas_enqueue_styles' );

/**
 * Add new addon option with SKU field.
 *
 * @return array
 */
function rf_add_new_addon_option() {
	$new_addon_options = array(
		'sku' => '',
	);

	return $new_addon_options;
}
add_filter( 'woocommerce_product_addons_new_addon_option', 'rf_add_new_addon_option', 0 );

/**
 * Add SKU field to addon options.
 *
 * @param object $post           The post object.
 * @param array  $product_addons The product addons array.
 * @param int    $loop           The loop index.
 * @param array  $option         The option array.
 * @return void
 */
function apg_add_checkbox_sku_field( $post, $product_addons, $loop, $option ) {
	$value = ! empty( $option['sku'] ) ? $option['sku'] : '';
	?>
		<div class="wc-pao-addon-content-sku">
			<input type="text" class="sku" name="product_addon_option_sku[<?php echo esc_attr( $loop ); ?>][]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_html_e( 'Enter a Sku', 'woocommerce-product-addons' ); ?>"/>
		</div>
		<?php
}
add_action( 'woocommerce_product_addons_panel_option_row', 'apg_add_checkbox_sku_field', 10, 4 );

/**
 * Add checkbox headings to addon fields.
 */
function rf_add_checkbox_heading_fields() {
	?>
		<div class="wc-pao-addon-content-sku-header">Sku</div>
	<?php
}
add_action( 'woocommerce_product_addons_panel_option_heading', 'rf_add_checkbox_heading_fields', 10, 0 );

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
		'sku'        => '',
	);

	return apply_filters( 'woocommerce_product_addons_new_addon_option', $new_addon_option );
}

/**
 * Save sku addon field.
 *
 * @param array $data Addon data being saved.
 * @param int   $i    Addon index.
 * @return array
 */
function apg_save_checkbox_sku_field( $data, $i ) {
	if ( empty( $data['options'] ) || ! is_array( $data['options'] ) ) {
		return $data;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$addon_option_sku = isset( $_POST['product_addon_option_sku'] ) ? wp_unslash( $_POST['product_addon_option_sku'] ) : array();
	// phpcs:enable

	$option_sku = isset( $addon_option_sku[ $i ] ) && is_array( $addon_option_sku[ $i ] ) ? $addon_option_sku[ $i ] : array();

	foreach ( $data['options'] as $ii => $option ) {
		$data['options'][ $ii ]['sku'] = isset( $option_sku[ $ii ] ) ? sanitize_text_field( $option_sku[ $ii ] ) : '';
	}

	return $data;
}
add_filter( 'woocommerce_product_addons_save_data', 'apg_save_checkbox_sku_field', 10, 2 );

/**
 * Sort addons.
 *
 * @param  array $a First item to compare.
 * @param  array $b Second item to compare.
 * @return int
 **/
function addons_sku_cmp( $a, $b ) {
	if ( $a['position'] === $b['position'] ) {
		return 0;
	}

	return ( $a['position'] < $b['position'] ) ? -1 : 1;
}

/**
 * Add Sku to Cart Item Meta.
 * Also saves in order meta.
 *
 * @param array $data  Existing cart item data.
 * @param array $addon The addon data.
 * @return array
 */
function apg_save_cart_item_data( $data, $addon ) {
	if ( empty( $addon['options'] ) || ! is_array( $data ) ) {
		return $data;
	}

	$sku_by_option = array();

	foreach ( $addon['options'] as $option ) {
		if ( empty( $option['label'] ) ) {
			continue;
		}

		$normalized_label                   = strtolower( sanitize_title( $option['label'] ) );
		$sku_by_option[ $normalized_label ] = array(
			'sku'        => isset( $option['sku'] ) ? sanitize_text_field( $option['sku'] ) : '',
			'price_type' => isset( $option['price_type'] ) ? $option['price_type'] : 'flat_fee',
			'price'      => isset( $option['price'] ) ? (float) $option['price'] : 0,
		);
	}

	foreach ( $data as $index => $cart_addon ) {
		if ( empty( $cart_addon['value'] ) ) {
			continue;
		}

		$cart_option_key = strtolower( sanitize_title( $cart_addon['value'] ) );

		if ( ! isset( $sku_by_option[ $cart_option_key ] ) ) {
			continue;
		}

		$matched_option = $sku_by_option[ $cart_option_key ];

		if ( ! isset( $data[ $index ]['name'] ) ) {
			$data[ $index ]['name'] = isset( $addon['name'] ) ? sanitize_text_field( $addon['name'] ) : '';
		}

		if ( ! isset( $data[ $index ]['field_name'] ) ) {
			$data[ $index ]['field_name'] = isset( $addon['field_name'] ) ? $addon['field_name'] : '';
		}

		if ( ! isset( $data[ $index ]['field_type'] ) ) {
			$data[ $index ]['field_type'] = isset( $addon['type'] ) ? $addon['type'] : '';
		}

		if ( ! isset( $data[ $index ]['price_type'] ) ) {
			$data[ $index ]['price_type'] = $matched_option['price_type'];
		}

		if ( ! isset( $data[ $index ]['price'] ) ) {
			$data[ $index ]['price'] = $matched_option['price'];
		}

		if ( ! empty( $matched_option['sku'] ) ) {
			$data[ $index ]['sku'] = $matched_option['sku'];

			if ( false === strpos( $data[ $index ]['value'], 'Sku:' ) ) {
				$data[ $index ]['value'] .= ': Sku:' . $matched_option['sku'];
			}
		}
	}

	return $data;
}
add_filter( 'woocommerce_product_addon_cart_item_data', 'apg_save_cart_item_data', 10, 2 );

/**
 * Ensure add-on SKU is persisted in order line item meta.
 *
 * @param array                 $meta_data Order line item meta data from Product Add-Ons.
 * @param array                 $addon     Add-on cart item data.
 * @param WC_Order_Item_Product $item      Order item object.
 * @param array                 $values    Cart item values.
 * @return array
 */
function apg_add_sku_to_order_line_item_meta( $meta_data, $addon, $item, $values ) {
	if ( empty( $addon['sku'] ) ) {
		return $meta_data;
	}

	$sku = sanitize_text_field( $addon['sku'] );

	if ( '' === $sku ) {
		return $meta_data;
	}

	if ( ! isset( $meta_data['value'] ) ) {
		$meta_data['value'] = '';
	}

	if ( false === stripos( $meta_data['value'], 'SKU:' ) ) {
		$meta_data['value'] .= ' | SKU: ' . $sku;
	}

	$meta_data['sku'] = $sku;

	return $meta_data;
}
add_filter( 'woocommerce_product_addons_order_line_item_meta', 'apg_add_sku_to_order_line_item_meta', 10, 4 );
