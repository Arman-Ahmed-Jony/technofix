<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Orders keep the code they used; turning a coupon off keeps its usage count.
            DeleteAction::make()->modalDescription('Orders that used it keep the code. To stop it working but keep its history, turn it off instead.'),
        ];
    }
}
