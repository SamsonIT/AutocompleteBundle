<?php

namespace Samson\Bundle\AutocompleteBundle\Form\Listener;

use Samson\Bundle\AutocompleteBundle\Autocomplete\Autocomplete;
use Samson\Bundle\AutocompleteBundle\Form\Type\AutoCompleteType;
use Samson\Bundle\AutocompleteBundle\Query\ResultsFetcher;
use Samson\Bundle\AutocompleteBundle\Response\AutocompleteResponse;
use Samson\Bundle\AutocompleteBundle\Templating\AutocompleteResponseFormatter;
use Samson\Bundle\AutocompleteBundle\Templating\LabelBuilder;
use Samson\Bundle\UnexpectedResponseBundle\Exception\UnexpectedResponseException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class AutoCompleteTypeListener implements EventSubscriberInterface
{
    /**
     * @var AutoCompleteType
     */
    private $type;

    /**
     * @var array 
     */
    private $options;

    /**
     * @var Container
     */
    private $container;

    /**
     *
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     *
     * @var ResultsFetcher
     */
    private $resultsFetcher;

    /**
     *
     * @var AutocompleteResponseFormatter
     */
    private $responseFormatter;

    /**
     * Construct a new AutoCompleteFormListener
     * 
     * @param FormType   $type
     * @param Container $container     The service container
     * @param array     $options       An array of options
     * @param ResultsFetcher ResultsFetcher 
     * @param LabelBuilder LabelBuilder
     */
    public function __construct(AutoCompleteType $type, Container $container, array $options, ResultsFetcher $results, AutocompleteResponseFormatter $formatter)
    {
        $this->type = $type;
        $this->options = $options;
        $this->container = $container;
        $this->propertyAccessor = PropertyAccess::getPropertyAccessor();
        $this->resultsFetcher = $results;
        $this->responseFormatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_BIND => array('onPostBind', -100),
        );
    }

    /**
     * Executed on event FormEvents::POST_BIND
     *
     * @param FormEvent $event
     *
     * @throws UnexpectedResponseException to force data to be send to client
     */
    public function onPostBind(FormEvent $event)
    {
        $form = $event->getForm();

        $request = $this->container->get('request');
        if (!$request->request->has(Autocomplete::KEY_PATH)) {
            return;
        }

        $name = $request->request->get(Autocomplete::KEY_PATH);
        $parts = $this->parseNameIntoParts($name, $form->getRoot()->getName());

        $field = $form->getRoot();
        foreach ($parts as $part) {
            if (!$field->has($part)) {
                return;
            }
            $field = $field->get($part);
        }

        if ($field !== $form) {
            return;
        }

        $searchFields = $form->getConfig()->getAttribute('search-fields');
        $template = $this->options['template'];
        $extraParams = $this->options['extra_params'];

        $results = $this->resultsFetcher->getPaginatedResults($request, $form->getConfig()->getAttribute('query-builder'), $searchFields);

        $responseData = array();
        $options = array('search-fields' => $searchFields, 'extra_params' => $extraParams);
        foreach ($results as $result) {
            $identifier = $this->options['identifier_propertypath'] ? $this->propertyAccessor->getValue($result, $this->options['identifier_propertypath']) : $result->getId();
            $responseData[] = $this->responseFormatter->formatResultLineForAutocompleteResponse($template, $result, $options, $identifier);
        }
        throw new UnexpectedResponseException(new AutocompleteResponse($responseData));
    }

    private function parseNameIntoParts($name, $formName)
    {
        $cur = strlen($formName);
        $parts = array();

        while ($cur < strlen($name)) {
            $nextPart = substr($name, $cur + 1, strpos($name, ']', $cur + 1) - $cur - 1);
            if ($nextPart == 'q') {
                break;
            }
            $cur += strlen($nextPart) + 2;

            $parts[] = $nextPart;
        }

        return $parts;
    }
}
