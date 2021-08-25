<?php
namespace Coderun\VkPoster;

/**
 * Базовый Класс для плагина
 */
class CorePlugin {
    
    const NAME_PLUGIN = 'VK Poster Group';
    const PATCH_PLUGIN = 'vk-poster-group'; //Директория плагина
    const URL_ADMIN_MENU_PLUGIN = 'vkposter-page'; //Адрес в админке
    const NAME_TITLE_PLUGIN_PAGE = 'VK Poster Group'; // Название титульной страницы плагина
    const NAME_MENU_OPTIONS_PAGE = 'VKPosterGP'; // Название пунтка меню
    const NAME_SERVIC_ORIGINAL_TEXT = 'VK Poster Group plugin';
    const URL_PLUGIN_CONTROL = 'options-general.php?page=vkposter-page'; //Адрес админки плагина полный
    const URL_VK_DEVCREATE = 'https://vk.com/editapp?act=create'; //ССылка на создание придложения
    const OPTIONS_NAME_PAGE = 'page/tab-list.php'; //страница опций плагина
    const META_BOX_KEY = '_vkposter_meta_value_key';
    /**
     * Префикс для скриптов,стилей, названий опций
     */
    const PREF_PLG = 'coderun_vk_poster_group';
    const PREF_PLG_PRO = 'coderun_vk_poster_group_pro';
    
    /**
     * URL страницы подменю
     */
    const URL_SUB_MENU = 'vkposter-page';
    
    protected static $_instance = null;
    
    protected $options = null;
    
    protected $pluginUri = '';
    
    /**
     * Массив вкладок для страницы настроек
     *
     * @var type
     */
    protected $plugin_admin_pages = array(
        1 => [
            'name' => ' Lite-Настройки', //Имя вкладки
            'path' => '/page/tab1.php', //Путь до подключаемого файла страницы настроек. Относительно папки плагина
            'icon' => 'glyphicon glyphicon-cog', //Иконка для вкладки - класс иконки
        ],
        2 =>  [
            'name' => ' PRO-Настройки',
            'path' => '/page/tab5.php',
            'icon' => 'glyphicon glyphicon-asterisk',
        ],
        3 => [
            'name' => ' Журнал',
            'path' => '/page/tab2.php',
            'icon' => 'glyphicon glyphicon-wrench',
        ],
        4 => [
            'name' => ' Справка',
            'path' => '/page/tab3.php',
            'icon' => 'glyphicon glyphicon-list',
        ],
        5 => [
            'name' => ' Автор',
            'path' => '/page/tab4.php',
            'icon' => 'glyphicon-thumbs-up',
        ]
    
    );
    
    public function addEvents()
    {
        add_action( 'transition_post_status', [$this, 'transition_post_status'], 10, 3 );
        if (is_admin()) {
            register_setting(sprintf('%s_options', self::PREF_PLG), self::PREF_PLG, [
                'type'              => 'array',
                'group'             => sprintf('%s_options', self::PREF_PLG),
                'description'       => '',
                'sanitize_callback' => function($forms) {
                    if (!empty($forms['vkposter_token']))    {
                        $forms['vkposter_token'] = Services::getInstance()->getTokenUrl($forms['vkposter_token']);
                    }
                    return $forms;
                },
                'show_in_rest'      => false,
                'default' => [],
            ]);
            register_setting(sprintf('%s_options', self::PREF_PLG_PRO), self::PREF_PLG_PRO, [
                'type'              => 'array',
                'group'             => sprintf('%s_options', self::PREF_PLG_PRO),
                'description'       => '',
                'sanitize_callback' => function($forms) {
                    return $forms;
                },
                'show_in_rest'      => false,
                'default' => [],
            ]);
        }
    }
    
    /**
     * Настройки плагина
     * @param type $name
     */
    public function getOptions($name = null)
    {
        $defaultTypes = [
            'vkposter_prooptions' => [],
            'vkposter_posttype' => [],
            'vkposter_jornal' => [],
            'text_source' => '',
        ];
        
        $values = \get_option(self::PREF_PLG, []);
        $valuesPro = \get_option(self::PREF_PLG_PRO, []);
        if (count($values) === 0) {
            add_option(self::PREF_PLG, [], '', false);
        }
        if (count($valuesPro) === 0) {
            add_option(self::PREF_PLG_PRO, [], '', false);
        }
        $this->options = $values + $valuesPro;
        if ($name !== null) {
            if (isset($this->options[$name])) {
                return $this->options[$name];
            }
            if (array_key_exists($name, $defaultTypes)) {
                return $defaultTypes[$name];
            }
            
            return '';
        }
        
        return $this->options;
    }
    
    public function updateOptions($name, $value)
    {
        $options = \get_option(self::PREF_PLG, []);
        if (isset($options[$name]) || $name === 'vkposter_jornal') {
            $options[$name] = $value;
            update_option(self::PREF_PLG, $options);
        }
        $options = \get_option(self::PREF_PLG_PRO, []);
        if (isset($options[$name])) {
            $options[$name] = $value;
            update_option(self::PREF_PLG_PRO, $options);
        }
    }
    
