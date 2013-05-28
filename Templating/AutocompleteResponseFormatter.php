<?php

namespace Samson\Bundle\AutocompleteBundle\Templating;

class AutocompleteResponseFormatter
{
    private $labelBuilder;

    public function __construct(LabelBuilder $labelBuilder)
    {
        $this->labelBuilder = $labelBuilder;
    }

    /**
     * 
     * @param type $template
     * @param type $entity
     * @param type $options array('search-fields', 'extra_params') 
     * @param type $identifier
     * @return type
     */
    public function formatResultLineForAutocompleteResponse($template, $entity, $options, $identifier = null)
    {
        $searchFields = array_key_exists('search-fields', $options) ? $options['search-fields'] : array();
        $extraParams = array_key_exists('extra_params', $options) ? $options['extra_params'] : array();
        $id = null === $identifier ? $entity->getId() : $identifier;
        return array(
            'id' => $id,
            'text' => $this->labelBuilder->getLabel($template, $entity, $searchFields, $extraParams),
            'textHighlight' => $this->labelBuilder->getLabelForHighlight($template, $entity, $searchFields, $extraParams)
        );
    }
}
