<?php
/**
 * Functions modifying the Business Directory Plugin
 *
 * @link https://github.com/INN/umbrella-currentorg/pull/24
 */

/**
 * Add custom form fields to wpbdp_tag taxonomy add/edit pages
 *
 * @param Mixed $tag The term being edited
 */
function wpbdp_tag_edit_form_fields( $tag ) {

	if ( is_object( $tag ) ){

		$wpbdp_tag_meta = get_term_meta( $tag->term_id );
		$wpbdp_tag_parent_category = $wpbdp_tag_meta['wpbdp_tag_parent_category'][0];
		$wpbdp_tag_parent_category = str_replace('wpbdp_category--', '', $wpbdp_tag_parent_category);

	} else {

		$wpbdp_tag_parent_category = '';

	}

	printf(
		'<tr class="form-field">
			<th scope="row" valign="top">
				<label for="wpbdp_tag_parent_category">%1$s</label>
			</th>
			<td>
				%2$s
				<p class="description">%3$s</p>
			</td>
		</tr>',
		esc_html__( 'Parent Category', 'currentorg' ),
		wp_dropdown_categories(
			array(
				'taxonomy'         => 'wpbdp_category',
				'id'               => 'wpbdp_tag_parent_category',
				'name'             => 'wpbdp_tag_parent_category',
				'show_option_none' => __( 'Select category' ),
				'depth'            => 1,
				'echo'             => false,
				'selected'         => $wpbdp_tag_parent_category,
				'hide_empty'	   => 0,
				'orderby'		   => 'title',
				'order'			   => 'ASC'
			)
		),
		__( 'Select the parent category for this tag.', 'currentorg' )
	);

	wp_nonce_field( 'wpbdp_tag_parent_category_update', 'wpbdp_tag_parent_category_nonce' );

}
add_action( 'wpbdp_tag_add_form_fields', 'wpbdp_tag_edit_form_fields' );
add_action( 'wpbdp_tag_edit_form_fields', 'wpbdp_tag_edit_form_fields' );

/**
 * Save custom form fields from wpbdp_tag taxonomy add/edit pages
 *
 * @param int $term_id
 * @param int $tt_id
 */
function wpbdp_tag_form_fields_save( $term_id, $tt_id ) {

	if( isset( $_POST['wpbdp_tag_parent_category_nonce'] ) && wp_verify_nonce( $_POST['wpbdp_tag_parent_category_nonce'], 'wpbdp_tag_parent_category_update' ) ) {

		if ( ! empty( $_POST['wpbdp_tag_parent_category'] ) ) {

			update_term_meta( $term_id, 'wpbdp_tag_parent_category', 'wpbdp_category--' . $_POST['wpbdp_tag_parent_category'] );

		}

	}

}
add_action( 'created_wpbdp_tag', 'wpbdp_tag_form_fields_save', 10, 2 );
add_action( 'edited_wpbdp_tag', 'wpbdp_tag_form_fields_save', 10, 2 );

/**
 * Helper to get the tags for a given category by their meta
 *
 * @param WP_Term 
 * @return Arrray of WP_Term objects
 */
function wpbdp_get_tags_by_category( $term ) {
	$args = array(
		'taxonomy' => WPBDP_TAGS_TAX,
		'hide_empty' => true,
		'meta_key' => 'wpbdp_tag_parent_category',
		'meta_value' => 'wpbdp_category--' . $term->term_id,
	);
	$tags_query = new WP_Term_Query( $args );
	return $tags_query->terms;
}

/**
 * Modify the wpdb_tag tag cloud to include wpbdp_category--id class
 *
 * @param Array $tag_data
 * @return Array the new tag data
 */
function wpbdp_modify_tag_cloud( $tag_data ) {
	return array_map(
		function ( $tag ) {
			$term = get_term_by( 'slug', $tag[ 'slug' ], 'wpbdp_tag' );

			if ( 'wpbdp_tag' === $term->taxonomy ) {

				$wpbdp_tag_parent_category = get_term_meta( $term->term_id );
				$wpbdp_tag_parent_category = $wpbdp_tag_parent_category['wpbdp_tag_parent_category'][0];
				$tag['class'] .= ' ' . $wpbdp_tag_parent_category;

			}

			return $tag;
		},
		(array) $tag_data
	);
}
add_filter( 'wp_generate_tag_cloud_data', 'wpbdp_modify_tag_cloud' );

