<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<?php

/**
 * Базовый Класс для плагина 
 */
class VKPOSTERBASE {

    const NAME_PLUGIN = 'VK Poster Group';
    const PATCH_PLUGIN = 'vk-poster-group'; //Директория плагина
    const URL_ADMIN_MENU_PLUGIN = 'vkposter-page'; //Адрес в админке
    const NAME_TITLE_PLUGIN_PAGE = 'VK Poster Group'; // Название титульной страницы плагина
    const NAME_MENU_OPTIONS_PAGE = 'VKPosterGP'; // Название пунтка меню
    const NAME_SERVIC_ORIGINAL_TEXT = 'VK Poster Group plugin';
    const URL_PLUGIN_CONTROL = 'options-general.php?page=vkposter-page'; //Адрес админки плагина полный
    const URL_VK_DEVCREATE = 'https://vk.com/editapp?act=create'; //ССылка на создание придложения

    /**
     * URL страницы подменю
     */
    const URL_SUB_MENU = 'vkposter-page';

    /**
     * Путь до страницы опций плагина HTML
     */
    const OPTIONS_NAME_PAGE = 'page/option1.php';

    /**
     * Констурктора класса
     */
    public function __construct() {
        $this->addOptions();
        $this->addActios();
    }

    /**
     * Опции вызываемые деактивацией
     */
    public function deactivationPlugin() {
        delete_option('vkposter_id'); //ID группы или пользователя
        delete_option('vkposter_friends_only'); //Доступность записи, 0 - всем
        delete_option('vkposter_from_group'); //От чьего имени публиковать
        delete_option('vkposter_signed');
        delete_option('vkposter_counttext');
        delete_option('vkposter_onoff');
        delete_option('vkposter_jornal');
        delete_option('vkposter_idsoft'); //ID приложения
        delete_option('vkposter_token'); //Токен приложения
        delete_option('vkposter_userid'); //ID пользователя, создателя группы
        delete_option('vkposter_posttype'); //Типы выбранных записей
        delete_option('vkposter_proxy'); //Прокси
        delete_option('vkposter_proxy_userpaswd'); //Прокси юзер и пароль
        delete_option('vkposter_prooptions'); //Про опции
    }

    /**
     * Активация фишек
     */
    public function addActios() {
        add_action('admin_menu', array($this, 'adminOptions'));
        // add_action('wp_enqueue_scripts', array($this, 'syleScriptHomepage')); //стили на фронтэнде
        add_filter('plugin_action_links', array($this, 'pluginLinkSetting'), 10, 2); //Настройка на странице плагинов
        //add_action('admin_init', array($this, 'provNonce'));
        add_action('add_meta_boxes', array($this, 'settingMetabox')); //Добавляем метабокс в пост
//        add_action('save_post', array($this, 'metaboxSavePost')); //Сохранение галки в постах
//        add_action('publish_post', array($this, 'metaboxSavePost')); //Сохранение галки в постах
//        add_action('publish_page', array($this, 'metaboxSavePost')); //Сохранение галки в постах
//        add_action('publish_post', array($this, 'metaboxSentVK')); //Отправка значений На стену ВК
//        add_action('publish_page', array($this, 'metaboxSentVK')); //Отправка значений На стену ВК
        $array_posts = get_option('vkposter_posttype'); //Типы постов
        foreach ($array_posts as $k => $v) {
            add_action('save_' . $v, array($this, 'metaboxSavePost'), 9); //Сохранение галки в постах
            add_action('publish_' . $v, array($this, 'metaboxSavePost'), 9); //Сохранение галки в постах
            add_action('publish_' . $v, array($this, 'metaboxSentVK'), 11); //Отправка значений На стену ВК
            add_action('publish_future_' . $v, array($this, 'futureSentVk'), 10, 2); //Публикация отложенной записи
        }

        //фильтры для добавления ссылки  отправки записи
        add_filter('post_row_actions', array($this, 'vkp_action_row'), 10, 2);
        add_filter('page_row_actions', array($this, 'vkp_action_row'), 10, 2);

        //Ссылка на отправку записи на стену, появляется на страницах выбранных посттайпов
        //Отправка записи по клику на ссылку. Действие происходит при загрузке страницы посттайпа.
        add_action('admin_head-edit.php', array($this, 'admin_head_post_listing'));
    }

