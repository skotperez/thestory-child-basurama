<?php
// theme setup main function
add_action( 'after_setup_theme', 'basurama_theme_setup' );
function basurama_theme_setup() {

	// hook migration functions
//	add_action( 'wp_footer','basurama_posts_to_portfolio_pt');

	// Redefine Story Theme custom post type portfolio
	add_action( 'init', 'pexeto_register_portfolio_post_type' );

	/* Load JavaScript files for admin screens */
	add_action( 'admin_enqueue_scripts', 'basurama_load_admin_scripts' );
	add_action( 'wp_enqueue_scripts', 'basurama_load_frontend_scripts');

	// Custom Meta Boxes
	add_filter( 'cmb2_meta_boxes', 'basurama_metaboxes' );

	// custom loops for each template
	add_filter( 'pre_get_posts', 'basurama_custom_args_for_loops' );

	// change options array for pages $pexeto_page
	add_action('the_post','basurama_pexeto_page_options');

	// get last year of a project and add the number as a CF to sort portfolio gallery
	add_action( 'save_post', 'basurama_project_add_last_year_cf', 10 );

	// load text domain for child theme
	load_theme_textdomain( 'basurama', get_stylesheet_directory_uri() . '/lang' );

	// unfilter admin and editor roles to allow them to include HTML tags in content when activating theme
	add_action( 'init', 'basurama_kses_init', 11 );
	add_action( 'set_current_user', 'basurama_kses_init', 11 );
	add_action( 'after_switch_theme', 'basurama_unfilter_roles', 10 ); 

	// refilter admin and editor roles when deactivating theme
	add_action( 'switch_theme', 'basurama_refilter_roles', 10 );

} // end theme setup main function

// remove KSES filters for admins and editors
function basurama_kses_init() {
 	if ( current_user_can( 'edit_others_posts' ) )
		kses_remove_filters();
}

// add unfiltered_html capability for admins and editors
function basurama_unfilter_roles() {
	// Makes sure $wp_roles is initialized
	get_role( 'administrator' );

	global $wp_roles;
	// Dont use get_role() wrapper, it doesn't work as a one off.
	// (get_role does not properly return as reference)
	$wp_roles->role_objects['administrator']->add_cap( 'unfiltered_html' );
	$wp_roles->role_objects['editor']->add_cap( 'unfiltered_html' );
}

// remove unfiltered_html capability for admins and editors
function basurama_refilter_roles() {
 	get_role( 'administrator' );
	global $wp_roles;
	// Could use the get_role() wrapper here since this function is never
	// called as a one off.  It is always called to alter the role as
	// stored in the DB.
	$wp_roles->role_objects['administrator']->remove_cap( 'unfiltered_html' );
	$wp_roles->role_objects['editor']->remove_cap( 'unfiltered_html' );
}

// load js scripts to avoid conflicts
function basurama_load_admin_scripts() {
	wp_enqueue_script(
		'clone-metabox',
		get_stylesheet_directory_uri().'/js/clone.metabox.js',
		array( 'jquery' ),
		'0.1',
		false
	);
 
} // end load eadmin js scripts to avoid conflicts

// load js scripts to avoid conflicts
function basurama_load_frontend_scripts() {
	wp_deregister_script( 'pexeto-portfolio-gallery' );
	wp_enqueue_script(
		'basurama-portfolio-gallery',
		get_stylesheet_directory_uri().'/js/basurama-portfolio-gallery.js',
		array( 'jquery' ),
		'0.1',
		false
	);

} // end load frontend js scripts to avoid conflicts


// custom args for loops
function basurama_custom_args_for_loops( $query ) {
	if ( is_page_template('template-portfolio-gallery.php') && array_key_exists('post_type', $query->query_vars ) && $query->query_vars['post_type'] == PEXETO_PORTFOLIO_POST_TYPE ) { 
		$query->set( 'order','DESC');
		$query->set( 'orderby','meta_value_num date');
		$query->set( 'meta_key','_basurama_project_date_last');
	} elseif ( !is_admin() && is_search() && $query->is_main_query() ) {
		$query->set( 'post_type',PEXETO_PORTFOLIO_POST_TYPE);
	}
	return $query;
} // END custom args for loops

