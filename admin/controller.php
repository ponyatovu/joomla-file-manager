<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_filemanager
 */

defined('_JEXEC') or die;

/**
 * Filemanager Component Controller
 */
class FilemanagerController extends JControllerLegacy
{
    /**
     * The default view.
     *
     * @var    string
     */
    protected $default_view = 'files';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe url parameters and their variable types
     *
     * @return  JController  This object to support chaining.
     */
    public function display($cachable = false, $urlparams = false)
    {
        $view   = $this->input->get('view', 'files');
        $layout = $this->input->get('layout', 'default');
        $id     = $this->input->getInt('id');

        // Check for edit form.
        if ($view == 'file' && $layout == 'edit' && !$this->checkEditId('com_filemanager.edit.file', $id))
        {
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_filemanager&view=files', false));

            return false;
        }

        parent::display();

        return $this;
    }
}
