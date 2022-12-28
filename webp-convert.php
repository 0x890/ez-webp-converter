<?php
/*
Plugin Name: EZ Webp Image Converter
Plugin URI: https://github.com/0x890
Description: EZ WebP Converter is a powerful plugin that allows you to easily convert your images to the WebP format. WebP is a modern image format that offers superior lossless and lossy compression for images on the web. By converting your images to WebP, you can significantly reduce the size of your website and improve its loading speed. With EZ WebP Converter, you can select individual images or convert all images in your media library with just a few clicks. The plugin also integrates seamlessly with your WordPress media library, allowing you to easily manage your WebP images. Try EZ WebP Converter today and take the first step towards a faster and more efficient website!
Version: 1.0
Author: Noureddine Bellounis
Author URI: https://kyo-conseil.com
*/




function convert_image_to_webp( $image_path, $quality ) {
	if ( function_exists( 'imagewebp' ) ) {
		$image_type = exif_imagetype( $image_path );
		$path_info = pathinfo( $image_path );
		$image_extension = $path_info['extension'];

		switch ( $image_type ) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg( $image_path );
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng( $image_path );
				break;
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif( $image_path );
				break;
		}

		if ( $image ) {
			imagewebp( $image, str_replace($image_extension,'webp', $image_path ), $quality);
			imagedestroy( $image );
		}
	}
}

function scan_directories_for_images() {
	$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
	$images_per_page = 10;

	// Determine the current page
	$current_page = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1;

	// Calculate the offset for the query
	$offset = ( $current_page - 1 ) * $images_per_page;

	// Get the images for the current page
	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => $images_per_page,
		'offset'         => $offset,
		'orderby'        => 'date',
		'order'          => 'DESC',
		's'              => $search_query,
	);

	$images = get_posts( $args );
	$image_paths = array();
	foreach ( $images as $image ) {
		$image_path = get_attached_file( $image->ID );
		$image_paths[] = $image_path;
	}
	return $image_paths;
}


function get_quality_dropdown() {
	$output = '<select name="quality" id="quality" class="form-control">';

	for ( $i = 20; $i <= 100; $i+=20 ) {
		$output .= '<option value="' . $i . '">' . $i . '%</option>';
	}

	$output .= '</select>';

	return $output;
}

