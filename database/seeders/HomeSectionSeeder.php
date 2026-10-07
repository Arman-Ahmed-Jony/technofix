<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

/**
 * The launch home page, section by section. Runs only while there are no sections yet, so the owner's changes are
 * never overwritten.
 */
class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        if (HomeSection::exists()) {
            return;
        }

        $ids = Category::pluck('id', 'slug');
        $id = fn (string $slug) => $ids[$slug] ?? null;

        $sections = [
            ['type' => 'hero', 'settings' => ['brand_zones' => true]],
            ['type' => 'ticker', 'settings' => ['label' => 'Notice', 'icon' => 'speakerphone', 'speed' => 'normal', 'messages' => [
                ['text' => 'Cash on delivery all over Bangladesh — order online and pay when it arrives.'],
                ['text' => 'Genuine products with official brand warranty.'],
            ]]],
            ['type' => 'categories', 'eyebrow' => 'Browse', 'title' => 'Shop by category', 'settings' => ['category_ids' => collect([
                'laptop', 'monitor', 'processor', 'motherboard', 'graphics-card', 'ram', 'ssd', 'power-supply',
                'router', 'ip-camera', 'wifi-camera', 'access-control', 'mouse', 'keyboard', 'printer', 'ups',
            ])->map($id)->filter()->values()->all()]],
            ['type' => 'departments', 'eyebrow' => 'Departments', 'title' => 'Everything tech, under one roof', 'settings' => [
                'big' => ['category_id' => $id('laptop'), 'heading' => 'Power for work, play & study'],
                'wide' => ['category_id' => $id('component'), 'heading' => 'Build-grade parts', 'text' => 'Intel, ASUS, Gigabyte, Corsair & more'],
                'small1' => ['category_id' => $id('cctv-security'), 'text' => 'Protect what matters'],
                'small2' => ['category_id' => $id('monitor'), 'text' => 'Crisp, fast displays'],
            ]],
            ['type' => 'deals', 'eyebrow' => 'Happy Hour Deals', 'title' => 'Today’s hottest prices'],
            ['type' => 'product_tabs', 'eyebrow' => 'Handpicked', 'title' => 'Top picks for you', 'settings' => ['limit' => 10, 'tabs' => [
                ['label' => 'Featured', 'source' => 'featured'],
                ['label' => 'New arrivals', 'source' => 'new'],
                ['label' => 'Best sellers', 'source' => 'best'],
            ]]],
            ['type' => 'pc_builder', 'eyebrow' => 'PC Builder', 'title' => 'Build a PC that fits your budget — and just works.',
                'subtitle' => 'Choose each part yourself or start from a ready build. We check compatibility and power, then assemble and test it for you.',
                'settings' => ['highlight' => 'and just works.', 'steps' => [
                    ['icon' => 'list-check', 'title' => 'Choose parts', 'text' => 'Pick CPU, motherboard, RAM, storage and more from in-stock options.'],
                    ['icon' => 'shield-check', 'title' => 'We check fit', 'text' => 'Socket, memory type, size and power are verified automatically.'],
                    ['icon' => 'truck-delivery', 'title' => 'Built & delivered', 'text' => 'Assembled and tested by our technicians, then delivered to you.'],
                ]]],
            ['type' => 'category_row', 'eyebrow' => 'Work, play & create', 'settings' => ['category_id' => $id('laptop'), 'limit' => 10]],
            ['type' => 'category_row', 'eyebrow' => 'Protect home & business', 'settings' => ['category_id' => $id('cctv-security'), 'limit' => 10]],
            ['type' => 'category_row', 'eyebrow' => 'Fast, reliable connections', 'settings' => ['category_id' => $id('networking'), 'limit' => 10]],
            ['type' => 'services', 'eyebrow' => 'Repair & IT Services', 'title' => 'We fix it, set it up and keep it running',
                'subtitle' => 'Trained technicians for laptops, desktops, printers, CCTV and networks — with a clear quote before any work starts.'],
            ['type' => 'brands', 'title' => 'Official brands we carry'],
            ['type' => 'about', 'title' => 'TechnoFix — computer, CCTV & IT shop in Bangladesh',
                'body' => 'TechnoFix brings genuine laptops, desktop components, monitors, CCTV and security systems, networking gear, printers, software and accessories together in one place — with official warranty, fair prices and cash on delivery across Bangladesh. Build your own PC with our PC Builder, or call our team for advice. We also repair laptops, desktops and printers and install CCTV and office networks.'],
        ];

        foreach ($sections as $sort => $section) {
            HomeSection::create([...$section, 'sort' => $sort]);
        }
    }
}