// change options array for pages $pexeto_page
function basurama_pexeto_page_options() {
	global $pexeto_page;
	if ( is_search() ) { $pexeto_page['layout'] = 'full'; }
} // END change options array for pages $pexeto_page

// get last year of a project and add the number as a CF to sort portfolio gallery
function basurama_project_add_last_year_cf($post_id) {
	global $post;
	if ( wp_is_post_revision( $post_id ) || PEXETO_PORTFOLIO_POST_TYPE != get_post_type($post_id) )
		return;

	$years = get_post_meta($post_id,'_basurama_project_date');
	if ( count($years) >= 1 ) {
		rsort($years);
		update_post_meta($post_id,'_basurama_project_date_last',$years[0]);
	}

} // END get last year of a project and add the number as a CF to sort portfolio gallery

////
// functions for migrate the content in basurama.org
// to story post types and options
////

// move post in project category to portfolio post type
function basurama_posts_to_portfolio_pt() {
//	$cat = 'projects';
	$cat = 'projetos';
	$begin_pt = 'post';
	$begin_tax = 'category';
	$end_pt = 'portfolio';
	$end_tax = 'portfolio_category';

	$args = array(
		'post_type' => $begin_pt,
		'category_name' => $cat,
		'nopaging' => true
	);
	$projects = get_posts($args);
	$update_ids = array();
	$update_where = array();
	foreach ( $projects as $p ) {
		$update_ids[] = $p->ID;
		$update_where[] = "%s";
echo $p->ID;
echo "<br>";
echo $p->post_title;
echo "<br>";
		// categs to custom fields
		$cats = get_the_category($p->ID);
		$end_tax_slugs = array();
		foreach ( $cats as $c ) {
			/* Country */ if ( $c->category_parent == '6' ) { add_post_meta($p->ID, "_basurama_project_country", $c->name); }
			/* Material */ elseif ( $c->category_parent == '8' ) { add_post_meta($p->ID, "_basurama_project_material", $c->name); }
			/* Date */ elseif ( $c->category_parent == '10' ) { add_post_meta($p->ID, "_basurama_project_date", $c->name); }
			/* City */ elseif ( $c->category_parent == '104' ) { add_post_meta($p->ID, "_basurama_project_city", $c->name); }
			/* Type */ elseif ( $c->category_parent == '7' ) {
				add_post_meta($p->ID, "_basurama_project_type", $c->name);
				// create term in portfolio_category tax if it doesn't exist
				if ( !term_exists($c->name,$end_tax) ) { wp_insert_term( $c->name, $end_tax ); }
				$end_tax_slugs[] = $c->name;
			}
				
		} // end foreach $cats

		// post to portfolio post type
		$args = array(
			'ID' => $p->ID,
			'post_type' => $end_pt
		);
		$project_id = wp_update_post($args);
		if ( $project_id == 0 ) { echo "<p style='color: red;'>La transformación del post ".$p->ID." ha fallado.</p>"; }
		else {
			echo "<p>El post ".$p->ID." se ha transformado correctamente.</p>";
			// project terms
			$inserted_terms = wp_set_object_terms( $project_id, $end_tax_slugs, $end_tax );
			if ( is_wp_error($inserted_terms) ) { echo "<p style='color: red;'>Se produjo un problema mientras se insertaban los terms para este proyecto.</p>"; }
			else { unset($end_tax_slugs); }

		}
	} // end foreach $projects

	// update WPML translations table
	global $wpdb;
	$update_where_string = implode(", ",$update_where);
	$table = $wpdb->prefix . "icl_translations";
	$col_to_update = "element_type";
	$old_value = "post_post";
	$new_value = "post_portfolio";
	$query_update = "
		UPDATE $table
		SET $col_to_update = $new_value
		WHERE id IN ($update_where_string)
		  AND $col_to_update = $old_value
	";
	$wpdb->query( $wpdb->prepare($query_update, $update_ids) );

} // end move post in project category to portfolio post type


