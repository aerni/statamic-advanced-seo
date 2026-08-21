<?php

return [

    'redirect_source_invalid' => 'Die Quelle muss mit einem Schrägstrich beginnen oder ein in # eingeschlossener regulärer Ausdruck sein.',
    'redirect_source_invalid_regex' => 'Die Quelle ist kein gültiger regulärer Ausdruck.',
    'redirect_source_not_unique' => 'Eine Weiterleitung mit dieser Quelle existiert bereits für diese Website.',
    'redirect_site_invalid' => 'Wähle eine gültige Site aus.',
    'redirect_destination_invalid' => 'Das Ziel muss ein Pfad sein, der mit einem Schrägstrich beginnt (z. B. /about), oder eine vollständige URL inklusive Schema (z. B. https://example.com).',
    'redirect_destination_missing' => 'Der Zieleintrag existiert nicht.',
    'redirect_destination_unpublished' => 'Das Ziel muss ein veröffentlichter Eintrag sein, sonst würde die Weiterleitung zu einem 404 führen.',
    'redirect_destination_circular' => 'Das Ziel führt zur selben URL wie die Quelle.',
    'redirect_import_missing_columns' => 'Deiner CSV fehlt die erforderliche Spalte :columns.|Deiner CSV fehlen die erforderlichen Spalten :columns.',
    'redirect_import_invalid_response_code' => 'Ungültiger Antwortcode „:code". Verwende 301, 302 oder 410.',
    'redirect_import_duplicate' => 'Doppelte source in der Datei.',
    'redirect_import_invalid_json' => 'Das JSON muss ein nicht-leeres Array von Weiterleitungsobjekten sein.',
    'redirect_import_invalid_file' => 'Wähle einen gültigen CSV- oder JSON-Upload aus.',
    'redirect_import_missing_site' => 'Eine Site ist erforderlich.',
    'redirect_import_invalid_site' => 'Unbekannte oder nicht autorisierte Site „:site".',

];
