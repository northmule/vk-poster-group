<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<script type="text/javascript">

    jQuery("document").ready(function () {


        jQuery("#toltipstatus").tooltip({placement: 'bottom'});


    });


</script>

<h2>Журнал работы плагина</h2>

<?php
$vkposter_id = get_option('vkposter_id'); //ID группы или пользователя
$vkposter_friends_only = get_option('vkposter_friends_only'); //Доступность записи, 0 - всем
$vkposter_from_group = get_option('vkposter_from_group'); //От чьего имени публиковать
$vkposter_signed = get_option('vkposter_signed');
$vkposter_counttext = get_option('vkposter_counttext');
$vkposter_onoff = get_option('vkposter_onoff');
$vkposter_jornal = get_option('vkposter_jornal');

$vkposter_idsoft = get_option('vkposter_idsoft'); //ID приложения
$vkposter_token = get_option('vkposter_token'); //Токен приложения

$active_plugin = get_option('active_plugins'); //Активные плагины
//
$plugins_url = admin_url() . 'options-general.php?page='.VKPOSTERBASE::URL_SUB_MENU.'&tab=jornal'; //URL страницы плагина
$dir_plugin_absolut = plugin_dir_path(__FILE__);
?>
<h3>Активированные плагины</h3>

<table class="table table-bordered table-hover table-condensed">
    <thead>
        <tr>
            <th>Название плагина</th> 

        </tr>
    </thead>
    <tbody>
        <?php foreach ($active_plugin as $plug_name) { ?>
            <tr class="info">
                <th><?php echo $plug_name; ?></th>

            </tr>
        <?php } ?>
    </tbody>

</table>

<?php
if (isset($_GET['clearjornal'])) {
    update_option('vkposter_jornal', array());
    ?>
    <script type = "text/javascript">
        document.location.href = "<?php echo $plugins_url; ?>";
    </script>
    <?php
}
?>

<a class="btn btn-primary" href="<?php echo $plugins_url . '&clearjornal'; ?>">Очистить журнал</a>

<h3>Журнал отправленных записей на стену VK</h3>

<table class="table table-bordered table-hover table-condensed">
    <thead>
        <tr>
            <th>Дата и время добавления</th> 
            <th>Номер записи (id поста по Wordpress)</th>
            <th>Заголовок записи</th>
            <th>Ответ сервера ВК (статус добавления)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($vkposter_jornal as $jornalprint) { ?>
            <tr class="info">
                <th><?php echo $jornalprint['time']; ?></th>
                <th><?php echo $jornalprint['idpost']; ?></th>
                <th><?php echo $jornalprint['title']; ?></th>
                <th><?php echo $jornalprint['status']; ?></th>
            </tr>
        <?php } ?>
    </tbody>



</table>