////
// Functions to modify Story theme functions
// these functions have the same name that Story's function which they modify,
// but changing prefix pexeto by basurama
////
function basurama_get_gallery_thumbnail_html($post) {
	$output = "";
	$city_country = get_post_meta($post->ID,'_basurama_project_city_country_output',true);
	if ( $city_country != '' ) {
		$output .= "<div class='ps-basic ps-basic-where'>".$city_country."</div>";

	} else {
		$place = array();
		$cities = get_post_meta($post->ID,'_basurama_project_city');
		$countries = get_post_meta($post->ID,'_basurama_project_country');
		if ( count($cities) >= 1 ) { $place[] = $cities[0]; }
		if ( count($countries) >= 1 ) { $place[] = $countries[0]; }
		$output .= "<div class='ps-basic ps-basic-where ps-basic-inline'>".implode(", ",$place)."</div>";
	}
	$date = get_post_meta($post->ID,'_basurama_project_date');
	if ( count($date) >> 1 ) {
		sort($date);
		$output .= "<div class='ps-basic ps-basic-when'>".$date[0]." - ".end($date)."</div>";
	} elseif ( count($date) == 1 ) { $output .= " <div class='ps-basic ps-basic-when ps-basic-inline'>".$date[0]."</div>"; }

	return $output;
} // end basurama_get_gallery_thumbnail_html

function basurama_get_portfolio_slider_item_html($post) {
	$terms=wp_get_post_terms( $post->ID, PEXETO_PORTFOLIO_TAXONOMY );
	$term_names=array();
	foreach ( $terms as $term ) {
		$term_names[]=str_replace('@'.ICL_LANGUAGE_CODE, '', $term->name);
	}

	$ps_basics = basurama_get_gallery_thumbnail_html($post);
	$materials = get_post_meta($post->ID,'_basurama_project_material');
	if ( count($materials) >= 1 ) {
		sort($materials);
		$ps_basics .= '<div class="ps-basic ps-basic-materials">'.implode( '. ', $materials ).'.</div>';
	}

	$ps_extra = "";
	$basu_extra['coauthor'] = array( 'label' => __('Co-authors','basurama'), 'text' => get_post_meta($post->ID,'_basurama_project_coauthor',true));
	$basu_extra['institution'] = array( 'label' => __('Institutions','basurama'), 'text' => get_post_meta($post->ID,'_basurama_project_institution',true));
	$basu_extra['collaborator'] = array( 'label' => __('Collaborators','basurama'), 'text' => get_post_meta($post->ID,'_basurama_project_collaborator',true));
	$wysiwyg_fields = array(
		array( __('Measures','basurama'),'measurements' ),
		array( __('Funders','basurama'),'funder' ),
		array( __('Thanks to','basurama'),'thanks' )
	);
	foreach ( $wysiwyg_fields as $w ) {
		$text = get_post_meta($post->ID,'_basurama_project_'.$w[1],true);
		if ( $text != '' ) { $basu_extra[$w[1]] = array( 'label' => $w[0], 'text' => wpautop($text) ); }
	}

	foreach ( $basu_extra as $k => $f ) {
		if ( $f['text'] !=  '' ) {
			$label = $f['label'];
			$text = array();
			if ( is_array($f['text']) ) {
				foreach ($f['text'] as $i ) {
					if ( array_key_exists('url',$i) && $i['url'] != '' ) { $text[] = "<a href='".$i['url']."'>".$i['text']."</a>"; }
					else { $text[] = $i['text']; }
				}
			} else { $text[] = $f['text']; }
			if ( count($text) >= 1 ) {
				$ps_extra .= '<div class="ps-extra ps-extra-'.$k.'"><div class="ps-extra-label">'.$label.'</div><div class="ps-extra-text">'.implode( ', ',$text).'</div></div>';
			}
		}
	}

	$output='
	<div class="ps-side">
		<div class="ps-side-up">
			<h2 class="ps-title">'.$post->post_title.'</h2>
			<span class="ps-categories">'.implode( ' / ', $term_names ).'</span>
			'.$ps_basics.'
		</div>
	';
	if ( $ps_extra != '' ) {
		$output .= '
		<aside class="ps-side-down">
			<div class="ps-side-title">'.__('Technical details','basurama').'</div>
			'.$ps_extra.'
		</aside>
		';
	}
	$output .= '</div>';

	$content = pexeto_option( 'ps_strip_gallery' ) ?
		pexeto_remove_gallery_from_content( $post->post_content ) :
		$post->post_content;
	$output.='<div class="ps-content-text">'.do_shortcode( apply_filters( 'the_content', $content ) ).'</div>';

	return $output;
} // basurama_get_portfolio_slider_item_html


