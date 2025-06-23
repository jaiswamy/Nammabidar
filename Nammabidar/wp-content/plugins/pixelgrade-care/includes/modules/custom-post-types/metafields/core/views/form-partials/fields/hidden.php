<?php defined( 'ABSPATH' ) or die;
/* @var PixelgradeCare_MetafieldsFormField $field */
/* @var PixelgradeCare_MetafieldsForm $form */
/* @var mixed $default */
/* @var string $name */
/* @var string $idname */
/* @var string $label */
/* @var string $desc */
/* @var string $rendering */

isset( $type ) or $type = 'hidden';

$attrs =
	[
	'name'  => $name,
	'id'    => $idname,
	'type'  => 'hidden',
	'value' => $form->autovalue( $name )
	];
?>

<input <?php echo $field->htmlattributes( $attrs ) ?>/>
