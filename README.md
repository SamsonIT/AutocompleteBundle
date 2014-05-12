README
======

Checkout SamsonIT/ShowCase to see a working example of how to use this bundle.

How to install
--------------

Currently, you'll need to follow these steps to ensure the autocomplete will
work:

 * Enable this and the UnexpectedResponseBundle in your AppKernel

Example: https://github.com/SamsonIT/ShowCase/blob/master/app/AppKernel.php#L22

 * Make sure the latest JQuery version is loaded (JQuery UI is not necessary)
 * Make sure the latest Select2 javascript is loaded
 * Make sure the latest Select2 css files are loaded and that the images bundled
   with it are in place
 * Make sure the autocomplete.js file in this bundle is loaded
 * Make sure the autocomplete.css file in this bundle is loaded (this one is
   not all that necessary)

An example of the above requirements can be found in https://github.com/SamsonIT/ShowCase/blob/master/src/Samson/Bundle/ShowCaseBundle/Resources/views/Autocomplete/index.html.twig

 * Make sure the autocomplete.html.twig form theme is included into twig

Find an example here: https://github.com/SamsonIT/ShowCase/blob/master/app/config/config.yml#L24

How to use
----------

Using the autocomplete is very simple. There are three required options to
configure the Form type, and one recommended:

```php
$form = $this->createForm('autocomplete', null, array(
  'class' => 'SomeEntityClass',
  'template' => 'Template to use to display the entity.html.twig',
  'search_fields' => array('list of fields to search in'),
  'query_builder' => function(EntityRepository $er) {
    return $er->createQueryBuilder('s');
  }
);
```

The template can be defined as follows:

```twig
{% if highlight %}
Found: {{ result.name|highlight }}
{% else %}
{{ result.name }}
{% endif %}
```

The ```highlight``` filter will simply do nothing if the ```highlight```
property is false, so this is also a valid template:

```twig
{{ result.name|highlight }}
```

Upgrade
----------
See [Upgrade.md](Upgrade.md)