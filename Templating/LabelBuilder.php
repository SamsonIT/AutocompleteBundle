<?php

namespace Samson\Bundle\AutocompleteBundle\Templating;

use Symfony\Bundle\TwigBundle\TwigEngine;

class LabelBuilder
{
    /**
     *
     * @var TwigEngine
     */
    private $templating;

    public function __construct(TwigEngine $templating)
    {
        $this->templating = $templating;
    }
    
    public function getLabel($template, $result, array $searchFields = array(), array $extraParams = null)
    {
        if ($template) {
            $params = $this->getParams($result, $searchFields, $extraParams);
            $params['highlight'] = false;
            $result = $this->templating->render($template, $params);
        }
        return (string) $result;
    }

    public function getLabelForHighlight($template, $result, array $searchFields = array(), array $extraParams = null)
    {
        if ($template) {
            $params = $this->getParams($result, $searchFields, $extraParams);
            $params['highlight'] = true;
            $result = $this->templating->render($template, $params);
        }
        return (string) $result;
    }

    private function getParams($result, array $searchFields = array(), array $extraParams = null)
    {
        $params = array('result' => $result, 'search_words' => $searchFields);
        if (null !== $extraParams) {
            $params = array_merge($params, $extraParams);
        }
        return $params;
    }
}
