<?php
/* 
    Plugin Name: Bg Nginx Cache Reset
    Plugin URI: https://bogaiskov.ru
    Description: Сброс кеш Nginx вручную и при сохранении постов 
    Version: 1.1
    Author: VBog
    Author URI: https://bogaiskov.ru 
	License:     GPL2
	GitHub Plugin URI: https://github.com/VBog/bg-nginx-cache/
*/

/*  Copyright 2020  Vadim Bogaiskov  (email: vadim.bogaiskov@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*****************************************************************************************

	Блок загрузки плагина 
	
******************************************************************************************/

// Запрет прямого запуска скрипта
if ( !defined('ABSPATH') ) {
	die( 'Sorry, you are not allowed to access this page directly.' ); 
}
define('BG_NGINX_CACHE_VERSION', '1.1');
define('BG_NGINX_CACHE_LOG', dirname(__FILE__ ).'/bg_nginx_cache.log');


// Сбросить кеш при сохранении поста 
add_action( 'save_post', 'bg_nginx_cache_reset' );
function bg_nginx_cache_reset ( $post_id ) {

	// Если это ревизия, то ничего не делаем
	if ( wp_is_post_revision( $post_id ) || get_post($post_id)->post_status != 'publish' ) return;

	// Сбрасываем кеш страницы home_url()
	$post_url = get_permalink( $post_id );
	reset_nginx_cache ($post_url);
	error_log(date ("Y-m-d H:i:s")." Reset cache of post ID=".$post_id." URL=".$post_url.PHP_EOL, 3, BG_NGINX_CACHE_LOG);
	
	// Сбрасываем кеш главной страницы 
	$post_url = trailingslashit(home_url());
	reset_nginx_cache ($post_url);
	error_log(date ("Y-m-d H:i:s")." Reset cache of home page URL=".$post_url.PHP_EOL, 3, BG_NGINX_CACHE_LOG);
	
	// Сбрасываем кеш рубрик
	foreach( get_the_category($post_id) as $category ){ 
		$post_url = get_category_link( $category->term_id );
		reset_nginx_cache ($post_url);
		error_log(date ("Y-m-d H:i:s")." Reset cache of category page URL=".$post_url.PHP_EOL, 3, BG_NGINX_CACHE_LOG);
	}
	
	// Сбрасываем кеш меток
	foreach( get_the_tags($post_id) as $tag ){ 
		$post_url = get_tag_link( $tag->term_id );
		reset_nginx_cache ($post_url);
		error_log(date ("Y-m-d H:i:s")." Reset cache of tag page URL=".$post_url.PHP_EOL, 3, BG_NGINX_CACHE_LOG);
	}
}

// Добавляем кнопку "Сбросить кеш Nginx" в админ панель
add_action( 'admin_bar_menu', 'bg_nginx_cache_reset_current_page', 100 );
function bg_nginx_cache_reset_current_page() {
    global $wp, $wp_admin_bar;
	if ( is_admin() || !is_super_admin() || !is_admin_bar_showing() ) return;
	
	$href = wp_nonce_url(plugin_dir_url( __FILE__ ).'reset_curent_page.php?url='.site_url( $wp->request ), 'bg_nginx_cache');
	
	$wp_admin_bar->add_menu (
		array ( 
			'id' => 'reset_nginx_cache',
			'title' => 'Сброс Nginx Cache',
		)
	);
	$wp_admin_bar->add_menu (
		array ( 
			'parent' => 'reset_nginx_cache', // параметр id из первой ссылки
			'id' => 'reset_current_page',
			'title' => 'Текущая страница',
			'href' => wp_nonce_url(plugin_dir_url( __FILE__ ).'reset_curent_page.php?url='.site_url( $wp->request ), 'bg_nginx_cache')
		)
	);
	$wp_admin_bar->add_menu (
		array ( 
			'parent' => 'reset_nginx_cache', // параметр id из первой ссылки
			'id' => 'reset_all',
			'title' => 'Весь сайт',
			'href' => wp_nonce_url(plugin_dir_url( __FILE__ ).'reset_curent_page.php?url=*', 'bg_nginx_cache')
		)
	);
}
 

/*****************************************************************************************
	
	Сброс Nginx кеш страницы
	
******************************************************************************************/
function reset_nginx_cache ($url) {
	// создание нового ресурса cURL
	$ch = curl_init();

	// установка URL и других необходимых параметров
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Update: 1'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	// загрузка страницы и выдача её браузеру
    if( ! $result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }

	// завершение сеанса и освобождение ресурсов
	curl_close($ch);
	
	return $result;
}