    /**
     * Отправка запланированной записи
     * @param type $post_id
     * @param type $post
     * @return type
     */
    public function futureSentVk($post_id, $post) {
//        if(!DOING_CRON) {
//            return $post_id;
//        }
//        if($post->post_status!=='future') {
//             return $post_id;
//        }
//        $date_create = $post->post_date_gmt; //Дата создания записи
//        $date_modificed = $post->post_modified_gmt; //Дата изменения записи
//        if($date_create<$date_modificed) {
//            return $post_id;
//           
//        } 
        $postData = get_post($post_id);
        $title = $postData->post_title;
        $vkposter_onoff = get_option('vkposter_onoff');
        $vkpunk = new VKPOSTERFUNCTION;
        $status_post = $postData->post_status; //Статус поста
        if ($status_post == 'draft' || $status_post == 'private' || $status_post == 'trash') {
            return $post_id;
        }
        if ($vkposter_onoff == 'on') {
            $status_sent = $vkpunk->setVkWall($post_id); //Отправка текста
            $vkpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
        }
        return $post_id;
    }

    /**
     * 
     */
    public function admin_head_post_listing() {

        global $post;
        if (in_array($post->post_type, get_option('vkposter_posttype')) AND isset($_GET['vkp_repost'])) {
            $post_id = $_GET['vkp_repost'];
            $vkpunk = new VKPOSTERFUNCTION;
            $status_sent = $vkpunk->setVkWall($post_id); //Отправка текста
            $status_arr = json_decode($status_sent);
            //Удаляем из строки запроса номер записи
            $parts = parse_url($_SERVER["REQUEST_URI"]);
            $queryParams = array();
            parse_str($parts['query'], $queryParams);
            unset($queryParams['vkp_repost']);
            $queryString = http_build_query($queryParams);
            $_SERVER["REQUEST_URI"] = $parts['path'] . '?' . $queryString;
            //--Конец обработки строки запроса
            $vkpunk->logJornal($post_id, $post->post_title, $status_sent); //Логируем результаты

            if (VKPOSTERFUNCTION::compareOldPHPVer('5.3.0', '<') == FALSE) { //PHP>5.3
                if (!$status_arr->{'error'}) {
                    add_action('all_admin_notices', function() {
                        echo '<div class="notice notice-success"><p>Запись #' . $_GET['vkp_repost'] . ' отправлена вконтакте!</p></div>';
                    });
                } else {
                    add_action('all_admin_notices', function() {

                        echo '<div class="notice notice-error"><p>Запись #' . $_GET['vkp_repost'] . ' не отправлена вконтакте! Подробнее см. в <a href="' . get_admin_url() . VKPOSTERBASE::URL_PLUGIN_CONTROL . '&tab=jornal">журнале</a></p></div>';
                    });
                }
            }
        }
    }

    /**
     * Фильтр для добавления ссылки на отправку записи в листинг постов/страниц
     * @param array $actions Массив ссылок текущий
     * @param object $post Информаця о текущем посте
     * @return array Массив ссылок для листинга + добавленная ссылка
     * 
     */
    public function vkp_action_row($actions, $post) {
        //Для указанных в настройках посттайпов добавляем ссылку
        if (in_array($post->post_type, get_option('vkposter_posttype'))) {
            //Добавляем в строку запроса номер записи, которую надо репостить.
            $parts = parse_url($_SERVER["REQUEST_URI"]);
            $queryParams = array();
            parse_str($parts['query'], $queryParams);
            $queryParams['vkp_repost'] = $post->ID;
            $queryString = http_build_query($queryParams);
            $url = $parts['path'] . '?' . $queryString;

            //--Конец обработки строки запроса
            //Добавляем ссылку
            $actions['vkp-post'] = '<a href="http://' . $_SERVER["HTTP_HOST"] . $url . '">Отправить вконтакте</a>';
        }
        return $actions;
    }

