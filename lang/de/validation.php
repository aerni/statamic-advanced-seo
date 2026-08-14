<?php

return [

    'redirect_source_invalid' => 'Die Quelle muss mit einem Schrägstrich beginnen oder ein in # eingeschlossener regulärer Ausdruck sein.',
    'redirect_source_invalid_regex' => 'Die Quelle ist kein gültiger regulärer Ausdruck.',
    'redirect_source_not_unique' => 'Eine Weiterleitung mit dieser Quelle existiert bereits für diese Website.',
    'redirect_destination_invalid' => 'Das Ziel muss ein Pfad sein, der mit einem Schrägstrich beginnt (z. B. /about), oder eine vollständige URL inklusive Schema (z. B. https://example.com).',
    'redirect_destination_unpublished' => 'Das Ziel muss ein veröffentlichter Eintrag sein, sonst würde die Weiterleitung zu einem 404 führen.',
    'redirect_destination_circular' => 'Das Ziel führt zur selben URL wie die Quelle.',

];
