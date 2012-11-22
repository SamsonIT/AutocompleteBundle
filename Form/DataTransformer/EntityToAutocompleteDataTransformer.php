<?php

namespace Samson\Bundle\AutocompleteBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Util\PropertyPath;

/**
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class EntityToAutocompleteDataTransformer implements DataTransformerInterface
{
    
    /**
     * @var EntityManager
     */
    private $manager;

    /**
     * @var string The entity class to select 
     */
    private $class;

    /**
     * @var PropertyPath|null the property path to use as value
     */
    private $identifier;

    /**
     * @param EntityManager $em
     * @param string $class
     * @param PropertyPath $identifier
     */
    public function __construct(EntityManager $em, $class, PropertyPath $identifier = null)
    {
        $this->manager = $em;
        $this->class = $class;
        $this->identifier = $identifier;
    }

    /**
     * @param object|null $value The selected entity object
     * 
     * @return mixed The value by which we are selecting
     */
    public function transform($value)
    {
        if (null === $value) {
            return array();
        }
        if($this->identifier){
            return $this->identifier->getValue($value);
        }

        return $value->getId();
    }

    /**
     * @param mixed $value The value by which we are selecting
     * 
     * @return object|null The resulting object
     */
    public function reverseTransform($value)
    {
        if (null === $this->identifier) {
            return $value ? $this->manager->getRepository($this->class)->find($value) : null;
        } else {
            $elements = $this->identifier->getElements();
            return $value ? $this->manager->getRepository($this->class)->findOneBy(array($elements[0] => $value)) : null;
        }
    }

}