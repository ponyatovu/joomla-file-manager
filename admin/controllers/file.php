<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

/**
 * File controller class.
 */
class FilemanagerControllerFile extends JControllerForm
{
    /**
     * Method to save a file.
     */
    public function save($key = null, $urlVar = null)
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $data = $this->input->post->get('jform', array(), 'array');
        $folder = $this->input->getString('folder', '');

        $model = $this->getModel('File');
        $result = $model->save($data, $folder);

        if ($result)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_FILE_SAVED'), 'message');
        }

        $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
    }

    /**
     * Method to cancel an edit.
     */
    public function cancel($key = null)
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $folder = $this->input->getString('folder', '');
        $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
    }
}