////
// Story theme functions redefinition:
//
// 1. pexeto_get_gallery_thumbnail_html
// 2. pexeto_get_portfolio_slider_item_html
// 3. pexeto_register_portfolio_post_type
////
	/**
	 * Generates the HTML code for a gallery thumbnail item.
	 *
	 * @param object  $post         the post that will represent the gallery item
	 * @param int     $columns      the number of columns of items the gallery will contain
	 * @param int     $image_height the height of the thumbnail image
	 * @param string  $itemclass    the class of the wrapping div
	 * @return string               the generated HTML code for the item
	 */
	function pexeto_get_gallery_thumbnail_html( $post, $columns, $image_height, $itemclass='pg-item' ) {

		$size_key = $itemclass == 'pc-item' ? 'carousel' : 'gallery';
		$size_options = pexeto_get_image_size_options($columns, $size_key);

		$image_width = $size_options['width'];
		$settings = pexeto_get_post_meta( $post->ID, array( 'type' ), PEXETO_PORTFOLIO_POST_TYPE );
		$exclude_info = pexeto_option( 'portfolio_exclude_info' );
		$add_class = sizeof( $exclude_info ) == 2 ? ' pg-info-dis' : '';

		$html='<div class="'.$itemclass.$add_class.'" data-defwidth="'.( $image_width+10 ).'"'.
			' data-type="'.$settings['type'].'"'.
			' data-itemid="'.$post->ID.'">';


		$preview = pexeto_get_portfolio_preview_img( $post->ID );

		$crop = $image_height ? true : false;

		//retrieve the image URL
		if ( $preview['custom'] ) {
			//use the original image set
			$img_url = $preview['img'];
		}else {
			//use a resized image
			$big_image_width = $image_width + 200;
			$big_image_height = empty($image_height)?$image_height:$image_height*($image_width+200)/$image_width;
			$img_url = pexeto_get_resized_image( $preview['img'],
				$big_image_width,
				$big_image_height );
		}

		//load the categories assigned to the item
		$terms=wp_get_post_terms( $post->ID, PEXETO_PORTFOLIO_TAXONOMY );
		$term_names=array();
		foreach ( $terms as $term ) {
			$term_names[]=$term->name;
		}

		$href='#';
		$rel='';
		$target='';

		//set the link of the item according to its type
		switch ( $settings['type'] ) {
		case 'smallslider':
		case 'fullslider':
		case 'standard':
		case 'fullvideo':
		case 'smallvideo':
			$href = get_permalink( $post->ID );
			break;
		case 'custom':
			$href = pexeto_get_single_meta( $post->ID, 'custom_link' );
			if( pexeto_get_single_meta( $post->ID, 'custom_link_open' )=='new' ){
				$target = ' target="_blank"';
			}
			break;
		case 'lightbox':
			$lightbox_preview = array();
			if($preview['custom']){
				//get the image preview, skipping the thumbnail image
				$lightbox_preview = pexeto_get_portfolio_preview_img( $post->ID, true );
			}
			$href = empty($lightbox_preview['img']) ? $preview['img'] : $lightbox_preview['img'];
			//gallery items should be in a group in the lightbox preview
			$add_rel = $itemclass == 'pg-item'?'[group]':'';
			$rel = ' data-rel="pglightbox'.$add_rel.'"';
		}

		$alt = isset($preview['alt']) ? $preview['alt'] : $post->post_title;


		$html.='<a href="'.$href.'" title="'.$post->post_title.'"'.$rel.$target.'>'.
			'<div class="pg-img-wrapper">';
			//add the item icon
			$html.='<span class="icon-circle">'.
			'<span class="pg-icon '.$settings['type'].'-icon"></span>'.
			'</span>'.
			'<img src="'.$img_url.'" alt="'.esc_attr($alt).'"/></div>';

		


			//display the item info
			$html.='<div class="pg-info">';
			$html.='<div class="pg-details'.$add_class.'">';
			if ( !in_array( 'title', $exclude_info ) ) {
				$html.='<h2>'.$post->post_title.'</h2>';
			}
			if ( !in_array( 'category', $exclude_info ) ) {
				$html.='<span class="pg-categories">'.implode( ' / ', $term_names ).'</span>';
			}
			$html .= basurama_get_gallery_thumbnail_html($post);
			$html.='</div></div>';
		

		$html.='</a></div>';

		return $html;
	}

	/**
	 * Generates the gallery slider HTML code.
	 *
	 * @param int     $itemid the ID of the item(post) that will represent the slider
	 * @param boolean $single setting whether it is a single item page or the
	 * slider was loaded from the gallery, as part of the gallery
	 * @return string          the HTML code of the slider
	 */
	function pexeto_get_portfolio_slider_item_html( $itemid, $single=true ) {
		$html = '';
		global $post;
		if ( empty( $post ) || $post->ID !== $itemid ) {
			$post = get_post( $itemid );
		}

		$item_type = pexeto_get_single_meta( $itemid, 'type' );
		$fullwidth = $item_type=='fullslider' || $item_type=='fullvideo'?true:false;
		$video = $item_type=='fullvideo' || $item_type=='smallvideo'?true:false;
		$content_class = $video ? 'ps-video':'ps-images';

		$preview = pexeto_get_portfolio_preview_img( $post->ID );

		if ( !empty( $post ) ) {
			$add_class = $fullwidth ? ' ps-fullwidth':'';

			$html = '<div class="ps-wrapper'.$add_class.'">';

			//add the slider
			$html.='<div class="'.$content_class.'">';
			if ( $video ) {
				global $pexeto_content_sizes;
				$width = $fullwidth ?
					$pexeto_content_sizes['fullwidth'] : $pexeto_content_sizes['sliderside'];
				$video_url=pexeto_get_single_meta( $itemid, 'video' );
				$html.=pexeto_get_video_html( $video_url, $width );
			}
			$html.='</div>';

			//add the content
			$html.='<div class="ps-content">';

			//get the categories
			//load the categories assigned to the item	
			//add the title and content_class
			$html.= basurama_get_portfolio_slider_item_html($post);

			//add the share buttons
			$share = pexeto_get_share_btns_html( $itemid, 'slider' );
			if ( !empty( $share ) ) {
				$html.='<div class="ps-share">'.$share.'</div>';
			}
			$html.='</div>';
			$html.='<div class="clear"></div></div>';
		}

		return $html;
	}

	/**
	 * Registers the portfolio custom type.
	 */
	function pexeto_register_portfolio_post_type() {

		//the labels that will be used for the portfolio items
		$labels = array(
			'name' => _x( 'Portfolio', 'portfolio name', 'pexeto_admin' ),
			'singular_name' => _x( 'Portfolio Item', 'portfolio type singular name', 'pexeto_admin' ),
			'add_new' => _x( 'Add New', 'portfolio', 'pexeto_admin' ),
			'add_new_item' => __( 'Add New Item', 'pexeto_admin' ),
			'edit_item' => __( 'Edit Item', 'pexeto_admin' ),
			'new_item' => __( 'New Portfolio Item', 'pexeto_admin' ),
			'view_item' => __( 'View Item', 'pexeto_admin' ),
			'search_items' => __( 'Search Portfolio Items', 'pexeto_admin' ),
			'not_found' =>  __( 'No portfolio items found', 'pexeto_admin' ),
			'not_found_in_trash' => __( 'No portfolio items found in Trash', 'pexeto_admin' ),
			'parent_item_colon' => ''
		);

		//register the custom post type
		register_post_type( PEXETO_PORTFOLIO_POST_TYPE,
			array( 'labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'capability_type' => 'post',
				'hierarchical' => false,
				'rewrite' => array( 'slug'=>'projects' ),
				'taxonomies' => array( PEXETO_PORTFOLIO_TAXONOMY ),
				'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes','revisions' ) ) );

		flush_rewrite_rules();
	}



