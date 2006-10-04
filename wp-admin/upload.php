<?php
require_once('admin.php');

@header('Content-type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));

wp_reset_vars(array('action', 'tab', 'from_tab', 'style', 'post_id', 'ID', 'paged', 'post_title', 'post_content', 'delete'));

require_once('upload-functions.php');
if ( !$tab )
	$tab = 'browse-all';

do_action( "upload_files_$tab" );

add_action( 'admin_head', 'wp_upload_admin_head' );

$pid = 0;
if ( $post_id < 0 )
	$pid = $post_id;
elseif ( get_post( $post_id ) )
	$pid = $post_id;
$wp_upload_tabs = array();
$all_atts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment'");
$post_atts = 0;

if ( $pid ) {
	// 0 => tab display name, 1 => required cap, 2 => function that produces tab content, 3 => total number objects OR array(total, objects per page), 4 => add_query_args
	$wp_upload_tabs['upload'] = array(__('Upload'), 'upload_files', 'wp_upload_tab_upload', 0);
	if ( $all_atts && $post_atts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = '$post_id'") )
		$wp_upload_tabs['browse'] = array(__('Browse'), 'upload_files', "wp_upload_tab_browse", $action ? 0 : $post_atts);
	if ( $post_atts < $all_atts )
		$wp_upload_tabs['browse-all'] = array(__('Browse All'), 'upload_files', 'wp_upload_tab_browse', $action ? 0 : $all_atts);
} else
	$wp_upload_tabs['browse-all'] = array(__('Browse All'), 'upload_files', 'wp_upload_tab_browse', $action ? 0 : $all_atts);

	$wp_upload_tabs = array_merge($wp_upload_tabs, apply_filters( 'wp_upload_tabs', array() ));

if ( !function_exists($wp_upload_tabs[$tab][2]) ) {
	$to_tab = isset($wp_upload_tabs['upload']) ? 'upload' : 'browse-all';
	wp_redirect( add_query_arg( 'tab', $to_tab ) );
	exit;
}

foreach ( $wp_upload_tabs as $t => $tab_array ) {
	if ( !current_user_can( $tab_array[1] ) ) {
		unset($wp_upload_tabs[$t]);
		if ( $tab == $t )
			wp_die(__("You are not allowed to be here"));
	}
}

include_once('admin-header.php');

echo "<ul id='upload-menu'>\n";
foreach ( $wp_upload_tabs as $t => $tab_array ) { // We've already done the current_user_can check
	$class = 'upload-tab';
	$href = add_query_arg( array('tab' => $t, 'ID' => '', 'action' => '', 'paged' => '') );
	if ( isset($tab_array[4]) && is_array($tab_array[4]) )
		add_query_arg( $tab_array[4], $href );
	$_href = wp_specialchars( $href, 1 );
	$page_links = '';
	if ( $tab == $t ) {
		$class .= ' current';
		if ( $tab_array[3] ) {
			if ( is_array($tab_array[3]) ) {
				$total = $tab_array[3][0];
				$per = $tab_array[3][1];
			} else {
				$total = $tab_array[3];
				$per = 10;
			}
			$page_links = paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'total' => ceil($total / $per),
				'current' => $paged ? $paged : 1,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;'
			));
			if ( $page_links )
				$page_links = "<span id='current-tab-nav'>: $page_links</span>";
		}
	}

	echo "\t<li class='$class left'><div><a href='$_href' title='{$tab_array[0]}'>{$tab_array[0]}</a>$page_links</div></li>\n";
}
unset($t, $tab_array, $href, $_href, $page_links, $total, $per, $class);
echo "</ul>\n\n";

echo "<div id='upload-content'>\n";

call_user_func( $wp_upload_tabs[$tab][2] );

echo "</div>\n";

include_once('admin-footer.php');
?>