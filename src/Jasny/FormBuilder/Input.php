<?php

namespace Jasny\FormBuilder;

/**
 * Representation of an <input> element in a form.
 * 
 * @option string id           Element id
 * @option string name         Element name
 * @option string description  Description as displayed on the label
 * @option string type         HTML5 input type
 * @option mixed  value        Element value
 * 
 * @todo Support multiple file upload
 */
class Input extends Control
{
    /**
     * Upload data. Only used for <input type="file">.
     * @var array
     */
    protected $upload;
    
    
    /**
     * Class constructor.
     * 
     * @param array  $options  Element options
     * @param array  $attr     HTML attributes
     */
    public function __construct(array $options=[], array $attr=[])
    {
        if (isset($options['value'])) $attr['value'] = $options['value'];
        if (isset($options['type'])) $attr['type'] = $options['type'];
        $attr += $this->attr + ['type'=>'text'];
        
        if ($attr['type'] === 'checkbox' && !isset($attr['value'])) $attr['value'] = 1;

        if (in_array($attr['type'], ['button', 'submit', 'reset']) && !isset($attr['value'])) {
            $attr['value'] = function() {
                return $this->getDescription();
            };
        }
        
        $noPlaceholder = ['hidden', 'button', 'submit', 'reset', 'checkbox', 'radio', 'file'];
        if (!in_array($attr['type'], $noPlaceholder) && !isset($attr['placeholder'])) {
            $attr['placeholder'] = function() {
                return $this->getOption('label') ? null : $this->getDescription();
            };
        }
        
        unset($options['type'], $options['value']);
        parent::__construct($options, $attr);
    }
    
    
    /**
     * Get HTML5 input type
     * 
     * @return string
     */
    final public function getType()
    {
        return $this->attr['type'];
    }
    
    /**
     * Get the value of the control.
     * 
     * @return mixed
     */
    public function getValue()
    {
        $type = $this->attr['type'];
        if ($type === 'file') return $this->upload;
        
        $value = $this->attr['value'];
        if ($value instanceof FormElement) $value = $value->getValue();

        if (($type === 'checkbox' || $type === 'radio') && !$this->attr['checked']) $value = false;
        
        return $value;
    }
    
    /**
     * Set the value of the control.
     * 
     * @param mixed $value
     * @return Boostrap/Control $this
     */
    public function setValue($value)
    {
        switch ($this->attr['type']) {
            case 'file':
                $this->upload = $value;
            case 'checkbox':
                $checked = (boolean)$value;
                $this->attr['checked'] = $checked;
                if ($checked && !is_bool($value)) $this->attr['value'] = $value;
                break;
            case 'radio':
                $this->attr['checked'] = ($value == $this->attr['value']);
                break;
            default:
                $this->attr['value'] = $value;
                break;
        }
        
        return $this;
    }

    /**
     * Get all options.
     * 
     * @return array
     */
    public function getOptions()
    {
        $options = parent::getOptions();
        
        switch ($this->attr['type']) {
            case 'hidden':
                if (!isset($this->options['label'])) $options['label'] = false;
                if (!isset($this->options['container'])) $options['container'] = false;
                break;
            
            case 'checkbox':
            case 'radio':
                if (!isset($this->options['label'])) $options['label'] = 'inside';
                break;
                
            case 'button':
            case 'submit':
            case 'reset':
                if (!isset($this->options['label'])) $options['label'] = false;
                break;
        }
        
        return $options;
    }
    
    
    /**
     * Validate the input control.
     * 
     * @return boolean
     */
    protected function validate()
    {
        if (!$this->getOption('basic-validation')) return true;
        
        if (!$this->validateRequired()) return false;

        // Empty and not required, means no further validation
        if ($this->getValue() === null || $this->getValue() === '') return true;

        if ($this->attr['type'] === 'file' && !$this->validateUpload()) return false;
        if (!$this->validateType()) return false;
        if (!$this->validateMinMax()) return false;
        if (!$this->validateLength()) return false;
        if (!$this->validatePattern()) return false;

        if (!$this->validateMatch()) return false;
        
        return true;
    }
    

    /**
     * Render the element field to HTML.
     * 
     * @param string $control  Control HTML
     * @return string
     */
    protected function renderField($control)
    {
        // Determine default options and attributes
        if ($this->attr['type'] == 'checkbox' && $this->getOption('add-hidden')) {
            $name = htmlentities($this->attr['name']);
            $control = '<input type="hidden" name="' . $name . '" value="">' . $control;
        }

        return parent::renderField($control);
    }

    /**
     * Render the <input>.
     * 
     * @return string
     */
    protected function renderControl()
    {
        return "<input {$this->attr}>";
    }
}
