<?php

namespace Samson\Bundle\AutocompleteBundle\Autocomplete;

class Autocomplete
{
    /**
     * HTTP POST key to use to tell autocomplete what field to use for searching
     * 
     */
    const KEY_PATH = '__autocomplete_path';

    /**
     * HTTP POST key to use to pass the searched value to autocomplete
     * 
     */
    const KEY_SEARCH = '__autocomplete_search';

    /**
     * HTTP POST key to pass the page-limit
     */
    const KEY_LIMIT = '__autocomplete_page_limit';

    /**
     * HTTP POST key to tell the current page
     */
    const KEY_PAGE = '__autocomplete_page';

}