/**
 * Define the metabox and field configurations.
 *
 * @param  array $meta_boxes
 * @return array
 */
function basurama_metaboxes( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_basurama_';
	
	/**
	* Sample metabox to demonstrate each field type included
	*/
	foreach ( array('coauthor','institution','collaborator') as $f ) {
		$meta_boxes[] = array(
			'id'            => 'project_'.$f.'s',
			'title'         => $f.'s',
			'object_types'  => array( 'portfolio', ),
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => true,
			'fields'        => array(
				array(
					'id'          => $prefix. 'project_' .$f,
					'type'        => 'group',
					'options'     => array(
						'group_title'   => $f.' {#}', // since version 1.1.4, {#} gets replaced by row number
						'add_button'    => 'Add Another '.$f,
						'remove_button' => 'Remove '.$f,
						'sortable'      => true, // beta
					),
					// Fields array works the same, except id's only need to be unique for this group. Prefix is not needed.
					'fields'      => array(
						array(
							'name' => $f.' complete name',
							'id'   => 'text',
							'type' => 'text',
							// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
						),
						array(
							'name' => 'URL',
							'id'   => 'url',
							'type' => 'text_url',
							'protocols' => array( 'http', 'https' )
						),
					),
				),
			),
		);
	} // end foreach group type fields

	foreach ( array('measurements','funder','thanks') as $f ) {
		$meta_boxes[] = array(
			'id'            => 'project_'.$f,
			'title'         => $f,
			'object_types'  => array( 'portfolio', ),
			'context'       => 'normal',
			'priority'      => 'high',
			'show_names'    => false,
			'fields'        => array(
				array(
					'name' => $f,
					'id'   => $prefix. 'project_' .$f,
					'type' => 'wysiwyg',
				),
			),
		);
	} // end foreach wysiwyg type fields

	return $meta_boxes;
}

