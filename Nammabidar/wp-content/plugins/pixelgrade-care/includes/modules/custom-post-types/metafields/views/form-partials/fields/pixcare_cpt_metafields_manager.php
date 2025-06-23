<?php defined( 'ABSPATH' ) or die;
/* @var PixelgradeCare_MetafieldsFormField $field */
/* @var PixelgradeCare_MetafieldsForm $form */
/* @var mixed $default */
/* @var string $name */
/* @var string $idname */
/* @var string $label */
/* @var string $desc */
/* @var string $rendering */

// [!!] the counter field needs to be able to work inside other fields; if
// the field is in another field it will have a null label

if ( ! get_post_type() ) {
	return;
}

$values = $form->autovalue( $name, $default );

$values = get_option( PixelgradeCare_CPT_Metafields::OPTION_NAME_FIELDS_LIST );

$post_type = get_post_type(); ?>

<div class="pixcare_cpt_fields_wrapper">
	<?php if ( ! empty( $label ) ) { ?>
		<h2 class="field_title">
			<?php echo $label; ?>
			<a class="media-modal-close" href="#"><span class="media-modal-icon"><span class="screen-reader-text">Close media panel</span></span></a>
		</h2>
	<?php }

	if ( ! empty( $description ) ) { ?>
		<span class="field_description"><?php echo $description; ?></span>
	<?php } ?>
	<div class="pixcare_cpt_fields_box">
		<ul class="table_head">
			<li class="pixcare_cpt_field">
				<span class="label"><?php esc_html_e( 'Name', 'pixelgrade_care' ) ?></span>
				<span class="filterable"><?php esc_html_e( 'Filter', 'pixelgrade_care' ) ?></span>
			</li>
		</ul>
		<ul class="pixcare_cpt_fields_list ui-sortable ui-draggable">
			<?php
			if ( ! empty( $values[ $post_type ] ) ) {

				foreach ( $values[ $post_type ] as $key => $value ) {

					$attrs = $key_attrs = $default_attrs = $filterable_atts = [
						'name' => $name . '[' . $post_type . ']',
					];

					$label_atts['name'] = $attrs['name'] . '[' . $key . '][label]';

					if ( isset( $value['label'] ) ) {
						$label_atts['value'] = $value['label'];
					}

					$meta_key_attrs = [];
					if ( isset( $value['meta_key'] ) || ! empty( $value['meta_key'] ) ) {
						$meta_key_attrs['name']  = $attrs['name'] . '[' . $key . '][meta_key]';
						$meta_key_attrs['value'] = $value['meta_key'];
					}

					$filterable_atts['name'] = $attrs['name'] . '[' . $key . '][filter]';
					if ( isset( $value['filter'] ) ) {
						$filterable_atts['checked'] = $value['filter'];
					} ?>
					<li class="pixcare_cpt_field">
						<span class="drag"><span class="dashicons dashicons-move"></span></span>
						<span class="label"><input type="text" <?php echo $field->htmlattributes( $label_atts ); ?> /></span>
						<?php
						if ( ! empty( $meta_key_attrs ) ) { ?>
							<span class="meta_key"><input type="hidden" <?php echo $field->htmlattributes( $meta_key_attrs ); ?> /></span>
						<?php } ?>
						<span class="filterable"><input type="checkbox" <?php echo $field->htmlattributes( $filterable_atts ); ?> /></span>
						<a href="#" class="delete_field"
						   title="<?php esc_html_e( 'Remove this meta field', 'pixelgrade_care' ) ?>"><?php esc_html_e( 'Delete', 'pixelgrade_care' ); ?></a>
					</li>
				<?php }
			} ?>
		</ul>
	</div>

	<ul class="pixcare_cpt_fields_add_new_field">
		<li class="pixcare_cpt_field" data-post_type="<?php echo get_post_type(); ?>">
			<span class="drag"><span class="dashicons dashicons-move"></span></span>
			<span class="label"><input type="text" name="add_pixfield[label]"
			                           placeholder="<?php esc_html_e( 'Enter field name..', 'pixelgrade_care' ); ?>"/></span>
			<span class="filterable"><input type="checkbox" name="add_pixfield[filter]"/></span>
			<span class="button add_field"><?php esc_html_e( 'Add Field', 'pixelgrade_care' ); ?></span>
		</li>
	</ul>
	<div class="control_bar">
		<div class="update_btn_wrapper">
			<span class="button button-primary update_pixcare_cpt_fields"><?php esc_html_e( 'Update', 'pixelgrade_care' ); ?></span>
		</div>
	</div>
</div>