    /**
     * Добавление опций в базу данных
     */
    public function addOptions() {
        add_option('vkposter_id', '-'); //ID группы или пользователя
        add_option('vkposter_friends_only', '0'); //Доступность записи, 0 - всем
        add_option('vkposter_from_group', '1'); //От чьего имени публиковать
        add_option('vkposter_signed', '1');
        add_option('vkposter_counttext', '40');
        add_option('vkposter_onoff');
        add_option('vkposter_jornal', array());
        add_option('vkposter_idsoft'); //ID приложения
        add_option('vkposter_token'); //Токен приложения
        add_option('vkposter_userid'); //ID пользователя, создателя группы
        add_option('vkposter_posttype', array('post' => 'post')); //Типы выбранных записей
        add_option('vkposter_proxy'); //Прокси
        add_option('vkposter_proxy_user'); //Прокси юзер
        add_option('vkposter_proxy_userpaswd');  //Прокси юзер и пароль
        add_option('vkposter_prooptions', array());  //ПРО настройки
    }

    /**
     * Добавляет пункт настроек на странице активированных плагинов
     */
    public function pluginLinkSetting($links, $file) {
        $this_plugin = self::PATCH_PLUGIN . '/index-vkp.php';
        if ($file == $this_plugin) {
            $settings_link1 = '<a href="' . self::URL_PLUGIN_CONTROL . '">' . __("Settings", "default") . '</a>';
            array_unshift($links, $settings_link1);
        }
        return $links;
    }

    /**
     * Параметры активируемого меню
     */
    public function adminOptions() {
        $page_option = add_options_page(self::NAME_TITLE_PLUGIN_PAGE, self::NAME_MENU_OPTIONS_PAGE, 8, self::URL_ADMIN_MENU_PLUGIN, array($this, 'showSettingPage'));
        add_action('admin_print_styles-' . $page_option, array($this, 'syleScriptAddpage')); //загружаем стили только для страницы плагина
        add_action('admin_print_scripts-' . $page_option, array($this, 'scriptAddpage')); //Скрипты админки
    }

    /**
     * Стили, скрипты Админка
     */
    public function syleScriptAddpage() {

        wp_register_style('vkposter_bootstrapcss1', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'bootstrap/css/bootstrap.css');
        wp_enqueue_style('vkposter_bootstrapcss1');
        wp_register_style('vkposter_adminpagecss', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'css/adminpag.css');
        wp_enqueue_style('vkposter_adminpagecss');
        wp_register_style('vkposter_lightboxcss', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'lib/lightbox/ekko-lightbox.min.css');
        wp_enqueue_style('vkposter_lightboxcss');
    }

    /**
     * Сприпты
     */
    public function scriptAddpage() {
        wp_register_script('vkposter_bootstrapjs1', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'bootstrap/js/bootstrap.js');
        wp_enqueue_script('vkposter_bootstrapjs1');
        wp_register_script('vkposter_lightbox', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'lib/lightbox/ekko-lightbox.min.js');
        wp_enqueue_script('vkposter_lightbox');
        //wp_register_script('vkposter_admin', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'js/admin_order.js');
        //wp_enqueue_script('vkposter_admin');
    }

    /**
     * Страница меню
     */
    public function showSettingPage() {
        include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/' . self::OPTIONS_NAME_PAGE;
    }

    /**
     * Метабокс в записи
     */
    public function settingMetabox() {
        $array_posts = get_option('vkposter_posttype'); //Типы постов
        foreach ($array_posts as $k => $v) { //Появляется только там где выбрал пользователь
            add_meta_box('vkposter-metabox', self::NAME_SERVIC_ORIGINAL_TEXT, array($this, 'metaboxHtml'), "$v", 'side', 'high');
        }
    }

    /**
     * Отрисовка МетаБокса
     */
    public function metaboxHtml($post) {
        $vkposter_onoff = get_option('vkposter_onoff');
        // Используем nonce для верификации
        wp_nonce_field(plugin_basename(__FILE__), 'vkposter_noncename');
        // Поля формы для введения данных
        if (empty($vkposter_onoff)) {
            if (get_post_meta($post->ID, '_vkposter_meta_value_key', true) == 'on') {
                $cheked = 'checked';
            } else {
                $cheked = '';
            }
        } elseif (!empty($vkposter_onoff)) {
            $cheked = 'checked';
        }
        echo '<input type="checkbox" name="vkposter_new_field" ' . $cheked . '/>';
        echo '<span class="description">Добавлять текст на стену VK при публикации?</span>';
        echo '<br><input type="radio" name="vkposter_new_field_radio" checked value="1">При публикации</>';
        echo '<br><input type="radio" name="vkposter_new_field_radio" value="2">При обновлении</>';
    }

