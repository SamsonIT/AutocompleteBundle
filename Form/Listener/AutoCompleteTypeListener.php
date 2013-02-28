<?php

namespace Samson\Bundle\AutocompleteBundle\Form\Listener;

use Closure;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Samson\Bundle\AutocompleteBundle\Form\Type\AutoCompleteType;
use Samson\Bundle\UnexpectedResponseBundle\Exception\UnexpectedResponseException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class AutoCompleteTypeListener implements EventSubscriberInterface
{

    /**
     * HTTP POST key to use to tell autocomplete what field to use for searching
     */
    const KEY_PATH   = '__autocomplete_path';

    /**
     * HTTP POST key to use to pass the searched value to autocomplete
     */
    const KEY_SEARCH = '__autocomplete_search';

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
     * Construct a new AutoCompleteFormListener
     * 
     * @param Closure   $labelCallback The callback that generates the label
     * @param Container $container     The service container
     * @param array     $options       An array of options
     */
    public function __construct(AutoCompleteType $type, Container $container, array $options)
    {
        $this->type = $type;
        $this->options = $options;
        $this->container = $container;
        $this->propertyAccessor = PropertyAccess::getPropertyAccessor();
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
        if (!$request->request->has(self::KEY_PATH)) {
            return;
        }

        $name = $request->request->get(self::KEY_PATH);
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
        
        $search = preg_split('/\s+/', trim($request->request->get(self::KEY_SEARCH)));
        $searchFields = $form->getAttribute('search-fields');
        $qb = $form->getAttribute('query-builder');

        $this->appendQuery($qb, $search, $searchFields);
        $query = $qb->getQuery();

        $pageSize = $request->request->get('__autocomplete_page_limit', 10);
        $page = $request->request->get('__autocomplete_page', 1);

        $query->setFirstResult(($page - 1) * $pageSize);
        $query->setMaxResults($pageSize);
        $results = new Paginator($query);

        $responseData = array();

        foreach ($results as $result) {
            $id = $this->options['identifier_propertypath'] ? $this->propertyAccessor->getValue($result, $this->options['identifier_propertypath']) : $result->getId() ;

            $responseData[] = array(
                "id" => $id,
                "textHighlight" => $this->type->getLabel($this->options['template'], $result, $search, true, $this->options['extra_params']),
                "text" => $this->type->getLabel($this->options['template'], $result, $search, false),
            );
        }

        $response = new JsonResponse(array('results'=>$responseData, 'total'=>count($results)));

        throw new UnexpectedResponseException($response);
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
    
    private function appendQuery(QueryBuilder $qb, array $searchWords, array $searchFields)
    {   
        foreach ($searchWords as $key => $searchWord) {
            $expressions = array();

            foreach ($searchFields as $field) {
                $expressions[] = $qb->expr()->like($field, ':query'.$key);
            }
            $qb->andWhere("(".call_user_func_array(array($qb->expr(), 'orx'), $expressions).")");
            $qb->setParameter('query'.$key, '%'.$searchWord.'%');
        }
    }
}
