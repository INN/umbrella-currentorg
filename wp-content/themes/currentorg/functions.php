<?php

define('FEATURED_MEDIA', true);


/**
 * Register a custom homepage layout
 *
 * @see "homepages/layouts/current.php"
 */
function current_register_custom_homepage_layout() {
	// load the layout
	include_once __DIR__ . '/homepages/layouts/current.php';
	register_homepage_layout('CurrentHomepage');
}
add_action('init', 'current_register_custom_homepage_layout', 0);


/**
 * Add the current.js file.
 *
 * @since 1.0
 */
function current_enqueue_script() {
	$version = '0.1.0';
	wp_enqueue_script('current',get_stylesheet_directory_uri() . '/js/current.js',array('jquery'),$version,true);
}
add_action('wp_enqueue_scripts', 'current_enqueue_script');


/**
 * Add typekit fonts
 *
 * @since 1.0
 */
function current_typekit_fonts() { ?>
	<script src="//use.typekit.net/eje1din.js?v=2"></script>
	<script>try{Typekit.load({ async: true });}catch(e){}</script>
<?php
}
add_action('wp_head', 'current_typekit_fonts');


/**
 * Register extra widget areas used in the layout.
 *
 * @since 1.0
 */
function current_register_sidebars() {

	$sidebars = array();

	$sidebars[] = array(
		'name' => __('Homepage after third post', 'current'),
		'id' => 'homepage-after-third-post',
		'description' => __('A widget area that appears after the third post on the homepage.', 'current'),
		'before_widget' => '<div class="hp-after-three-widget">' . "\n",
		'after_widget' => '</div>' . "\n"
	);

	$sidebars[] = array(
		'name' => __('Homepage next to second post', 'current'),
		'id' => 'homepage-next-second-post',
		'description' => __('A widget area that appears next to the second post on the homepage.', 'current'),
		'before_widget' => '<aside id="%1$s" class="%2$s clearfix">',
		'after_widget' 	=> "</aside>",
		'before_title' 	=> '<h3 class="widgettitle">',
		'after_title' 	=> '</h3>',
	);

	$sidebars[] = array(
		'name' => __('Homepage bottom, between posts', 'current'),
		'id' => 'home-bottom-doubled',
		'description' => __( 'This area is output twice in the bottom half of the homepage. Use it to place ad widgets.' ,'current'),
		'before_widget' => '<aside id="%1$s" class="%2$s clearfix">',
		'after_widget' 	=> "</aside>",
		'before_title' 	=> '<h3 class="widgettitle visuallyhidden">',
		'after_title' 	=> '</h3>',
	);

	foreach ($sidebars as $sidebar)
		register_sidebar($sidebar);
}
add_action('widgets_init', 'current_register_sidebars', 11);


/**
 * Extra user profile fields
 *
 * @since 1.0
 */
function current_user_profile_fields($user) {
	$phone_number = get_user_meta($user->ID, 'phone_number', true);
	$topics_covered = get_user_meta($user->ID, 'topics_covered', true);

	$topics_covered_args = array(
		'walker' => new ProfileCategoryWalker(),
		'name' => 'topics_covered',
		'checked' => $topics_covered,
		'echo' => false,
		'title_li' => false,
		'hierarchical' => false
	);
	?>
	<style type="text/css">
	.category-select {
		height: 250px;
		background: #fff;
		overflow: scroll;
		max-width: 350px;
		box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
		border: 1px solid #ddd;
	}
	.category-select ul {
		margin: 10px;
	}
	</style>

	<tr>
		<th><label for="phone_number"><?php _e('Phone number', 'current'); ?></label></th>
		<td><input type="text" value="<?php if (!empty($phone_number)) { echo $phone_number; } ?>" name="phone_number"></td>
	</tr>
	<tr>
		<th><label for="topics_covered"><?php _e('Topics covered', 'current'); ?></label></th>
		<td><div class="category-select"><ul><?php echo wp_list_categories($topics_covered_args); ?></ul></div></td>
	</tr>

	<?php
}
add_action('largo_more_profile_information', 'current_user_profile_fields', 10, 1);


/**
 * Save profile fields when a user saves a profile page.
 *
 * @since 1.0
 */
function current_save_user_profile_fields($user_id) {
	if (!empty($_POST)) {
		update_user_meta($user_id, 'phone_number', $_POST['phone_number']);
		update_user_meta($user_id, 'topics_covered', $_POST['topics_covered']);
	}
}
add_action('edit_user_profile_update', 'current_save_user_profile_fields');
add_action('personal_options_update', 'current_save_user_profile_fields');


class ProfileCategoryWalker extends Walker_Category{

	public function start_el(&$output, $term, $depth = 0, $args = Array(), $id = 0 ){
		$args = wp_parse_args($args, array(
			'name' => 'profile_category_input',
			'checked' => array(),
		));

		extract($args);
		ob_start(); ?>
		<li>
			<input type="checkbox" <?php checked(in_array($term->term_id, $checked)); ?>
				id="category-<?php print $term->term_id; ?>"
				name="<?php print $name; ?>[]"
				value="<?php print $term->term_id; ?>" />
			<label for="category-<?php print $term->term_id; ?>">
				<?php print esc_attr($term->name); ?>
			</label>
<?php
		$output .= ob_get_contents();
		ob_end_clean();
	}
}


/**
 * Add profile fields to user pages.
 *
 * @since 1.0
 */
function current_add_user_profile_fields($context, $slug, $name) {
	if ($slug == 'partials/author-bio' && $name == 'description') {
		$user = $context['author_obj'];

		$topics_ids = get_user_meta($user->ID, 'topics_covered', true);
		$topics_covered = array();

		if (!empty($topics_ids)) {
			foreach ($topics_ids as $id)
				array_push($topics_covered, get_category($id));
		}

		$context = array_merge($context, array(
			'job_title' => get_user_meta($user->ID, 'job_title', true),
			'phone_number' => get_user_meta($user->ID, 'phone_number', true),
			'topics_covered' => $topics_covered
		));
	}
	return $context;
}
add_filter('largo_render_template_context', 'current_add_user_profile_fields', 10, 3);


/**
 * Add a span to all widget titles.
 *
 * @since 1.0
 */
function current_widget_title($title) {
	if (!empty($title))
		return '<span>' . $title . '</span>';
	else
		return $title;
}
add_filter('widget_title', 'current_widget_title', 10, 1);


/**
 * Insert a widget after the third post on the homepage.
 *
 * @since 1.0
 */
function current_insert_home_list_widget_area($post, $query) {
	if ($query->current_post == 2)
		dynamic_sidebar('homepage-after-third-post');
}
add_action('largo_after_home_list_post', 'current_insert_home_list_widget_area', 10, 2);

// Add updated Universal Google Analytics tracking code to head

function universal_analytics_tracking() {
	?>
	<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

	ga( 'create', 'UA-6017171-1', 'auto' );
	ga( 'send', 'pageview' );

	</script>
	<?php
}
add_action( 'wp_head', 'universal_analytics_tracking' );
