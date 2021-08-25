<?php if (!defined('ABSPATH')) {
    exit;
} ?>
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a class="nav-tab <?php VKPOSTERBASE::adminActiveTab('general'); ?>" href="<?php echo add_query_arg( array( 'page' => VKPOSTERBASE::URL_SUB_MENU, 'tab' => 'general' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-cog"></span> Lite-Настройки</a>
        <a class="nav-tab <?php VKPOSTERBASE::adminActiveTab('progeneral'); ?>" href="<?php echo add_query_arg( array( 'page' => VKPOSTERBASE::URL_SUB_MENU, 'tab' => 'progeneral' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-asterisk"></span> PRO-Настройки</a>
        <a class="nav-tab <?php VKPOSTERBASE::adminActiveTab('jornal'); ?>" href="<?php echo add_query_arg( array( 'page' => VKPOSTERBASE::URL_SUB_MENU, 'tab' => 'jornal' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-wrench"></span> Журнал</a>
        <a class="nav-tab <?php VKPOSTERBASE::adminActiveTab('help'); ?>" href="<?php echo add_query_arg( array( 'page' => VKPOSTERBASE::URL_SUB_MENU, 'tab' => 'help' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-list"></span> Справка</a>
        <a class="nav-tab <?php VKPOSTERBASE::adminActiveTab('about'); ?>" href="<?php echo add_query_arg( array( 'page' => VKPOSTERBASE::URL_SUB_MENU, 'tab' => 'about' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-thumbs-up"></span> Автор</a>
    </h2>
    <?php VKPOSTERBASE::tabViwer();//Показать страницу в зависимости от закладки ?>

