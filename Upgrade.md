UPGRADE
======

## 1.x to 2.0

### AutocompleteResponse

The response now expects the amount of possible results as a 2nd argument, instead of calculating the total through the $responseData as this is a paginated result always.
By providing the total amount of possible results select2 is triggered automatically to load more requests when scrolling.

Before:

```
$response = new AutocompleteResponse($responseData);
```

After:

```
$response = new AutocompleteResponse($responseData, $ttl);
```
