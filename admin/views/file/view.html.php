<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

/**
 * View to edit a file.
 */
class FilemanagerViewFile extends JViewLegacy
{
    protected $form;
    protected $item;
    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     */
    protected function addToolbar()
    {
        JFactory::getApplication()->input->set('hidemainmenu', true);

        $isNew = empty($this->item->filename);

        JToolBarHelper::title(JText::sprintf('COM_FILEMANAGER_MANAGER_FILE_EDIT', $this->item->filename), 'file-2');

        JToolBarHelper::apply('file.apply', 'JTOOLBAR_APPLY');
        JToolBarHelper::save('file.save', 'JTOOLBAR_SAVE');
        JToolBarHelper::cancel('file.cancel', 'JTOOLBAR_CLOSE');
    }
}
