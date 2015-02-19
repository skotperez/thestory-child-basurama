<?php
// theme setup main function
add_action( 'after_setup_theme', 'basurama_theme_setup' );
function basurama_theme_setup() {

	// hook migration functions
	// add_action( 'wp_footer','basurama_posts_to_portfolio_pt');

	/* Load JavaScript files for admin screens */
	add_action( 'admin_enqueue_scripts', 'basurama_load_admin_scripts' );

	// Custom Meta Boxes
	add_filter( 'cmb2_meta_boxes', 'basurama_metaboxes' );
	/* Meta Box plugin CF registration */
	add_filter( 'rwmb_meta_boxes', 'basurama_extra_metaboxes' );

} // end theme setup main function

// load js scripts to avoid conflicts
function basurama_load_admin_scripts() {
	wp_enqueue_script(
		'clone-metabox-js',
		get_stylesheet_directory_uri().'/js/clone.metabox.js',
		array( 'jquery' ),
		'0.1',
		false
	);

} // end load js scripts to avoid conflicts


////
// functions for migrate the content in basurama.org
// to story post types and options
////

// move post in project category to portfolio post type
function basurama_posts_to_portfolio_pt() {
	$begin_pt = 'post';
	$begin_tax = 'category';
	$end_pt = 'portfolio';
	$end_tax = 'portfolio_category';

	$args = array(
		'post_type' => $begin_pt,
		'category_name' => 'proyecto',
		'nopaging' => true
	);
	$projects = get_posts($args);
	foreach ( $projects as $p ) {
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

} // end move post in project category to portfolio post type


////
// Functions to modify Story theme functions
// these functions have the same name that Story's function which they modify,
// but changing prefix pexeto by basurama
////
function basurama_get_gallery_thumbnail_html($post) {
	$output = "";
	$basu_extra['city'] = get_post_meta($post->ID,'_basurama_project_city');
	$basu_extra['country'] = get_post_meta($post->ID,'_basurama_project_country');
	$basu_extra['date'] = get_post_meta($post->ID,'_basurama_project_date');
	foreach ( $basu_extra as $f ) {
		if ( count($f) >= 1 ) {
			$output.='<span class="pg-extra">'.implode( ' / ', $f ).'</span> ';
		}
	}
	return $output;
} // end basurama_get_gallery_thumbnail_html

function basurama_get_portfolio_slider_item_html($post) {
	$terms=wp_get_post_terms( $post->ID, PEXETO_PORTFOLIO_TAXONOMY );
	$term_names=array();
	foreach ( $terms as $term ) {
		$term_names[]=$term->name;
	}

	$ps_extra = "";
	$basu_extra['city'] = get_post_meta($post->ID,'_basurama_project_city');
	$basu_extra['country'] = get_post_meta($post->ID,'_basurama_project_country');
	$basu_extra['date'] = get_post_meta($post->ID,'_basurama_project_date');
	$basu_extra['material'] = get_post_meta($post->ID,'_basurama_project_material');
//	$basu_extra['coauthor'] = get_post_meta($post->ID,'_basurama_project_coauthor');
	foreach ( $basu_extra as $f ) {
		if ( count($f) >= 1 ) {
			$ps_extra.='<div class="ps-extra">'.implode( ' / ', $f ).'</div> ';
		}
	}

			$output='<div class="ps-side"><div class="ps-side-up">';
			$output.='<h2 class="ps-title">'.$post->post_title.'</h2>';
			$content = pexeto_option( 'ps_strip_gallery' ) ?
				pexeto_remove_gallery_from_content( $post->post_content ) :
				$post->post_content;
			$output.='<span class="ps-categories">'.implode( ' / ', $term_names ).'</span>';
			$output.= $ps_extra;
			$output.='</div><aside class="ps-side-down">';
			$output.='</aside></div>';
			$output.='<div class="ps-content-text">'.
				do_shortcode( apply_filters( 'the_content', $content ) ).'</div>';

	return $output;
} // basurama_get_portfolio_slider_item_html


////
// Story theme functions redefinition:
//
// 1. pexeto_get_gallery_thumbnail_html
// 2. pexeto_get_portfolio_slider_item_html
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
					'id'          => $prefix . $f,
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

	foreach ( array('measurements','financials','thanks') as $f ) {
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
					'id'   => $prefix . $f,
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
	global $wpdb;
	
	$table_pm = $wpdb->prefix . "postmeta";
	$sql_query = "
		SELECT
		  pm.meta_value
		FROM $table_pm pm
		WHERE pm.meta_key = '$meta_key'
		ORDER BY pm.meta_value
	";
	$query_results = $wpdb->get_results( $sql_query , OBJECT_K );
	$options = array();
	foreach ( $query_results as $r ) {
		$options[$r->meta_value] = $r->meta_value;
	}
	return $options;
}

/*
 * extra meta boxes
 * out of Meta Box plugin
 */
function basurama_extra_metaboxes( $meta_boxes ) {
	/**
	* prefix of meta keys (optional)
	* Use underscore (_) at the beginning to make keys hidden
	* Alt.: You also can make prefix empty to disable it
	*/
	// Better has an underscore as last sign
	$prefix = '_basurama_';

	foreach ( array("date","city","country","material") as $mb ) {
	// Project meta boxex
	$meta_boxes[] = array(	
		'id' => 'project_'.$mb,// Meta box id, UNIQUE per meta box. Optional since 4.1.5
		'title' => 'Project '.$mb, // Meta box title - Will appear at the drag and drop handle bar. Required.	
		'post_types' => array( 'portfolio' ), // Post types, accept custom post types as well - DEFAULT is 'post'. Can be array (multiple post types) or string (1 post type). Optional.
		'context' => 'side', // Where the meta box appear: normal (default), advanced, side. Optional.	
		'priority' => 'high',// Order of meta box: high (default), low. Optional.	
		'autosave' => true,// Auto save: true, false (default). Optional.
		// List of meta fields
		'fields' => array(
			array(
				//'name' => 'Years',
				'id' => "{$prefix}project_".$mb,
				'type' => 'checkbox_list',
				// Options of checkboxes, in format 'value' => 'Label'
				'options' => basurama_get_meta('_basurama_project_'.$mb)
			)
		)
	);
	}
	return $meta_boxes;

} // end basurama_extra_metaboxes

?>
