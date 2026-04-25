<?php

return [
    'dependencies' => ['backend', 'rte-ckeditor'],
    'imports' => [
        // All JS files of this extension accessible under @porthd/sitemasterdata/
        '@porthd/sitemasterdata/' => [
            'path' => 'EXT:sitemasterdata/Resources/Public/JavaScript/',
        ],
    ],
];
