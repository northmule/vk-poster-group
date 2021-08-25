<?php
namespace zixnru\vkpost;
if (!defined('ABSPATH')) {
    exit;
} ?>
<?php

/**
 * Взаимодействие с js файлами
 */
class jsClass {

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->addaction();
    }

    /**
     * Адды
     */
    public function addaction() {
       
        //add_action('wp_ajax_incPro', array($this, 'ajaxChekError'));
        //add_action('wp_ajax_nopriv_incPro', array($this, 'ajaxChekError'));
    }

    /**
     * Обаботка приходящих данных о галочке ведения сообщений ошибок
     * Установка чекбокса
     */
    public function ajaxChekError() {
        $arrProopt = get_option('vkposter_prooptions');
        $text = $_POST['text'];
        if ($text === 'checked') {
            $opt = 1;
        } else {
            $opt = 0;
        }
        $arrProopt['chekpro'] = $opt;
        update_option('vkposter_prooptions',$arrProopt );
        wp_die();
    }

}
