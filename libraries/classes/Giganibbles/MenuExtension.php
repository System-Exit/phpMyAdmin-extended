<?php
/**
 * Menu extension
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
namespace PhpMyAdmin\Giganibbles;

/**
 * Class MenuExtension
 * @package PhpMyAdmin\Giganibbles
 *
 * Used as a method for extending the menu. Methods in this class are used
 * throughout the rest of the program when the menu is set up, created, and
 * printed.
 */
class MenuExtension
{

    /**
     * Returns a list of tabs that are extended by the Message system plugin.
     * This function allows for easy extension of the menu that does not require
     * further editing to existing PMA files.
     *
     * This function is used by Menu::getMenu().
     * @see Menu::_getMenu()
     *
     * @param null $level 'server', 'db', or 'table'
     *
     * @return array
     */
    public static function getMenuTabs($level = null): array
    {
        $tabs = [];
        if ($level == 'server') {

            // message system
            $tabs['messages']['icon'] = 's_asci';
            $tabs['messages']['link'] = 'server_messages.php';
            $tabs['messages']['text'] = __('Messages');

            // user stats page
            $tabs['stats']['icon'] = 's_rights';
            $tabs['stats']['link'] = 'server_user_stats.php';
            $tabs['stats']['text'] = __('User Statistics');

        }
        return $tabs;
    }

    /**
     * Used to confirm that a menu item is acceptable. This is a weird security
     * feature that prevents menus from showing up when they are not desired.
     * This function allows for easy extension of the menu that does not require
     * further editing to existing PMA files.
     *
     * This function is used by Menu::getMenu().
     * @see Menu::_getMenu()
     *
     * @param null $level 'server', 'db', or 'table'
     *
     * @return array
     */
    public static function getAllowedTabs($level = null): array
    {
        $tablist = [];
        if ($level == 'server')
        {
            $tablist['messages'] = __('Messages');
            $tablist['stats'] = __('User Statistics');
        }
        return $tablist;
    }
}