<?php

/*
|--------------------------------------------------------------------------
| TechnoFix category map
|--------------------------------------------------------------------------
|
| Source: owner's "WEB SITE.xlsx" (spelling corrected). Top-level entries are
| the menu groups. `brands` lists the brands the owner named for a category;
| brands are separate records, not sub-categories (see PLAN.md §5.1).
|
| Used by the Phase 1 prototypes for the mega menu, and by the Phase 2
| seeder to create the real category tree.
|
*/

return [

    'brands' => [
        'a4tech' => 'A4Tech',
        'acer' => 'Acer',
        'adata' => 'ADATA',
        'antec' => 'Antec',
        'apacer' => 'Apacer',
        'apollo' => 'Apollo',
        'aptech' => 'Aptech',
        'asus' => 'ASUS',
        'aula' => 'AULA',
        'canon' => 'Canon',
        'corsair' => 'Corsair',
        'd-link' => 'D-Link',
        'dahua' => 'Dahua',
        'deepcool' => 'DeepCool',
        'dell' => 'Dell',
        'delux' => 'Delux',
        'dtech' => 'DTECH',
        'epson' => 'Epson',
        'eset' => 'ESET',
        'esonic' => 'Esonic',
        'ezviz' => 'EZVIZ',
        'fantech' => 'Fantech',
        'fast-key' => 'Fast Key',
        'gigabyte' => 'Gigabyte',
        'havit' => 'Havit',
        'hero' => 'Hero',
        'hikvision' => 'Hikvision',
        'hp' => 'HP',
        'huntkey' => 'Huntkey',
        'imou' => 'Imou',
        'intel' => 'Intel',
        'kaspersky' => 'Kaspersky',
        'lenovo' => 'Lenovo',
        'lexar' => 'Lexar',
        'logitech' => 'Logitech',
        'maxgreen' => 'Maxgreen',
        'mercusys' => 'Mercusys',
        'micropack' => 'Micropack',
        'microsoft' => 'Microsoft',
        'orasix' => 'Orasix',
        'pc-power' => 'PC Power',
        'pny' => 'PNY',
        'rapoo' => 'Rapoo',
        'razer' => 'Razer',
        'samsung' => 'Samsung',
        'seagate' => 'Seagate',
        'targus' => 'Targus',
        'tenda' => 'Tenda',
        'toshiba' => 'Toshiba',
        'tp-link' => 'TP-Link',
        'transcend' => 'Transcend',
        'twinmos' => 'TwinMOS',
        'txrui' => 'TXRUI',
        'ugreen' => 'UGREEN',
        'value-top' => 'Value-Top',
        'vention' => 'Vention',
        'western-digital' => 'Western Digital',
        'xiaomi' => 'Xiaomi',
        'xtreme' => 'Xtreme',
        'zkteco' => 'ZKTeco',
    ],

    // Brands with their own "Exclusive Zone" brand-store page (slug => tagline).
    'brand_zones' => [
        'ugreen' => 'Chargers, hubs & cables',
        'dtech' => 'Converters & adapters',
        'vention' => 'Cables, splitters & docks',
    ],

    'categories' => [

        [
            'name' => 'Components', 'slug' => 'component', 'icon' => 'cpu',
            'children' => [
                ['name' => 'Motherboard', 'slug' => 'motherboard', 'icon' => 'cpu-2', 'brands' => ['gigabyte', 'asus', 'esonic']],
                ['name' => 'Processor', 'slug' => 'processor', 'icon' => 'cpu', 'brands' => ['intel']],
                ['name' => 'RAM', 'slug' => 'ram', 'icon' => 'components', 'brands' => ['adata', 'apacer', 'transcend', 'pny', 'corsair', 'txrui']],
                ['name' => 'Hard Disk Drive (HDD)', 'slug' => 'hdd', 'icon' => 'database', 'brands' => ['western-digital', 'toshiba', 'seagate']],
                ['name' => 'Solid State Drive (SSD)', 'slug' => 'ssd', 'icon' => 'device-floppy', 'brands' => ['adata', 'apacer', 'pny', 'corsair', 'gigabyte', 'lexar', 'pc-power', 'samsung', 'twinmos']],
                ['name' => 'Power Supply', 'slug' => 'power-supply', 'icon' => 'bolt', 'brands' => ['value-top', 'corsair', 'huntkey', 'gigabyte', 'antec']],
                ['name' => 'Casing', 'slug' => 'casing', 'icon' => 'box', 'brands' => ['value-top', 'acer', 'xtreme']],
                ['name' => 'CPU Cooler', 'slug' => 'cpu-cooler', 'icon' => 'snowflake'],
                ['name' => 'Casing Fan', 'slug' => 'casing-fan', 'icon' => 'car-fan'],
                ['name' => 'Graphics Card', 'slug' => 'graphics-card', 'icon' => 'gpu', 'brands' => ['asus', 'gigabyte']],
            ],
        ],

        [
            'name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'device-laptop',
            'brands' => ['hp', 'dell', 'asus', 'lenovo'],
        ],

        [
            'name' => 'Monitor', 'slug' => 'monitor', 'icon' => 'device-desktop',
            'brands' => ['hikvision', 'value-top', 'dahua', 'samsung', 'hp'],
        ],

        [
            'name' => 'Networking', 'slug' => 'networking', 'icon' => 'router',
            'children' => [
                ['name' => 'Router', 'slug' => 'router', 'icon' => 'router'],
                ['name' => 'Switch', 'slug' => 'network-switch', 'icon' => 'sitemap'],
                ['name' => 'WiFi Range Extender', 'slug' => 'wifi-range-extender', 'icon' => 'antenna-bars-5'],
                ['name' => 'WiFi Adapter', 'slug' => 'wifi-adapter', 'icon' => 'wifi'],
                ['name' => 'UTP Cable', 'slug' => 'utp-cable', 'icon' => 'plug-connected'],
                ['name' => 'LAN Card', 'slug' => 'lan-card', 'icon' => 'network'],
                ['name' => 'Connector', 'slug' => 'connector', 'icon' => 'plug'],
                ['name' => 'ONU', 'slug' => 'onu', 'icon' => 'access-point'],
                ['name' => 'Crimping Tool', 'slug' => 'crimping-tool', 'icon' => 'tool'],
                ['name' => 'Cable Tester', 'slug' => 'cable-tester', 'icon' => 'tools'],
                ['name' => 'Media Converter', 'slug' => 'media-converter', 'icon' => 'arrows-exchange'],
                ['name' => 'TJ Box', 'slug' => 'tj-box', 'icon' => 'box'],
                ['name' => 'LC Patch Cable', 'slug' => 'lc-patch-cable', 'icon' => 'plug-connected'],
                ['name' => 'Splicer Kit', 'slug' => 'splicer-kit', 'icon' => 'tools'],
            ],
        ],

        [
            'name' => 'CCTV & Security', 'slug' => 'cctv-security', 'icon' => 'device-cctv',
            'children' => [
                ['name' => 'WiFi Camera', 'slug' => 'wifi-camera', 'icon' => 'camera', 'brands' => ['ezviz', 'imou', 'hero']],
                ['name' => 'IP Camera', 'slug' => 'ip-camera', 'icon' => 'device-cctv', 'brands' => ['hikvision', 'dahua']],
                ['name' => 'Analog Camera', 'slug' => 'analog-camera', 'icon' => 'device-cctv', 'brands' => ['hikvision', 'dahua']],
                ['name' => 'DVR / XVR', 'slug' => 'dvr-xvr', 'icon' => 'server', 'brands' => ['hikvision', 'dahua']],
                ['name' => 'NVR', 'slug' => 'nvr', 'icon' => 'server', 'brands' => ['hikvision', 'dahua']],
                ['name' => 'Power Adapter', 'slug' => 'cctv-power-adapter', 'icon' => 'plug'],
                ['name' => 'Video Jointer', 'slug' => 'video-jointer', 'icon' => 'arrows-split'],
                ['name' => 'Access Control', 'slug' => 'access-control', 'icon' => 'fingerprint', 'brands' => ['zkteco']],
            ],
        ],

        [
            'name' => 'Accessories', 'slug' => 'accessories', 'icon' => 'headphones',
            'children' => [
                ['name' => 'Mouse', 'slug' => 'mouse', 'icon' => 'mouse', 'brands' => ['a4tech', 'logitech', 'havit', 'orasix', 'micropack', 'fantech', 'gigabyte', 'rapoo', 'razer', 'targus', 'value-top', 'hp', 'dell', 'delux', 'aptech']],
                ['name' => 'Keyboard', 'slug' => 'keyboard', 'icon' => 'keyboard', 'brands' => ['a4tech', 'aptech', 'asus', 'aula', 'delux', 'fantech', 'fast-key', 'logitech', 'micropack', 'orasix', 'pc-power', 'value-top', 'rapoo']],
                ['name' => 'Headphone', 'slug' => 'headphone', 'icon' => 'headphones', 'brands' => ['a4tech', 'logitech', 'rapoo', 'havit']],
                ['name' => 'Mouse Pad', 'slug' => 'mouse-pad', 'icon' => 'mouse'],
                ['name' => 'Speaker', 'slug' => 'speaker', 'icon' => 'device-speaker'],
                ['name' => 'Webcam', 'slug' => 'webcam', 'icon' => 'camera'],
                ['name' => 'Card Reader', 'slug' => 'card-reader', 'icon' => 'device-sd-card'],
                ['name' => 'USB Hub', 'slug' => 'usb-hub', 'icon' => 'usb'],
                ['name' => 'Microphone', 'slug' => 'microphone', 'icon' => 'microphone'],
                ['name' => 'Presenter', 'slug' => 'presenter', 'icon' => 'presentation'],
                ['name' => 'Memory Card', 'slug' => 'memory-card', 'icon' => 'device-sd-card'],
                ['name' => 'Sound Card', 'slug' => 'sound-card', 'icon' => 'device-speaker'],
                ['name' => 'Pen Drive', 'slug' => 'pen-drive', 'icon' => 'device-usb'],
                ['name' => 'Thermal Paste', 'slug' => 'thermal-paste', 'icon' => 'temperature'],
                ['name' => 'HDD Enclosure', 'slug' => 'hdd-case', 'icon' => 'database'],
                ['name' => 'Power Strip', 'slug' => 'power-strip', 'icon' => 'outlet'],
                ['name' => 'Bluetooth Adapter', 'slug' => 'bluetooth-adapter', 'icon' => 'bluetooth'],
                ['name' => 'Printer Hub', 'slug' => 'printer-hub', 'icon' => 'printer'],
                ['name' => 'Smart TV Box', 'slug' => 'smart-tv-box', 'icon' => 'device-tv'],
                ['name' => 'Blower', 'slug' => 'blower', 'icon' => 'wind'],
                ['name' => 'HDD Caddy', 'slug' => 'hdd-caddy', 'icon' => 'database'],
                ['name' => 'Cleaning Kit', 'slug' => 'cleaning-kit', 'icon' => 'spray'],
                ['name' => 'Router Adapter', 'slug' => 'router-adapter', 'icon' => 'plug'],
                ['name' => 'Monitor Adapter', 'slug' => 'monitor-adapter', 'icon' => 'plug'],
                ['name' => 'ONU Adapter', 'slug' => 'onu-adapter', 'icon' => 'plug'],
                ['name' => 'Switch Adapter', 'slug' => 'switch-adapter', 'icon' => 'plug'],
                ['name' => 'HDMI Splitter', 'slug' => 'hdmi-splitter', 'icon' => 'arrows-split'],
                ['name' => 'VGA Splitter', 'slug' => 'vga-splitter', 'icon' => 'arrows-split'],
                ['name' => 'HDMI Switch', 'slug' => 'hdmi-switch', 'icon' => 'arrows-exchange'],
                ['name' => 'VGA Switch', 'slug' => 'vga-switch', 'icon' => 'arrows-exchange'],
                ['name' => 'Laptop Battery', 'slug' => 'laptop-battery', 'icon' => 'battery-charging'],
                ['name' => 'CMOS Battery', 'slug' => 'cmos-battery', 'icon' => 'battery'],
                ['name' => 'Wall Mount', 'slug' => 'wall-mount', 'icon' => 'device-tv'],
                ['name' => 'Router Stand', 'slug' => 'router-stand', 'icon' => 'router'],
            ],
        ],

        [
            'name' => 'Cable & Converter', 'slug' => 'cable-converter', 'icon' => 'plug-connected',
            'children' => [
                [
                    'name' => 'Cable', 'slug' => 'cable', 'icon' => 'plug-connected',
                    'children' => [
                        ['name' => 'Display Cable', 'slug' => 'display-cable'],
                        ['name' => 'HDMI Cable', 'slug' => 'hdmi-cable'],
                        ['name' => 'VGA Cable', 'slug' => 'vga-cable'],
                        ['name' => 'Mini / Micro HDMI Cable', 'slug' => 'mini-micro-hdmi-cable'],
                        ['name' => 'USB Extension Cable', 'slug' => 'usb-extension-cable'],
                        ['name' => 'Fiber Patch Cord', 'slug' => 'fiber-patch-cord'],
                        ['name' => 'Scanner Data Cable', 'slug' => 'scanner-data-cable'],
                        ['name' => 'Printer Data Cable', 'slug' => 'printer-data-cable'],
                        ['name' => 'Audio Cable', 'slug' => 'audio-cable'],
                        ['name' => 'DVI Cable', 'slug' => 'dvi-cable'],
                        ['name' => 'SATA Data Cable', 'slug' => 'sata-data-cable'],
                    ],
                ],
                [
                    'name' => 'Converter', 'slug' => 'converter', 'icon' => 'arrows-exchange',
                    'children' => [
                        ['name' => 'Type-C Hub (4 in 1)', 'slug' => 'type-c-hub-4-in-1'],
                        ['name' => 'Type-C Hub (5 in 1)', 'slug' => 'type-c-hub-5-in-1'],
                        ['name' => 'Type-C Hub (8 in 1)', 'slug' => 'type-c-hub-8-in-1'],
                        ['name' => 'Type-C Hub (11 in 1)', 'slug' => 'type-c-hub-11-in-1'],
                        ['name' => 'Type-C to HDMI', 'slug' => 'type-c-to-hdmi'],
                        ['name' => 'Type-C to VGA', 'slug' => 'type-c-to-vga'],
                        ['name' => 'Type-C to LAN', 'slug' => 'type-c-to-lan'],
                        ['name' => 'HDMI to VGA', 'slug' => 'hdmi-to-vga'],
                        ['name' => 'VGA to HDMI', 'slug' => 'vga-to-hdmi'],
                        ['name' => 'HDMI to AV', 'slug' => 'hdmi-to-av'],
                        ['name' => 'AV to HDMI', 'slug' => 'av-to-hdmi'],
                        ['name' => 'HDMI to DVI', 'slug' => 'hdmi-to-dvi'],
                        ['name' => 'DVI to HDMI', 'slug' => 'dvi-to-hdmi'],
                        ['name' => 'DVI to VGA', 'slug' => 'dvi-to-vga'],
                        ['name' => 'DP to HDMI', 'slug' => 'dp-to-hdmi'],
                        ['name' => 'DP to VGA', 'slug' => 'dp-to-vga'],
                        ['name' => 'USB to HDMI', 'slug' => 'usb-to-hdmi'],
                        ['name' => 'USB to VGA', 'slug' => 'usb-to-vga'],
                        ['name' => 'USB to LAN / RS232', 'slug' => 'usb-to-rs232'],
                        ['name' => 'USB to PS/2', 'slug' => 'usb-to-ps2'],
                        ['name' => 'USB to SATA', 'slug' => 'usb-to-sata'],
                        ['name' => 'IDE to SATA', 'slug' => 'ide-to-sata'],
                        ['name' => 'HDMI Extender', 'slug' => 'hdmi-extender'],
                        ['name' => 'USB Extender', 'slug' => 'usb-extender'],
                        ['name' => 'Capture Card', 'slug' => 'capture-card'],
                        ['name' => 'OTG Adapter', 'slug' => 'otg-adapter'],
                        ['name' => 'SD to Micro SD Adapter', 'slug' => 'sd-to-micro-sd-adapter'],
                        ['name' => 'UTP Jointer', 'slug' => 'utp-jointer'],
                    ],
                ],
            ],
        ],

        [
            'name' => 'Printing', 'slug' => 'printing-solution', 'icon' => 'printer',
            'children' => [
                ['name' => 'Printer', 'slug' => 'printer', 'icon' => 'printer', 'brands' => ['hp', 'canon', 'epson']],
                ['name' => 'Scanner', 'slug' => 'scanner', 'icon' => 'scan'],
                ['name' => 'Refill Ink', 'slug' => 'refill-ink', 'icon' => 'droplet'],
                ['name' => 'Toner', 'slug' => 'toner', 'icon' => 'box'],
                ['name' => 'Cartridge', 'slug' => 'cartridge', 'icon' => 'droplet'],
            ],
        ],

        [
            'name' => 'Software', 'slug' => 'software', 'icon' => 'app-window',
            'children' => [
                ['name' => 'Anti-Virus', 'slug' => 'anti-virus', 'icon' => 'virus', 'brands' => ['kaspersky', 'eset']],
                ['name' => 'Windows', 'slug' => 'windows', 'icon' => 'brand-windows', 'brands' => ['microsoft']],
                ['name' => 'Office', 'slug' => 'office', 'icon' => 'brand-office', 'brands' => ['microsoft']],
            ],
        ],

        [
            'name' => 'UPS', 'slug' => 'ups', 'icon' => 'battery-charging',
            'brands' => ['apollo', 'maxgreen'],
        ],

    ],

];