/**
 * Return all tags in a list in the wpbdp_tag tag cloud.
 *
 * Runs during an AJAX call to get the tags in the list.
 *
 * @param Array $args
 */
function wpbdp_tag_cloud_show_all_tags( $args ) {
	
	if( in_array( 'wpbdp_tag', $args['taxonomy'] ) ){

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['action'] ) && $_POST['action'] === 'get-tagcloud') {

			unset( $args['number'] );
			$args['hide_empty'] = 0;

		}

	}

	return $args;
}
add_filter( 'get_terms_args', 'wpbdp_tag_cloud_show_all_tags' );

/**
 * Show all existing tags inside of the wpbdp_tag tag cloud
 */
function wpbdp_tag_cloud_custom_css_js(){

	global $current_screen;

	if( 'wpbdp_listing' === $current_screen->post_type ) {
		?>
		<script type="text/javascript">
			jQuery(window).load(function() {

				jQuery("body.wp-admin #tagsdiv-wpbdp_tag #link-wpbdp_tag").trigger("click");
				jQuery("body.wp-admin #tagsdiv-wpbdp_tag #link-wpbdp_tag").hide();

			});

			jQuery(document).ready(function(){

				jQuery('#tagsdiv-wpbdp_tag .inside').prepend('<label>Selected Tags:</label>');
				jQuery('<label>Available Tags:</label>').insertAfter('#tagsdiv-wpbdp_tag .tagsdiv');

				// if a category is selected/deselected, do things
				jQuery("#wpbdp_categorychecklist input").on("click", function(){

					// get id of clicked category
					var wpbdp_category_id = jQuery(this).val();

					if(jQuery(this).is(":checked")){
						// show all child tags of this category
						jQuery("#tagsdiv-wpbdp_tag .wpbdp_category--"+wpbdp_category_id).removeClass("hidden");
					} else {
						// hide all child tags of this category
						jQuery("#tagsdiv-wpbdp_tag .wpbdp_category--"+wpbdp_category_id).addClass("hidden");
					}

					// if this categoy is unchecked, we need to remove all of its child tags that were selected
					if(!jQuery(this).is(":checked")){

						// grab text name of child tag
						var wpbdp_parent_category_child_tag = jQuery("#tagsdiv-wpbdp_tag .wpbdp_category--"+wpbdp_category_id).text();

						// loop through all selected tags and remove if it matches the name of one with an unchecked parent category
						jQuery(".tagchecklist li").each(function(){
							if(jQuery(this).find(".screen-reader-text").text().replace("Remove term: ", "") == wpbdp_parent_category_child_tag){
								jQuery(this).find("button").click();
							}
						});
					}

				});

			});

			jQuery(document).ajaxStop(function() {

				// loop through all selected categories and hide any tags without active parent categories
				jQuery("#wpbdp_categorychecklist li input").each(function(){

					var wpbdp_category_id = jQuery(this).val();

					if(!jQuery(this).is(":checked")){
						jQuery("#tagsdiv-wpbdp_tag .wpbdp_category--"+wpbdp_category_id).addClass("hidden");
					}
					
				});

			});

		</script>
		<style>
			/* hide the show/hide button */
			body.wp-admin #tagsdiv-wpbdp_tag #link-wpbdp_tag{visibility:hidden;}
			body.wp-admin #tagsdiv-wpbdp_tag #wpbdp_tag .jaxtag{display:none;} 
			/* make the tag cloud not a tag cloud */
			body.wp-admin #tagsdiv-wpbdp_tag #tagcloud-wpbdp_tag.the-tagcloud ul li{display:block;margin-bottom:0;}
			body.wp-admin #tagsdiv-wpbdp_tag #tagcloud-wpbdp_tag.the-tagcloud ul li a{font-size:13px!important;}
		</style>
		<?php
	}

}
add_action( 'admin_head', 'wpbdp_tag_cloud_custom_css_js' );

