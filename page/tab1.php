<?php
use Coderun\VkPoster\CorePlugin;
use Coderun\VkPoster\Services;

if (!defined('ABSPATH')) {
    exit;
}
?>

<?php
$optionsName = CorePlugin::PREF_PLG;
$plugin = CorePlugin::getInstance();

?>

<p>
    Всё просто! Выполните Шаг 1, полученное ИД приложения вставте в поле настроек плагина и нажмите "Сохранить"
    <a class="btn btn-danger" href="<?php echo $plugin::URL_VK_DEVCREATE; ?>" target="_blank"> ШАГ №1 - Создать приложение VK</a>
</p>
<p>
  После прохождения Шага 1 и сохранения настроек плагина, нажмите на ссылку "Шаг2". В результате вы поличите токен, который так же вставте в поле настроек плагина и нажмите "Сохранить"
    <a  href="https://oauth.vk.com/authorize?client_id=<?php echo $plugin->getOptions('vkposter_idsoft'); ?>&scope=wall,photos,offline&redirect_uri=https://oauth.vk.com/blank.html&display=page&response_type=token" target="_blank"> ШАГ №2 - Получить токен</a>

</p>

<form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
    <?php settings_fields(sprintf('%s_options', $plugin::PREF_PLG)); ?>

    <table class="form-table">
        <h3>Общие настройки</h3>
        <tr valign="top">
            <th scope="row">ID приложения</th>
            <td>
                <input type="text" pattern="[0-9]*" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_idsoft]" value="<?php echo $plugin->getOptions('vkposter_idsoft'); ?>" />
                <span class="description">ID standalone приложения. Данное поле вы заполните после прохождения Шага №1</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Токен</th>
            <td>
                <input size="90" type="text" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_token]" value="<?php echo $plugin->getOptions('vkposter_token'); ?>" />
                <span class="description">Служит для обмена данными между вашим сайтом и вашей стеной ВК. Это поле вы заполните данными полученными после прохождения Шага №2</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">ID группы</th>
            <td>
                <input type="text" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_id]" value="<?php echo $plugin->getOptions('vkposter_id'); ?>" />
                <span class="description">Введите сюда ID группы или пользователя вконтакте, перед ID группы обязателен знак -. Если вы будете постить в группу то впишите сюда ID группы с знаком "-", пример "-54354332". Если вы будете постить на стену пользователя, то впишите сюда ID пользователя, пример "138684319"</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">ID пользователя</th>
            <td>
                <input type="text" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_userid]" value="<?php echo $plugin->getOptions('vkposter_userid'); ?>" />
                <span class="description">ID пользователя, владельца группы. Данный параметр необходим для публикации фотографий из записи Wordpress. Если вы будете постить на стену пользователя, то укажите ID пользователя из поля выше.</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Доступность</th>
            <td>

                <select name="<?php echo $plugin::PREF_PLG; ?>[vkposter_friends_only]">
                    <option value="0" <?php selected($plugin->getOptions('vkposter_friends_only'), '0', true); ?>>Всем пользователям</option>
                    <option value="1" <?php selected($plugin->getOptions('vkposter_friends_only'), '1', true); ?>>Только участникам</option>
                </select>
                <span class="description">Видно только участникам группы или видно всем пользователям </span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Автор</th>
            <td>
                <select name="<?php echo $plugin::PREF_PLG; ?>[vkposter_from_group]">
                    <option value="0" <?php selected($plugin->getOptions('vkposter_from_group'), '0', true); ?>>От имени пользователя</option>
                    <option value="1" <?php selected($plugin->getOptions('vkposter_from_group'), '1', true); ?>>От имени группы</option>

                </select>
                <span class="description">Запись будет опубликована от имени группы или запись будет опубликована от имени пользователя</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Подпись</th>
            <td>


                <select name="<?php echo $plugin::PREF_PLG; ?>[vkposter_signed]">
                    <option value="0" <?php selected($plugin->getOptions('vkposter_signed'), '0', true); ?>>Подпись добавлена не будет</option>
                    <option value="1" <?php selected($plugin->getOptions('vkposter_signed'), '1', true); ?>>Будет добавлена подпись</option>

                </select>

                <span class="description">У записи, размещенной от имени сообщества, будет добавлена подпись (имя пользователя, разместившего запись) или подписи добавлена не будет. Параметр учитывается только при публикации на стене сообщества</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Размер сообщения</th>
            <td>
                <input type="text"  pattern="[0-9]*" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_counttext]" value="<?php
                if (empty($plugin->getOptions('vkposter_counttext'))) {
                    echo 0;
                } else {
                    echo $plugin->getOptions('vkposter_counttext');
                }
                ?>" />
                <span class="description">Количество слов отправляемых на стену ВК. Без ограничений — 0. При этом запись будет публиковаться вся.".</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Источник текста</th>
            <td>

                <select name="<?php echo $plugin::PREF_PLG; ?>[text_source]">
                    <option value="post_content" <?php selected($plugin->getOptions('text_source'), 'post_content', true); ?>>Главный текст</option>
                    <option value="post_excerpt" <?php selected($plugin->getOptions('text_source'), 'post_excerpt', true); ?>>Отрывок поста</option>
                </select>
                <span class="description">Из какого поля брать текст для отправки в ВК</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Действие по умолчанию</th>
            <td>
                <input type="checkbox" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_onoff]" <?php checked($plugin->getOptions('vkposter_onoff'), 'on', 1); ?>/>
                <span class="description">При установке галки, записи из Wordpress всегда будут добавляться на стену Вконтакте при публикации.</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Типы записей</th>
            <td>
                <?php
                $array_posts = get_post_types('', 'names', 'and');
                foreach ($array_posts as $v) {
                    ?>

                    <p><input name="<?php echo $plugin::PREF_PLG; ?>[vkposter_posttype][<?php echo $v; ?>]" type="checkbox" value="<?php echo $v; ?>" <?php
                        if (isset($plugin->getOptions('vkposter_posttype')[$v])) {
                            checked($plugin->getOptions('vkposter_posttype')[$v], $v, 1);
                        }
                        ?>><?php echo $v; ?></p>
                    <?php
                }
                ?>
                <span class="description">Выберите типы «записей» при добавление которых будет работать функция отправки на стену VK. По умолчанию всегда активен тип записей «Post». Если вам необходимо что бы была возможность отправлять данные из «Произвольных типов записей» поставте напротив «галочку». Если вы не знаете что такое «Произвольный тип записей» - ни чего не трогайте.</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Специальные опции</th>
            <td>
                <span class="description">Ниже расположены специфические настройки, в 99% случаев не требующие указания.</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Прокси сервер</th>
            <td>
                <input type="text" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_proxy]" value="<?php echo $plugin->getOptions('vkposter_proxy'); ?>" />
                <span class="description">Формат заполнения IP:ПОРТ (Пример 82.151.117.162:8080)</span>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Прокси Пользователь и пароль</th>
            <td>
                <input type="text" name="<?php echo $plugin::PREF_PLG; ?>[vkposter_proxy_userpaswd]" value="<?php echo $plugin->getOptions('vkposter_proxy_userpaswd'); ?>" />
                <span class="description">Данные для авторизации на прокси, вида user:password(Пример bublik:J3dJkl1d). Если вы используете анонимный прокси, без авторизации - тут ни чего указывать не надо.</span>
            </td>
        </tr>

    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="<?php echo $plugin::PREF_PLG; ?>" />
    <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>


