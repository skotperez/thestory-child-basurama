<?php
// theme setup main function
add_action( 'after_setup_theme', 'basurama_theme_setup' );
function basurama_theme_setup() {

	// hook migration functions
//	add_action( 'wp_footer','basurama_posts_to_portfolio_pt');

} // end theme setup main function


// functions for migrate the content in basurama.org
// to story post types and options
// february 2015
////
// move post in project category to portfolio post type
function basurama_posts_to_portfolio_pt() {
	$args = array(
		'post_type' => 'post',
		'category_name' => 'proyecto',
		'nopaging' => true
	);
	$projects = get_posts($args);
	foreach ( $projects as $p ) {
		// categs to custom fields
		$cats = get_the_category($p->ID);
		foreach ( $cats as $c ) {
			/* Country */ if ( $c->category_parent == '6' ) { add_post_meta($p->ID, "_basurama_project_country", $c->name); }
			/* Material */ elseif ( $c->category_parent == '8' ) { add_post_meta($p->ID, "_basurama_project_material", $c->name); }
			/* Date */ elseif ( $c->category_parent == '10' ) { add_post_meta($p->ID, "_basurama_project_date", $c->name); }
			/* City */ elseif ( $c->category_parent == '104' ) { add_post_meta($p->ID, "_basurama_project_city", $c->name); }
				
		} // end foreach $cats

		// post to portfolio post type
		$args = array(
			'ID' => $p->ID,
			'post_type' => 'portfolio',
		);
		$project_id = wp_update_post($args);
		if ( $project_id == 0 ) { echo "<p style='color: red;'>La transformación del post ".$p->ID." ha fallado.</p>"; }
		else { echo "<p>El post ".$p->ID." se ha transformado correctamente.</p>"; }
	} // end foreach $projects

} // end move post in project category to portfolio post type

?>
