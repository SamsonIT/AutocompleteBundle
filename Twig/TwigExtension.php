<?php

namespace Samson\Bundle\AutocompleteBundle\Twig;

use Twig_Error_Runtime;
use Twig_Extension;
use Twig_Filter_Method;
use Samson\Bundle\AutocompleteBundle\Autocomplete\Autocomplete;

/**
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class TwigExtension extends Twig_Extension
{

    public function getFilters()
    {
        return array(
            'highlight' => new Twig_Filter_Method($this, 'highlight', array('needs_context' => true, 'is_safe' => array('html'))),
        );
    }

    public function getName()
    {
        return 'autocomplete_highlight';
    }

    public function highlight(array $context, $str)
    {
        if (!array_key_exists('search_words', $context)) {
            throw new Twig_Error_Runtime('This filter can only be used in autocomplete templates!');
        }
        if (!$context['highlight']) {
            return $str;
        }
        if (!array_key_exists(Autocomplete::KEY_SEARCH, $_POST)) {
            return $str;
        }
        $searchWords = preg_split('/\s+/', trim($_POST[Autocomplete::KEY_SEARCH]));

        foreach ($searchWords as &$searchword) {
            $searchword = preg_quote($searchword, '/');
        }
        preg_match_all("/" . implode("|", $searchWords) . "/i", $str, $m, PREG_PATTERN_ORDER);
        $matches = array_values($m[0]);
        $replaces = array();
        $str = str_replace(' ', '&nbsp;', $str);
        foreach ($matches as $match) {
            $replaces[] = '<span class="select2-match">' . $match . '</span>';
            $str = preg_replace('/' . preg_quote($match, '/') . '/', '#######', $str, 1);
        }
        $str = str_replace('#######', '%s', $str);
        $str = vsprintf($str, $replaces);

        return $str;
    }
}
