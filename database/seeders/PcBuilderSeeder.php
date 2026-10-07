<?php

namespace Database\Seeders;

use App\Enums\BuilderRole;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\PcBuilderSlot;
use Illuminate\Database\Seeder;

/**
 * Starter PC Builder slots mapped to the catalog categories. Runs only while there are no slots yet, so the
 * owner's changes in the admin are never overwritten.
 */
class PcBuilderSeeder extends Seeder
{
    /** name, icon, section, role, category slugs, required, max qty, description */
    private const SLOTS = [
        ['Processor', 'cpu', 'core', BuilderRole::Processor, ['processor'], true, 1, 'The brain of your PC'],
        ['CPU Cooler', 'snowflake', 'core', BuilderRole::CpuCooler, ['cpu-cooler'], false, 1, 'Optional if your processor comes with a cooler'],
        ['Motherboard', 'cpu-2', 'core', BuilderRole::Motherboard, ['motherboard'], true, 1, 'Connects everything together'],
        ['RAM', 'components', 'core', BuilderRole::Memory, ['ram'], true, 4, 'Memory for multitasking'],
        ['Storage', 'device-floppy', 'core', null, ['ssd', 'hdd'], true, 1, 'SSD for speed, HDD for space'],
        ['Storage 2', 'database', 'core', null, ['ssd', 'hdd'], false, 1, 'Add a second drive'],
        ['Graphics Card', 'gpu', 'core', BuilderRole::GraphicsCard, ['graphics-card'], false, 1, 'For gaming and creative work'],
        ['Power Supply', 'bolt', 'core', BuilderRole::PowerSupply, ['power-supply'], true, 1, 'Clean, steady power'],
        ['Casing', 'box', 'core', BuilderRole::Casing, ['casing'], true, 1, 'Houses and cools your parts'],
        ['Casing Fan', 'car-fan', 'core', null, ['casing-fan'], false, 6, 'Extra airflow'],
        ['Monitor', 'device-desktop', 'peripheral', null, ['monitor'], false, 3, null],
        ['Keyboard', 'keyboard', 'peripheral', null, ['keyboard'], false, 1, null],
        ['Mouse', 'mouse', 'peripheral', null, ['mouse'], false, 1, null],
        ['Headphone', 'headphones', 'peripheral', null, ['headphone'], false, 1, null],
        ['Speaker', 'device-speaker', 'peripheral', null, ['speaker'], false, 1, null],
        ['UPS', 'battery-charging', 'peripheral', null, ['ups'], false, 1, 'Keeps you running through load-shedding'],
        ['Windows', 'brand-windows', 'peripheral', null, ['windows'], false, 1, null],
        ['Anti-Virus', 'virus', 'peripheral', null, ['anti-virus'], false, 1, null],
    ];

    public function run(): void
    {
        if (PcBuilderSlot::exists()) {
            return;
        }

        $categories = Category::pluck('id', 'slug');
        $formFactor = Attribute::where('code', 'form_factor')->value('id');

        foreach (self::SLOTS as $sort => [$name, $icon, $section, $role, $slugs, $required, $maxQty, $description]) {
            $ids = collect($slugs)->map(fn ($slug) => $categories[$slug] ?? null)->filter()->values()->all();

            if (! $ids) {
                continue;
            }

            PcBuilderSlot::create([
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'section' => $section,
                'role' => $role,
                'category_ids' => $ids,
                // Desktop builds: laptop (SO-DIMM) memory stays out of the RAM slot.
                'filter_attribute_id' => $role === BuilderRole::Memory ? $formFactor : null,
                'filter_values' => $role === BuilderRole::Memory && $formFactor ? ['Desktop (DIMM)'] : null,
                'is_required' => $required,
                'max_qty' => $maxQty,
                'sort' => $sort,
            ]);
        }
    }
}
