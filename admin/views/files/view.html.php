<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

/**
 * View class for a list of files.
 */
class FilemanagerViewFiles extends JViewLegacy
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $sidebar;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->state = $this->get('State');
        
        // Get current folder
        $app = JFactory::getApplication();
        $this->folder = $app->input->getString('folder', '');
        
        // Get parent folder
        $this->parentFolder = '';
        if (!empty($this->folder))
        {
            $parts = explode('/', $this->folder);
            array_pop($parts);
            $this->parentFolder = implode('/', $parts);
        }

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();
        
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     */
    protected function addToolbar()
    {
        $canDo = JHelperContent::getActions('com_filemanager');

        JToolBarHelper::title(JText::_('COM_FILEMANAGER_MANAGER_FILES'), 'folder');

        if ($canDo->get('core.create'))
        {
            JToolBarHelper::custom('files.createFolder', 'folder-plus', '', 'COM_FILEMANAGER_TOOLBAR_NEW_FOLDER', false);
            JToolBarHelper::divider();
        }

        if ($canDo->get('core.delete'))
        {
            JToolBarHelper::deleteList('', 'files.delete', 'JTOOLBAR_DELETE');
            JToolBarHelper::divider();
        }

        if ($canDo->get('core.admin'))
        {
            JToolBarHelper::preferences('com_filemanager');
        }

        JToolBarHelper::help('', false, 'http://docs.joomla.org');
    }

    /**
     * Returns an array of fields the table can be sorted by
     */
    protected function getSortFields()
    {
        return array(
            'name' => JText::_('COM_FILEMANAGER_HEADING_NAME'),
            'type' => JText::_('COM_FILEMANAGER_HEADING_TYPE'),
            'size' => JText::_('COM_FILEMANAGER_HEADING_SIZE'),
            'modified' => JText::_('COM_FILEMANAGER_HEADING_MODIFIED')
        );
    }
}
