<?php
namespace Coderun\VkPoster;


class ServicesAjax
{
    
    const SUCCESS_SEND_TEXT = 'Запись отправлена в ВК';
    const ERROR_SEND_TEXT = 'Ошибка. Запись не отправлена';
    
    protected static $_instance = null;
    
    public static function getInstance() {
        
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function addEventListener()
    {
        add_action('wp_ajax_coderun_send_post_to_vk', array($this, 'sendPostToVk'));
    }
    
    public function sendPostToVk()
    {
        $postId = isset($_POST['postId']) ? $_POST['postId'] : $_POST['postId'];
        $postId = \intval($postId);
        if (empty($postId)) {
            wp_send_json_error(self::ERROR_SEND_TEXT);
        }
        $post =  get_post($postId);
        if (!$post instanceof \WP_Post) {
            wp_send_json_error(self::ERROR_SEND_TEXT);
        }
        $status_sent = Services::getInstance()->setVkWall($post->ID); //Отправка текста
        Services::getInstance()->logJornal($post->ID, $post->post_title, $status_sent); //Логируем результаты
        $status_arr = \json_decode($status_sent);
        if (!empty($status_arr->response->post_id)) {
            Services::getInstance()->setMetaPostUrlVK($postId, $status_arr->response->post_id);
        }
        if (!empty($status_arr->error)) {
            wp_send_json_error(self::ERROR_SEND_TEXT);
        }
        wp_send_json_success(self::SUCCESS_SEND_TEXT);
    }
}