/**
 * Loop through all saved wpbdp_tags and remove any that don't
 * have any active parent category.
 *
 * Runs during save_post hook
 *
 * @param int $post_id
 */
function wpbdp_verify_tags_on_post_save( $post_id ){

	$post = get_post( $post_id );
	$post_type = $post->post_type;
	
	if( $post_type == 'wpbdp_listing' && is_admin() ){

		// grab the current listing categories and tags
		$wpbdp_listing_categories = get_the_terms( $post_id, 'wpbdp_category' );
		$wpbdp_listing_tags = get_the_terms( $post_id, 'wpbdp_tag' );

		if( $wpbdp_listing_tags ){

			// loop through each saved tag
			foreach( $wpbdp_listing_tags as $wpbdp_listing_tag ){

				$wpbdp_tag_meta = get_term_meta( $wpbdp_listing_tag->term_id );
				
				$wpbdp_tag_parent_category = $wpbdp_tag_meta['wpbdp_tag_parent_category'][0];
				$wpbdp_tag_parent_category = str_replace( 'wpbdp_category--', '', $wpbdp_tag_parent_category );

				$wpbdp_tag_parent_category_selected = false;

				// loop through each saved listing category and if a parent category is found
				// with an id that matches the tag parent id, set $wpbdp_tag_parent_category_selected = true
				foreach( $wpbdp_listing_categories as $key => $wpbdp_listing_category ){

					if( $wpbdp_listing_category->term_id == $wpbdp_tag_parent_category ){

						$wpbdp_tag_parent_category_selected = true;

					}

				}

				// if no parent category has been found with a matching id, 
				// let's go ahead and remove the tag that shouldn't be there
				if( !$wpbdp_tag_parent_category_selected ){

					wp_remove_object_terms( $post_id, $wpbdp_listing_tag->term_id, 'wpbdp_tag' );

				}

			}

		}

	}

}
add_action( 'save_post', 'wpbdp_verify_tags_on_post_save' );

/**
 * Switches default template for single wpbdp listing pages
 * since by default they inherit the parent directory page template
 * 
 * @return String of the current page/post template
 */
function wpbdp_single_listing_page_template( $page_template ) {

    if( wpbdp_check_if_specific_page_type( '_wpbdp_listing' ) ){
            
            $page_template = get_template_directory() . '/single-two-column.php'; 

	}
    
    return $page_template;

}
add_filter( 'page_template', 'wpbdp_single_listing_page_template' );

/**
 * Output flavor text and a list of tags on the category page
 *
 * This is for a hook in /plugins/business-directory-plugin/templates/category.tpl.php
 */
function wpbdp_category_preface_matter() {

	$term = get_queried_object();

	/**
	 * Get the term's tags and output them
	 *
	 * Same logic is used in business-directory/main_page.tpl.php
	 */
	$tags = wpbdp_get_tags_by_category( $term );
	if ( ! empty ( $tags ) ) {
		printf(
			'<p class="category-description">%1$s</p>',
			esc_html__( 'The companies featured here offer the following services to public media:', 'currentorg' )
		);
		echo '<ul class="category-tags-list">';
		foreach ( $tags as $tag ) {
			printf(
				'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
				WPBDP_TAGS_TAX . '-' . $tag->term_id,
				get_term_link( $tag ),
				esc_html( $tag->name )
			);
		}
		echo '</ul>';
	}
}
add_action( 'wpbdp_before_category_page', 'wpbdp_category_preface_matter' );

/**
 * Modify the post_content in response to specific WPBDP conditions.
 *
 * This function exists because all WPBDP views are different outputs from the
 * [businessdirectory] shortcode in response to URL parameters.
 *
 * @return Object of the current post
 * @uses wpbdp_check_if_specific_wpbdp_view
 * @uses wpbdp_check_if_specific_page_type
 */
