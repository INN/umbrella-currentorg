<?php

define('FEATURED_MEDIA', true);


/**
 * Include theme files and register a homepage layout
 *
 * Based off of how Largo loads files: https://github.com/INN/Largo/blob/master/functions.php#L358
 *
 * 1. hook function Largo() on after_setup_theme
 * 2. function Largo() runs Largo::get_instance()
 * 3. Largo::get_instance() runs Largo::require_files()
 *
 * This function is intended to be easily copied between child themes, and for that reason is not prefixed with this child theme's normal prefix.
 *
 * @link https://github.com/INN/Largo/blob/master/functions.php#L145
 * @see "homepages/layouts/current.php"
nc */
function largo_child_require_files() {
	// load the layout
	$includes = array(
		'/homepages/layouts/current.php',
		'/inc/custom-taxonomies.php'
	);
	foreach ( $includes as $include ) {
		require_once( get_stylesheet_directory() . $include );
	}
	register_homepage_layout( 'CurrentHomepage' );
}
add_action( 'init', 'largo_child_require_files', 0 );


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
		'name' => __('Homepage next to second post', 'current'),
		'id' => 'homepage-next-second-post',
		'description' => __('A widget area that appears next to the second post on the homepage.', 'current'),
		'before_widget' => '<aside id="%1$s" class="%2$s clearfix">',
		'after_widget' 	=> "</aside>",
		'before_title' 	=> '<h3 class="widgettitle">',
		'after_title' 	=> '</h3>',
	);

	$sidebars[] = array(
		'name' => __('Homepage after second post', 'current'),
		'id' => 'homepage-after-second-post',
		'description' => __('A widget area that appears after the second post on the homepage.', 'current'),
		'before_widget' => '<div class="hp-after-three-widget">' . "\n",
		'after_widget' => '</div>' . "\n"
	);

	$sidebars[] = array(
		'name' => __('Homepage after third post', 'current'),
		'id' => 'homepage-after-third-post',
		'description' => __('A widget area that appears after the third post on the homepage.', 'current'),
		'before_widget' => '<div class="hp-after-three-widget">' . "\n",
		'after_widget' => '</div>' . "\n"
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

/**
 * Insert a widget after the third post on the homepage.
 *
 * @since 1.0
 */
function current_insert_home_list_widget_area_earlier($post, $query) {
	if ($query->current_post == 0)
		dynamic_sidebar('homepage-after-second-post');
}
add_action('largo_before_home_list_post', 'current_insert_home_list_widget_area_earlier', 10, 2);

/**
 * wallit paywall, with some Chartbeat logging integration
 *
 * If user is logged in, this script does not output.
 *
 * When a wallit user (has a wallit account) is granted access:
 * - attempt to push the 'lgdin' status to chartbeat
 * - if the reason for granting the access is because of money, try to push the 'paid' status to chartbeat
 *
 * @link https://wallit.github.io/api/js#resourceaccessdata-object#i-need-to-replace-my-content-on-access-granted-with-the-full-content
 * @link https://wallit.github.io/api/js#resourceaccessdataquota-object
 */
function current_wallit_js() {
	if ( is_user_logged_in() )
		return;
	?>
		<script src="https://cdn.wallit.io/paywall.min.js"></script>
		<script type="text/javascript">
		wallit.paywall.init(
			'0bf27215-847a-4f97-93f5-6633b76d27ff',
			{
				accessGranted: function(data) {
					var maybe_anon = true;
					_cbq = window._cbq = (window._cbq || []);
					if (
						data.AccessReason === 'Purchase'
						|| data.AccessReason === 'Subscription'
						|| data.AccessReason === 'PropertyUser'
					) {
						maybe_anon = false;
						try {
							_cbq.push(['_acct', 'lgdin']);
						} catch (e) {
							console.log('Error when trying to pass the Wallit logged-in status to Chartbeat.');
							console.log(e);
						}
					}

					if (
						data.AccessReason === 'Purchase'
						|| data.AccessReason === 'Subscription'
					) {
						maybe_anon = false;
						try {
							_cbq.push(['_acct', 'paid']);
						} catch (e) {
							console.log('Error when trying to pass the Wallit paid status to Chartbeat.');
							console.log(e);
						}
					}

					if ( maybe_anon ) {
						try {
							_cbq.push(['_acct', 'anon']);
						} catch (e) {
							console.log('Error when trying to pass the Wallit anonymous status to Chartbeat.');
							console.log(e);
						}
					}
				}
			}
		);
		</script>
	<?php
}
add_action( 'wp_head', 'current_wallit_js' );


/**
 * Chartbeat tracking script.
 *
 * If user is logged in, this script does not output.
 *
 */
function current_chartbeat() {
	if ( is_user_logged_in() )
		return;
	?>
	<script type='text/javascript' id="current_chartbeat">
		var _sf_async_config = _sf_async_config || {};

		/** CONFIGURATION START **/
		_sf_async_config.uid = 57004;
		_sf_async_config.domain = 'current.org';
		_sf_async_config.useCanonical = true;
		_cbq = window._cbq = (window._cbq || []);
		<?php

		?>
		/** CONFIGURATION END **/

		(function() {
			function loadChartbeat() {
				var e = document.createElement('script');
				e.setAttribute('language', 'javascript');
				e.setAttribute('type', 'text/javascript');
				e.setAttribute('src', '//static.chartbeat.com/js/chartbeat.js');
				document.body.appendChild(e);
			}
			var oldonload = window.onload;
			window.onload = (typeof window.onload != 'function') ?
			loadChartbeat : function() {
					oldonload();
					loadChartbeat();
				};
		})();
	</script>
	<?php
}
add_action( 'wp_footer', 'current_chartbeat' );

if ( ! function_exists( 'largo_excerpt' ) ) {
	function largo_excerpt( $the_post = null, $sentence_count = 5, $use_more = null, $more_link = null, $echo = true, $strip_tags = false, $strip_shortcodes = true ) {
		if (!empty($use_more))
			_deprecated_argument(__FUNCTION__, '0.5.1', 'Parameter $use_more is deprecated. Please use null as the argument.');
		if (!empty($more_link))
			_deprecated_argument(__FUNCTION__, '0.5.1', 'Parameter $more_link is deprecated. Please use null as the argument.');

		$the_post = get_post($the_post); // Normalize it into a post object

		if (!empty($the_post->post_excerpt)) {
			// if a post has a custom excerpt set, we'll use that
			$content = apply_filters('get_the_excerpt', $the_post->post_excerpt);
		} else if (is_home() && preg_match('/<!--more(.*?)?-->/', $the_post->post_content, $matches) > 0) {
			// if we're on the homepage and the post has a more tag, use that
			$parts = explode($matches[0], $the_post->post_content, 2);
			$content = $parts[0];
		} else {
			// otherwise we'll just do our best and make the prettiest excerpt we can muster
			$content = largo_trim_sentences($the_post->post_content, $sentence_count);
		}

		// optionally strip shortcodes and html
		$output = '';
		if ( $strip_tags && $strip_shortcodes )
			$output .= strip_tags( strip_shortcodes ( $content ) );
		else if ( $strip_tags )
			$output .= strip_tags( $content );
		else if ( $strip_shortcodes )
			$output .= strip_shortcodes( $content );
		else
			$output .= $content;

		$output = apply_filters('the_content', $output);

		if ( $echo )
			echo $output;

		return $output;
	}
}
