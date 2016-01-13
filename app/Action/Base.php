<?php

namespace Kanboard\Action;

use Kanboard\Event\GenericEvent;

/**
 * Base class for automatic actions
 *
 * @package action
 * @author  Frederic Guillot
 */
abstract class Base extends \Kanboard\Core\Base
{
    /**
     * Extended events
     *
     * @access private
     * @var array
     */
    private $compatibleEvents = array();

    /**
     * Flag for called listener
     *
     * @access private
     * @var boolean
     */
    private $called = false;

    /**
     * Project id
     *
     * @access private
     * @var integer
     */
    private $projectId = 0;

    /**
     * User parameters
     *
     * @access private
     * @var array
     */
    private $params = array();

    /**
     * Get automatic action name
     *
     * @final
     * @access public
     * @return string
     */
    final public function getName()
    {
        return '\\'.get_called_class();
    }

    /**
     * Get automatic action description
     *
     * @abstract
     * @access public
     * @return string
     */
    abstract public function getDescription();

    /**
     * Execute the action
     *
     * @abstract
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool            True if the action was executed or false when not executed
     */
    abstract public function doAction(array $data);

    /**
     * Get the required parameter for the action (defined by the user)
     *
     * @abstract
     * @access public
     * @return array
     */
    abstract public function getActionRequiredParameters();

    /**
     * Get the required parameter for the event (check if for the event data)
     *
     * @abstract
     * @access public
     * @return array
     */
    abstract public function getEventRequiredParameters();

    /**
     * Get the compatible events
     *
     * @abstract
     * @access public
     * @return array
     */
    abstract public function getCompatibleEvents();

    /**
     * Check if the event data meet the action condition
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool
     */
    abstract public function hasRequiredCondition(array $data);

    /**
     * Return class information
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Set project id
     *
     * @access public
     * @return Base
     */
    public function setProjectId($project_id)
    {
        $this->projectId = $project_id;
        return $this;
    }

    /**
     * Get project id
     *
     * @access public
     * @return integer
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Set an user defined parameter
     *
     * @access public
     * @param  string  $name    Parameter name
     * @param  mixed   $value   Value
     * @param  Base
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Get an user defined parameter
     *
     * @access public
     * @param  string  $name            Parameter name
     * @param  mixed   $default         Default value
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * Check if an action is executable (right project and required parameters)
     *
     * @access public
     * @param  array   $data
     * @param  string  $eventName
     * @return bool
     */
    public function isExecutable(array $data, $eventName)
    {
        return $this->hasCompatibleEvent($eventName) &&
               $this->hasRequiredProject($data) &&
               $this->hasRequiredParameters($data) &&
               $this->hasRequiredCondition($data);
    }

    /**
     * Check if the event is compatible with the action
     *
     * @access public
     * @param  string  $eventName
     * @return bool
     */
    public function hasCompatibleEvent($eventName)
    {
        return in_array($eventName, $this->getEvents());
    }

    /**
     * Check if the event data has the required project
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool
     */
    public function hasRequiredProject(array $data)
    {
        return isset($data['project_id']) && $data['project_id'] == $this->getProjectId();
    }

    /**
     * Check if the event data has required parameters to execute the action
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool            True if all keys are there
     */
    public function hasRequiredParameters(array $data)
    {
        foreach ($this->getEventRequiredParameters() as $parameter) {
            if (! isset($data[$parameter])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the action
     *
     * @access public
     * @param  \Kanboard\Event\GenericEvent   $event
     * @param  string                         $eventName
     * @return bool
     */
    public function execute(GenericEvent $event, $eventName)
    {
        // Avoid infinite loop, a listener instance can be called only one time
        if ($this->called) {
            return false;
        }

        $data = $event->getAll();
        $result = false;

        if ($this->isExecutable($data, $eventName)) {
            $this->called = true;
            $result = $this->doAction($data);
        }

        $this->logger->debug('AutomaticAction '.$this->getName().' => '.($result ? 'true' : 'false'));

        return $result;
    }

    /**
     * Register a new event for the automatic action
     *
     * @access public
     * @param  string $event
     * @param  string $description
     */
    public function addEvent($event, $description = '')
    {
        if ($description !== '') {
            $this->eventManager->register($event, $description);
        }

        $this->compatibleEvents[] = $event;
        return $this;
    }

    /**
     * Get all compatible events of an automatic action
     *
     * @access public
     * @return array
     */
    public function getEvents()
    {
        return array_unique(array_merge($this->getCompatibleEvents(), $this->compatibleEvents));
    }
}
