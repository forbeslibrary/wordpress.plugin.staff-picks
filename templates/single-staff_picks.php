<?php
/*
Template Name: Single Staff Pick
Description: This tempalte is used when a single staff_pick is displayed on a page of its own.
*/
$post = get_post();

get_header();
?>
<div id="content">
<?php load_template( dirname( __FILE__ ) . '/content-staff_picks.php', False ); ?>
</div>
<?php
get_footer();
