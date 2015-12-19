<?php
/*
Template Name: Single Staff Pick
*/
$post = get_post();
$helper = new Staff_Picks_Helper();

get_header();
?>
<div id="content">
<?php echo $helper->display($post); ?>
</div>
<?php
get_footer();
