<?php
/**
 * A base module for [products] and [products*]
 *
 * @author Alen Birindzic
 * @date 30/08/2015
 * @license MIT License
 */

/* Shortcode handler */

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_products' );

function wpcf7_add_shortcode_products() {
	wpcf7_add_shortcode( array( 'products', 'products*' ),
		'wpcf7_products_shortcode_handler', true );
}

function wpcf7_products_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );

	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->is_required() )
		$atts['aria-required'] = 'true';

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$multiple = $tag->has_option( 'multiple' );
	$include_blank = $tag->has_option( 'include_blank' );
	$first_as_label = $tag->has_option( 'first_as_label' );

	// Change product query settings here
	$product_posts = get_posts( array(
		'post_type' => 'product',
		'post_status' => 'publish',
		'numberposts' => -1,
	) );

	// Display product options
	$values = array();
	foreach ( $product_posts as $product ) {
		// Get product SKU
		$sku = get_post_meta( $product->ID, '_sku', true );
		// Set `values` with SKU & product title
		$values[] = '#' . $sku . ' | ' . $product->post_title;
	}

	$values = $values;
	$labels = array_values( $values );

	$shifted = false;

	if ( $include_blank || empty( $values ) ) {
		array_unshift( $labels, '---' );
		array_unshift( $values, '' );
		$shifted = true;
	} elseif ( $first_as_label ) {
		$values[0] = '';
	}

	$html = '';
	$hangover = wpcf7_get_hangover( $tag->name );

	foreach ( $values as $key => $value ) {
		$selected = false;

		if ( $hangover ) {
			if ( $multiple ) {
				$selected = in_array( esc_sql( $value ), (array) $hangover );
			} else {
				$selected = ( $hangover == esc_sql( $value ) );
			}
		} else {
			if ( ! $shifted && in_array( (int) $key + 1, (array) $defaults ) ) {
				$selected = true;
			} elseif ( $shifted && in_array( (int) $key, (array) $defaults ) ) {
				$selected = true;
			}
		}

		$item_atts = array(
			'value' => $value,
			'selected' => $selected ? 'selected' : '' );

		$item_atts = wpcf7_format_atts( $item_atts );

		$label = isset( $labels[$key] ) ? $labels[$key] : $value;

		$html .= sprintf( '<option %1$s>%2$s</option>',
			$item_atts, esc_html( $label ) );
	}

	if ( $multiple )
		$atts['multiple'] = 'multiple';

	$atts['name'] = $tag->name . ( $multiple ? '[]' : '' );

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><select %2$s>%3$s</select>%4$s</span>',
		sanitize_html_class( $tag->name ), $atts, $html, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_products', 'wpcf7_products_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_products*', 'wpcf7_products_validation_filter', 10, 2 );

function wpcf7_products_validation_filter( $result, $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	$name = $tag->name;

	if ( isset( $_POST[$name] ) && is_array( $_POST[$name] ) ) {
		foreach ( $_POST[$name] as $key => $value ) {
			if ( '' === $value )
				unset( $_POST[$name][$key] );
		}
	}

	$empty = ! isset( $_POST[$name] ) || empty( $_POST[$name] ) && '0' !== $_POST[$name];

	if ( $tag->is_required() && $empty ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	}

	return $result;
}


/* Tag generator */

if ( is_admin() ) {
	add_action( 'admin_init', 'wpcf7_add_products_tag_generator_menu', 25 );
}

function wpcf7_add_products_tag_generator_menu() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'products', __( 'WooCommerce Products drop-down menu', 'contact-form-7' ),
		'wpcf7_tag_products_generator_menu' );
}

function wpcf7_tag_products_generator_menu( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'products';

	$description = __( "Generate a form-tag for a WooCommerce Products drop-down menu. For more details, see %s.", 'contact-form-7' );

	$desc_link = wpcf7_link( __( 'https://github.com/alenb/Contact-Form-7-WooCommerce-Products/', 'contact-form-7' ), __( 'WooCommerce Product dropdown menu', 'contact-form-7' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<label><input type="checkbox" name="multiple" class="option" /> <?php echo esc_html( __( 'Allow multiple selections', 'contact-form-7' ) ); ?></label><br />
		<label><input type="checkbox" name="include_blank" class="option" /> <?php echo esc_html( __( 'Insert a blank item as the first option', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
