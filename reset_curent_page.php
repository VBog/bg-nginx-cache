<?php
header("Content-type: text/html; charset: UTF-8");
require_once(dirname(dirname(dirname(__DIR__))).'/wp-load.php');

if( !wp_verify_nonce( $_GET['_wpnonce'], 'bg_nginx_cache' ) ) {
	die( '<p>Sorry, you are not allowed to access this page.</p>' );
}

// Сброс кеш текущей страницы 
$post_url = $_GET['url'];

reset_nginx_cache ($post_url);
error_log(date ("Y-m-d H:i:s")." Reset cache of current page URL=".$post_url.PHP_EOL, 3, BG_NGINX_CACHE_LOG);
wp_redirect( $post_url );
exit();