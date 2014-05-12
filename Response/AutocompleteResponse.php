<?php

namespace Samson\Bundle\AutocompleteBundle\Response;

class AutocompleteResponse extends \Symfony\Component\HttpFoundation\JsonResponse
{

    public function __construct(array $data, $ttl, $status = 200, $headers = array())
    {
        parent::__construct(array('results' => $data, 'total' => $ttl), $status, $headers);
    }
}
