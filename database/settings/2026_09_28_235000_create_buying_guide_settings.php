<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/*
| Default buying-guide text and questions (the texts the site used before they became editable).
*/
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('buying_guide.text', 'Shop genuine {name} products at TechnoFix with official warranty and the latest prices in Bangladesh. '
            .'Compare specifications, filter by brand and budget, and order online with cash on delivery anywhere in the country — '
            .'or visit us for expert advice before you buy.');

        $this->migrator->add('buying_guide.faqs', [
            [
                'question' => 'What is the price of {name} in Bangladesh?',
                'answer' => 'Prices on this page are updated regularly. Use the price filter to find {name} options within your budget, or call us for the latest offers.',
            ],
            [
                'question' => 'Do you offer warranty on {name}?',
                'answer' => 'Yes. Every product comes with the official brand warranty shown on its product page.',
            ],
            [
                'question' => 'Can I pay cash on delivery?',
                'answer' => 'Yes, cash on delivery is available all over Bangladesh.',
            ],
        ]);
    }
};
