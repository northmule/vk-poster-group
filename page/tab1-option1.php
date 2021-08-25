<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<script type="text/javascript">
    jQuery("document").ready(function () {
        jQuery('.step3clic').click(function () {
            jQuery("#modalstep3ok").modal();
        });

        jQuery("#shag1").tooltip({placement: 'bottom'});
        jQuery("#toltipid1").tooltip({placement: 'bottom'});



        jQuery("#toltipparse").tooltip({placement: 'bottom'});
        jQuery("#toltiptitle").tooltip({placement: 'bottom'});

        jQuery("#tekentip").tooltip({placement: 'bottom'});

        jQuery("#idsofttip").tooltip({placement: 'bottom'});
    });
</script>	

<?php
$vkpfun = new VKPOSTERFUNCTION;
$vkpfun->IfElseUpdate();
?>

<?php
$vkposter_id = get_option('vkposter_id'); //ID группы или пользователя
$vkposter_friends_only = get_option('vkposter_friends_only'); //Доступность записи, 0 - всем
$vkposter_from_group = get_option('vkposter_from_group'); //От чьего имени публиковать
$vkposter_signed = get_option('vkposter_signed');
$vkposter_counttext = get_option('vkposter_counttext');
$vkposter_proxy = get_option('vkposter_proxy'); //Прокси сервер, для публикации через прокси
//if ($vkposter_counttext > 40 OR empty($vkposter_counttext)) {
//    $vkposter_counttext = 40;
//    update_option('vkposter_counttext', $vkposter_counttext);
//}
$vkposter_onoff = get_option('vkposter_onoff');
$vkposter_jornal = get_option('vkposter_jornal');

$vkposter_idsoft = get_option('vkposter_idsoft'); //ID приложения
$vkposter_token = get_option('vkposter_token'); //Токен приложения
if (!empty($vkposter_token)) {
    $vkposter_token = $vkpfun->getTokenUrl($vkposter_token);
    update_option('vkposter_token', $vkposter_token);
}

$vkposter_userid = get_option('vkposter_userid'); //ID пользователя, создателя группы

$active_plugin = get_option('active_plugins'); //Активные плагины
$vkposter_posttype = get_option('vkposter_posttype'); //Типы записей, где будет доступна отправка на стену

$plugins_url = admin_url() . 'options-general.php?page=' . VKPOSTERBASE::URL_ADMIN_MENU_PLUGIN; //URL страницы плагина
$dir_plugin_absolut = plugin_dir_path(__FILE__);
?>

<h2><?php _e('Настройка вашего сайта на работу с плагином ' . VKPOSTERBASE::NAME_TITLE_PLUGIN_PAGE) ?></h2>
<span class="description">Lite версия настроек — это базовый режим при котором настройка плагина такая-же простая как и в первых версиях. Для тех кому «базы» мало — вкладка «PRO».</span>

