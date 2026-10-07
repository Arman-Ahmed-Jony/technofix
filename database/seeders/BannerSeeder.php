<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

/**
 * Starter hero slides (the launch designs) and a sample announcement (switched off). Runs only while there are no
 * banners yet, so the owner's changes are never overwritten.
 */
class BannerSeeder extends Seeder
{
    public function run(): void
    {
        if (Banner::exists()) {
            return;
        }

        $slides = [
            [
                'eyebrow' => 'PC Builder', 'icon' => 'cpu', 'visual' => 'pc-build', 'color' => 'blue',
                'title' => 'Build your dream PC, part by part', 'highlight' => 'dream PC',
                'text' => 'Pick compatible components, watch the total update live, and get it assembled and tested by our technicians.',
                'button_label' => 'Start building', 'button_url' => '/pc-builder', 'button2_label' => 'Ask an expert', 'button2_url' => '{call}',
            ],
            [
                'eyebrow' => 'Happy Hour', 'icon' => 'flame', 'visual' => 'deals', 'color' => 'orange',
                'title' => 'Deals up to 15% off — today only', 'highlight' => '15% off',
                'text' => 'Limited-time prices on components, laptops, networking and accessories while the timer runs.',
                'button_label' => 'Shop deals', 'button_url' => '#happy-hour', 'button2_label' => 'All offers', 'button2_url' => '/offers',
            ],
            [
                'eyebrow' => 'CCTV & Security', 'icon' => 'device-cctv', 'visual' => 'cctv', 'color' => 'teal',
                'title' => 'Smart CCTV for home & business', 'highlight' => 'home & business',
                'text' => 'Hikvision, Dahua and EZVIZ systems with a free site survey, professional installation and phone viewing.',
                'button_label' => 'Shop CCTV', 'button_url' => '/cctv-security', 'button2_label' => 'Book a survey', 'button2_url' => '{call}',
            ],
            [
                'eyebrow' => 'Repair Service', 'icon' => 'tool', 'visual' => 'repair', 'color' => 'indigo',
                'title' => 'Laptop & desktop repair, done right', 'highlight' => 'done right',
                'text' => 'Fast diagnosis, genuine parts and a clear quote before any work starts. Call or WhatsApp us.',
                'button_label' => 'Our services', 'button_url' => '/services', 'button2_label' => 'Call us', 'button2_url' => '{call}',
            ],
        ];

        foreach ($slides as $sort => $slide) {
            Banner::create([...$slide, 'placement' => 'hero', 'style' => 'designed', 'sort' => $sort]);
        }

        Banner::create([
            'placement' => 'announcement', 'icon' => 'truck-delivery', 'color' => 'blue', 'is_active' => false,
            'title' => 'Cash on delivery all over Bangladesh — order online, pay when it arrives.',
            'button_label' => 'Shop now', 'button_url' => '/',
        ]);
    }
}
