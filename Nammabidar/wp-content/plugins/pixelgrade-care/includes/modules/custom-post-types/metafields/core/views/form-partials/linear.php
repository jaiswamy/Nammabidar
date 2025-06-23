<?php defined( 'ABSPATH' ) or die;
/* @var $form PixelgradeCare_MetafieldsForm */
/* @var $conf PixelgradeCare_MetafieldsMeta */

/* @var $f PixelgradeCare_MetafieldsForm */
$f = &$form;
?>

<?php foreach ( $conf->get( 'fields', [] ) as $fieldname ): ?>

	<?php echo $f->field( $fieldname )
	             ->addmeta( 'special_sekrit_property', '!!' )
	             ->render() ?>

<?php endforeach; ?>
