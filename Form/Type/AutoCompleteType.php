<?php

namespace Samson\Bundle\AutocompleteBundle\Form\Type;

use Closure;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Samson\Bundle\AutocompleteBundle\Form\DataTransformer\EntityToAutocompleteDataTransformer;
use Samson\Bundle\AutocompleteBundle\Form\Listener\AutoCompleteTypeListener;
use Samson\Bundle\AutocompleteBundle\Query\ResultsFetcher;
use Samson\Bundle\AutocompleteBundle\Templating\AutocompleteResponseFormatter;
use Samson\Bundle\AutocompleteBundle\Templating\LabelBuilder;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyPath;


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
     * @var Container
     */
    private $container;
    
    private $resultsFetcher;
    
    private $responseFormatter;
        
    
    public function __construct(Registry $doctrine, ContainerInterface $container, ResultsFetcher $r, AutocompleteResponseFormatter $f)
    {
        $this->doctrine = $doctrine;
        $this->container = $container;
        $this->resultsFetcher = $r;
        $this->responseFormatter = $f;
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

        $builder->addEventSubscriber(new AutoCompleteTypeListener($this, $this->container, $options, $this->resultsFetcher, $this->responseFormatter));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (array_key_exists('data-display-value', $view->vars['attr']) && $view->vars['attr']['data-display-value']) {
            return;
        }

        $em = $this->doctrine->getManager();
        $softDeleteEnabled = array_key_exists('soft_delete', $em->getFilters()->getEnabledFilters());
        if ($softDeleteEnabled) {
            $em->getFilters()->disable('soft_delete');
        }
        $view->vars['attr']['data-display-value'] = null !== $form->getData() ? trim($this->responseFormatter->formatLabelForAutocompleteResponse($options['template'], $form->getData())) : null;
        if ($softDeleteEnabled) {
            $em->getFilters()->enable('soft_delete');
        }
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
            'width' => '186px',
            'identifier' => null,
            'extra_params' => array(),
            'data_class' => null,
            'identifier_propertypath' => null,
            'attr' => function(Options $options) {
                return array('style' => 'width: '.$options['width']);
            }
        ));

        $resolver->setRequired(array('search_fields', 'class', 'template'));
    }
}