function image_converter_settings_page() {
	$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		's'              => $search_query,

	);
	$all_images = get_posts( $args );
	$total_images = count( $all_images );
	$images_per_page = 10;
	$total_pages = ceil( $total_images / $images_per_page );
	$current_page = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1;


	if ( isset( $_POST['convert_images'] ) ) {
		$image_paths = $_POST['image_paths'];
		$quality = (int) $_POST['quality'];

		foreach ( $image_paths as $image_path ) {
			convert_image_to_webp( $image_path, $quality );
		}

		echo '<div class="alert alert-success alert-dismissible fade show mt-4 mr-4" role="alert">';
		echo 'The selected image has been converted to WebP format.';
		echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
		echo '<span aria-hidden="true">&times;</span>';
		echo '</button>';
		echo '</div>';
	}

	if ( isset( $_POST['convert_all_images'] ) ) {
		$image_paths = scan_directories_for_images();

		foreach ( $image_paths as $image_path ) {
			convert_image_to_webp( $image_path, '80' );
		}
		echo '<div class="alert alert-success alert-dismissible fade show mt-4 mr-4" role="alert">';
		echo 'All images have been converted to WebP format.';
		echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
		echo '<span aria-hidden="true">&times;</span>';
		echo '</button>';
		echo '</div>';
	}

	$image_paths = scan_directories_for_images();
	$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

	echo '<h2 class="mt-4">Webp images converter : </h2>';
	echo '<p>Select images you want to convert or click on convert all images. </p>';
	echo '<form id="search-form" method="get">';
	echo '<input type="hidden" name="page" value="image-converter-settings">';
	echo '<div class="form-group row">';
	echo '<div class="col-md-2">';
	echo '<input type="text" name="s" id="search-query" class="form-control"  value="'.htmlspecialchars($search_query).'" placeholder="Search images...">';
	echo '</div>';
	echo '<div class="col-md-1">';
	echo '<button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>';
	echo '</div>';
	echo '</div>';
	echo '</form>';
	echo '<form method="post" id="image-search-form">';
	echo '<div class="form-group row">';
	echo '</div>';
	echo '<table class="wp-list-table widefat fixed striped m">';
	echo '<thead><tr><th>Image</th><th>Name</th><th class="size-column">Size</th><th>Status</th><th>Convert</th></tr></thead>';
	echo '<tbody id="images-table-body">';

	foreach ( $image_paths as $image_path ) {
		$image_name = basename( $image_path );
		$image_size = size_format( filesize( $image_path ) );
		$path_info = pathinfo( $image_path );
		$image_extension = $path_info['extension'];

		echo '<tr>';
		echo '<td><img src="/' . str_replace( ABSPATH, '', $image_path )
		     . '" style="max-width: 100px;"></td>';
		echo '<td class="title column-title">' . $image_name . '</td>';
		echo '<td><strong>' . $image_size . '</strong></td>';
		echo '<td>' . (file_exists(str_replace($image_extension,'webp', $image_path)) ?  '<i class="fas fa-check-circle" style="color: green;"></i>' : '<i class="fas fa-check-circle" style="color: grey;"></i>') . '</td>';
		echo '<td><input type="checkbox" class="image-checkbox" name="image_paths[]" value="' . $image_path . '"></td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '<div class="form-group row mt-4" style="justify-content: start">';
	echo '<div class="col-1">';
	echo get_quality_dropdown();
	echo '</div>';
	echo '<div class="col-2">';
	echo '<button type="submit" name="convert_images" class="btn btn-primary btn-block" id="convert-images-button">Convert selected images</button>';
	echo '</div>';
	echo '</div>';
	echo '<div class="form-group row mt-4" style="justify-content: center">';
	echo '<div class="col-2">';
	echo get_pagination_links( $total_pages, $current_page );
	echo '</div>';
	echo '</div>';
	echo '</form>';
}
function get_pagination_links( $total_pages, $current_page ) {
	$pagination_links = paginate_links( array(
		'base'      => add_query_arg( 'paged', '%#%' ),
		'format'    => '',
		'prev_text' => __( '<i class="fas fa-arrow-left"></i>' ),
		'next_text' => __( '<i class="fas fa-arrow-right"></i>' ),
		'total'     => $total_pages,
		'current'   => $current_page,
	) );

	if ( $pagination_links ) {
		return '<nav aria-label="Page navigation example" class="mt-4 d-flex justify-content-center"><ul class="pagination justify-content-center">' . $pagination_links . '</ul></nav>';
	}

	return '';
}

function add_image_converter_menu_item() {
	add_menu_page( 'Image Converter Settings', 'Image Converter', 'manage_options', 'image-converter-settings', 'image_converter_settings_page', 'dashicons-format-image', 100 );
}
add_action( 'admin_menu', 'add_image_converter_menu_item' );

add_action( 'admin_enqueue_scripts', function() {
	global $pagenow;
	if ( is_admin() && $pagenow == 'admin.php' && $_GET['page'] == 'image-converter-settings' ) {
		wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), '4.5.3', true );
		wp_enqueue_script( 'plugin-script', plugin_dir_url( __FILE__ ) . 'js/plugin.js', array( 'jquery' ), '1.0', true );
	}

});



function enqueue_plugin_styles() {
	wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css', array(), '4.5.3' );
	wp_enqueue_style( 'font-awesome', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.1/css/all.min.css', array(), '5.15.1' );
	wp_enqueue_style( 'plugin-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );


}
add_action( 'admin_enqueue_scripts', 'enqueue_plugin_styles' );