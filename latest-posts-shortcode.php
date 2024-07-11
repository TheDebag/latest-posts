<?php
/**
 * Plugin Name: Latest Posts Shortcode
 * Description: Displays a list of the latest posts using a shortcode.
 * Version: 1.0
 * Author: Владимир Павленко
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once __DIR__ . '/vendor/autoload.php'; // Подключение автозагрузчика Composer

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Инициализация логгера
$logger = new Logger('latest-posts-logger');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/error.log', LogLevel::WARNING));

// Функция для шорткода
function latest_posts_shortcode($atts) {
    global $logger;

    // Получение атрибутов шорткода
    $atts = shortcode_atts(array('count' => '10'), $atts, 'latest_posts');
    $count = intval($atts['count']);

    // Запрос к базе данных для получения последних постов
    $args = array(
        'posts_per_page' => $count,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        $logger->warning('No posts found');
        return "No posts found";
    }

    $output = '<ul>';
    while ($query->have_posts()) {
        $query->the_post();
        $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }
    $output .= '</ul>';

    wp_reset_postdata();

    return $output;
}

add_shortcode('latest_posts', 'latest_posts_shortcode');

// Создание страницы настроек
function latest_posts_settings_menu() {
    add_options_page(
        'Latest Posts Settings',
        'Latest Posts',
        'manage_options',
        'latest-posts-settings',
        'latest_posts_settings_page'
    );
}

add_action('admin_menu', 'latest_posts_settings_menu');

// Страница настроек
function latest_posts_settings_page() {
    ?>
    <div class="wrap">
        <h1>Latest Posts Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('latest-posts-settings');
            do_settings_sections('latest-posts-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Регистрация настроек
function latest_posts_register_settings() {
    register_setting('latest-posts-settings', 'latest_posts_count');
    add_settings_section(
        'latest-posts-settings-section',
        'Settings',
        'latest_posts_settings_section_callback',
        'latest-posts-settings'
    );
    add_settings_field(
        'latest_posts_count',
        'Number of Posts',
        'latest_posts_count_callback',
        'latest-posts-settings',
        'latest-posts-settings-section'
    );
}

add_action('admin_init', 'latest_posts_register_settings');

function latest_posts_settings_section_callback() {
    echo '<p>Enter the number of latest posts to display.</p>';
}

function latest_posts_count_callback() {
    $count = get_option('latest_posts_count', 10);
    echo "<input type='number' name='latest_posts_count' value='$count' />";
}
