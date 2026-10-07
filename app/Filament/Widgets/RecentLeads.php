<?php

namespace App\Filament\Widgets;

use App\Enums\StaffArea;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Widgets\Widget;

/** The latest enquiries (contact, repair requests, PC builds…). */
class RecentLeads extends Widget
{
    protected string $view = 'filament.widgets.recent-leads';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'leads' => Lead::query()->latest('id')->limit(5)->get()->map(fn (Lead $lead) => [
                'name' => $lead->name,
                'type' => $lead->type->getLabel(),
                'icon' => $lead->type->getIcon(),
                'status' => $lead->status,
                'when' => $lead->created_at->diffForHumans(short: true),
                'url' => LeadResource::getUrl('view', ['record' => $lead]),
            ]),
            'allUrl' => LeadResource::getUrl('index'),
        ];
    }

    public static function canView(): bool
    {
        return StaffArea::Leads->allowed();
    }
}
