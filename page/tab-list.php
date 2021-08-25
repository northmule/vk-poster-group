<?php if (!defined('ABSPATH')) {
    exit;
}
$plugin = \Coderun\VkPoster\CorePlugin::getInstance();
?>
<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
    <?php foreach ($plugin->get_plugin_admin_pages() as $num => $tab) { ?>
        <a class="nav-tab <?php $plugin->active_tab_page($num); ?>"
           href="<?php echo add_query_arg(array('page' => $plugin->get_url_admin_page(), 'tab' => $tab['uri']), 'admin.php'); ?>"
        ><span class="<?php echo $tab['icon'] ?>"></span>
            <?php echo $tab['name']; ?>
        </a>
    <?php } ?>
</h2>
<?php
$plugin->view_tab_page();



