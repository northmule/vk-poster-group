<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<?php

/**
 * Класс с функционалом и обработками
 */
class VKPOSTERFUNCTION {

    const METHOD_URL_VK = 'https://api.vk.com/method/wall.post?'; //Метод постинга сообщений
    const METHOD_URL_VKIMAGE = 'https://api.vk.com/method/photos.getWallUploadServer?'; //Метод получения сервера загрузок изображения
    const METHOD_URL_VKIMAGE_SAVE = 'https://api.vk.com/method/photos.saveWallPhoto?'; //Загружает изображение в группу

    /**
     *
     * @var string Прокси сервер для CURL IP:порт
     */

    var $proxy;

    /**
     *
     * @var string Прокси сервер для CURL Пользователь:пароль
     */
    var $proxy_userpaswd;

    protected $version_api='5.73';

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->proxy = get_option('vkposter_proxy');
        $this->proxy_userpaswd = get_option('vkposter_proxy_userpaswd');
    }

    /**
     * Условия для обновления версии плагина
     */
    public function IfElseUpdate() {
        $vkposter_posttype = get_option('vkposter_posttype'); //Типы постов
        if (empty($vkposter_posttype)) { //установка опции по умолчанию
            update_option('vkposter_posttype', array('post' => 'post'));
        }
    }

    /**
     * Основная функция
     * Создавалка записи на стене вконтакте
     */
    public function setVkWall($post_id) {

        $postData = get_post($post_id);
        $title = $postData->post_title;
        $text = $postData->post_content;
//$link_post = $postData->guid; // Вариант не ЧПУ
        $link_post = get_permalink($post_id); // ссылка на запись (теперь ЧПУ) 
        $vkposter_id = get_option('vkposter_id'); //ID группы или пользователя
        $vkposter_friends_only = get_option('vkposter_friends_only'); //Доступность записи, 0 - всем
        $vkposter_from_group = get_option('vkposter_from_group'); //От чьего имени публиковать
        $vkposter_signed = get_option('vkposter_signed');
        $vkposter_counttext = get_option('vkposter_counttext');
        $vkposter_userid = get_option('vkposter_userid'); //ID пользователя, создателя группы
        $vkposter_token = get_option('vkposter_token'); //Токен приложения
        $postType = get_post_type($post_id);
        $vkposter_prooptions = get_option('vkposter_prooptions',array());

//
        $image = $this->setImageVK($text, $post_id)->id; //Получаем ID фотографии
        $text = str_replace('<!--more-->', '', strip_tags(strip_shortcodes($text))) . "\n\n"; //вырезаем шорткоды, теги, "далее"//
//woo

        if ($this->isWoo() and in_array($postType, $this->getWooPostType()) and $this->isProParam()) {
            $product = new WC_Product($post_id);
            $price = $product->price;
            if (in_array('woo_price', $this->isWooParam())) {
                $text .= "\n\nЦена: " . $price . " " . get_woocommerce_currency_symbol() . "\n\n";
            }
            if (in_array('woo_met', $this->isWooParam())) {

                $arrMet = get_the_terms($post_id, 'product_tag');
                if ($arrMet) {

                    foreach ($arrMet as $met) {
                        $text .= '#' . $met->name . ' ';
                    }
                    $text .= "\n\n";
                }
            }
        }

        /**
         * Метки WP
         */
        if(!empty($vkposter_prooptions['wp_met'])) {
            $arrMet = get_the_terms($post_id, 'post_tag');

            if ($arrMet) {
                foreach ($arrMet as $met) {
                    $text .= '#' . $met->name . ' ';
                }
                $text .= "\n\n";
            }
        }

        $wp_insert_global_text='';
        if(!empty($vkposter_prooptions['wp_insert_global_text'])) {
            $wp_insert_global_text= wp_kses($vkposter_prooptions['wp_insert_global_text'], 'strip');
        }

        if ($vkposter_counttext == 0) { //пост без ограничений
            $text_clear = wp_kses($title, 'strip') . "\n\n " . wp_kses($text, 'strip');
        } else { //Пост с обрезкой до кол-ва знаков указанных пользователем
            $text_clear = wp_kses($title, 'strip') . "\n\n " . wp_trim_words(wp_kses($text, 'strip'), $vkposter_counttext, '...');
        }
        unset($text);

        $send_text=$text_clear.$wp_insert_global_text;

        $argument=array();

        if (!empty($image)) {
            $argument = array(
                "owner_id" => $vkposter_id,
                "from_group" => $vkposter_from_group,
                "friends_only" => $vkposter_friends_only,
                "signed" => $vkposter_signed,
                "message" => $send_text,
                "attachments" =>  'photo'.$vkposter_userid.'_'.$image . ',' . $link_post
            );
        }
        if (empty($image)) { //Миниатюры нет
            $argument = array(
                "owner_id" => $vkposter_id,
                "from_group" => $vkposter_from_group,
                "friends_only" => $vkposter_friends_only,
                "signed" => $vkposter_signed,
                "message" => $send_text . $link_post
            );
        }
        $result = $this->sentRequesVK(self::METHOD_URL_VK, $argument, $this->proxy);

        return $result;
    }

    /**
     * Запросы к сервису Вконтакте
     * @param strint $method Полный метод ВК, с УРЛ и знаком ?
     * @param array $arg Массив аргументов для создания запроса
     */
    public function sentRequesVK($method, $arg, $proxy = null) {
        $vkposter_token = get_option('vkposter_token'); //Токен приложения
        $arg['access_token'] = $vkposter_token;
        $arg['v']=$this->version_api;
        $query = http_build_query($arg);
        if (!empty($proxy)) { //Если прокси, то отправляем по старорму
            $url = $method . $query;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 12);
            if (!empty($this->proxy_userpaswd)) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_userpaswd);
            }
            $curlinfo = curl_exec($curl); //Результат запроса
            $response = curl_getinfo($curl); //Информация о запросе
            curl_close($curl);
            return $curlinfo;
        } else { //Если не прокси, шлём так
            $curlinfo = wp_remote_post($method, array('body' => $query));
            if (is_wp_error($curlinfo)) {
                $errMessage = $curlinfo->get_error_message();
                echo 'Ошибка отправки: ' . $errMessage;
            }
            return $curlinfo['body'];
        }
    }

    /**
     * Проверка Чекеда
     * @param string $options Опция из базы данных
     * @param string $value Текущее значение для сравнения (например значение из цикла)
     * @return echo checked или пусто
     */
    public function chekedOptions($options, $value) {
        if (!empty($options) or ! empty($value)) {
            if ($options == $value) {
                echo 'checked';
            } elseif ($options !== $value) {
                echo '';
            }
        }
    }

    /**
     * Получение изображения поста из миниатюры или из прикрепленного изображения
     */
    public function setImageVK($text, $post_id) {
        $vkposter_id = get_option('vkposter_id'); //ID группы или пользователя
        $images_post = get_attached_file(get_post_thumbnail_id($post_id));

        $vkposter_token = get_option('vkposter_token'); //Токен приложения
        if (empty($images_post)) {
            $media = get_attached_media('image', $post_id);
            $image = array_shift($media);
            $images_post = get_attached_file($image->ID); //Изображение прикреплённое к посту
            if (empty($images_post)) {
                $images_post = $this->searchImageText($text); // Ищем изображение в тексте
                if (empty($images_post)) {
                    return false;
                }
            }
        }
        $argument = array(
            "group_id" => trim($vkposter_id, '-'),
            "version" => $this->version_api,
        );
        $curlinfo = $this->sentRequesVK(self::METHOD_URL_VKIMAGE, $argument, $this->proxy);
        unset($argument);

        if (!empty($curlinfo)) {
            $UrlObj = json_decode($curlinfo);
            $urlimgvk = $UrlObj->response->upload_url;
        }
        if (!empty($urlimgvk)) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $urlimgvk);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if (self::compareOldPHPVer('5.6.0', '<')) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, array('photo' => '@' . $images_post));
            } elseif (self::compareOldPHPVer('5.5.0', '>')) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, ['photo' => new CurlFile($images_post)]);
            }
            if (!empty($this->proxy)) {
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 12);
            }
            if (!empty($this->proxy_userpaswd)) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_userpaswd);
            }
            $curlinfo = curl_exec($curl); //Результат запроса
            $response = curl_getinfo($curl); //Информация о запросе
            curl_close($curl);
            $imageObject = json_decode($curlinfo);
            if (!empty($imageObject->server) && !empty($imageObject->photo) && !empty($imageObject->hash)) {

                $argument = array(
                    "group_id" => trim($vkposter_id, '-'),
                    "server" => $imageObject->server,
                    "photo" => $imageObject->photo,
                    "hash" => $imageObject->hash
                );
                $curlinfo = $this->sentRequesVK(self::METHOD_URL_VKIMAGE_SAVE, $argument, $this->proxy);
                unset($argument);

                $resultObject = json_decode($curlinfo);

                if (isset($resultObject) && isset($resultObject->response[0]->id)) {
                    return $resultObject->response[0];
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Поиск изображений в тексте
     * @param string $text Текст поста с тегами HTML
     * @return string абсолютный серверный путь до изображения
     */
    public function searchImageText($text) {
        $first_img = '';
        ob_start();
        ob_end_clean();
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);
        $first_img = $matches [1] [0];
        $col1 = strlen(WP_CONTENT_URL);
        $col2 = strlen($first_img);
        $patch = substr($first_img, $col1, $col2);
        $result = WP_CONTENT_DIR . $patch;
//В перспективе изображение по умолчанию (сейчас не реализованно)
        if (empty($first_img)) {
            return false;
//$first_img = "/images/default.jpg";
        }
        return $result;
    }

    /**
     *
     * Относительный путь до фото преобразует в абсолютный
     * @return string абсолютный серверный путь до изображения
     */
    public function searchImgaeHTTP($http) {
        $text = '<img src="' . $http . '">';
        $first_img = '';
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);
        $first_img = $matches [1] [0];
        $col1 = strlen(WP_CONTENT_URL);
        $col2 = strlen($first_img);
        $patch = substr($first_img, $col1, $col2);
        $result = WP_CONTENT_DIR . $patch;
// В перспективе изображение по умолчанию (сейчас не реализованно)
        if (empty($first_img)) {
            return false;
//$first_img = "/images/default.jpg";
        }
        return $result;
    }

    /**
     * Функция логирования, для вкладки журнал
     */
    public function logJornal($idpost, $title, $status) {

        $vkposter_jornal_old = get_option('vkposter_jornal');
        if (count($vkposter_jornal_old) >= 50) {
            $vkposter_jornal_old = array_slice($vkposter_jornal_old, -40);
        }
        $time = current_time('mysql');
        $vkposter_jornal_temp = array('time' => $time, 'idpost' => $idpost, 'title' => $title, 'status' => $status);
        $vkposter_jornal_new = $vkposter_jornal_old;
        array_push($vkposter_jornal_new, $vkposter_jornal_temp);
        update_option('vkposter_jornal', $vkposter_jornal_new);
    }

    /**
     * Получает токен из УРЛ
     * @param string $stringurl Урл содержащий токен, или сам токен
     */
    public function getTokenUrl($stringurl) {

        $start = 'access_token=';
        $end = '&expires_in';
        if (stripos($stringurl, $start)) {
            $result = $this->cutTextStartEnd($stringurl, $start, $end);
        } else {
            $result = $stringurl;
        }

        return $result;
    }

    /**
     * Обрезка контента по условию начала и конца
     * @param string $text Текст который мы хотим обрезать
     * @param string $start Начало от куда нужно обрезать
     * @param string $end Конец до куда нужно резать
     */
    public function cutTextStartEnd($text, $start, $end) {
        $posStart = stripos($text, $start);
        if ($posStart === false)
            return FALSE;

        $text = substr($text, $posStart + strlen($start));
        $posEnd = stripos($text, $end);
        if ($posEnd === false)
            return FALSE;

        $result = substr($text, 0, 0 - (strlen($text) - $posEnd));
        return $result;
    }

    /**
     * Получает списко категорий постов (НЕ WOO)
     */
    public static function getAllCategory() {
        $arg = array(
            'hide_empty' => '0',
            'order' => 'ASC'
        );
        $categories = get_categories($args);
        foreach ($categories as $cat) {
            ?>

            <p><input name="vkposter_prooptions[selectcat[<?php echo $cat->cat_ID; ?>]]" type="checkbox" value="<?php echo $cat->cat_ID; ?>" <?php
                if (isset($vkposter_prooptions['selectcat'][$cat->cat_ID])) {
                    checked($vkposter_prooptions['selectcat'][$cat->cat_ID], $cat->cat_ID, 1);
                }
                ?>><?php echo $cat->name; ?></p>
            <?php
        }
    }

    /**
     * Проверка установки Woo
     * @return bool true или false
     */
    public function isWoo() {

        if (class_exists('woocommerce')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверка использования ПРО
     * @return bool true или false
     */
    protected function isProParam() {
        $vkposter_prooptions = get_option('vkposter_prooptions');
        if (!empty($vkposter_prooptions['chekpro'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверка использования Опций Woo
     * Если настройки Woo не активированы - false
     * @return array array(woo_chek,woo_price,woo_met)
     */
    public function isWooParam() {
        $arrRes = array();
        $vkposter_prooptions = get_option('vkposter_prooptions');
        if (!empty($vkposter_prooptions['woo_chek'])) {
            array_push($arrRes, 'woo_chek');
        } else {
            return false;
        }
        if (!empty($vkposter_prooptions['woo_price'])) {
            array_push($arrRes, 'woo_price');
        }
        if (!empty($vkposter_prooptions['woo_met'])) {
            array_push($arrRes, 'woo_met');
        }
        return $arrRes;
    }

    /**
     * Возвращает доступные таксономии Woo
     * @return array Массив доступных таксономий Woocommerce
     */
    protected function getWooPostType() {
        $arrTax = array(
            'product',
            'product_variation',
            'shop_order',
            'shop_order_refund',
            'shop_coupon',
            'shop_webhook',
        );
        return $arrTax;
    }

    /**
     * Сравнивает версии PHP
     * Пример 5.3.0
     * Возвращает true если версия PHP меньше или больше указанной, зависит от знака
     * @param $zn < или >
     * @param $php_v версия
     * @return bool true если текущая PHP менье указаной
     */
    static public function compareOldPHPVer($php_v, $zn) {
        //PHP<5.3
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
        }
        if (version_compare(PHP_VERSION, $php_v, "{$zn}")) {
            return true;
        } else {
            return false;
        }
    }

}
