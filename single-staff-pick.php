<?php
/*
Template Name: Single Staff Pick
*/
$post = get_post();

get_header();
?>
<div id="content">
<?php echo staff_picks_display($post); ?>
</div>
<?php
get_footer();
