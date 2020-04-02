<?php
/*
 * Plugin Name: Поиск дублей заголовков
 * Description: Плагин для поиска и удаления дублирующихся заголовков h2 и h3
 * Author:      SVteam
 * Version:     1.0
 */


add_action('admin_menu', 'createLinkOnMainMenuDouble');


function createLinkOnMainMenuDouble()
{
    add_menu_page(
        'Поиск дублей заголовков',
        'Поиск дублей заголовков',
        'edit_others_posts',
        '/wp_delete_double/includes/index.php',
        null,
        'dashicons-admin-page'
    );

    add_submenu_page(
        null,
        'Перемешать',
        'Перемешать параграфы',
        'edit_others_posts',
        '/wp_delete_double/includes/rand.php'
    );

    add_submenu_page(
        null,
        'Перемешать',
        'Перемешать параграфы',
        'edit_others_posts',
        '/wp_delete_double/includes/search.php'
    );
}

