<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LeadType: string implements HasColor, HasIcon, HasLabel
{
    case Contact = 'contact';
    case ServiceRequest = 'service_request';
    case Quote = 'quote';
    case SiteVisit = 'site_visit';
    case CallForPrice = 'call_for_price';
    case StockAlert = 'stock_alert';
    case PcBuild = 'pc_build';
    case Corporate = 'corporate';
    case IncompleteCheckout = 'incomplete_checkout';

    public function getLabel(): string
    {
        return match ($this) {
            self::IncompleteCheckout => 'Incomplete checkout',
            self::Contact => 'Contact message',
            self::ServiceRequest => 'Repair / service request',
            self::Quote => 'Quote request',
            self::SiteVisit => 'Site visit',
            self::CallForPrice => 'Call for price',
            self::StockAlert => 'Stock alert',
            self::PcBuild => 'PC build enquiry',
            self::Corporate => 'Corporate / bulk',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::IncompleteCheckout => 'danger',
            self::ServiceRequest, self::SiteVisit => 'warning',
            self::Quote, self::Corporate, self::PcBuild => 'success',
            default => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Contact => 'heroicon-o-chat-bubble-left-right',
            self::ServiceRequest => 'heroicon-o-wrench-screwdriver',
            self::Quote, self::Corporate => 'heroicon-o-document-text',
            self::SiteVisit => 'heroicon-o-map-pin',
            self::CallForPrice => 'heroicon-o-phone',
            self::StockAlert => 'heroicon-o-bell-alert',
            self::PcBuild => 'heroicon-o-cpu-chip',
            self::IncompleteCheckout => 'heroicon-o-shopping-cart',
        };
    }
}
