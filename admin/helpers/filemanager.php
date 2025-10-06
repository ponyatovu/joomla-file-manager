<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

/**
 * Filemanager component helper.
 */
abstract class FilemanagerHelper
{
    /**
     * Configure the Linkbar.
     */
    public static function addSubmenu($vName = 'files')
    {
        JHtmlSidebar::addEntry(
            JText::_('COM_FILEMANAGER_MANAGER_FILES'),
            'index.php?option=com_filemanager&view=files',
            $vName == 'files'
        );
    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return  JObject
     */
    public static function getActions()
    {
        $user   = JFactory::getUser();
        $result = new JObject;

        $actions = array(
            'core.admin',
            'core.manage',
            'core.create',
            'core.edit',
            'core.delete',
        );

        foreach ($actions as $action)
        {
            $result->set($action, $user->authorise($action, 'com_filemanager'));
        }

        return $result;
    }
}