function wpbdp_filter_the_content(){

	global $post;

	/*
	 * If we are on a single wpbdp listing or category page,
	 * let's remove the parent post content from the $post obj
	 *
	 * For https://github.com/INN/umbrella-currentorg/pull/35
	 */
	if( wpbdp_check_if_specific_page_type( array( '_wpbdp_listing', '_wpbdp_category' ) ) ){

		$post->post_content = '[businessdirectory]';

	}

	/*
	 * Add a specific message to the top of the submit-listing page.
	 *
	 * For https://github.com/INN/umbrella-currentorg/issues/48 .
	 */
	if( wpbdp_check_if_specific_wpbdp_view( array( 'submit_listing' ) ) ){

		$post->post_content = __( '<p>Public media is a $3.5 billion industry comprised of hundreds of noncommercial radio and TV stations that serve nearly every community in the U.S. Public broadcasters seek trusted vendors for a wide range of products and services that will help their companies succeed. Current is where they connect with you.</p><p>Have questions or need assistance? Contact Kathy Bybee Hartzell - <a href="mailto:kathy@current.org">kathy@current.org</a></p>', 'currentorg');
		
		// display images for each fee plan
		$post->post_content .= '<div class="wpbdp-submit-listing-flex-grid">';
			$post->post_content .= '<div class="wpbdp-fee-plan-img display-none-important"><img src="'.esc_attr( get_stylesheet_directory_uri() . '/business-directory/img/basic-fee-plan.png' ).'"></div>';
			$post->post_content .= '<div class="wpbdp-fee-plan-img display-none-important"><img src="'.esc_attr( get_stylesheet_directory_uri() . '/business-directory/img/enhanced-fee-plan.png' ).'"></div>';
			$post->post_content .= '<div class="wpbdp-fee-plan-img display-none-important"><img src="'.esc_attr( get_stylesheet_directory_uri() . '/business-directory/img/leading-fee-plan.png' ).'"></div>';
		$post->post_content .= '</div>';

		$post->post_content .= '[businessdirectory]';

		wp_enqueue_script( 'business-directory-plugin-submit-listing', esc_attr( get_stylesheet_directory_uri() . '/js/business-directory-plugin-submit-listing.js' ), $deps, '1.0', true );

		wp_localize_script(
            'business-directory-plugin-submit-listing', 'wpbdpSubmitListingL10n', array(
				'categoriesPlaceholderTxt' => _x( 'Click this field to add categories', 'submit listing', 'WPBDM' ),
				'completeListingTxt'       => _x( 'Complete Listing', 'submit listing', 'WPBDM' ),
				'continueToPaymentTxt'     => _x( 'Continue to Payment', 'submit listing', 'WPBDM' ),
				'isAdmin'                  => current_user_can( 'administrator' ),
				'waitAMoment'              => _x( 'Please wait a moment!', 'submit listing', 'WPBDM' ),
				'somethingWentWrong'       => _x( 'Something went wrong!', 'submit listing', 'WPBDM' ),
            )
		);
		
	}

	if( wpbdp_check_if_specific_wpbdp_view( array( 'login' ) ) ){

		$post->post_content = __( '<p>Public media is a $3.5 billion industry comprised of hundreds of noncommercial radio and TV stations that serve nearly every community in the U.S. Public broadcasters seek trusted vendors for a wide range of products and services that will help their companies succeed. Current is where they connect with you.</p><p>Have questions or need assistance? Contact Kathy Bybee Hartzell - <a href="mailto:kathy@current.org">kathy@current.org</a></p>', 'currentorg');
		
		// display images for each fee plan
		$post->post_content .= '<div class="wpbdp-submit-listing-flex-grid">';
			$post->post_content .= '<div class="wpbdp-fee-plan-img"><img src="'.esc_attr( get_stylesheet_directory_uri() . '/business-directory/img/basic-fee-plan.png' ).'"></div>';
			$post->post_content .= '<div class="wpbdp-fee-plan-img"><img src="'.esc_attr( get_stylesheet_directory_uri() . '/business-directory/img/enhanced-fee-plan.png' ).'"></div>';
			$post->post_content .= '<div class="wpbdp-fee-plan-img"><img src="'.esc_attr( get_stylesheet_directory_uri() . '/business-directory/img/leading-fee-plan.png' ).'"></div>';
		$post->post_content .= '</div>';

		$post->post_content .= '[businessdirectory]';

	}

	if( wpbdp_check_if_specific_wpbdp_view( array( 'edit_listing' ) ) ){

		wp_enqueue_script( 'business-directory-plugin-edit-listing', esc_attr( get_stylesheet_directory_uri() . '/js/business-directory-plugin-edit-listing.js' ), $deps, '1.0', true );

	}

	return $post;

}
add_filter( 'wp', 'wpbdp_filter_the_content' );

