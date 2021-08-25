<?php
namespace Coderun\VkPoster;

/**
 * Класс с функционалом и обработками
 */
class Services {
    
    const METHOD_URL_VK = 'https://api.vk.com/method/wall.post?'; //Метод постинга сообщений
    const METHOD_URL_VKIMAGE = 'https://api.vk.com/method/photos.getWallUploadServer?'; //Метод получения сервера загрузок изображения
    const METHOD_URL_VKIMAGE_SAVE = 'https://api.vk.com/method/photos.saveWallPhoto?'; //Загружает изображение в группу
    
    const NAME_META_URL_VK = 'coderun_vk_url_post';
    
    protected static $_instance = null;
    
    /**
     *
     * @var string Прокси сервер для CURL IP:порт
     */
    
    public $proxy;
    
    /**
     *
     * @var string Прокси сервер для CURL Пользователь:пароль
     */
    public $proxy_userpaswd;
    
    public $version_api='5.70';
    
    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->proxy = CorePlugin::getInstance()->getOptions('vkposter_proxy');
        $this->proxy_userpaswd = CorePlugin::getInstance()->getOptions('vkposter_proxy_userpaswd');
    }
    
    
    /**
     * Основная функция
     * Создавалка записи на стене вконтакте
     */
    public function setVkWall($post_id) {
        
        $postData = get_post($post_id);
        $title = $postData->post_title;
        $text = $postData->post_content;
        if (CorePlugin::getInstance()->getOptions('text_source') === 'post_excerpt') {
            $text = $postData->post_excerpt;
        }
        $text = apply_shortcodes($text);
        $link_post = get_permalink($post_id); // ссылка на запись (теперь ЧПУ)
        $vkposter_id = CorePlugin::getInstance()->getOptions('vkposter_id'); //ID группы или пользователя
        $vkposter_friends_only = CorePlugin::getInstance()->getOptions('vkposter_friends_only'); //Доступность записи, 0 - всем
        $vkposter_from_group = CorePlugin::getInstance()->getOptions('vkposter_from_group'); //От чьего имени публиковать
        $vkposter_signed = CorePlugin::getInstance()->getOptions('vkposter_signed');
        $vkposter_counttext = CorePlugin::getInstance()->getOptions('vkposter_counttext');
        $vkposter_userid = CorePlugin::getInstance()->getOptions('vkposter_userid'); //ID пользователя, создателя группы
        $postType = get_post_type($post_id);
        $vkposter_prooptions = CorePlugin::getInstance()->getOptions('vkposter_prooptions');
        
        $image = $this->setImageVK($text, $post_id);
        $image = isset($image->id) ? $image->id : ''; //Получаем ID фотографии
        $text = str_replace('<!--more-->', '', strip_tags(strip_shortcodes($text))) . "\n\n"; //вырезаем шорткоды, теги, "далее"//
        
        if ($this->isWoo() and in_array($postType, $this->getWooPostType()) and $this->isProParam()) {
            $product = new \WC_Product($post_id);
            $price = $product->get_price();
            if (in_array('woo_price', $this->isWooParam())) {
                $text .= "\n\nЦена: " . $price . " " . get_woocommerce_currency_symbol() . "\n\n";
            }
            if (in_array('woo_met', $this->isWooParam())) {
                
                $arrMet = get_the_terms($post_id, 'product_tag');
                if ($arrMet) {
                    
                    foreach ($arrMet as $met) {
                        if (!$met instanceof \WP_Term) {
                            continue;
                        }
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
        
        $send_text = $text_clear.$wp_insert_global_text;
        
        $argument = [
            "owner_id" => $vkposter_id,
            "from_group" => $vkposter_from_group,
            "friends_only" => $vkposter_friends_only,
            "signed" => $vkposter_signed,
        ];
        
        if ($image) {
            $argument['message'] = $send_text;
            $argument['attachments'] = 'photo'.$vkposter_userid.'_'.$image . ',' . $link_post;
        } else {
            $argument['message'] = $send_text . $link_post;
        }
        
        return $this->sentRequesVK(self::METHOD_URL_VK, $argument, $this->proxy);;
    }
    
    /**
     * Запись URL в мета поле
     * @param        $postId
     * @param        $vkId
     * @param string $name
     */
    public function setMetaPostUrlVK($postId, $vkId)
    {
        $url = $this->getUrlPostVK($vkId);
        if (empty($url)) {
            return;
        }
        update_post_meta(\intval($postId), self::NAME_META_URL_VK, $url);
        
        return $url;
    }
    
    /**
     * Запросы к сервису Вконтакте
     * @param strint $method Полный метод ВК, с УРЛ и знаком ?
     * @param array $arg Массив аргументов для создания запроса
     */
    public function sentRequesVK($method, $arg, $proxy = null) {
        $vkposter_token = CorePlugin::getInstance()->getOptions('vkposter_token'); //Токен приложения
        $arg['access_token'] = $vkposter_token;
        $arg['v'] = $this->version_api;
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
     * Получение изображения поста из миниатюры или из прикрепленного изображения
     */
    public function setImageVK($text, $post_id) {
        $vkposter_id = CorePlugin::getInstance()->getOptions('vkposter_id'); //ID группы или пользователя
        $images_post = get_attached_file(get_post_thumbnail_id($post_id));
        
        $vkposter_token = CorePlugin::getInstance()->getOptions('vkposter_token'); //Токен приложения
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
            $curl = \curl_init();
            \curl_setopt($curl, CURLOPT_URL, $urlimgvk);
            \curl_setopt($curl, CURLOPT_POST, 1);
            \curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            \curl_setopt($curl, CURLOPT_POSTFIELDS, ['photo' => new \CurlFile($images_post)]);
            
            if (!empty($this->proxy)) {
                \curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
                \curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 12);
            }
            if (!empty($this->proxy_userpaswd)) {
                \curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_userpaswd);
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
            
        }
        return $result;
    }
    
    
    /**
     * Функция логирования, для вкладки журнал
     */
    public function logJornal($idpost, $title, $status) {
        
        $vkposter_jornal_old = CorePlugin::getInstance()->getOptions('vkposter_jornal');
        if (count($vkposter_jornal_old) >= 50) {
            $vkposter_jornal_old = array_slice($vkposter_jornal_old, -40);
        }
        $time = current_time('mysql');
        $response = json_decode($status, true);
        $url = '';
        if (!empty($response['response']['post_id'])) {
            $url = $this->getUrlPostVK($response['response']['post_id']);
        }
        $vkposter_jornal_temp = array('time' => $time, 'idpost' => $idpost, 'title' => $title, 'status' => $status, 'url' => $url);
        array_push($vkposter_jornal_old, $vkposter_jornal_temp);
        CorePlugin::getInstance()->updateOptions('vkposter_jornal', $vkposter_jornal_old);
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
     * Проверка установки Woo
     * @return bool true или false
     */
    public function isWoo() {
        
        if (\class_exists('woocommerce')) {
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
        $vkposter_prooptions = CorePlugin::getInstance()->getOptions('vkposter_prooptions') ? CorePlugin::getInstance()->getOptions('vkposter_prooptions') : [];
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
        $vkposter_prooptions = CorePlugin::getInstance()->getOptions('vkposter_prooptions');
        if (is_array($vkposter_prooptions) && !empty($vkposter_prooptions['woo_chek'])) {
            array_push($arrRes, 'woo_chek');
        } else {
            return false;
        }
        if (is_array($vkposter_prooptions) && !empty($vkposter_prooptions['woo_price'])) {
            array_push($arrRes, 'woo_price');
        }
        if (is_array($vkposter_prooptions) && !empty($vkposter_prooptions['woo_met'])) {
            array_push($arrRes, 'woo_met');
        }
        return $arrRes;
    }
    
    /**
     * Возвращает доступные таксономии Woo
     * @return array Массив доступных таксономий Woocommerce
     */
    protected function getWooPostType() {
        return [
            'product',
            'product_variation',
            'shop_order',
            'shop_order_refund',
            'shop_coupon',
            'shop_webhook',
        ];
    }
    
    /**
     * Получить URL на созданную запись в ВК
     * @param $vkPostId
     */
    protected function getUrlPostVK($vkPostId)
    {
        if (empty($vkPostId)) {
            return '';
        }
        $groupOption = CorePlugin::getInstance()->getOptions('vkposter_id');
        $group = \trim($groupOption,'-');
        if (\stripos($groupOption,'-') !== false) {
            $mask = sprintf('https://vk.com/club%s?w=wall%s_%s', $group, $groupOption, $vkPostId); // стена сообщества
        } else {
            $mask = sprintf('https://vk.com/id%s?w=wall%s_%s', $group, $group, $vkPostId); // стена пользователя
        }
        
        return $mask;
        
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
    
}
