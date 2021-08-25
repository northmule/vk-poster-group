<?php
use Coderun\VkPoster\CorePlugin;
use Coderun\VkPoster\Services;

if (!defined('ABSPATH')) {
    exit;
}
?>

<h2>Журнал работы плагина</h2>

<?php

$optionsName = CorePlugin::PREF_PLG;
$plugin = CorePlugin::getInstance();
$jornal = $plugin->getOptions('vkposter_jornal');

?>

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
    <?php foreach ($jornal as $jornalprint) { ?>
        <tr class="info">
            <th><?php echo $jornalprint['time']; ?></th>
            <th><?php echo $jornalprint['idpost']; ?></th>
            <th><?php echo $jornalprint['title']; ?></th>
            <th><?php echo $jornalprint['status']; ?></th>
            <th><?php echo isset($jornalprint['url']) ? sprintf('<a href="%s" target="_blank">тынс</a>', $jornalprint['url']) : ''; ?></th>
        </tr>
    <?php } ?>
    </tbody>
</table>