// to get all values in a meta key
// ordered alphabetically or numerically
function basurama_get_meta($meta_key) {

	if ( !array_key_exists('post',$_GET) )
		return;

	global $wpdb;
	if ( function_exists('icl_object_id') ) {
		$post_id = sanitize_text_field($_GET['post']);
		$table_tr = $wpdb->prefix . "icl_translations";
		$sql_query = "
			SELECT
			  tr.language_code
			FROM $table_tr tr
			WHERE tr.element_type = 'post_portfolio'
			  AND tr.element_id = '$post_id'
		";
		$post_lang = $wpdb->get_results( $sql_query );
		$lang_code = $post_lang[0]->language_code;

		$table_pm = $wpdb->prefix . "postmeta";
		$sql_query = "
			SELECT
			  pm.meta_value
			FROM $table_pm pm
			INNER JOIN $table_tr tr
			  ON pm.post_id = tr.element_id
			WHERE pm.meta_key = '$meta_key'
			  AND tr.element_type = 'post_portfolio'
			  AND tr.language_code = '$lang_code'
			ORDER BY pm.meta_value
		";
		$query_results = $wpdb->get_results( $sql_query , OBJECT_K );

	} else {
		$table_pm = $wpdb->prefix . "postmeta";
		$sql_query = "
			SELECT
			pm.meta_value
			FROM $table_pm pm
			WHERE pm.meta_key = '$meta_key'
			ORDER BY pm.meta_value
		";
		$query_results = $wpdb->get_results( $sql_query , OBJECT_K );

	} // END if WPML plugin is active
	
	if ( $query_results == NULL )
		return NULL;

	$options = array();
	foreach ( $query_results as $r ) { $options[$r->meta_value] = $r->meta_value; }
	return $options;

} // END to get all values in a meta key

// to get all post IDs of one post type
// this functions is used to make changes directly in the DB
function basurama_get_post_ids($post_type) {
	$args = array(
		'post_type' => $post_type,
		'nopaging' => true,
		'meta_key' => '_thumbnail_id'
	);
	$posts = get_posts($args);
	$post_ids = array();
	foreach ( $posts as $p ) { $post_ids[] = $p->ID; }
	return implode(", ",$post_ids);
} // END to get all post IDs of one post type

?>
