<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

JFactory::getDocument()->addScriptDeclaration('
    Joomla.submitbutton = function(task)
    {
        if (task == "file.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
        {
            Joomla.submitform(task, document.getElementById("item-form"));
        }
    };
');
?>

<form action="<?php echo JRoute::_('index.php?option=com_filemanager&view=file&layout=edit&file=' . urlencode($this->item->path)); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
    
    <div class="row-fluid">
        <div class="span12">
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_FILEMANAGER_FILE_DETAILS'); ?></legend>
                
                <div class="control-group">
                    <div class="control-label">
                        <label><?php echo JText::_('COM_FILEMANAGER_FIELD_FILENAME'); ?></label>
                    </div>
                    <div class="controls">
                        <strong><?php echo $this->escape($this->item->filename); ?></strong>
                    </div>
                </div>

                <div class="control-group">
                    <div class="control-label">
                        <label><?php echo JText::_('COM_FILEMANAGER_FIELD_PATH'); ?></label>
                    </div>
                    <div class="controls">
                        <?php echo $this->escape($this->item->folder ? $this->item->folder : '/'); ?>
                    </div>
                </div>

                <div class="control-group">
                    <div class="control-label">
                        <label for="jform_content"><?php echo JText::_('COM_FILEMANAGER_FIELD_CONTENT'); ?></label>
                    </div>
                    <div class="controls">
                        <textarea name="jform[content]" id="jform_content" rows="25" cols="80" style="width: 100%; font-family: monospace;"><?php echo $this->escape($this->item->content); ?></textarea>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>

    <input type="hidden" name="jform[path]" value="<?php echo $this->escape($this->item->path); ?>" />
    <input type="hidden" name="jform[filename]" value="<?php echo $this->escape($this->item->filename); ?>" />
    <input type="hidden" name="folder" value="<?php echo $this->escape($this->item->folder); ?>" />
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>
