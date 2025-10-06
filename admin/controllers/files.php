<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

/**
 * Files list controller class.
 */
class FilemanagerControllerFiles extends JControllerAdmin
{
    /**
     * Method to delete files
     */
    public function delete()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $paths = $this->input->get('cid', array(), 'array');
        $folder = $this->input->getString('folder', '');

        if (empty($paths))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_NO_FILES_SELECTED'), 'warning');
            $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
            return false;
        }

        $model = $this->getModel('Files');
        $result = $model->delete($paths, $folder);

        if ($result)
        {
            $app->enqueueMessage(JText::plural('COM_FILEMANAGER_N_ITEMS_DELETED', count($paths)), 'message');
        }

        $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
    }

    /**
     * Method to create a new folder
     */
    public function createFolder()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $folder = $this->input->getString('folder', '');
        $newFolder = $this->input->getString('new_folder', '');

        if (empty($newFolder))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_FOLDER_NAME_EMPTY'), 'warning');
            $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
            return false;
        }

        $model = $this->getModel('Files');
        $result = $model->createFolder($folder, $newFolder);

        if ($result)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_FOLDER_CREATED'), 'message');
        }

        $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
    }

    /**
     * Method to upload files
     */
    public function upload()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $folder = $this->input->getString('folder', '');
        
        // Получаем файлы напрямую из $_FILES для надежности
        $files = isset($_FILES['files']) ? $_FILES['files'] : null;

        // Проверка наличия выбранных файлов
        if (empty($files) || !isset($files['name']) || !is_array($files['name']) || 
            empty($files['name']) || (count($files['name']) == 1 && empty($files['name'][0])))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_NO_FILES_SELECTED'), 'warning');
            $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
            return false;
        }

        $model = $this->getModel('Files');
        $result = $model->upload($folder, $files);

        if ($result)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_FILES_UPLOADED'), 'message');
        }

        $this->setRedirect('index.php?option=com_filemanager&view=files&folder=' . urlencode($folder));
    }
    
    /**
     * Get file content via AJAX
     */
    public function getFileContent()
    {
        JSession::checkToken() or jexit(json_encode(array('success' => false, 'message' => JText::_('JINVALID_TOKEN'))));
        
        $app = JFactory::getApplication();
        $filePath = $this->input->getString('file', '');
        
        try {
            $model = $this->getModel('Files');
            
            if (!$model) {
                echo json_encode(array('success' => false, 'message' => 'Model not found'));
                $app->close();
                return;
            }
            
            $result = $model->getFileContent($filePath);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Exception: ' . $e->getMessage()));
        }
        
        $app->close();
    }
    
    /**
     * Get file info via AJAX
     */
    public function getFileInfo()
    {
        JSession::checkToken() or jexit(json_encode(array('success' => false, 'message' => JText::_('JINVALID_TOKEN'))));
        
        $app = JFactory::getApplication();
        $filePath = $this->input->getString('file', '');
        
        try {
            $model = $this->getModel('Files');
            $result = $model->getFileInfo($filePath);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Exception: ' . $e->getMessage()));
        }
        
        $app->close();
    }
    
    /**
     * Get folder info via AJAX
     */
    public function getFolderInfo()
    {
        JSession::checkToken() or jexit(json_encode(array('success' => false, 'message' => JText::_('JINVALID_TOKEN'))));
        
        $app = JFactory::getApplication();
        $folderPath = $this->input->getString('folder', '');
        
        try {
            $model = $this->getModel('Files');
            $result = $model->getFolderInfo($folderPath);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Exception: ' . $e->getMessage()));
        }
        
        $app->close();
    }
    
    /**
     * Change file/folder permissions via AJAX
     */
    public function changePermissions()
    {
        JSession::checkToken() or jexit(json_encode(array('success' => false, 'message' => JText::_('JINVALID_TOKEN'))));
        
        $app = JFactory::getApplication();
        $path = $this->input->getString('path', '');
        $type = $this->input->getString('type', '');
        $permissions = $this->input->getString('permissions', '');
        
        try {
            $model = $this->getModel('Files');
            // Передаём только path и permissions, type не используется
            $result = $model->changePermissions($path, $permissions);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Exception: ' . $e->getMessage()));
        }
        
        $app->close();
    }
    
    /**
     * Download file
     */
    public function download()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        
        $app = JFactory::getApplication();
        $filePath = $this->input->getString('file', '');
        
        $model = $this->getModel('Files');
        $model->downloadFile($filePath);
    }

    /**
     * Search files and folders
     */
    public function search()
    {
        JSession::checkToken() or jexit(json_encode(array('success' => false, 'message' => JText::_('JINVALID_TOKEN'))));
        
        $app = JFactory::getApplication();
        $query = $this->input->getString('query', '');
        $folder = $this->input->getString('folder', '');
        $dateFrom = $this->input->getString('date_from', '');
        $dateTo = $this->input->getString('date_to', '');
        
        try {
            $model = $this->getModel('Files');
            $results = $model->searchFiles($query, $folder, $dateFrom, $dateTo);
            
            header('Content-Type: application/json');
            echo json_encode(array(
                'success' => true,
                'data' => $results
            ));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'message' => 'Exception: ' . $e->getMessage()));
        }
        
        $app->close();
    }
}
