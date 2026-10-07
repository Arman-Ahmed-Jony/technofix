<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * PC Builder page text and compatibility checks — edited in Admin → Catalog → PC Builder rules.
 */
class PcBuilderSettings extends Settings
{
    public string $title;

    public string $intro;

    /** Printed at the bottom of the build quotation. */
    public string $quote_note;

    /** Processor ↔ motherboard ↔ CPU cooler socket. */
    public bool $check_socket;

    /** RAM ↔ motherboard memory type (DDR4 / DDR5). */
    public bool $check_memory;

    /** Motherboard size ↔ casing (Mini-ITX < Micro-ATX < ATX < E-ATX). */
    public bool $check_form_factor;

    /** Graphics card length ↔ casing clearance. */
    public bool $check_gpu_length;

    /** Estimated power draw ↔ power supply wattage. */
    public bool $check_power;

    /** Watts added for the motherboard, RAM, storage and fans on top of processor + graphics card TDP. */
    public int $base_watts;

    /** Extra margin recommended on top of the estimate (%). */
    public int $psu_headroom;

    public static function group(): string
    {
        return 'pc_builder';
    }
}