<p><span id="shag1" data-toggle="tooltip" title="После прохождения вы получите ID приложения, который нужно будет ввести в поле ниже и сохранить"> <a class="btn btn-danger" href="<?php echo VKPOSTERBASE::URL_VK_DEVCREATE; ?>" target="_blank"> ШАГ №1 - Создать приложение VK</a></span> </p>
<p></p>
<?php if (!empty($vkposter_idsoft)) { ?>
    <a class="btn btn-danger" href="https://oauth.vk.com/authorize?client_id=<?php echo $vkposter_idsoft; ?>&scope=wall,photos,offline&redirect_uri=https://oauth.vk.com/blank.html&display=page&response_type=token" target="_blank"> ШАГ №2 - Получить токен</a>
<?php } ?>
<form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>

    <table class="form-table">
        <h3>Общие настройки</h3>
        <tr valign="top">
            <th scope="row">ID приложения</th>
            <td>
                <input id="idsofttip" data-toggle="tooltip" title="Данное поле вы заполните после прохождения Шага №1" type="text" pattern="[0-9]*" name="vkposter_idsoft" value="<?php echo $vkposter_idsoft; ?>" />
                <span class="description">ID standalone приложения</span>
            </td>
        </tr>
        <?php if (!empty($vkposter_idsoft)) { ?>
            <tr valign="top">
                <th scope="row">Токен</th>
                <td>
                    <input id="tekentip" data-toggle="tooltip" title="Это поле вы заполните данными полученными после прохождения Шага №2" size="90" type="text" name="vkposter_token" value="<?php echo $vkposter_token; ?>" />
                    <span class="description">Служит для обмена данными между вашим сайтом и вашей стеной ВК</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">ID группы</th>
                <td>
                    <input id="toltipid1" data-toggle="tooltip" title="Введите сюда ID группы или пользователя вконтакте, перед ID группы обязателен знак -" type="text" name="vkposter_id" value="<?php echo $vkposter_id; ?>" />
                    <span class="description">Если вы будете постить в группу то впишите сюда ID группы с знаком "-", пример "-54354332". Если вы будете постить на стену пользователя, то впишите сюда ID пользователя, пример "138684319"</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">ID пользователя</th>
                <td>
                    <input type="text" name="vkposter_userid" value="<?php echo $vkposter_userid; ?>" />
                    <span class="description">ID пользователя, владельца группы. Данный параметр необходим для публикации фотографий из записи Wordpress. Если вы будете постить на стену пользователя, то укажите ID пользователя из поля выше.</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Доступность</th>
                <td>

                    <select name="vkposter_friends_only">
                        <option value="0" <?php selected($vkposter_friends_only, '0', true); ?>>Всем пользователям</option>
                        <option value="1" <?php selected($vkposter_friends_only, '1', true); ?>>Только участникам</option>

                    </select>
                    <span class="description">Видно только участникам группы или видно всем пользователям </span>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Автор</th>
                <td>

                    <select name="vkposter_from_group">
                        <option value="0" <?php selected($vkposter_from_group, '0', true); ?>>От имени пользователя</option>
                        <option value="1" <?php selected($vkposter_from_group, '1', true); ?>>От имени группы</option>

                    </select>
                    <span class="description">Запись будет опубликована от имени группы или запись будет опубликована от имени пользователя</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Подпись</th>
                <td>


                    <select name="vkposter_signed">
                        <option value="0" <?php selected($vkposter_signed, '0', true); ?>>Подпись добавлена не будет</option>
                        <option value="1" <?php selected($vkposter_signed, '1', true); ?>>Будет добавлена подпись</option>

                    </select>

                    <span class="description">У записи, размещенной от имени сообщества, будет добавлена подпись (имя пользователя, разместившего запись) или подписи добавлена не будет. Параметр учитывается только при публикации на стене сообщества</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Размер сообщения</th>
                <td>
                    <input id="toltiptitle" data-toggle="tooltip" title="Количество слов для отправки на стену" type="text"  pattern="[0-9]*" name="vkposter_counttext" value="<?php
                    if (empty($vkposter_counttext)) {
                        echo 0;
                    } else {
                        echo $vkposter_counttext;
                    }
                    ?>" />
                    <span class="description">Количество слов отправляемых на стену ВК. Без ограничений — 0. При этом запись будет публиковаться вся.".</span>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Действие по умолчанию</th>
                <td>
                    <input type="checkbox" name="vkposter_onoff" <?php checked($vkposter_onoff, 'on', 1); ?>/>
                    <span class="description">При установке галки, записи из Wordpress всегда будут добавляться на стену Вконтакте при каждом обновлении или публикации. Настройка так же влияет на публикацию запланированных записей.</span>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Типы записей</th>
                <td>
                    <?php
                    $array_posts = get_post_types('', 'names', 'and');
                    foreach ($array_posts as $v) {
                        ?>

                        <p><input name="vkposter_posttype[<?php echo $v; ?>]" type="checkbox" value="<?php echo $v; ?>" <?php
                            if (isset($vkposter_posttype[$v])) {
                                checked($vkposter_posttype[$v], $v, 1);
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
                    <input type="text" name="vkposter_proxy" value="<?php echo $vkposter_proxy; ?>" />
                    <span class="description">Формат заполнения IP:ПОРТ (Пример 82.151.117.162:8080)</span>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Прокси Пользователь и пароль</th>
                <td>
                    <input type="text" name="vkposter_proxy_userpaswd" value="<?php echo $vkpfun->proxy_userpaswd; ?>" />
                    <span class="description">Данные для авторизации на прокси, вида user:password(Пример bublik:J3dJkl1d). Если вы используете анонимный прокси, без авторизации - тут ни чего указывать не надо. В качестве рекламы, качественный платный <a href="http://proxy-seller.ru/" target="_blank">прокси сервер</a>.</span>
                </td>
            </tr>
<?php } //условие есть ID или нет   ?>
    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="vkposter_idsoft, vkposter_token, vkposter_id,vkposter_userid, vkposter_friends_only, vkposter_from_group, vkposter_signed, vkposter_counttext, vkposter_onoff, vkposter_posttype,vkposter_proxy,vkposter_proxy_userpaswd" />
    <p class="submit">
        <input type="submit" class="btn btn-large btn-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>


