<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

/**
 * File model.
 */
class FilemanagerModelFile extends JModelAdmin
{
    /**
     * Method to get the record form.
     */
    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm('com_filemanager.file', 'file', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form))
        {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     */
    protected function loadFormData()
    {
        $data = JFactory::getApplication()->getUserState('com_filemanager.edit.file.data', array());

        if (empty($data))
        {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to get a single record.
     */
    public function getItem($pk = null)
    {
        $app = JFactory::getApplication();
        $filePath = $app->input->getString('file', '');
        
        if (empty($filePath))
        {
            return new JObject();
        }

        $basePath = JPATH_ROOT;
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $realPath = realpath($fullPath);

        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
            return new JObject();
        }

        if (!file_exists($realPath) || !is_file($realPath))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_FILE_NOT_FOUND'), 'error');
            return new JObject();
        }

        $item = new JObject();
        $item->filename = basename($realPath);
        $item->path = $filePath;
        $item->folder = dirname($filePath);
        if ($item->folder == '.') {
            $item->folder = '';
        }
        $item->content = file_get_contents($realPath);

        return $item;
    }

    /**
     * Method to save the form data.
     */
    public function save($data, $folder)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $filePath = $data['path'];
        $content = $data['content'];
        
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $realPath = realpath(dirname($fullPath));

        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
            return false;
        }

        if (!JFile::write($fullPath, $content))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_SAVE_FILE'), 'error');
            return false;
        }

        return true;
    }

    /**
     * Method to get the table.
     */
    public function getTable($type = 'File', $prefix = 'FilemanagerTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }
}
