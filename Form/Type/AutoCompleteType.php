<?php

namespace Samson\Bundle\AutocompleteBundle\Form\Type;

use Closure;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Samson\Bundle\AutocompleteBundle\Form\DataTransformer\EntityToAutocompleteDataTransformer;
use Samson\Bundle\AutocompleteBundle\Form\Listener\AutoCompleteTypeListener;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Util\PropertyPath;
use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class AutoCompleteType extends AbstractType
{

    /**
     * @var Registry 
     */
    private $doctrine;

    /**
     * @var TwigEngine 
     */
    private $templating;

    /**
     * @var Container
     */
    private $container;

    public function __construct(Registry $doctrine, TwigEngine $templating, ContainerInterface $container)
    {
        $this->doctrine = $doctrine;
        $this->templating = $templating;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->doctrine->getManager($options['em']);
        $er = $em->getRepository($options['class']);

        if ( ! isset($options['query_builder'])) {
            $queryBuilder = null;
        } else {
            $queryBuilder = $options['query_builder'];
        }

        if (!(null === $queryBuilder || $queryBuilder instanceof QueryBuilder || $queryBuilder instanceof Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
        }

        if ($queryBuilder instanceof Closure) {
            if (!(null === $queryBuilder || $queryBuilder instanceof QueryBuilder || $queryBuilder instanceof Closure)) {
                throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
            }
            $queryBuilder = $queryBuilder($er);

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder');
            }
        }

        if (null === $queryBuilder) {
            $queryBuilder = $er->createQueryBuilder('autocomplete');
        }

        $builder->setAttribute('search-fields', $options['search_fields']);
        $builder->setAttribute('query-builder', $queryBuilder);
        $builder->setAttribute('limit', 15);
        $builder->setAttribute('template', $options['template']);

        $transformer = new EntityToAutocompleteDataTransformer($em, $options['class'], $options['identifier_propertypath']);
        $builder->addViewTransformer($transformer);

        $builder->addEventSubscriber(new AutoCompleteTypeListener($this, $this->container, $options));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (array_key_exists('data-display-value', $view->vars['attr']) && $view->vars['attr']['data-display-value']) {
            return;
        }
        $view->vars['attr']['data-display-value'] = null !== $form->getData() ? trim($this->getLabel($options['template'], $form->getData())) : null;
    }

    public function getLabel($template, $result, array $searchWords = array(), $highlight = false, array $extraParams = null)
    {
        if ($template) {
            $params = array("result" => $result, 'search_words' => $searchWords, 'highlight' => $highlight);
            if (null !== $extraParams) {
                $params = array_merge($params, $extraParams);
            }
            $result = $this->templating->render($template, $params);
        }

        return (string) $result;
    }

    public function getName()
    {
        return 'autocomplete';
    }

    public function getParent()
    {
        return 'hidden';
    }
   
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults( array(
            'em' => 'default',
            'query_builder' => null,
            'error_bubbling' => false,
            'width' => '200px',
            'identifier' => null,
            'extra_params' => array(),
            'data_class' => null,
            'identifier_propertypath' => function(Options $options) {
                return $options['identifier'] ? new PropertyPath($options['identifier']) : null;
            },
            'attr' => function(Options $options) {
                return array('style' => 'width: '.$options['width']);
            }
        ));

        $resolver->setRequired(array('search_fields', 'class', 'template'));
    }
}