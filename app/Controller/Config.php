<?php

namespace Kanboard\Controller;

/**
 * Config controller
 *
 * @package  controller
 * @author   Frederic Guillot
 */
class Config extends Base
{
    /**
     * Common layout for config views
     *
     * @access private
     * @param  string    $template   Template name
     * @param  array     $params     Template parameters
     * @return string
     */
    private function layout($template, array $params)
    {
        $params['board_selector'] = $this->projectUserRole->getActiveProjectsByUser($this->userSession->getId());
        $params['values'] = $this->config->getAll();
        $params['errors'] = array();
        $params['config_content_for_layout'] = $this->template->render($template, $params);

        return $this->template->layout('config/layout', $params);
    }

    /**
     * Common method between pages
     *
     * @access private
     * @param  string     $redirect    Action to redirect after saving the form
     */
    private function common($redirect)
    {
        if ($this->request->isPost()) {
            $values =  $this->request->getValues();

            switch ($redirect) {
                case 'application':
                    $values += array('password_reset' => 0);
                    break;
                case 'project':
                    $values += array('subtask_restriction' => 0, 'subtask_time_tracking' => 0, 'cfd_include_closed_tasks' => 0);
                    break;
                case 'integrations':
                    $values += array('integration_gravatar' => 0);
                    break;
                case 'calendar':
                    $values += array('calendar_user_subtasks_time_tracking' => 0);
                    break;
            }

            if ($this->config->save($values)) {
                $this->config->reload();
                $this->flash->success(t('Settings saved successfully.'));
            } else {
                $this->flash->failure(t('Unable to save your settings.'));
            }

            $this->response->redirect($this->helper->url->to('config', $redirect));
        }
    }

    /**
     * Display the about page
     *
     * @access public
     */
    public function index()
    {
        $this->response->html($this->layout('config/about', array(
            'db_size' => $this->config->getDatabaseSize(),
            'title' => t('Settings').' &gt; '.t('About'),
        )));
    }

    /**
     * Display the plugin page
     *
     * @access public
     */
    public function plugins()
    {
        $this->response->html($this->layout('config/plugins', array(
            'plugins' => $this->pluginLoader->plugins,
            'title' => t('Settings').' &gt; '.t('Plugins'),
        )));
    }

    /**
     * Display the application settings page
     *
     * @access public
     */
    public function application()
    {
        $this->common('application');

        $this->response->html($this->layout('config/application', array(
            'languages' => $this->config->getLanguages(),
            'timezones' => $this->config->getTimezones(),
            'date_formats' => $this->dateParser->getAvailableFormats(),
            'title' => t('Settings').' &gt; '.t('Application settings'),
        )));
    }

    /**
     * Display the project settings page
     *
     * @access public
     */
    public function project()
    {
        $this->common('project');

        $this->response->html($this->layout('config/project', array(
            'colors' => $this->color->getList(),
            'default_columns' => implode(', ', $this->board->getDefaultColumns()),
            'title' => t('Settings').' &gt; '.t('Project settings'),
        )));
    }

    /**
     * Display the board settings page
     *
     * @access public
     */
    public function board()
    {
        $this->common('board');

        $this->response->html($this->layout('config/board', array(
            'title' => t('Settings').' &gt; '.t('Board settings'),
        )));
    }

    /**
     * Display the calendar settings page
     *
     * @access public
     */
    public function calendar()
    {
        $this->common('calendar');

        $this->response->html($this->layout('config/calendar', array(
            'title' => t('Settings').' &gt; '.t('Calendar settings'),
        )));
    }

    /**
     * Display the integration settings page
     *
     * @access public
     */
    public function integrations()
    {
        $this->common('integrations');

        $this->response->html($this->layout('config/integrations', array(
            'title' => t('Settings').' &gt; '.t('Integrations'),
        )));
    }

    /**
     * Display the webhook settings page
     *
     * @access public
     */
    public function webhook()
    {
        $this->common('webhook');

        $this->response->html($this->layout('config/webhook', array(
            'title' => t('Settings').' &gt; '.t('Webhook settings'),
        )));
    }

    /**
     * Display the api settings page
     *
     * @access public
     */
    public function api()
    {
        $this->response->html($this->layout('config/api', array(
            'title' => t('Settings').' &gt; '.t('API'),
        )));
    }

    /**
     * Download the Sqlite database
     *
     * @access public
     */
    public function downloadDb()
    {
        $this->checkCSRFParam();
        $this->response->forceDownload('db.sqlite.gz');
        $this->response->binary($this->config->downloadDatabase());
    }

    /**
     * Optimize the Sqlite database
     *
     * @access public
     */
    public function optimizeDb()
    {
        $this->checkCSRFParam();
        $this->config->optimizeDatabase();
        $this->flash->success(t('Database optimization done.'));
        $this->response->redirect($this->helper->url->to('config', 'index'));
    }

    /**
     * Regenerate webhook token
     *
     * @access public
     */
    public function token()
    {
        $type = $this->request->getStringParam('type');

        $this->checkCSRFParam();
        $this->config->regenerateToken($type.'_token');

        $this->flash->success(t('Token regenerated.'));
        $this->response->redirect($this->helper->url->to('config', $type));
    }
}
