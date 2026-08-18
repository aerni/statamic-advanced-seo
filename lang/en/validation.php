<?php

return [

    'redirect_source_invalid' => 'The source must start with a forward slash, or be a regular expression wrapped in #.',
    'redirect_source_invalid_regex' => 'The source is not a valid regular expression.',
    'redirect_source_not_unique' => 'A redirect with this source already exists for this site.',
    'redirect_destination_invalid' => 'The destination must be a path starting with a slash (e.g. /about) or a full URL including the scheme (e.g. https://example.com).',
    'redirect_destination_missing' => 'The destination entry does not exist.',
    'redirect_destination_unpublished' => 'The destination entry must be published, otherwise the redirect would lead to a 404.',
    'redirect_destination_circular' => 'The destination resolves to the same URL as the source.',

];
