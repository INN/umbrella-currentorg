<?php
/**
 * Functions relating to Current.org's custom taxonomies
 *
 * This adds custom hierarchical hidden taxonomies for:
 * - City
 * - State
 * - Sources quoted in the story
 * - Stations mentioned in story
 * - Source, where "Source" is something like: Press Release, Hearken, Tip, Pitch, Other publication, Event, Reporter Idea, Editor idea, Social media
 *
 * Hierarchical was chosen because it avoids duplication of terms and variation on capitalization: problems that would require use of the Term Debt Consolidator.
 *
 * @link https://secure.helpscout.net/conversation/452083230/1388/?folderId=1219602
 */

/**
 * Register these custom taxonomies
 *
 * @since WordPress 4.8.2
 */
function current_register_taxonomies() {
	register_taxonomy(
		'current-city',
		'post',
		array(
			'public' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => false,
			'description' => 'Cities, towns, municipalities and other regions involved in posts.',
			'hierarchical' => true,
			'labels' => array(
				'name' => __( 'City', 'current' ),
				'singular_name' => __( 'City', 'current' ),
				'menu_name' => __( 'Cities', 'current' ),
				'all_items' => __( 'All Cities', 'current' ),
				'edit_item' => __( 'Edit City', 'current' ),
				'view_item' => __( 'View City', 'current' ),
				'update_item' => __( 'Update City', 'current' ),
				'parent_item' => __( 'Parent City', 'current' ),
				'parent_item_colon' => __( 'Parent City:', 'current' ),
				'search_items' => __( 'Search Cities', 'current' ),
				'popular_items' => __( 'Popular Cities', 'current' ),
				'not_found' => __( 'City not located.', 'current' ),
				'add_new_item' => __( 'Add new city', 'current' ),
				'new_item_name' => __( 'New City Name', 'current' ),
			)
		)
	);

	register_taxonomy(
		'current-state',
		'post',
		array(
			'public' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => false,
			'description' => 'States and other territories mentioned in posts.',
			'hierarchical' => true,
			'labels' => array(
				'name' => __( 'State', 'current' ),
				'singular_name' => __( 'State', 'current' ),
				'menu_name' => __( 'States', 'current' ),
				'all_items' => __( 'All States', 'current' ),
				'edit_item' => __( 'Edit State', 'current' ),
				'view_item' => __( 'View State', 'current' ),
				'update_item' => __( 'Update State', 'current' ),
				'parent_item' => __( 'Parent State', 'current' ),
				'parent_item_colon' => __( 'Parent State:', 'current' ),
				'search_items' => __( 'Search States', 'current' ),
				'popular_items' => __( 'Popular States', 'current' ),
				'not_found' => __( 'States not located.', 'current' ),
				'add_new_item' => __( 'Add new state', 'current' ),
				'new_item_name' => __( 'New State Name', 'current' ),
			)
		)
	);
	register_taxonomy(
		'current-mentioned-sources',
		'post',
		array(
			'public' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => false,
			'description' => 'Quoted sources and documents mentioned in posts.',
			'hierarchical' => true,
			'labels' => array(
				'name' => __( 'Mentioned Sources', 'current' ),
				'singular_name' => __( 'Mentioned Source', 'current' ),
				'menu_name' => __( 'Mentioned Sources', 'current' ),
				'all_items' => __( 'All Mentioned Sources', 'current' ),
				'edit_item' => __( 'Edit Mentioned Source', 'current' ),
				'view_item' => __( 'View Mentioned Source', 'current' ),
				'update_item' => __( 'Update Mentioned Source', 'current' ),
				'parent_item' => __( 'Parent Mentioned Source', 'current' ),
				'parent_item_colon' => __( 'Parent Mentioned Source:', 'current' ),
				'search_items' => __( 'Search Mentioned Sources', 'current' ),
				'popular_items' => __( 'Popular Mentioned Sources', 'current' ),
				'not_found' => __( 'Mentioned Source not found.', 'current' ),
				'add_new_item' => __( 'Add new mentioned source', 'current' ),
				'new_item_name' => __( 'New Source Name', 'current' ),
			)
		)
	);
	register_taxonomy(
		'current-mentioned-stations',
		'post',
		array(
			'public' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => false,
			'description' => 'Member stations/orgs mentioned in posts.',
			'hierarchical' => true,
			'labels' => array(
				'name' => __( 'Mentioned Stations/Orgs', 'current' ),
				'singular_name' => __( 'Station/Org', 'current' ),
				'menu_name' => __( 'Stations/Orgs', 'current' ),
				'all_items' => __( 'All Stations/Orgs', 'current' ),
				'edit_item' => __( 'Edit Station/Org', 'current' ),
				'view_item' => __( 'View Station/Org', 'current' ),
				'update_item' => __( 'Update Station/Org', 'current' ),
				'parent_item' => __( 'Parent Station/Org', 'current' ),
				'parent_item_colon' => __( 'Parent Station/Org:', 'current' ),
				'search_items' => __( 'Search Stations/Orgs', 'current' ),
				'popular_items' => __( 'Popular Stations/Orgs', 'current' ),
				'not_found' => __( 'Station/Org not located.', 'current' ),
				'add_new_item' => __( 'Add new station/org', 'current' ),
				'new_item_name' => __( 'New Station/Org Name', 'current' ),
			)
		)
	);
	register_taxonomy(
		'current-source',
		'post',
		array(
			'public' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => false,
			'description' => 'Where did we get the initial idea for this post?',
			'hierarchical' => true,
			'labels' => array(
				'name' => __( 'Source of Story', 'current' ),
				'singular_name' => __( 'Source', 'current' ),
				'menu_name' => __( 'Sources', 'current' ),
				'all_items' => __( 'All Sources', 'current' ),
				'edit_item' => __( 'Edit Source', 'current' ),
				'view_item' => __( 'View Source', 'current' ),
				'update_item' => __( 'Update Source', 'current' ),
				'parent_item' => __( 'Parent Source', 'current' ),
				'parent_item_colon' => __( 'Parent Source:', 'current' ),
				'search_items' => __( 'Search Sources', 'current' ),
				'popular_items' => __( 'Popular Sources', 'current' ),
				'not_found' => __( 'Source not found.', 'current' ),
				'add_new_item' => __( 'Add new source', 'current' ),
				'new_item_name' => __( 'New Source Type', 'current' ),
			)
		)
	);
}
add_action( 'init', 'current_register_taxonomies', 10 );