    /**
     * Активация необходимых зависимостей
     */
    public function addAdminActions()
    {
        add_action('admin_menu', array($this, 'adminOptions'));
        add_filter('plugin_action_links', array($this, 'pluginLinkSetting'), 10, 2); //Настройка на странице плагинов
        add_action('add_meta_boxes', array($this, 'settingMetaBox')); //Добавляем метабокс в пост
        
        
        //фильтры для добавления ссылки  отправки записи
        add_filter('post_row_actions', array($this, 'vkp_action_row'), 10, 2);
        add_filter('page_row_actions', array($this, 'vkp_action_row'), 10, 2);
        
        //Ссылка на отправку записи на стену, появляется на страницах выбранных посттайпов
        //Отправка записи по клику на ссылку. Действие происходит при загрузке страницы посттайпа.
        add_action('admin_head-edit.php', array($this, 'admin_head_post_listing'));
    }
    
    /**
     * Используется при автоматической отправки записей
     * Отправка записей на событии изменения статуса поста
     * @param          $new_status
     * @param          $old_status
     * @param \WP_Post $post
     */
    public function transition_post_status($new_status, $old_status, \WP_Post $post)
    {
        $postType = $post->post_type;
        if (\in_array($postType, $this->getOptions('vkposter_posttype'), true) === false) {
            return;
        }
        if ($new_status !== 'publish' || $old_status == 'publish') {
            return;
        }
        if ($new_status === $old_status) {
            return;
        }
        /** Включена автоматическая отправка записей */
        if (empty($this->getOptions('vkposter_onoff'))) {
            return;
        }
        $status_sent = Services::getInstance()->setVkWall($post->ID);
        Services::getInstance()->logJornal($post->ID, $post->post_title, $status_sent);
        $status_arr = \json_decode($status_sent);
        if (!empty($status_arr->response->post_id)) {
            Services::getInstance()->setMetaPostUrlVK($post->ID, $status_arr->response->post_id);
        }
        
    }
    
    
    /**
     *
     */
    public function admin_head_post_listing() {
        
        global $post;
        if (in_array($post->post_type, $this->getOptions('vkposter_posttype')) AND isset($_GET['vkp_repost'])) {
            $post_id = $_GET['vkp_repost'];
            $status_sent = Services::getInstance()->setVkWall($post_id); //Отправка текста
            $status_arr = \json_decode($status_sent);
            //Удаляем из строки запроса номер записи
            $parts = \parse_url($_SERVER["REQUEST_URI"]);
            $queryParams = array();
            parse_str($parts['query'], $queryParams);
            unset($queryParams['vkp_repost']);
            $queryString = http_build_query($queryParams);
            $_SERVER["REQUEST_URI"] = $parts['path'] . '?' . $queryString;
            //--Конец обработки строки запроса
            Services::getInstance()->logJornal($post_id, $post->post_title, $status_sent); //Логируем результаты
            
            if (!$status_arr->{'error'}) {
                add_action('all_admin_notices', function() {
                    echo '<div class="notice notice-success"><p>Запись #' . $_GET['vkp_repost'] . ' отправлена вконтакте!</p></div>';
                });
            } else {
                add_action('all_admin_notices', function() {
                    echo '<div class="notice notice-error"><p>Запись #' . $_GET['vkp_repost'] . ' не отправлена вконтакте! Подробнее см. в <a href="' . get_admin_url() . self::URL_PLUGIN_CONTROL . '&tab=jornal">журнале</a></p></div>';
                });
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
        if (!empty($this->getOptions('vkposter_posttype')) && \in_array($post->post_type, $this->getOptions('vkposter_posttype'))) {
            //Добавляем в строку запроса номер записи, которую надо репостить.
            $parts = parse_url($_SERVER["REQUEST_URI"]);
            $queryParams = array();
            parse_str($parts['query'], $queryParams);
            $queryParams['vkp_repost'] = $post->ID;
            $queryString = http_build_query($queryParams);
            $url = $parts['path'] . '?' . $queryString;
            $actions['vkp-post'] = '<a href="http://' . $_SERVER["HTTP_HOST"] . $url . '">Отправить вконтакте</a>';
        }
        return $actions;
    }
    
    
    /**
     * Добавляет пункт настроек на странице активированных плагинов
     */
    public function pluginLinkSetting($links, $file) {
        $this_plugin = self::PATCH_PLUGIN . '/index-vkp.php';
        if ($file === $this_plugin) {
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
        add_action('admin_print_styles-' . $page_option, array($this, 'adminStyles')); //загружаем стили только для страницы плагина
        add_action('admin_print_scripts-' . $page_option, array($this, 'adminScripts')); //Скрипты админки
    }
    
    /**
     * Стили, скрипты Админка
     */
    public function adminStyles() {
        wp_register_style('vkposter_adminpagecss', plugins_url() . '/' . self::PATCH_PLUGIN . '/' . 'css/adminpag.css');
        wp_enqueue_style('vkposter_adminpagecss');
    }
    
    /**
     * Сприпты
     */
    public function adminScripts() {
    
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
    public function settingMetaBox() {
        $array_posts = $this->getOptions('vkposter_posttype'); //Типы постов
        foreach ($array_posts as $k => $v) { //Появляется только там где выбрал пользователь
            add_meta_box('vkposter-metabox', 'Отправка в VK', array($this, 'metaboxHtml'), "$v", 'side', 'high');
        }
    }
    
    /**
     * Отрисовка МетаБокса
     */
    public function metaboxHtml(\WP_Post $post) {
        $urlVk = get_post_meta($post->ID, Services::NAME_META_URL_VK, true);
        if (!empty($this->getOptions('vkposter_onoff'))) {
            echo sprintf('<div class="description">Включена автоматическая отправка записей при публикации</div>');
        } else {
            $buttonDisabled = 'disabled="disabled"';
            $classDisabled = 'disabled';
            $textDisabled = 'Запись должна быть опубликована';
            if ($post->post_status === 'publish') {
                $buttonDisabled = '';
                $classDisabled = '';
                $textDisabled = '';
            }
            
            echo sprintf('<div class="description"><p>Ручной режим отправки записи</p></div>');
            echo sprintf('<div><input %s type="button" data-post_id="%d" name="coderun_send_post_to_vk" class="button-primary %s" value="Отправить в ВК" /> </div>', $buttonDisabled,$post->ID, $classDisabled);
            echo sprintf('<div id="coderun_status_send_to_vk">%s</div>', $textDisabled);
            echo sprintf('<script>
                           jQuery(\'[name="coderun_send_post_to_vk"]\').click(function(){
                           var self = this;
                           if (jQuery(self).attr(\'disabled\')) {
                                return;
                           }
                            var postId = jQuery(self).attr(\'data-post_id\');
                            jQuery(self).attr(\'disabled\', \'disabled\');
                            jQuery.ajax({
                                url: window.ajaxurl,
                                type: "POST",
                                data: {
                                    action: \'coderun_send_post_to_vk\',
                                    postId: postId
                                },
                            }).done(function (response) {
                              if (response[\'success\']) {
                                jQuery(\'#coderun_status_send_to_vk\').html(response.data);
                              } else {
                               jQuery(\'#coderun_status_send_to_vk\').html(response.data);
                              }
                            }).fail(function (response) {
                   
                            }).complete(function () {
                             jQuery(self).attr(\'disabled\', false);
                            })
                        })
                </script>');
        }
        if ($urlVk) {
            echo sprintf('<div id="coderun_url_vk">Ссылка: <a href="%s" target="_blank">%s</a></div>', $urlVk, $urlVk);
        }
    }
    
    
    /**
     * Активная вкладка в админпанели плагина
     * @return string css Класс для активной вкладки
     */
    public function active_tab_page($tab_name = 1) {
        
        $tab = $this->get_tab_numger();
        $tab_name = \intval($tab_name);
        $output = '';
        if ($tab_name === $tab) {
            $output = ' nav-tab-active';
        }
        echo $output;
    }
    
    public function get_url_admin_page() {
        return self::URL_ADMIN_MENU_PLUGIN;
    }
    
    /**
     * Подключает нужную страницу исходя из вкладки на страницы настроек плагина
     * @result include_once tab{номер вкладки}.php
     */
    public function view_tab_page() {
        $tab = $this->get_tab_numger();
        if (\file_exists(WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . $this->plugin_admin_pages[$tab]['path'])) {
            include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . $this->plugin_admin_pages[$tab]['path'];
        } else {
            echo '<br><p>Страница ещё не создана...</p>';
        }
    }
    
    protected function get_tab_numger() {
        $result = 1;
        $tab = 'null_1';
        if (isset($_GET['tab'])) {
            $tab = $_GET['tab'];
        }
        $params_tab = explode('_', $tab);
        if (isset($params_tab[1])) {
            $result = intval($params_tab[1]);
        }
        
        return $result;
    }
    
    protected function generate_num_tab_uri($num) {
        return substr(md5(self::NAME_PLUGIN), 1, 5) . '_' . $num;
    }
    /**
     * Дописывает uri и возвращает массив настроек вкладок
     * @return type
     */
    public function get_plugin_admin_pages() {
        foreach ($this->plugin_admin_pages as $num => $tab) {
            $this->plugin_admin_pages[$num]['uri'] = $this->generate_num_tab_uri($num);
        }
        return $this->plugin_admin_pages;
    }
    
    /**
     * Singletone
     * @return self
     */
    public static function getInstance() {
        
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Get pluginUri
     *
     * @return string
     */
    public function getPluginUri()
    {
        return $this->pluginUri;
    }
    
    /**
     * Set pluginUri
     *
     * @param string $pluginUri
     *
     * @return CorePlugin
     */
    public function setPluginUri(string $pluginUri)
    {
        $this->pluginUri = $pluginUri;
        return $this;
    }
    
    
    
}