/**
 * Find out if a specific post/page is a wpbdp specific page type
 * 
 * Useful if you need to see if you're on a single listing or category page
 * 
 * @param Mixed $wpbdp_array_keys The keys to check in the query_vars array; should
 * be something such as _wpbdp_listing, _wpbdp_category, etc.
 * @return Boolean
 * @see wpbdp_filter_the_content
 */
function wpbdp_check_if_specific_page_type( $wpbdp_array_keys ) {

	global $post;
	global $wp_query;

	$wpbdp_specific_page_type = false;

	if ( is_a( $post, 'WP_Post' ) && $post->post_type == 'page' ){

		$query_vars = $wp_query->query_vars;

		if( is_array( $wpbdp_array_keys ) ) {

			foreach( $wpbdp_array_keys as $wpbdp_array_key ){

				if( array_key_exists( $wpbdp_array_key, $query_vars ) ){

					$wpbdp_specific_page_type = true;

				}

			}

		} else if ( array_key_exists( $wpbdp_array_keys, $query_vars ) ){

			$wpbdp_specific_page_type = true;

		} 

	}

	return $wpbdp_specific_page_type;
}

/**
 * Find out if a specific WPBDP view is being displayed
 *
 * Differs from wpbdp_check_if_specific_page_type() in that
 * this is not checking whether this page is a form of WPBDP page,
 * but instead checks whether this page is outputting a given view
 *
 * @param Array $wpbdp_views Array of query parameter values to check whether this page is one of those views.
 * @return Boolean
 * @see wpbdp_filter_the_content
 */
function wpbdp_check_if_specific_wpbdp_view( $wpbdp_views = array() ) {
	if ( ! is_array( $wpbdp_views ) ) {
		// not using _doing_it_wrong 'cos that's not a public WP function;
		// see https://developer.wordpress.org/reference/functions/_doing_it_wrong/
		error_log( 'wpbdp_check_if_specific_wpbdp_view must be passed an array!' );
		return false;
	}

	$return = false;

	global $wp_query;
	$query_vars = $wp_query->query_vars;

	if ( ! isset( $query_vars['wpbdp_view'] ) ) {
		return false;
	}

	foreach ( $wpbdp_views as $wpbdp_view ) {
		if ( in_array( $wpbdp_view, $query_vars, true ) ) {
			$return = true;
			break;
		}
	}

	return $return;
}

/**
 * Add underline CSS to the directory/listings buttons
 *
 * @link https://github.com/INN/umbrella-currentorg/issues/38#issuecomment-506518715
 */
function wpbdp_page_specific_css() {

	/**
	 * On the assumption that the page the directory is displayed on will always be post 5909
	 */
	$qo = get_queried_object();
	if ( ! is_object( $qo ) || ! isset( $qo->ID ) || 5909 !== (int) $qo->ID ) {
		return;
	}

	// this URL param is how WPBDP distinguishes the 'all listings' page.
	if ( isset( $_GET['wpbdp_view'] ) && 'all_listings' === $_GET['wpbdp_view'] ) {
		?>
			<style type="text/css">
				#wpbdp-main-box #wpbdp-bar-view-listings-button.button.wpbdp-button {
					border-bottom-color: #1c819e;
				}
				#wpbdp-main-box:hover #wpbdp-bar-view-listings-button.button.wpbdp-button:not(:hover) {
					border-bottom-color: transparent;
				}
			</style>
		<?php
	} else if ( isset( $_GET ) && empty( $_GET ) ) {
		global $wp;

		// check by exclusion that this page is just the main directory listing page
		// and not any other page in the directory listing that isn't the main page.
		if ( isset( $wp->request ) && 'directory-of-services' === $wp->request ) {
			?>
				<style type="text/css">
					#wpbdp-main-box #wpbdp-bar-show-directory-button.button.wpbdp-button {
						border-bottom-color: #1c819e;
					}
					#wpbdp-main-box:hover #wpbdp-bar-show-directory-button.button.wpbdp-button:not(:hover) {
						border-bottom-color: transparent;
					}
				</style>
			<?php
		}
	}
}
add_action( 'wp_head', 'wpbdp_page_specific_css', 10, 0 );

