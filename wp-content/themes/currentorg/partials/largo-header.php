<header id="site-header" class="clearfix" itemscope itemtype="http://schema.org/Organization">
	<?php largo_header(); ?>

	<?php if (is_home()) { ?>
		<div class="newsletter-signup">
			<a href="/subscribe/"><img src="/wp-content/themes/currentorg/img/subscribe.png"></a>
		</div>
	<?php } ?>

</header>
<header class="print-header">
	<p><strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong> (<?php echo esc_url( largo_get_current_url() ); ?>)</p>
</header>
