<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pc_builder.title', 'Build your PC');
        $this->migrator->add('pc_builder.intro', 'Pick a part for each slot — we check that everything fits together and works out the power you need. Not sure? Send us your build and our team will help.');
        $this->migrator->add('pc_builder.check_socket', true);
        $this->migrator->add('pc_builder.check_memory', true);
        $this->migrator->add('pc_builder.check_form_factor', true);
        $this->migrator->add('pc_builder.check_gpu_length', true);
        $this->migrator->add('pc_builder.check_power', true);
        $this->migrator->add('pc_builder.base_watts', 80);
        $this->migrator->add('pc_builder.psu_headroom', 30);
    }
};