    /**
     * Сохранение данных Метабокса при сохрание записи
     */
    public function metaboxSavePost($post_id) {

        // проверяем nonce нашей страницы, потому что save_post может быть вызван с другого места.
        if (!wp_verify_nonce($_POST['vkposter_noncename'], plugin_basename(__FILE__))) {
            return $post_id;
        }

        // проверяем, если это автосохранение ничего не делаем с данными нашей формы.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // проверяем разрешено ли пользователю указывать эти данные
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        //Убедимся что поле установлено.
        //if (!isset($_POST['vkposter_new_field']))
        //    return;

        $data1 = $_POST['vkposter_new_field'];
        //Обновление данных в базе даннхы
        update_post_meta($post_id, '_vkposter_meta_value_key', $data1);
    }

    /**
     * Отправка данных На стену ВК при публикации записи
     */
    public function metaboxSentVK($post_id) {


        $postData = get_post($post_id);
        $title = $postData->post_title;

        $vkposter_onoff = get_option('vkposter_onoff');
        $vkpunk = new VKPOSTERFUNCTION;
        $status_post = $postData->post_status; //Статус поста
        $date_create = $postData->post_date_gmt; //Дата создания записи
        $date_modificed = $postData->post_modified_gmt; //Дата изменения записи
        $radio_chek = $_POST['vkposter_new_field_radio']; // получаем значение РадиоБутон

        if (!wp_verify_nonce($_POST['vkposter_noncename'], plugin_basename(__FILE__))) {


            return $post_id;
        }

        // проверяем, если это автосохранение ничего не делаем с данными нашей формы.

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {

            return $post_id;
        }

        // проверяем разрешено ли пользователю указывать эти данные
        if (!current_user_can('edit_post', $post_id)) {

            return $post_id;
        }

        if ($status_post == 'draft' OR $status_post == 'private' OR $status_post == 'trash') {

            return $post_id;
        }


        $chek = get_post_meta($post_id, '_vkposter_meta_value_key', true);

        if ($vkposter_onoff == 'on') {
            if ($date_create == $date_modificed) {
                $status_sent = $vkpunk->setVkWall($post_id); //Отправка текста
                $vkpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
            } elseif ($date_create !== $date_modificed and $radio_chek !== '1') {
                $status_sent = $vkpunk->setVkWall($post_id); //Отправка текста
                $vkpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
            }
        } elseif ($chek == 'on') {

            if ($date_create == $date_modificed) {
                $status_sent = $vkpunk->setVkWall($post_id); //Отправка текста
                $vkpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
            } elseif ($date_create !== $date_modificed and $radio_chek !== '1') {
                $status_sent = $vkpunk->setVkWall($post_id); //Отправка текста
                $vkpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
            }
        } else {
            return $post_id;
        }

        return;
    }

    /**
     * Активная вкладка в админпанели плагина
     * @return string css Класс для активной вкладки
     */
    static public function adminActiveTab($tab_name = null, $tab = null) {

        if (isset($_GET['tab']) && !$tab)
            $tab = $_GET['tab'];
        else
            $tab = 'general';

        $output = '';
        if (isset($tab_name) && $tab_name) {
            if ($tab_name == $tab)
                $output = ' nav-tab-active';
        }
        echo $output;
    }

    /**
     * Подключает нужную страницу исходя из вкладки на страницы настроек плагина
     * @result include_once tab{номер вкладки}-option1.php
     */
    static public function tabViwer() {
        $tab = $_GET['tab'];
        switch ($tab) {
            case 'jornal':
                include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab2-option1.php';
                break;
            case 'help':
                include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab3-option1.php';
                break;
            case 'about':
                include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab4-option1.php';
                break;
            case 'progeneral':
                include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab5-option1.php';
                break;
            default :
                include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab1-option1.php';
        }
    }

}
