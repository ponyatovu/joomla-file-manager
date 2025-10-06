<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo JRoute::_('index.php?option=com_filemanager&view=files'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
    <?php if (!empty($this->sidebar)) : ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
    <?php else : ?>
    <div id="j-main-container">
    <?php endif; ?>

        <!-- Breadcrumbs -->
        <div class="alert alert-info">
            <strong><?php echo JText::_('COM_FILEMANAGER_CURRENT_PATH'); ?>:</strong>
            <a href="index.php?option=com_filemanager&view=files"><?php echo JText::_('COM_FILEMANAGER_ROOT'); ?></a>
            <?php if (!empty($this->folder)): ?>
                <?php
                $parts = explode('/', $this->folder);
                $path = '';
                foreach ($parts as $part):
                    $path .= ($path ? '/' : '') . $part;
                ?>
                    / <a href="index.php?option=com_filemanager&view=files&folder=<?php echo urlencode($path); ?>"><?php echo $this->escape($part); ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Toolbar -->
        <div class="well well-small">
            <div class="row-fluid">
                <!-- Search -->
                <div class="span4">
                    <div class="input-append">
                        <input type="text" id="search-query" placeholder="<?php echo JText::_('COM_FILEMANAGER_SEARCH_PLACEHOLDER'); ?>" class="input-medium" />
                        <button type="button" class="btn btn-info" onclick="searchFiles()">
                            <i class="icon-search"></i> <?php echo JText::_('COM_FILEMANAGER_SEARCH'); ?>
                        </button>
                    </div>
                </div>

                <!-- Upload Files -->
                <div class="span4">
                    <div class="input-append">
                        <input type="file" name="files[]" multiple="multiple" id="file-upload" style="display:inline-block; width:auto;" />
                        <button type="button" class="btn btn-primary" onclick="validateAndUpload()">
                            <i class="icon-upload"></i> <?php echo JText::_('COM_FILEMANAGER_UPLOAD'); ?>
                        </button>
                    </div>
                </div>

                <!-- Create Folder -->
                <div class="span4">
                    <div class="input-append">
                        <input type="text" name="new_folder" id="new_folder" placeholder="<?php echo JText::_('COM_FILEMANAGER_FOLDER_NAME'); ?>" class="input-medium" />
                        <button type="button" class="btn btn-success" onclick="Joomla.submitbutton('files.createFolder')">
                            <i class="icon-folder-close"></i> <?php echo JText::_('COM_FILEMANAGER_CREATE'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Search Filters -->
            <div class="row-fluid" style="margin-top: 10px;">
                <div class="span12">
                    <a href="#" onclick="toggleAdvancedSearch(); return false;" class="btn btn-mini">
                        <i class="icon-filter"></i> <?php echo JText::_('COM_FILEMANAGER_ADVANCED_SEARCH'); ?>
                    </a>
                </div>
            </div>
            
            <div id="advanced-search" style="display:none; margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 3px;">
                <div class="row-fluid">
                    <div class="span6">
                        <label><?php echo JText::_('COM_FILEMANAGER_SEARCH_DATE_FROM'); ?>:</label>
                        <input type="date" id="search-date-from" class="input-medium" />
                        <small class="muted"><?php echo JText::_('COM_FILEMANAGER_SEARCH_DATE_FROM_HELP'); ?></small>
                    </div>
                    <div class="span6">
                        <label><?php echo JText::_('COM_FILEMANAGER_SEARCH_DATE_TO'); ?>:</label>
                        <input type="date" id="search-date-to" class="input-medium" />
                        <small class="muted"><?php echo JText::_('COM_FILEMANAGER_SEARCH_DATE_TO_HELP'); ?></small>
                    </div>
                </div>
                <div class="row-fluid" style="margin-top: 10px;">
                    <div class="span12">
                        <button type="button" class="btn btn-small" onclick="clearDateFilters()">
                            <i class="icon-remove"></i> <?php echo JText::_('COM_FILEMANAGER_CLEAR_FILTERS'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div id="search-results" style="display:none;" class="alert alert-success">
            <button type="button" class="close" onclick="closeSearchResults()">&times;</button>
            <h4><i class="icon-search"></i> <?php echo JText::_('COM_FILEMANAGER_SEARCH_RESULTS'); ?></h4>
            <div id="search-results-content"></div>
        </div>

        <!-- Files Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th width="1%" class="hidden-phone">
                        <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
                    </th>
                    <th class="title">
                        <?php echo JText::_('COM_FILEMANAGER_HEADING_NAME'); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JText::_('COM_FILEMANAGER_HEADING_TYPE'); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JText::_('COM_FILEMANAGER_HEADING_SIZE'); ?>
                    </th>
                    <th width="15%" class="nowrap hidden-phone">
                        <?php echo JText::_('COM_FILEMANAGER_HEADING_MODIFIED'); ?>
                    </th>
                    <th width="10%" class="nowrap hidden-phone">
                        <?php echo JText::_('COM_FILEMANAGER_HEADING_PERMISSIONS'); ?>
                    </th>
                    <th width="15%" class="nowrap">
                        <?php echo JText::_('COM_FILEMANAGER_HEADING_ACTIONS'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($this->folder)): ?>
                <tr>
                    <td></td>
                    <td>
                        <a href="index.php?option=com_filemanager&view=files&folder=<?php echo urlencode($this->parentFolder); ?>">
                            <i class="icon-folder-close"></i> ..
                        </a>
                    </td>
                    <td colspan="4"></td>
                </tr>
                <?php endif; ?>

                <?php foreach ($this->items as $i => $item): ?>
                <tr class="row<?php echo $i % 2; ?>">
                    <td class="center hidden-phone">
                        <?php echo JHtml::_('grid.id', $i, $item->path); ?>
                    </td>
                    <td>
                        <?php if ($item->type == 'folder'): ?>
                            <a href="index.php?option=com_filemanager&view=files&folder=<?php echo urlencode($item->path); ?>">
                                <i class="icon-folder-close"></i> <?php echo $this->escape($item->name); ?>
                            </a>
                        <?php else: ?>
                            <a href="#" class="file-view-link" data-filepath="<?php echo $this->escape($item->path); ?>" data-filename="<?php echo $this->escape($item->name); ?>">
                                <i class="icon-file"></i> <?php echo $this->escape($item->name); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="hidden-phone">
                        <?php if ($item->type == 'folder'): ?>
                            <?php echo JText::_('COM_FILEMANAGER_TYPE_FOLDER'); ?>
                        <?php else: ?>
                            <?php echo strtoupper($item->extension); ?>
                        <?php endif; ?>
                    </td>
                    <td class="hidden-phone">
                        <?php echo $item->size; ?>
                    </td>
                    <td class="hidden-phone">
                        <?php echo $item->modified; ?>
                    </td>
                    <td class="hidden-phone">
                        <?php echo $item->permissions; ?>
                    </td>
                    <td class="center nowrap">
                        <div class="btn-group">
                            <?php if ($item->type == 'folder'): ?>
                                <!-- Folder actions -->
                                <a href="index.php?option=com_filemanager&view=files&folder=<?php echo urlencode($item->path); ?>" 
                                   class="btn btn-mini" title="<?php echo JText::_('COM_FILEMANAGER_ACTION_OPEN'); ?>">
                                    <i class="icon-folder-open"></i>
                                </a>
                                <a href="#" class="btn btn-mini folder-info-btn" data-folderpath="<?php echo $this->escape($item->path); ?>"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_INFO'); ?>">
                                    <i class="icon-info"></i>
                                </a>
                                <a href="#" class="btn btn-mini permissions-btn" data-itempath="<?php echo $this->escape($item->path); ?>" data-itemtype="folder"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_PERMISSIONS'); ?>">
                                    <i class="icon-lock"></i>
                                </a>
                                <a href="#" class="btn btn-mini btn-danger delete-btn" data-itempath="<?php echo $this->escape($item->path); ?>"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_DELETE'); ?>">
                                    <i class="icon-trash"></i>
                                </a>
                            <?php else: ?>
                                <!-- File actions -->
                                <a href="#" class="btn btn-mini file-view-btn" data-filepath="<?php echo $this->escape($item->path); ?>" data-filename="<?php echo $this->escape($item->name); ?>"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_VIEW'); ?>">
                                    <i class="icon-eye-open"></i>
                                </a>
                                <a href="index.php?option=com_filemanager&view=file&layout=edit&file=<?php echo urlencode($item->path); ?>&folder=<?php echo urlencode($this->folder); ?>" 
                                   class="btn btn-mini" title="<?php echo JText::_('COM_FILEMANAGER_ACTION_EDIT'); ?>">
                                    <i class="icon-edit"></i>
                                </a>
                                <a href="#" class="btn btn-mini download-btn" data-filepath="<?php echo $this->escape($item->path); ?>"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_DOWNLOAD'); ?>">
                                    <i class="icon-download"></i>
                                </a>
                                <a href="#" class="btn btn-mini file-info-btn" data-filepath="<?php echo $this->escape($item->path); ?>"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_INFO'); ?>">
                                    <i class="icon-info"></i>
                                </a>
                                <a href="#" class="btn btn-mini permissions-btn" data-itempath="<?php echo $this->escape($item->path); ?>" data-itemtype="file"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_PERMISSIONS'); ?>">
                                    <i class="icon-lock"></i>
                                </a>
                                <a href="#" class="btn btn-mini btn-danger delete-btn" data-itempath="<?php echo $this->escape($item->path); ?>"
                                   title="<?php echo JText::_('COM_FILEMANAGER_ACTION_DELETE'); ?>">
                                    <i class="icon-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="folder" value="<?php echo $this->escape($this->folder); ?>" />
        <input type="hidden" name="boxchecked" value="0" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>

<!-- Modal for file content view -->
<div id="fileViewModal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true" style="width: 80%; margin-left: -40%; max-height: 80%;">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="fileViewModalTitle"><?php echo JText::_('COM_FILEMANAGER_FILE_CONTENT'); ?></h3>
    </div>
    <div class="modal-body" style="max-height: 500px; overflow: auto;">
        <pre id="fileViewContent" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
    </div>
</div>

<!-- Modal for file/folder info -->
<div id="infoModal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="infoModalTitle"><?php echo JText::_('COM_FILEMANAGER_INFO'); ?></h3>
    </div>
    <div class="modal-body">
        <div id="infoContent"></div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
    </div>
</div>

<!-- Modal for permissions -->
<div id="permissionsModal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3><?php echo JText::_('COM_FILEMANAGER_CHANGE_PERMISSIONS'); ?></h3>
    </div>
    <div class="modal-body">
        <form id="permissionsForm" class="form-horizontal">
            <input type="hidden" id="perm_path" name="path" value="" />
            <input type="hidden" id="perm_type" name="type" value="" />
            <div class="control-group">
                <label class="control-label"><?php echo JText::_('COM_FILEMANAGER_PERMISSIONS_VALUE'); ?></label>
                <div class="controls">
                    <input type="text" id="perm_value" name="permissions" placeholder="0755" maxlength="4" />
                    <span class="help-inline"><?php echo JText::_('COM_FILEMANAGER_PERMISSIONS_HELP'); ?></span>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
        <button class="btn btn-primary" onclick="savePermissions()"><?php echo JText::_('JAPPLY'); ?></button>
    </div>
</div>

<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        if (task == 'files.createFolder') {
            var newFolder = document.getElementById('new_folder').value;
            if (newFolder == '') {
                alert('<?php echo JText::_('COM_FILEMANAGER_ERROR_FOLDER_NAME_EMPTY', true); ?>');
                return false;
            }
        }
        Joomla.submitform(task, document.getElementById('adminForm'));
    }

    function viewFileContent(filePath, fileName) {
        console.log('viewFileContent called with:', filePath, fileName);
        
        document.getElementById('fileViewModalTitle').textContent = fileName;
        document.getElementById('fileViewContent').textContent = '<?php echo JText::_('COM_FILEMANAGER_LOADING', true); ?>...';
        
        jQuery('#fileViewModal').modal('show');
        
        var requestData = {
            file: filePath,
            '<?php echo JSession::getFormToken(); ?>': 1
        };
        
        console.log('Sending AJAX request:', requestData);
        
        jQuery.ajax({
            url: 'index.php?option=com_filemanager&task=files.getFileContent',
            method: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                console.log('AJAX success, response:', response);
                if (response && response.success) {
                    document.getElementById('fileViewContent').textContent = response.data.content;
                } else {
                    var errorMsg = (response && response.message) ? response.message : '<?php echo JText::_('COM_FILEMANAGER_ERROR_LOADING_FILE', true); ?>';
                    console.error('Response error:', errorMsg);
                    document.getElementById('fileViewContent').textContent = errorMsg;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                console.error('Status Code:', jqXHR.status);
                console.error('Response Text:', jqXHR.responseText);
                console.error('Response Headers:', jqXHR.getAllResponseHeaders());
                
                var debugInfo = 'Status: ' + jqXHR.status + '\n';
                debugInfo += 'Error: ' + textStatus + '\n';
                debugInfo += 'Response: ' + jqXHR.responseText.substring(0, 500);
                
                document.getElementById('fileViewContent').textContent = '<?php echo JText::_('COM_FILEMANAGER_ERROR_LOADING_FILE', true); ?>' + '\n\n' + debugInfo;
            }
        });
    }

    function showFileInfo(filePath) {
        jQuery('#infoModal').modal('show');
        document.getElementById('infoContent').innerHTML = '<p><?php echo JText::_('COM_FILEMANAGER_LOADING', true); ?>...</p>';
        
        jQuery.ajax({
            url: 'index.php?option=com_filemanager&task=files.getFileInfo',
            method: 'POST',
            data: {
                file: filePath,
                '<?php echo JSession::getFormToken(); ?>': 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var info = response.data;
                    var html = '<table class="table table-striped">';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_NAME', true); ?></th><td>' + info.name + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_PATH', true); ?></th><td>' + info.path + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_SIZE', true); ?></th><td>' + info.size + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_TYPE', true); ?></th><td>' + info.type + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_MODIFIED', true); ?></th><td>' + info.modified + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_PERMISSIONS', true); ?></th><td>' + info.permissions + '</td></tr>';
                    html += '</table>';
                    document.getElementById('infoContent').innerHTML = html;
                } else {
                    document.getElementById('infoContent').innerHTML = '<p class="text-error">' + (response.message || '<?php echo JText::_('COM_FILEMANAGER_ERROR', true); ?>') + '</p>';
                }
            }
        });
    }

    function showFolderInfo(folderPath) {
        jQuery('#infoModal').modal('show');
        document.getElementById('infoContent').innerHTML = '<p><?php echo JText::_('COM_FILEMANAGER_LOADING', true); ?>...</p>';
        
        jQuery.ajax({
            url: 'index.php?option=com_filemanager&task=files.getFolderInfo',
            method: 'POST',
            data: {
                folder: folderPath,
                '<?php echo JSession::getFormToken(); ?>': 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var info = response.data;
                    var html = '<table class="table table-striped">';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_NAME', true); ?></th><td>' + info.name + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_PATH', true); ?></th><td>' + info.path + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_FILES', true); ?></th><td>' + info.filesCount + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_FOLDERS', true); ?></th><td>' + info.foldersCount + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_MODIFIED', true); ?></th><td>' + info.modified + '</td></tr>';
                    html += '<tr><th><?php echo JText::_('COM_FILEMANAGER_INFO_PERMISSIONS', true); ?></th><td>' + info.permissions + '</td></tr>';
                    html += '</table>';
                    document.getElementById('infoContent').innerHTML = html;
                } else {
                    document.getElementById('infoContent').innerHTML = '<p class="text-error">' + (response.message || '<?php echo JText::_('COM_FILEMANAGER_ERROR', true); ?>') + '</p>';
                }
            }
        });
    }

    function showPermissions(path, type) {
        document.getElementById('perm_path').value = path;
        document.getElementById('perm_type').value = type;
        document.getElementById('perm_value').value = '';
        jQuery('#permissionsModal').modal('show');
    }

    function savePermissions() {
        var path = document.getElementById('perm_path').value;
        var type = document.getElementById('perm_type').value;
        var perms = document.getElementById('perm_value').value;
        
        if (!/^[0-7]{3,4}$/.test(perms)) {
            alert('<?php echo JText::_('COM_FILEMANAGER_ERROR_INVALID_PERMISSIONS', true); ?>');
            return;
        }
        
        jQuery.ajax({
            url: 'index.php?option=com_filemanager&task=files.changePermissions',
            method: 'POST',
            data: {
                path: path,
                type: type,
                permissions: perms,
                '<?php echo JSession::getFormToken(); ?>': 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('<?php echo JText::_('COM_FILEMANAGER_PERMISSIONS_CHANGED', true); ?>');
                    jQuery('#permissionsModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || '<?php echo JText::_('COM_FILEMANAGER_ERROR', true); ?>');
                }
            }
        });
    }

    function downloadFile(filePath) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?option=com_filemanager&task=files.download';
        
        var fileInput = document.createElement('input');
        fileInput.type = 'hidden';
        fileInput.name = 'file';
        fileInput.value = filePath;
        form.appendChild(fileInput);
        
        var tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '<?php echo JSession::getFormToken(); ?>';
        tokenInput.value = '1';
        form.appendChild(tokenInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function validateAndUpload() {
        var fileInput = document.getElementById('file-upload');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('<?php echo JText::_('COM_FILEMANAGER_ERROR_NO_FILES_SELECTED', true); ?>');
            return false;
        }
        
        // Устанавливаем task и отправляем форму напрямую
        var form = document.getElementById('adminForm');
        var taskInput = form.elements['task'];
        if (!taskInput) {
            taskInput = document.createElement('input');
            taskInput.type = 'hidden';
            taskInput.name = 'task';
            form.appendChild(taskInput);
        }
        taskInput.value = 'files.upload';
        form.submit();
        return true;
    }

    function deleteItem(path) {
        if (confirm('<?php echo JText::_('COM_FILEMANAGER_CONFIRM_DELETE', true); ?>')) {
            document.getElementById('adminForm').elements['cid[]'].value = path;
            document.getElementById('adminForm').elements['boxchecked'].value = 1;
            Joomla.submitbutton('files.delete');
        }
    }

    // Toggle advanced search panel
    function toggleAdvancedSearch() {
        var panel = document.getElementById('advanced-search');
        if (panel.style.display === 'none') {
            panel.style.display = 'block';
        } else {
            panel.style.display = 'none';
        }
    }

    // Clear date filters
    function clearDateFilters() {
        document.getElementById('search-date-from').value = '';
        document.getElementById('search-date-to').value = '';
    }

    // Search functionality
    function searchFiles() {
        var query = document.getElementById('search-query').value.trim();
        
        if (query.length < 2) {
            alert('<?php echo JText::_('COM_FILEMANAGER_SEARCH_MIN_LENGTH', true); ?>');
            return;
        }
        
        // Get current folder from the form
        var currentFolder = document.getElementById('adminForm').elements['folder'].value || '';
        
        // Get date filters
        var dateFrom = document.getElementById('search-date-from').value;
        var dateTo = document.getElementById('search-date-to').value;
        
        jQuery.ajax({
            url: 'index.php?option=com_filemanager&task=files.search',
            method: 'POST',
            data: {
                query: query,
                folder: currentFolder,
                date_from: dateFrom,
                date_to: dateTo,
                '<?php echo JSession::getFormToken(); ?>': 1
            },
            dataType: 'json',
            beforeSend: function() {
                document.getElementById('search-results-content').innerHTML = '<p><i class="icon-spinner icon-spin"></i> <?php echo JText::_('COM_FILEMANAGER_SEARCHING', true); ?>...</p>';
                document.getElementById('search-results').style.display = 'block';
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    var html = '<ul class="unstyled">';
                    
                    for (var i = 0; i < response.data.length; i++) {
                        var item = response.data[i];
                        var icon = item.type === 'folder' ? 'icon-folder-close' : 'icon-file';
                        var typeText = item.type === 'folder' ? '<?php echo JText::_('COM_FILEMANAGER_TYPE_FOLDER', true); ?>' : '<?php echo JText::_('COM_FILEMANAGER_TYPE_FILE', true); ?>';
                        
                        html += '<li style="margin: 5px 0; padding: 5px; border-bottom: 1px solid #ddd;">';
                        html += '<i class="' + icon + '"></i> ';
                        html += '<a href="#" onclick="openSearchResult(\'' + item.folder + '\'); return false;" style="font-weight: bold;">';
                        html += item.name + '</a> ';
                        html += '<span class="muted">(' + typeText + ')</span><br />';
                        html += '<small class="muted"><i class="icon-folder-open"></i> ' + item.path + '</small>';
                        html += '</li>';
                    }
                    
                    html += '</ul>';
                    html += '<p class="muted"><?php echo JText::_('COM_FILEMANAGER_SEARCH_FOUND', true); ?>: ' + response.data.length + '</p>';
                    document.getElementById('search-results-content').innerHTML = html;
                } else {
                    document.getElementById('search-results-content').innerHTML = '<p class="text-warning"><?php echo JText::_('COM_FILEMANAGER_SEARCH_NO_RESULTS', true); ?></p>';
                }
            },
            error: function() {
                document.getElementById('search-results-content').innerHTML = '<p class="text-error"><?php echo JText::_('COM_FILEMANAGER_ERROR', true); ?></p>';
            }
        });
    }

    function openSearchResult(folder) {
        window.location.href = 'index.php?option=com_filemanager&view=files&folder=' + encodeURIComponent(folder);
    }

    function closeSearchResults() {
        document.getElementById('search-results').style.display = 'none';
        document.getElementById('search-query').value = '';
    }

    // Allow search on Enter key
    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('search-query');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    searchFiles();
                }
            });
        }
    });

    // Event handlers using jQuery and data attributes
    jQuery(document).ready(function($) {
        // File view link handler
        $(document).on('click', '.file-view-link', function(e) {
            e.preventDefault();
            var filePath = $(this).data('filepath');
            var fileName = $(this).data('filename');
            viewFileContent(filePath, fileName);
        });

        // File view button handler
        $(document).on('click', '.file-view-btn', function(e) {
            e.preventDefault();
            var filePath = $(this).data('filepath');
            var fileName = $(this).data('filename');
            viewFileContent(filePath, fileName);
        });

        // File info button handler
        $(document).on('click', '.file-info-btn', function(e) {
            e.preventDefault();
            var filePath = $(this).data('filepath');
            showFileInfo(filePath);
        });

        // Folder info button handler
        $(document).on('click', '.folder-info-btn', function(e) {
            e.preventDefault();
            var folderPath = $(this).data('folderpath');
            showFolderInfo(folderPath);
        });

        // Permissions button handler
        $(document).on('click', '.permissions-btn', function(e) {
            e.preventDefault();
            var itemPath = $(this).data('itempath');
            var itemType = $(this).data('itemtype');
            showPermissions(itemPath, itemType);
        });

        // Download button handler
        $(document).on('click', '.download-btn', function(e) {
            e.preventDefault();
            var filePath = $(this).data('filepath');
            downloadFile(filePath);
        });

        // Delete button handler
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var itemPath = $(this).data('itempath');
            deleteItem(itemPath);
        });
    });
</script>
