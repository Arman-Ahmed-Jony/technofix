<?php

namespace App\Filament\Resources\NotFoundUrls\Pages;

use App\Filament\Resources\NotFoundUrls\NotFoundUrlResource;
use Filament\Resources\Pages\ListRecords;

class ListNotFoundUrls extends ListRecords
{
    protected static string $resource = NotFoundUrlResource::class;

    protected static ?string $title = 'Broken links';

    public function getSubheading(): ?string
    {
        return 'Addresses on your site that visitors or search engines tried, but that don’t exist — old links, mistyped addresses, deleted products. Redirect the ones people use to the right page. Entries not tried for 90 days are removed.';
    }
}