/**
 * Dequeue specific scripts or styles that are conflicting
 * with the WPBDP plugin.
 * 
 * @param String $hook Name of the current hook
 * @link https://github.com/INN/umbrella-currentorg/issues/57
 */
function wpbdp_dequeue_conflicts( $hook ) {

	if( 'post.php' == $hook && 'wpbdp_listing' == get_post_type() ) {

		// Broadstreet was causing the WPBDP listing image uploader to break
		wp_dequeue_script( 'Broadstreet-main' );

	}

}
add_action( 'admin_enqueue_scripts', 'wpbdp_dequeue_conflicts', 10 );

/**
 * Dequeue scripts that are interferring with customizations
 * such as the wpbdp-submit-listing js
 */
function wpbdp_dequeue_scripts() {
	wp_dequeue_script( 'wpbdp-submit-listing' );
 }
 add_action( 'wp_print_scripts', 'wpbdp_dequeue_scripts', 100 );

/**
 * Grab all wpbdp_categories and find the child tags of each one
 * 
 * @param Array $request The request object if it's provided
 * @return Array $wpbdp_categories_with_children All of the wpbdp_categories with their child tags
 */
function wpbdp_display_categories_with_child_tags( $request ) {

	// get all wpbdp categories
	$wpbdp_categories = get_terms([
		'taxonomy' => 'wpbdp_category',
		'hide_empty' => false
	]);

	$wpbdp_categories_with_children = array();

	foreach( $wpbdp_categories as $wpbdp_category ){

		$wpbdp_category_id = $wpbdp_category->term_id;
		$wpbdp_category_name = $wpbdp_category->name;

		$wpbdp_category_child_tags = get_terms([
			'taxonomy'   => 'wpbdp_tag',
			'hide_empty' => false,
			'meta_key'   => 'wpbdp_tag_parent_category',
			'meta_value' => 'wpbdp_category--'.$wpbdp_category_id,
		]);

		$wpbdp_category_with_child_tags = array(
			'wpbdp_category_name' => $wpbdp_category_name,
			'wpbdp_category_id'   => $wpbdp_category_id,
			'wpbdp_category_child_tags' => $wpbdp_category_child_tags
		);

		array_push( $wpbdp_categories_with_children, $wpbdp_category_with_child_tags );

	}

	return $wpbdp_categories_with_children;
	
}

/**
 * Add new route to REST API for wpbdp_categories
 * so we can view wpbdp_categories with their child tags
**/
function wpbdp_register_custom_category_rest_route(){

	register_rest_route( 'currentorg/v1', 'wpbdp_categories', array(
		'methods'  => 'GET',
		'callback' => 'wpbdp_display_categories_with_child_tags'
	));

};
add_action( 'rest_api_init', 'wpbdp_register_custom_category_rest_route' );

/**
 * Echo out custom css that applies specifically to the submit listing page
 * Only used for the submit listing page, not the submission received page
 * 
 * @param Object $listing An object with information about the current listing being submitted or edited.
 */
function wpbdp_submit_listing_page_css( $listing ){
	echo 
	'<style>
		.wpbdp-fee-plan-img {
			display: block!important;
		}
	</style>';
}
add_action( 'wpbdp_before_submit_listing_page', 'wpbdp_submit_listing_page_css', 99, 1 );
