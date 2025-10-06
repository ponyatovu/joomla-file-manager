<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Methods supporting a list of files.
 */
class FilemanagerModelFiles extends JModelList
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'name', 'type', 'size', 'modified'
            );
        }

        parent::__construct($config);
    }

    /**
     * Get the list of files and folders
     */
    public function getItems()
    {
        $app = JFactory::getApplication();
        $folder = $app->input->getString('folder', '');
        
        // Get root path
        $basePath = JPATH_ROOT;
        $currentPath = $basePath . ($folder ? DIRECTORY_SEPARATOR . $folder : '');
        
        // Security check - prevent directory traversal
        $realPath = realpath($currentPath);
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
            return array();
        }

        if (!is_dir($realPath))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_FOLDER_NOT_FOUND'), 'error');
            return array();
        }

        $items = array();
        
        // Get folders
        $folders = JFolder::folders($realPath, '.', false, false);
        foreach ($folders as $folderName)
        {
            $folderPath = $realPath . DIRECTORY_SEPARATOR . $folderName;
            $items[] = (object) array(
                'name' => $folderName,
                'path' => $folder ? $folder . '/' . $folderName : $folderName,
                'type' => 'folder',
                'size' => '-',
                'modified' => date('Y-m-d H:i:s', filemtime($folderPath)),
                'permissions' => substr(sprintf('%o', fileperms($folderPath)), -4)
            );
        }

        // Get files
        $files = JFolder::files($realPath, '.', false, false);
        foreach ($files as $fileName)
        {
            $filePath = $realPath . DIRECTORY_SEPARATOR . $fileName;
            $fileSize = filesize($filePath);
            
            $items[] = (object) array(
                'name' => $fileName,
                'path' => $folder ? $folder . '/' . $fileName : $fileName,
                'type' => 'file',
                'size' => $this->formatBytes($fileSize),
                'size_raw' => $fileSize,
                'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
                'extension' => JFile::getExt($fileName)
            );
        }

        // Sort items
        $listOrder = $this->getState('list.ordering', 'name');
        $listDirn = $this->getState('list.direction', 'ASC');
        
        usort($items, function($a, $b) use ($listOrder, $listDirn) {
            // Folders first
            if ($a->type != $b->type) {
                return $a->type == 'folder' ? -1 : 1;
            }
            
            $result = 0;
            switch ($listOrder) {
                case 'size':
                    $aVal = isset($a->size_raw) ? $a->size_raw : 0;
                    $bVal = isset($b->size_raw) ? $b->size_raw : 0;
                    $result = $aVal - $bVal;
                    break;
                case 'modified':
                    $result = strcmp($a->modified, $b->modified);
                    break;
                case 'name':
                default:
                    $result = strcasecmp($a->name, $b->name);
                    break;
            }
            
            return $listDirn == 'DESC' ? -$result : $result;
        });

        return $items;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Delete files and folders
     */
    public function delete($paths, $currentFolder)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        foreach ($paths as $path)
        {
            $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $realPath = realpath($fullPath);
            
            // Security check
            if ($realPath === false || strpos($realPath, $basePath) !== 0)
            {
                $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
                continue;
            }

            if (is_dir($realPath))
            {
                if (!JFolder::delete($realPath))
                {
                    $app->enqueueMessage(JText::sprintf('COM_FILEMANAGER_ERROR_DELETE_FOLDER', basename($path)), 'error');
                    return false;
                }
            }
            else
            {
                if (!JFile::delete($realPath))
                {
                    $app->enqueueMessage(JText::sprintf('COM_FILEMANAGER_ERROR_DELETE_FILE', basename($path)), 'error');
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create a new folder
     */
    public function createFolder($currentFolder, $newFolder)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        // Sanitize folder name
        $newFolder = JFile::makeSafe($newFolder);
        
        $fullPath = $basePath . ($currentFolder ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentFolder) : '') . DIRECTORY_SEPARATOR . $newFolder;
        
        // Security check
        $parentPath = dirname($fullPath);
        $realParentPath = realpath($parentPath);
        
        if ($realParentPath === false || strpos($realParentPath, $basePath) !== 0)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
            return false;
        }

        if (file_exists($fullPath))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_FOLDER_EXISTS'), 'error');
            return false;
        }

        if (!JFolder::create($fullPath))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_CREATE_FOLDER'), 'error');
            return false;
        }

        return true;
    }

    /**
     * Upload files
     */
    public function upload($currentFolder, $files)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $uploadPath = $basePath . ($currentFolder ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentFolder) : '');
        
        // Security check
        $realUploadPath = realpath($uploadPath);
        if ($realUploadPath === false || strpos($realUploadPath, $basePath) !== 0)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
            return false;
        }

        $count = count($files['name']);
        
        for ($i = 0; $i < $count; $i++)
        {
            if ($files['error'][$i] != 0)
            {
                $app->enqueueMessage(JText::sprintf('COM_FILEMANAGER_ERROR_UPLOAD_FILE', $files['name'][$i]), 'error');
                continue;
            }

            $filename = JFile::makeSafe($files['name'][$i]);
            $src = $files['tmp_name'][$i];
            $dest = $uploadPath . DIRECTORY_SEPARATOR . $filename;

            if (!JFile::upload($src, $dest))
            {
                $app->enqueueMessage(JText::sprintf('COM_FILEMANAGER_ERROR_UPLOAD_FILE', $filename), 'error');
                return false;
            }
        }

        return true;
    }

    /**
     * Get file content for preview
     */
    public function getFileContent($filePath)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $realPath = realpath($fullPath);
        
        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'));
        }
        
        if (!file_exists($realPath) || !is_file($realPath))
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_FILE_NOT_FOUND'));
        }
        
        $size = filesize($realPath);
        
        // Limit preview to 1MB
        if ($size > 1048576)
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_FILE_TOO_LARGE'));
        }
        
        $content = file_get_contents($realPath);
        
        return array('success' => true, 'data' => array('content' => $content));
    }
    
    /**
     * Get file information
     */
    public function getFileInfo($filePath)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $realPath = realpath($fullPath);
        
        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'));
        }
        
        if (!file_exists($realPath))
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_FILE_NOT_FOUND'));
        }
        
        $info = array(
            'name' => basename($realPath),
            'path' => str_replace($basePath . DIRECTORY_SEPARATOR, '', $realPath),
            'size' => $this->formatBytes(filesize($realPath)),
            'type' => mime_content_type($realPath),
            'modified' => date('Y-m-d H:i:s', filemtime($realPath)),
            'permissions' => substr(sprintf('%o', fileperms($realPath)), -4)
        );
        
        return array('success' => true, 'data' => $info);
    }
    
    /**
     * Get folder information
     */
    public function getFolderInfo($folderPath)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $fullPath = $basePath . ($folderPath ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folderPath) : '');
        $realPath = realpath($fullPath);
        
        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'));
        }
        
        if (!is_dir($realPath))
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_FOLDER_NOT_FOUND'));
        }
        
        $files = JFolder::files($realPath);
        $folders = JFolder::folders($realPath);
        
        $info = array(
            'name' => basename($realPath),
            'path' => str_replace($basePath . DIRECTORY_SEPARATOR, '', $realPath),
            'filesCount' => count($files),
            'foldersCount' => count($folders),
            'modified' => date('Y-m-d H:i:s', filemtime($realPath)),
            'permissions' => substr(sprintf('%o', fileperms($realPath)), -4)
        );
        
        return array('success' => true, 'data' => $info);
    }
    
    /**
     * Change file/folder permissions
     */
    public function changePermissions($path, $permissions)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $realPath = realpath($fullPath);
        
        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'));
        }
        
        if (!file_exists($realPath))
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_NOT_FOUND'));
        }
        
        // Validate permissions format
        if (!preg_match('/^[0-7]{3,4}$/', $permissions))
        {
            return array('success' => false, 'message' => JText::_('COM_FILEMANAGER_ERROR_INVALID_PERMISSIONS'));
        }
        
        // Convert octal string to decimal
        // Ensure we have proper octal conversion by prefixing with 0
        $mode = octdec(str_pad($permissions, 4, '0', STR_PAD_LEFT));
        
        if (!@chmod($realPath, $mode))
        {
            // Get detailed error
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : JText::_('COM_FILEMANAGER_ERROR_CHANGE_PERMISSIONS');
            return array('success' => false, 'message' => $errorMsg);
        }
        
        return array('success' => true, 'message' => JText::_('COM_FILEMANAGER_PERMISSIONS_CHANGED'));
    }
    
    /**
     * Download file
     */
    public function downloadFile($filePath)
    {
        $app = JFactory::getApplication();
        $basePath = JPATH_ROOT;
        
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        $realPath = realpath($fullPath);
        
        // Security check
        if ($realPath === false || strpos($realPath, $basePath) !== 0)
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_INVALID_PATH'), 'error');
            return;
        }
        
        if (!file_exists($realPath) || !is_file($realPath))
        {
            $app->enqueueMessage(JText::_('COM_FILEMANAGER_ERROR_FILE_NOT_FOUND'), 'error');
            return;
        }
        
        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($realPath));
        
        ob_clean();
        flush();
        readfile($realPath);
        exit;
    }

    /**
     * Search for files and folders in current directory
     *
     * @param   string  $query     Search query
     * @param   string  $folder    Current folder path
     * @param   string  $dateFrom  Modified date from (Y-m-d format)
     * @param   string  $dateTo    Modified date to (Y-m-d format)
     * @return  array   Array of found items
     */
    public function searchFiles($query, $folder = '', $dateFrom = '', $dateTo = '')
    {
        if (empty($query) || strlen($query) < 2)
        {
            return array();
        }

        $basePath = JPATH_ROOT;
        
        // Convert date filters to timestamps
        $timestampFrom = 0;
        $timestampTo = PHP_INT_MAX;
        
        if (!empty($dateFrom))
        {
            $timestampFrom = strtotime($dateFrom . ' 00:00:00');
            if ($timestampFrom === false)
            {
                $timestampFrom = 0;
            }
        }
        
        if (!empty($dateTo))
        {
            $timestampTo = strtotime($dateTo . ' 23:59:59');
            if ($timestampTo === false)
            {
                $timestampTo = PHP_INT_MAX;
            }
        }
        
        // Determine search start directory (current folder or root)
        if (!empty($folder))
        {
            $currentPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
            $realCurrentPath = realpath($currentPath);
            
            // Security check
            if ($realCurrentPath === false || strpos($realCurrentPath, $basePath) !== 0)
            {
                return array();
            }
            
            $searchBasePath = $realCurrentPath;
            $searchBaseRelative = $folder;
        }
        else
        {
            $searchBasePath = $basePath;
            $searchBaseRelative = '';
        }
        
        $results = array();
        $query = strtolower($query);
        
        // Recursive search function
        $searchInDirectory = function($dir, $relativePath = '') use (&$searchInDirectory, $basePath, $query, &$results, $timestampFrom, $timestampTo) {
            // Security check
            $realDir = realpath($dir);
            if ($realDir === false || strpos($realDir, $basePath) !== 0)
            {
                return;
            }

            // Skip certain directories
            $skipDirs = array('.git', '.svn', 'cache', 'tmp', 'logs');
            
            try
            {
                $items = @scandir($dir);
                if ($items === false)
                {
                    return;
                }

                foreach ($items as $item)
                {
                    if ($item === '.' || $item === '..')
                    {
                        continue;
                    }

                    $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
                    $itemRelativePath = $relativePath . ($relativePath ? '/' : '') . $item;
                    
                    // Skip system directories
                    $skip = false;
                    foreach ($skipDirs as $skipDir)
                    {
                        if ($item === $skipDir || strpos($itemRelativePath, $skipDir . '/') !== false)
                        {
                            $skip = true;
                            break;
                        }
                    }
                    
                    if ($skip)
                    {
                        continue;
                    }
                    
                    // Get modification time
                    $modifiedTime = @filemtime($fullPath);
                    
                    // Check if name matches search query (partial match with wildcards)
                    // Example: searching "test" will match "test.php", "mytest.txt", "test_file.js"
                    if (stripos($item, $query) !== false)
                    {
                        // Apply date filters
                        if ($modifiedTime < $timestampFrom || $modifiedTime > $timestampTo)
                        {
                            // Skip files that don't match date range
                            // But still search in subdirectories
                            if (is_dir($fullPath) && count($results) < 100)
                            {
                                $searchInDirectory($fullPath, $itemRelativePath);
                            }
                            continue;
                        }
                        
                        $isDir = is_dir($fullPath);
                        
                        // Get parent folder path
                        $folderPath = $relativePath;
                        
                        $results[] = array(
                            'name' => $item,
                            'type' => $isDir ? 'folder' : 'file',
                            'path' => $itemRelativePath,
                            'folder' => $folderPath ? $folderPath : '',
                            'size' => $isDir ? 0 : @filesize($fullPath),
                            'modified' => $modifiedTime
                        );
                        
                        // Limit results to prevent memory issues
                        if (count($results) >= 100)
                        {
                            return;
                        }
                    }

                    // Recursively search subdirectories (but not if it's a file)
                    if (is_dir($fullPath) && count($results) < 100)
                    {
                        $searchInDirectory($fullPath, $itemRelativePath);
                    }
                }
            }
            catch (Exception $e)
            {
                // Silently skip directories with access errors
            }
        };

        // Start search from current directory
        $searchInDirectory($searchBasePath, $searchBaseRelative);

        return $results;
    }

    /**
     * Method to auto-populate the model state.
     */
    protected function populateState($ordering = null, $direction = null)
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        parent::populateState('name', 'asc');
    }
}
