<?php

/*
| Only the keys we override; everything else comes from the package defaults.
| The package's generic <x-icon> component is renamed to <x-blade-icon> so our
| own resources/views/components/icon.blade.php (Tabler wrapper) owns <x-icon>.
*/

return [

    'components' => [
        'disabled' => false,
        'default' => 'blade-icon',
    ],

];
