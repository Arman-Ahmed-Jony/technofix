<?php

/*
|--------------------------------------------------------------------------
| Demo products (prototype data)
|--------------------------------------------------------------------------
|
| Real product models of the kind the reference shops sell, with approximate
| Bangladesh market prices. No images or copy were taken from other sites.
| Used for the Phase 1 design prototypes and as seed data in Phase 2; the
| owner replaces them with the real catalog before launch.
|
| stock:  in_stock | out_of_stock | pre_order | upcoming | call_for_price
| flags:  featured | new | best | deal
| filters: facet values used by category filters and the spec table
| builder: PC Builder fields that aren't filters (TDP, card length, casing clearance, cooler sockets)
|
*/

return [

    // ─── Motherboards ────────────────────────────────────────────────
    [
        'name' => 'Gigabyte B760M DS3H DDR4 Intel Micro-ATX Motherboard',
        'category' => 'motherboard', 'brand' => 'gigabyte', 'model' => 'B760M DS3H DDR4',
        'price' => 15500, 'regular_price' => 16500, 'warranty' => '3 Years', 'flags' => ['featured', 'deal'],
        'key_features' => ['Supports Intel 12th, 13th & 14th Gen CPUs', '4x DDR4 DIMM, up to 128GB', '2x PCIe 4.0 M.2 slots', 'Realtek 2.5GbE LAN'],
        'filters' => ['Chipset' => 'Intel B760', 'Socket' => 'LGA1700', 'Memory Type' => 'DDR4', 'Form Factor' => 'Micro-ATX'],
        'specs' => [
            'Basic Information' => ['Model' => 'B760M DS3H DDR4', 'Socket' => 'LGA1700', 'Chipset' => 'Intel B760', 'Form Factor' => 'Micro-ATX (24.4 x 24.4 cm)'],
            'Supported CPU' => ['Processor' => 'Intel Core 14th / 13th / 12th Gen, Pentium Gold, Celeron'],
            'Memory' => ['Memory Slots' => '4 x DIMM', 'Memory Type' => 'DDR4', 'Max Memory' => '128GB', 'Memory Speed' => 'Up to 5333 MHz (OC)'],
            'Storage' => ['M.2' => '2 x M.2 (PCIe 4.0 x4)', 'SATA' => '4 x SATA 6Gb/s'],
            'Expansion & Graphics' => ['PCIe Slots' => '1 x PCIe 4.0 x16, 1 x PCIe 3.0 x1', 'Video Outputs' => 'HDMI, DisplayPort, 2 x D-Sub'],
            'Networking & Audio' => ['LAN' => 'Realtek 2.5GbE', 'Audio' => 'Realtek HD Audio codec, 7.1 channel'],
            'Rear I/O' => ['USB' => '1 x USB 3.2 Gen 2, 3 x USB 3.2 Gen 1, 4 x USB 2.0', 'Others' => 'PS/2, 3 x audio jacks'],
        ],
    ],
    [
        'name' => 'ASUS TUF GAMING B760M-PLUS WIFI D4 Micro-ATX Motherboard',
        'category' => 'motherboard', 'brand' => 'asus', 'model' => 'TUF GAMING B760M-PLUS WIFI D4',
        'price' => 23500, 'regular_price' => 24500, 'warranty' => '3 Years', 'flags' => ['featured'],
        'key_features' => ['Intel LGA1700 socket for 12th–14th Gen', 'DDR4 up to 5333 MHz (OC)', 'WiFi 6 + Bluetooth 5.2', 'Military-grade TUF components'],
        'filters' => ['Chipset' => 'Intel B760', 'Socket' => 'LGA1700', 'Memory Type' => 'DDR4', 'Form Factor' => 'Micro-ATX'],
    ],
    [
        'name' => 'ASUS PRIME H610M-E D4 Intel Micro-ATX Motherboard',
        'category' => 'motherboard', 'brand' => 'asus', 'model' => 'PRIME H610M-E D4',
        'price' => 10800, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['Intel LGA1700 socket', '2x DDR4 DIMM, up to 64GB', 'PCIe 4.0 M.2 slot', 'HDMI, DisplayPort & VGA outputs'],
        'filters' => ['Chipset' => 'Intel H610', 'Socket' => 'LGA1700', 'Memory Type' => 'DDR4', 'Form Factor' => 'Micro-ATX'],
    ],
    [
        'name' => 'Gigabyte H610M K DDR4 Intel Micro-ATX Motherboard',
        'category' => 'motherboard', 'brand' => 'gigabyte', 'model' => 'H610M K DDR4',
        'price' => 9400, 'warranty' => '3 Years',
        'key_features' => ['Intel 12th–14th Gen support', '2x DDR4 DIMM', 'PCIe 3.0 M.2 slot', 'Realtek GbE LAN'],
        'filters' => ['Chipset' => 'Intel H610', 'Socket' => 'LGA1700', 'Memory Type' => 'DDR4', 'Form Factor' => 'Micro-ATX'],
    ],
    [
        'name' => 'Esonic H610 DDR4 Intel Micro-ATX Motherboard',
        'category' => 'motherboard', 'brand' => 'esonic', 'model' => 'ES-H610',
        'price' => 7200, 'warranty' => '3 Years', 'stock' => 'out_of_stock',
        'key_features' => ['Intel LGA1700 socket', '2x DDR4 DIMM, up to 64GB', 'M.2 NVMe slot', 'HDMI & VGA outputs'],
        'filters' => ['Chipset' => 'Intel H610', 'Socket' => 'LGA1700', 'Memory Type' => 'DDR4', 'Form Factor' => 'Micro-ATX'],
    ],

    // ─── Processors ──────────────────────────────────────────────────
    [
        'name' => 'Intel Core i5-12400 12th Gen Alder Lake Processor',
        'category' => 'processor', 'brand' => 'intel', 'model' => 'Core i5-12400',
        'builder' => ['TDP' => '65W'],
        'price' => 17800, 'warranty' => '3 Years', 'flags' => ['featured', 'best'],
        'key_features' => ['6 cores, 12 threads', 'Up to 4.40 GHz boost clock', '18MB Intel Smart Cache', 'Intel UHD Graphics 730'],
        'filters' => ['Series' => 'Core i5', 'Generation' => '12th Gen', 'Socket' => 'LGA1700', 'Cores' => '6'],
        'specs' => [
            'Basic Information' => ['Model' => 'Core i5-12400', 'Generation' => '12th Gen (Alder Lake)', 'Socket' => 'LGA1700', 'Lithography' => 'Intel 7'],
            'Performance' => ['Cores / Threads' => '6 / 12', 'Base Clock' => '2.50 GHz', 'Max Turbo' => '4.40 GHz', 'Cache' => '18MB Intel Smart Cache', 'Base Power' => '65W', 'Max Turbo Power' => '117W'],
            'Memory' => ['Memory Type' => 'DDR5 4800 / DDR4 3200', 'Max Memory' => '128GB', 'Channels' => '2'],
            'Graphics' => ['Integrated Graphics' => 'Intel UHD Graphics 730', 'Max Dynamic Frequency' => '1.45 GHz'],
            'Package' => ['Cooler' => 'Intel Laminar RM1 included'],
        ],
    ],
    [
        'name' => 'Intel Core i3-12100 12th Gen Alder Lake Processor',
        'category' => 'processor', 'brand' => 'intel', 'model' => 'Core i3-12100',
        'builder' => ['TDP' => '60W'],
        'price' => 12400, 'warranty' => '3 Years',
        'key_features' => ['4 cores, 8 threads', 'Up to 4.30 GHz boost clock', '12MB Intel Smart Cache', 'Intel UHD Graphics 730'],
        'filters' => ['Series' => 'Core i3', 'Generation' => '12th Gen', 'Socket' => 'LGA1700', 'Cores' => '4'],
    ],
    [
        'name' => 'Intel Core i5-14400F 14th Gen Raptor Lake Processor',
        'category' => 'processor', 'brand' => 'intel', 'model' => 'Core i5-14400F',
        'builder' => ['TDP' => '65W'],
        'price' => 21500, 'regular_price' => 22500, 'warranty' => '3 Years', 'flags' => ['deal'],
        'key_features' => ['10 cores (6P + 4E), 16 threads', 'Up to 4.70 GHz boost clock', '20MB Intel Smart Cache', 'Requires a discrete graphics card'],
        'filters' => ['Series' => 'Core i5', 'Generation' => '14th Gen', 'Socket' => 'LGA1700', 'Cores' => '10'],
    ],
    [
        'name' => 'Intel Core i7-14700K 14th Gen Raptor Lake Processor',
        'category' => 'processor', 'brand' => 'intel', 'model' => 'Core i7-14700K',
        'builder' => ['TDP' => '125W'],
        'price' => 46500, 'warranty' => '3 Years', 'flags' => ['new'],
        'key_features' => ['20 cores (8P + 12E), 28 threads', 'Up to 5.60 GHz boost clock', '33MB Intel Smart Cache', 'Unlocked for overclocking'],
        'filters' => ['Series' => 'Core i7', 'Generation' => '14th Gen', 'Socket' => 'LGA1700', 'Cores' => '20'],
    ],

    // ─── RAM ─────────────────────────────────────────────────────────
    [
        'name' => 'Corsair Vengeance LPX 16GB DDR4 3200MHz Desktop RAM',
        'category' => 'ram', 'brand' => 'corsair', 'model' => 'CMK16GX4M1E3200C16',
        'price' => 5400, 'warranty' => 'Lifetime', 'flags' => ['best'],
        'key_features' => ['16GB DDR4 single module', '3200MHz, CL16', 'Low-profile aluminium heat spreader', 'XMP 2.0 support'],
        'filters' => ['Type' => 'DDR4', 'Capacity' => '16GB', 'Speed' => '3200MHz', 'Form Factor' => 'Desktop (DIMM)'],
    ],
    [
        'name' => 'ADATA XPG Lancer 16GB DDR5 5200MHz Desktop RAM',
        'category' => 'ram', 'brand' => 'adata', 'model' => 'AX5U5200C3816G-CLABK',
        'price' => 6900, 'regular_price' => 7400, 'warranty' => 'Lifetime', 'flags' => ['new', 'deal'],
        'key_features' => ['16GB DDR5 single module', '5200MHz, CL38', 'On-die ECC', 'Intel XMP 3.0 & AMD EXPO ready'],
        'filters' => ['Type' => 'DDR5', 'Capacity' => '16GB', 'Speed' => '5200MHz', 'Form Factor' => 'Desktop (DIMM)'],
    ],
    [
        'name' => 'Apacer Panther 8GB DDR4 3200MHz Desktop RAM',
        'category' => 'ram', 'brand' => 'apacer', 'model' => 'Panther DDR4 8GB',
        'price' => 2450, 'warranty' => 'Lifetime',
        'key_features' => ['8GB DDR4', '3200MHz', 'Heat spreader design', '1.35V low voltage'],
        'filters' => ['Type' => 'DDR4', 'Capacity' => '8GB', 'Speed' => '3200MHz', 'Form Factor' => 'Desktop (DIMM)'],
    ],
    [
        'name' => 'Transcend JetRam 8GB DDR4 3200MHz Laptop RAM',
        'category' => 'ram', 'brand' => 'transcend', 'model' => 'JM3200HSG-8G',
        'price' => 2350, 'warranty' => 'Lifetime',
        'key_features' => ['8GB DDR4 SO-DIMM', '3200MHz', 'For laptops and mini PCs', '1.2V operating voltage'],
        'filters' => ['Type' => 'DDR4', 'Capacity' => '8GB', 'Speed' => '3200MHz', 'Form Factor' => 'Laptop (SO-DIMM)'],
    ],

    // ─── SSD / HDD ───────────────────────────────────────────────────
    [
        'name' => 'Samsung 990 EVO Plus 1TB PCIe 4.0 M.2 NVMe SSD',
        'category' => 'ssd', 'brand' => 'samsung', 'model' => 'MZ-V9S1T0BW',
        'price' => 12900, 'regular_price' => 13800, 'warranty' => '5 Years', 'flags' => ['featured', 'deal'],
        'key_features' => ['Up to 7,250 MB/s sequential read', 'Up to 6,300 MB/s sequential write', 'PCIe 4.0 x4 / 5.0 x2 NVMe', '600 TBW endurance'],
        'filters' => ['Capacity' => '1TB', 'Interface' => 'PCIe 4.0 NVMe', 'Form Factor' => 'M.2 2280'],
        'specs' => [
            'Basic Information' => ['Model' => 'MZ-V9S1T0BW', 'Capacity' => '1TB', 'Form Factor' => 'M.2 2280', 'Interface' => 'PCIe 4.0 x4 / PCIe 5.0 x2, NVMe 2.0'],
            'Performance' => ['Sequential Read' => 'Up to 7,250 MB/s', 'Sequential Write' => 'Up to 6,300 MB/s', 'Random Read' => 'Up to 850K IOPS', 'Random Write' => 'Up to 1,350K IOPS'],
            'Reliability' => ['Endurance' => '600 TBW', 'MTBF' => '1.5 million hours', 'Controller' => 'Samsung in-house', 'NAND' => 'Samsung V-NAND TLC'],
        ],
    ],
    [
        'name' => 'ADATA Legend 710 512GB PCIe 3.0 M.2 NVMe SSD',
        'category' => 'ssd', 'brand' => 'adata', 'model' => 'ALEG-710-512GCS',
        'price' => 4300, 'warranty' => '3 Years',
        'key_features' => ['Up to 2,400 MB/s read', 'Up to 1,800 MB/s write', 'Includes heatsink', '3D NAND flash'],
        'filters' => ['Capacity' => '512GB', 'Interface' => 'PCIe 3.0 NVMe', 'Form Factor' => 'M.2 2280'],
    ],
    [
        'name' => 'Lexar NM620 512GB PCIe 3.0 M.2 NVMe SSD',
        'category' => 'ssd', 'brand' => 'lexar', 'model' => 'LNM620X512G-RNNNG',
        'price' => 4100, 'warranty' => '5 Years',
        'key_features' => ['Up to 3,300 MB/s read', 'Up to 2,400 MB/s write', 'LDPC error correction', '3D TLC NAND'],
        'filters' => ['Capacity' => '512GB', 'Interface' => 'PCIe 3.0 NVMe', 'Form Factor' => 'M.2 2280'],
    ],
    [
        'name' => 'PC Power 512GB M.2 NVMe SSD',
        'category' => 'ssd', 'brand' => 'pc-power', 'model' => 'PCP-NVME-512',
        'price' => 3650, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['Up to 2,000 MB/s read', 'Up to 1,600 MB/s write', 'Low power consumption', 'M.2 2280 form factor'],
        'filters' => ['Capacity' => '512GB', 'Interface' => 'PCIe 3.0 NVMe', 'Form Factor' => 'M.2 2280'],
    ],
    [
        'name' => 'TwinMOS 256GB 2.5 Inch SATA III SSD',
        'category' => 'ssd', 'brand' => 'twinmos', 'model' => 'TM256GH2UGL',
        'price' => 2150, 'warranty' => '3 Years',
        'key_features' => ['Up to 580 MB/s read', 'Up to 550 MB/s write', 'SATA III 6Gb/s', 'Easy upgrade for old laptops & PCs'],
        'filters' => ['Capacity' => '256GB', 'Interface' => 'SATA III', 'Form Factor' => '2.5 Inch'],
    ],
    [
        'name' => 'Western Digital Blue 1TB 7200RPM 3.5 Inch Desktop HDD',
        'category' => 'hdd', 'brand' => 'western-digital', 'model' => 'WD10EZEX',
        'price' => 5200, 'warranty' => '2 Years',
        'key_features' => ['1TB capacity', '7200 RPM', '64MB cache', 'SATA 6Gb/s'],
        'filters' => ['Capacity' => '1TB', 'Use' => 'Desktop', 'RPM' => '7200'],
    ],
    [
        'name' => 'Seagate SkyHawk 2TB Surveillance HDD',
        'category' => 'hdd', 'brand' => 'seagate', 'model' => 'ST2000VX017',
        'price' => 7900, 'warranty' => '3 Years', 'flags' => ['featured'],
        'key_features' => ['Built for 24/7 CCTV recording', 'Supports up to 64 cameras', 'ImagePerfect firmware', '180 TB/year workload'],
        'filters' => ['Capacity' => '2TB', 'Use' => 'Surveillance', 'RPM' => '5400'],
    ],
    [
        'name' => 'Toshiba P300 1TB 7200RPM 3.5 Inch Desktop HDD',
        'category' => 'hdd', 'brand' => 'toshiba', 'model' => 'HDWD110UZSVA',
        'price' => 4950, 'warranty' => '2 Years', 'stock' => 'out_of_stock',
        'key_features' => ['1TB capacity', '7200 RPM', '64MB cache', 'SATA 6Gb/s'],
        'filters' => ['Capacity' => '1TB', 'Use' => 'Desktop', 'RPM' => '7200'],
    ],

    // ─── Power supply / casing / cooling ─────────────────────────────
    [
        'name' => 'Corsair CV650 650W 80 Plus Bronze Power Supply',
        'category' => 'power-supply', 'brand' => 'corsair', 'model' => 'CP-9020236-UK',
        'price' => 7300, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['650W continuous power', '80 PLUS Bronze efficiency', 'Compact 125mm casing', 'Quiet 120mm fan'],
        'filters' => ['Wattage' => '650W', 'Certification' => '80 Plus Bronze'],
    ],
    [
        'name' => 'Gigabyte P650G 650W 80 Plus Gold Power Supply',
        'category' => 'power-supply', 'brand' => 'gigabyte', 'model' => 'GP-P650G',
        'price' => 7900, 'warranty' => '5 Years', 'flags' => ['new'],
        'key_features' => ['650W, 80 PLUS Gold', 'Japanese main capacitors', 'OVP/OPP/SCP/UVP/OCP/OTP protection', '120mm hydraulic bearing fan'],
        'filters' => ['Wattage' => '650W', 'Certification' => '80 Plus Gold'],
    ],
    [
        'name' => 'Antec CSK550 550W 80 Plus Bronze Power Supply',
        'category' => 'power-supply', 'brand' => 'antec', 'model' => 'CSK550',
        'price' => 5600, 'warranty' => '3 Years',
        'key_features' => ['550W, 80 PLUS Bronze', 'Continuous power output', 'Silent 120mm fan', 'Full protection circuitry'],
        'filters' => ['Wattage' => '550W', 'Certification' => '80 Plus Bronze'],
    ],
    [
        'name' => 'Value-Top VT-450 450W Power Supply',
        'category' => 'power-supply', 'brand' => 'value-top', 'model' => 'VT-450',
        'price' => 2300, 'warranty' => '1 Year',
        'key_features' => ['450W rated power', 'Active PFC', '120mm cooling fan', 'Ideal for office PCs'],
        'filters' => ['Wattage' => '450W', 'Certification' => 'Standard'],
    ],
    [
        'name' => 'Value-Top VT-M100 Micro-ATX Desktop Casing',
        'category' => 'casing', 'brand' => 'value-top', 'model' => 'VT-M100',
        'builder' => ['Max GPU Length' => '220mm'],
        'price' => 2600, 'warranty' => 'No Warranty',
        'key_features' => ['Micro-ATX / Mini-ITX support', 'Front USB 3.0 port', 'Tool-less side panel', 'Space for 2 x 120mm fans'],
        'filters' => ['Form Factor' => 'Micro-ATX', 'Side Panel' => 'Metal'],
    ],
    [
        'name' => 'Xtreme XG-301 Mid Tower Gaming Casing',
        'category' => 'casing', 'brand' => 'xtreme', 'model' => 'XG-301',
        'builder' => ['Max GPU Length' => '320mm'],
        'price' => 3200, 'warranty' => 'No Warranty', 'flags' => ['new'],
        'key_features' => ['Tempered glass side panel', 'ATX / Micro-ATX support', '3 x RGB fans pre-installed', 'GPU up to 320mm'],
        'filters' => ['Form Factor' => 'ATX', 'Side Panel' => 'Tempered Glass'],
    ],
    [
        'name' => 'DeepCool AK400 Single Tower CPU Air Cooler',
        'category' => 'cpu-cooler', 'brand' => 'deepcool', 'model' => 'AK400',
        'builder' => ['Socket' => 'LGA1700, LGA1200, LGA115X, AM5, AM4'],
        'price' => 3100, 'warranty' => '1 Year',
        'key_features' => ['4 direct-contact heat pipes', '120mm FDB fan', 'Supports LGA1700 & AM5', '220W TDP'],
        'filters' => ['Type' => 'Air Cooler', 'Fan Size' => '120mm'],
    ],

    // ─── Graphics cards ──────────────────────────────────────────────
    [
        'name' => 'ASUS Dual GeForce RTX 4060 OC Edition 8GB GDDR6 Graphics Card',
        'category' => 'graphics-card', 'brand' => 'asus', 'model' => 'DUAL-RTX4060-O8G',
        'builder' => ['TDP' => '115W', 'Card Length' => '227mm'],
        'price' => 42500, 'regular_price' => 44000, 'warranty' => '3 Years', 'flags' => ['featured', 'deal'],
        'key_features' => ['NVIDIA Ada Lovelace architecture', '8GB GDDR6, 128-bit', 'DLSS 3 & ray tracing', 'Dual Axial-tech fans'],
        'filters' => ['Chipset' => 'NVIDIA', 'Memory' => '8GB', 'Memory Type' => 'GDDR6'],
    ],
    [
        'name' => 'Gigabyte GeForce RTX 3050 WINDFORCE OC 6GB Graphics Card',
        'category' => 'graphics-card', 'brand' => 'gigabyte', 'model' => 'GV-N3050WF2OC-6GD',
        'builder' => ['TDP' => '70W', 'Card Length' => '191mm'],
        'price' => 24500, 'warranty' => '3 Years',
        'key_features' => ['6GB GDDR6, 96-bit', 'WINDFORCE 2X cooling', 'No external power needed (70W)', 'HDMI + 2 x DisplayPort'],
        'filters' => ['Chipset' => 'NVIDIA', 'Memory' => '6GB', 'Memory Type' => 'GDDR6'],
    ],
    [
        'name' => 'Gigabyte GeForce RTX 5060 WINDFORCE OC 8GB Graphics Card',
        'category' => 'graphics-card', 'brand' => 'gigabyte', 'model' => 'GV-N5060WF2OC-8GD',
        'builder' => ['TDP' => '145W', 'Card Length' => '199mm'],
        'price' => 49500, 'warranty' => '3 Years', 'stock' => 'pre_order', 'flags' => ['new'],
        'key_features' => ['NVIDIA Blackwell architecture', '8GB GDDR7', 'DLSS 4 with Multi Frame Generation', 'WINDFORCE cooling'],
        'filters' => ['Chipset' => 'NVIDIA', 'Memory' => '8GB', 'Memory Type' => 'GDDR7'],
    ],

    // ─── Laptops ─────────────────────────────────────────────────────
    [
        'name' => 'ASUS TUF Gaming F15 FX507ZC4 Core i5 12th Gen RTX 3050 15.6" FHD Gaming Laptop',
        'category' => 'laptop', 'brand' => 'asus', 'model' => 'FX507ZC4-HN116W',
        'price' => 104000, 'regular_price' => 110000, 'warranty' => '2 Years', 'flags' => ['featured', 'deal'], 'emi' => true,
        'key_features' => ['Intel Core i5-12500H (up to 4.5 GHz)', '8GB DDR4 RAM, 512GB NVMe SSD', 'NVIDIA RTX 3050 4GB GDDR6', '15.6" FHD 144Hz display'],
        'filters' => ['Processor' => 'Intel Core i5', 'RAM' => '8GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Dedicated'],
        'specs' => [
            'Processor' => ['Processor Model' => 'Intel Core i5-12500H', 'Cores / Threads' => '12 / 16', 'Clock Speed' => 'Up to 4.5 GHz', 'Cache' => '18MB'],
            'Display' => ['Size' => '15.6 inch', 'Resolution' => 'FHD (1920 x 1080)', 'Panel' => 'IPS-level, anti-glare', 'Refresh Rate' => '144Hz'],
            'Memory & Storage' => ['RAM' => '8GB DDR4 3200MHz', 'RAM Slots' => '2 (up to 32GB)', 'Storage' => '512GB PCIe 3.0 NVMe SSD', 'Extra Slot' => '1 x M.2 PCIe'],
            'Graphics' => ['GPU' => 'NVIDIA GeForce RTX 3050', 'VRAM' => '4GB GDDR6'],
            'Connectivity' => ['WiFi' => 'WiFi 6', 'Bluetooth' => '5.2', 'Ports' => '1 x Thunderbolt 4, 2 x USB 3.2, 1 x HDMI 2.0b, RJ45, 3.5mm audio'],
            'Physical' => ['Keyboard' => 'Backlit chiclet (RGB)', 'Battery' => '56WHrs, 4-cell', 'Weight' => '2.2 kg', 'Color' => 'Mecha Gray'],
            'Software' => ['Operating System' => 'Windows 11 Home'],
        ],
    ],
    [
        'name' => 'Lenovo LOQ 15IRX9 Core i5 13th Gen RTX 4050 15.6" FHD Gaming Laptop',
        'category' => 'laptop', 'brand' => 'lenovo', 'model' => '83DV00UJLK',
        'price' => 124500, 'warranty' => '2 Years', 'flags' => ['new'], 'emi' => true,
        'key_features' => ['Intel Core i5-13450HX', '16GB DDR5 RAM, 512GB SSD', 'NVIDIA RTX 4050 6GB', '15.6" FHD 144Hz IPS'],
        'filters' => ['Processor' => 'Intel Core i5', 'RAM' => '16GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Dedicated'],
    ],
    [
        'name' => 'Lenovo IdeaPad Slim 3 15IRH8 Core i5 13th Gen 15.6" FHD Laptop',
        'category' => 'laptop', 'brand' => 'lenovo', 'model' => '83EM00ETLK',
        'price' => 69900, 'warranty' => '2 Years', 'flags' => ['featured'], 'emi' => true,
        'key_features' => ['Intel Core i5-13420H', '8GB LPDDR5 RAM, 512GB SSD', '15.6" FHD IPS display', 'Up to 11 hours battery'],
        'filters' => ['Processor' => 'Intel Core i5', 'RAM' => '8GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Integrated'],
    ],
    [
        'name' => 'Dell Inspiron 15 3530 Core i5 13th Gen 15.6" FHD Laptop',
        'category' => 'laptop', 'brand' => 'dell', 'model' => 'Inspiron 3530',
        'price' => 78000, 'regular_price' => 81000, 'warranty' => '2 Years', 'flags' => ['deal'], 'emi' => true,
        'key_features' => ['Intel Core i5-1334U', '8GB DDR4 RAM, 512GB SSD', '15.6" FHD 120Hz display', 'Spill-resistant keyboard'],
        'filters' => ['Processor' => 'Intel Core i5', 'RAM' => '8GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Integrated'],
    ],
    [
        'name' => 'HP 15-fd0xxx Core i5 13th Gen 15.6" FHD Laptop',
        'category' => 'laptop', 'brand' => 'hp', 'model' => '15-fd0335TU',
        'price' => 71500, 'warranty' => '2 Years', 'emi' => true,
        'key_features' => ['Intel Core i5-1334U', '8GB DDR4 RAM, 512GB SSD', '15.6" FHD anti-glare display', 'Fast Charge support'],
        'filters' => ['Processor' => 'Intel Core i5', 'RAM' => '8GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Integrated'],
    ],
    [
        'name' => 'ASUS Vivobook 15 X1504VA Core i3 13th Gen 15.6" FHD Laptop',
        'category' => 'laptop', 'brand' => 'asus', 'model' => 'X1504VA-NJ1001W',
        'price' => 54500, 'warranty' => '2 Years', 'flags' => ['best'], 'emi' => true,
        'key_features' => ['Intel Core i3-1315U', '8GB DDR4 RAM, 512GB SSD', '15.6" FHD display', 'Fingerprint sensor'],
        'filters' => ['Processor' => 'Intel Core i3', 'RAM' => '8GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Integrated'],
    ],
    [
        'name' => 'HP Victus 15 Core i5 13th Gen RTX 3050 15.6" FHD Gaming Laptop',
        'category' => 'laptop', 'brand' => 'hp', 'model' => '15-fa1xxx',
        'price' => 99000, 'warranty' => '2 Years', 'stock' => 'upcoming',
        'key_features' => ['Intel Core i5-13420H', '8GB DDR4 RAM, 512GB SSD', 'NVIDIA RTX 3050 6GB', '15.6" FHD 144Hz'],
        'filters' => ['Processor' => 'Intel Core i5', 'RAM' => '8GB', 'Storage' => '512GB SSD', 'Display' => '15.6"', 'Graphics' => 'Dedicated'],
    ],

    // ─── Monitors ────────────────────────────────────────────────────
    [
        'name' => 'Samsung Odyssey G3 27" FHD 180Hz Gaming Monitor',
        'category' => 'monitor', 'brand' => 'samsung', 'model' => 'LS27DG302EWXXL',
        'price' => 24900, 'regular_price' => 26500, 'warranty' => '3 Years', 'flags' => ['featured', 'deal'],
        'key_features' => ['27" FHD VA panel', '180Hz refresh, 1ms MPRT', 'AMD FreeSync Premium', 'Height-adjustable stand'],
        'filters' => ['Size' => '27"', 'Resolution' => 'Full HD', 'Panel' => 'VA', 'Refresh Rate' => '180Hz'],
        'specs' => [
            'Display' => ['Screen Size' => '27 inch', 'Panel Type' => 'VA', 'Resolution' => '1920 x 1080 (FHD)', 'Refresh Rate' => '180Hz', 'Response Time' => '1ms (MPRT)', 'Brightness' => '250 cd/m²', 'Contrast' => '3000:1'],
            'Features' => ['Adaptive Sync' => 'AMD FreeSync Premium', 'Eye Care' => 'Flicker Free, Eye Saver Mode', 'Stand' => 'Height, tilt, swivel & pivot'],
            'Connectivity' => ['Ports' => '1 x HDMI 1.4, 1 x DisplayPort 1.2, headphone out'],
            'Physical' => ['VESA Mount' => '100 x 100', 'Power' => 'Internal'],
        ],
    ],
    [
        'name' => 'Samsung LS24C310 24" FHD IPS 75Hz Monitor',
        'category' => 'monitor', 'brand' => 'samsung', 'model' => 'LS24C310EAWXXL',
        'price' => 13500, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['24" FHD IPS panel', '75Hz refresh rate', 'Borderless 3-sided design', 'HDMI & VGA inputs'],
        'filters' => ['Size' => '24"', 'Resolution' => 'Full HD', 'Panel' => 'IPS', 'Refresh Rate' => '75Hz'],
    ],
    [
        'name' => 'Hikvision DS-D5024F2 23.8" FHD IPS Monitor',
        'category' => 'monitor', 'brand' => 'hikvision', 'model' => 'DS-D5024F2-1V2S',
        'price' => 11200, 'warranty' => '3 Years',
        'key_features' => ['23.8" FHD IPS', '100Hz refresh rate', 'Low blue light', 'HDMI & VGA'],
        'filters' => ['Size' => '24"', 'Resolution' => 'Full HD', 'Panel' => 'IPS', 'Refresh Rate' => '100Hz'],
    ],
    [
        'name' => 'Dahua LM22-A200 21.45" FHD Monitor',
        'category' => 'monitor', 'brand' => 'dahua', 'model' => 'DHI-LM22-A200',
        'price' => 8600, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['21.45" FHD VA panel', '75Hz refresh rate', 'Ideal for CCTV & office', 'HDMI & VGA'],
        'filters' => ['Size' => '22"', 'Resolution' => 'Full HD', 'Panel' => 'VA', 'Refresh Rate' => '75Hz'],
    ],
    [
        'name' => 'HP V22i G5 21.5" FHD IPS Monitor',
        'category' => 'monitor', 'brand' => 'hp', 'model' => '65P56AA',
        'price' => 12300, 'warranty' => '3 Years',
        'key_features' => ['21.5" FHD IPS', '75Hz with AMD FreeSync', 'Anti-glare, low blue light', 'HDMI & VGA'],
        'filters' => ['Size' => '22"', 'Resolution' => 'Full HD', 'Panel' => 'IPS', 'Refresh Rate' => '75Hz'],
    ],
    [
        'name' => 'Value-Top 19" HD LED Monitor',
        'category' => 'monitor', 'brand' => 'value-top', 'model' => 'VT-19HD',
        'price' => 6900, 'warranty' => '2 Years',
        'key_features' => ['19" HD LED display', '60Hz refresh rate', 'VESA wall-mount ready', 'HDMI & VGA'],
        'filters' => ['Size' => '19"', 'Resolution' => 'HD', 'Panel' => 'TN', 'Refresh Rate' => '60Hz'],
    ],

    // ─── Networking ──────────────────────────────────────────────────
    [
        'name' => 'TP-Link Archer AX23 AX1800 Dual Band WiFi 6 Router',
        'category' => 'router', 'brand' => 'tp-link', 'model' => 'Archer AX23',
        'price' => 5400, 'regular_price' => 5800, 'warranty' => '3 Years', 'flags' => ['new', 'deal'],
        'key_features' => ['WiFi 6, up to 1.8 Gbps', '4 high-gain antennas', 'OFDMA & MU-MIMO', 'EasyMesh compatible'],
        'filters' => ['WiFi Standard' => 'WiFi 6', 'Speed' => 'AX1800', 'Band' => 'Dual Band'],
        'specs' => [
            'Wireless' => ['Standard' => 'WiFi 6 (802.11ax)', 'Speed' => '1201 Mbps (5 GHz) + 574 Mbps (2.4 GHz)', 'Antennas' => '4 x fixed high-gain', 'Technologies' => 'OFDMA, MU-MIMO, Beamforming'],
            'Hardware' => ['Ports' => '1 x Gigabit WAN, 4 x Gigabit LAN', 'Button' => 'WPS/WiFi, Reset, Power'],
            'Software' => ['Features' => 'EasyMesh, Parental Controls, Guest Network, IPv6', 'Management' => 'TP-Link Tether app, Web UI'],
        ],
    ],
    [
        'name' => 'TP-Link Archer C6 AC1200 Dual Band Gigabit Router',
        'category' => 'router', 'brand' => 'tp-link', 'model' => 'Archer C6',
        'price' => 3350, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['AC1200 dual band WiFi', 'Gigabit WAN & LAN ports', 'MU-MIMO', '4 external antennas'],
        'filters' => ['WiFi Standard' => 'WiFi 5', 'Speed' => 'AC1200', 'Band' => 'Dual Band'],
    ],
    [
        'name' => 'Xiaomi Router AX3000T Dual Band WiFi 6 Router',
        'category' => 'router', 'brand' => 'xiaomi', 'model' => 'AX3000T',
        'price' => 4200, 'warranty' => '1 Year', 'flags' => ['featured'],
        'key_features' => ['WiFi 6, up to 3 Gbps', '160MHz channel bandwidth', 'Mesh networking', 'Gigabit ports'],
        'filters' => ['WiFi Standard' => 'WiFi 6', 'Speed' => 'AX3000', 'Band' => 'Dual Band'],
    ],
    [
        'name' => 'Tenda AC10 AC1200 Dual Band Gigabit Router',
        'category' => 'router', 'brand' => 'tenda', 'model' => 'AC10',
        'price' => 2650, 'warranty' => '1 Year',
        'key_features' => ['AC1200 dual band', 'Gigabit ports', 'MU-MIMO', 'Beamforming+'],
        'filters' => ['WiFi Standard' => 'WiFi 5', 'Speed' => 'AC1200', 'Band' => 'Dual Band'],
    ],
    [
        'name' => 'Mercusys MR30G AC1200 Dual Band Gigabit Router',
        'category' => 'router', 'brand' => 'mercusys', 'model' => 'MR30G',
        'price' => 2350, 'warranty' => '1 Year',
        'key_features' => ['AC1200 dual band', 'Full gigabit ports', 'MU-MIMO', 'IPv6 supported'],
        'filters' => ['WiFi Standard' => 'WiFi 5', 'Speed' => 'AC1200', 'Band' => 'Dual Band'],
    ],
    [
        'name' => 'TP-Link TL-SG1008D 8-Port Gigabit Desktop Switch',
        'category' => 'network-switch', 'brand' => 'tp-link', 'model' => 'TL-SG1008D',
        'price' => 1950, 'warranty' => '3 Years', 'flags' => ['best'],
        'key_features' => ['8 x Gigabit ports', 'Plug & play', 'Green Ethernet power saving', 'Desktop design'],
        'filters' => ['Ports' => '8 Port', 'Speed' => 'Gigabit'],
    ],
    [
        'name' => 'D-Link DES-1016A 16-Port Fast Ethernet Switch',
        'category' => 'network-switch', 'brand' => 'd-link', 'model' => 'DES-1016A',
        'price' => 2900, 'warranty' => '1 Year',
        'key_features' => ['16 x 10/100 Mbps ports', 'Auto MDI/MDIX', 'Metal casing', 'Desktop or rack-mount'],
        'filters' => ['Ports' => '16 Port', 'Speed' => 'Fast Ethernet'],
    ],
    [
        'name' => 'TP-Link TL-WA850RE 300Mbps WiFi Range Extender',
        'category' => 'wifi-range-extender', 'brand' => 'tp-link', 'model' => 'TL-WA850RE',
        'price' => 1850, 'warranty' => '1 Year',
        'key_features' => ['Extends WiFi up to 300Mbps', 'One-touch range extension', 'Ethernet port', 'Smart signal indicator'],
        'filters' => ['Speed' => 'N300'],
    ],
    [
        'name' => 'TP-Link Archer T2U Nano AC600 USB WiFi Adapter',
        'category' => 'wifi-adapter', 'brand' => 'tp-link', 'model' => 'Archer T2U Nano',
        'price' => 1150, 'warranty' => '1 Year',
        'key_features' => ['AC600 dual band', 'Nano-size design', 'USB 2.0', 'Windows & macOS support'],
        'filters' => ['Speed' => 'AC600', 'Interface' => 'USB'],
    ],
    [
        'name' => 'D-Link Cat6 UTP Network Cable (305m Box)',
        'category' => 'utp-cable', 'brand' => 'd-link', 'model' => 'NCB-C6UGRYR-305',
        'price' => 11500, 'warranty' => 'No Warranty',
        'key_features' => ['Cat6, 23 AWG solid copper', '305 meter box', 'Gigabit ready', 'PVC jacket'],
        'filters' => ['Category' => 'Cat6', 'Length' => '305m'],
    ],

    // ─── CCTV & security ─────────────────────────────────────────────
    [
        'name' => 'Hikvision DS-2CD1023G0E-I 2MP IR Network Bullet Camera',
        'category' => 'ip-camera', 'brand' => 'hikvision', 'model' => 'DS-2CD1023G0E-I',
        'price' => 3650, 'warranty' => '2 Years', 'flags' => ['featured'],
        'key_features' => ['2MP, 1920 x 1080 resolution', 'EXIR night vision up to 30m', 'H.265+ compression', 'IP67 weatherproof'],
        'filters' => ['Resolution' => '2MP', 'Type' => 'Bullet', 'Night Vision' => 'IR'],
        'specs' => [
            'Camera' => ['Image Sensor' => '1/2.8" Progressive Scan CMOS', 'Resolution' => '1920 x 1080 (2MP)', 'Lens' => '2.8 / 4 / 6 mm fixed', 'Min. Illumination' => 'Color: 0.01 Lux'],
            'Night Vision' => ['IR Range' => 'Up to 30 m', 'Technology' => 'EXIR 2.0'],
            'Video' => ['Compression' => 'H.265+, H.265, H.264+, H.264', 'Frame Rate' => '30 fps'],
            'General' => ['Power' => '12V DC / PoE (802.3af)', 'Protection' => 'IP67', 'Network' => '1 x RJ45 10/100M'],
        ],
    ],
    [
        'name' => 'Hikvision DS-2CD1043G2-LIU 4MP Smart Hybrid Light Camera',
        'category' => 'ip-camera', 'brand' => 'hikvision', 'model' => 'DS-2CD1043G2-LIU',
        'price' => 5400, 'warranty' => '2 Years', 'flags' => ['new'],
        'key_features' => ['4MP resolution', 'Smart hybrid light (IR + white light)', 'Built-in microphone', 'Human & vehicle detection'],
        'filters' => ['Resolution' => '4MP', 'Type' => 'Bullet', 'Night Vision' => 'Color + IR'],
    ],
    [
        'name' => 'Dahua IPC-HDW1230T1-S5 2MP IR Dome Network Camera',
        'category' => 'ip-camera', 'brand' => 'dahua', 'model' => 'IPC-HDW1230T1-S5',
        'price' => 3350, 'warranty' => '2 Years',
        'key_features' => ['2MP progressive CMOS', 'IR range up to 30m', 'H.265 compression', 'PoE powered'],
        'filters' => ['Resolution' => '2MP', 'Type' => 'Dome', 'Night Vision' => 'IR'],
    ],
    [
        'name' => 'EZVIZ C6N 2MP Pan & Tilt WiFi Smart Home Camera',
        'category' => 'wifi-camera', 'brand' => 'ezviz', 'model' => 'CS-C6N',
        'price' => 3250, 'regular_price' => 3600, 'warranty' => '1 Year', 'flags' => ['best', 'deal'],
        'key_features' => ['1080p Full HD video', '360° pan & 55° tilt', 'Two-way talk', 'Smart motion tracking'],
        'filters' => ['Resolution' => '2MP', 'Type' => 'Pan & Tilt', 'Night Vision' => 'IR'],
        'specs' => [
            'Camera' => ['Resolution' => '1920 x 1080 (2MP)', 'Lens' => '4 mm, F2.2', 'Field of View' => '360° horizontal, 55° vertical', 'Night Vision' => 'Up to 10 m'],
            'Smart Features' => ['Detection' => 'Motion detection & tracking', 'Audio' => 'Two-way talk', 'Privacy' => 'Sleep mode'],
            'Storage & Network' => ['Local Storage' => 'MicroSD up to 256GB', 'Cloud' => 'EZVIZ CloudPlay', 'WiFi' => '2.4 GHz 802.11b/g/n'],
        ],
    ],
    [
        'name' => 'Imou Ranger 2 1080P Pan & Tilt WiFi Camera',
        'category' => 'wifi-camera', 'brand' => 'imou', 'model' => 'IPC-A22EP',
        'price' => 2990, 'warranty' => '1 Year',
        'key_features' => ['1080p Full HD', '355° pan, 80° tilt', 'Human detection', 'Two-way talk'],
        'filters' => ['Resolution' => '2MP', 'Type' => 'Pan & Tilt', 'Night Vision' => 'IR'],
    ],
    [
        'name' => 'Hikvision DS-7104NI-Q1/M 4-Channel Mini NVR',
        'category' => 'nvr', 'brand' => 'hikvision', 'model' => 'DS-7104NI-Q1/M',
        'price' => 4400, 'warranty' => '2 Years',
        'key_features' => ['Up to 4 IP cameras', 'Up to 4MP recording', 'H.265+ decoding', '1 SATA HDD bay (up to 6TB)'],
        'filters' => ['Channels' => '4 Channel'],
    ],
    [
        'name' => 'Dahua XVR1B08-I 8-Channel Penta-brid XVR',
        'category' => 'dvr-xvr', 'brand' => 'dahua', 'model' => 'XVR1B08-I',
        'price' => 4900, 'warranty' => '2 Years',
        'key_features' => ['8 channels, 1080N/720p', 'Supports HDCVI, AHD, TVI, CVBS & IP', 'AI SMD Plus', '1 SATA HDD up to 10TB'],
        'filters' => ['Channels' => '8 Channel'],
    ],
    [
        'name' => 'Hikvision DS-2CE16D0T-EXIPF 2MP Analog Bullet Camera',
        'category' => 'analog-camera', 'brand' => 'hikvision', 'model' => 'DS-2CE16D0T-EXIPF',
        'price' => 1550, 'warranty' => '2 Years', 'flags' => ['best'],
        'key_features' => ['2MP 1080p', 'EXIR night vision up to 20m', '4-in-1 video output', 'IP66 weatherproof'],
        'filters' => ['Resolution' => '2MP', 'Type' => 'Bullet', 'Night Vision' => 'IR'],
    ],
    [
        'name' => 'ZKTeco K40 Pro Fingerprint Time Attendance & Access Control',
        'category' => 'access-control', 'brand' => 'zkteco', 'model' => 'K40 Pro',
        'price' => 8800, 'warranty' => '1 Year', 'flags' => ['featured'],
        'key_features' => ['3,000 fingerprint capacity', 'RFID card support', 'TCP/IP & USB connectivity', 'Built-in battery backup'],
        'filters' => ['Verification' => 'Fingerprint + Card'],
    ],

    // ─── Accessories ─────────────────────────────────────────────────
    [
        'name' => 'Logitech G102 LIGHTSYNC RGB Gaming Mouse',
        'category' => 'mouse', 'brand' => 'logitech', 'model' => '910-005802',
        'price' => 2050, 'warranty' => '2 Years', 'flags' => ['featured', 'best'],
        'key_features' => ['8,000 DPI gaming sensor', 'LIGHTSYNC RGB lighting', '6 programmable buttons', 'Lightweight 85 g'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Gaming'],
        'specs' => [
            'Tracking' => ['Sensor' => 'Gaming-grade optical', 'Resolution' => '200 – 8,000 DPI', 'Polling Rate' => '1000 Hz'],
            'Design' => ['Buttons' => '6 programmable', 'Lighting' => 'LIGHTSYNC RGB, 16.8M colors', 'Weight' => '85 g'],
            'Connectivity' => ['Connection' => 'USB wired', 'Cable Length' => '2.1 m'],
        ],
    ],
    [
        'name' => 'A4Tech OP-620D 2X Click USB Optical Mouse',
        'category' => 'mouse', 'brand' => 'a4tech', 'model' => 'OP-620D',
        'price' => 390, 'warranty' => '1 Year',
        'key_features' => ['1000 DPI optical tracking', '2X Click double-click button', 'Comfortable ambidextrous design', 'USB plug & play'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Office'],
    ],
    [
        'name' => 'Rapoo M100 Silent Multi-Mode Wireless Mouse',
        'category' => 'mouse', 'brand' => 'rapoo', 'model' => 'M100 Silent',
        'price' => 890, 'regular_price' => 990, 'warranty' => '1 Year', 'flags' => ['deal'],
        'key_features' => ['Bluetooth + 2.4 GHz wireless', 'Silent click buttons', 'Connect up to 3 devices', '9-month battery life'],
        'filters' => ['Connectivity' => 'Wireless', 'Type' => 'Office'],
    ],
    [
        'name' => 'Fantech X9 Thor RGB Gaming Mouse',
        'category' => 'mouse', 'brand' => 'fantech', 'model' => 'X9 Thor',
        'price' => 1150, 'warranty' => '1 Year',
        'key_features' => ['Up to 4,800 DPI', '7 programmable buttons', 'RGB lighting', 'Braided cable'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Gaming'],
    ],
    [
        'name' => 'Logitech K120 USB Wired Keyboard',
        'category' => 'keyboard', 'brand' => 'logitech', 'model' => '920-002479',
        'price' => 780, 'warranty' => '1 Year', 'flags' => ['best'],
        'key_features' => ['Full-size layout', 'Spill-resistant design', 'Quiet, low-profile keys', 'USB plug & play'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Membrane'],
    ],
    [
        'name' => 'Fantech MAXFIT61 60% RGB Mechanical Keyboard',
        'category' => 'keyboard', 'brand' => 'fantech', 'model' => 'MK857',
        'price' => 3400, 'warranty' => '1 Year', 'flags' => ['new'],
        'key_features' => ['Compact 60% layout', 'Hot-swappable switches', 'Per-key RGB', 'Detachable Type-C cable'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Mechanical'],
    ],
    [
        'name' => 'A4Tech KR-85 Comfort Round Edge USB Keyboard',
        'category' => 'keyboard', 'brand' => 'a4tech', 'model' => 'KR-85',
        'price' => 620, 'warranty' => '1 Year',
        'key_features' => ['Comfort round-edge keycaps', 'Laser-inscribed keys', 'Splash-proof', 'USB connection'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Membrane'],
    ],
    [
        'name' => 'Havit H2002d Gaming Headphone with Mic',
        'category' => 'headphone', 'brand' => 'havit', 'model' => 'H2002d',
        'price' => 1650, 'warranty' => '1 Year',
        'key_features' => ['50mm drivers', 'Adjustable noise-cancelling mic', '3.5mm + USB power', 'Soft memory-foam ear cushions'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Gaming'],
    ],
    [
        'name' => 'Logitech H111 Stereo Headset with Microphone',
        'category' => 'headphone', 'brand' => 'logitech', 'model' => '981-000588',
        'price' => 950, 'warranty' => '1 Year',
        'key_features' => ['Stereo sound', 'Noise-cancelling microphone', 'Single 3.5mm jack', 'Adjustable headband'],
        'filters' => ['Connectivity' => 'Wired', 'Type' => 'Office'],
    ],
    [
        'name' => 'Logitech C270 HD 720p Webcam',
        'category' => 'webcam', 'brand' => 'logitech', 'model' => '960-000694',
        'price' => 2700, 'warranty' => '1 Year',
        'key_features' => ['720p/30fps video calls', 'Built-in noise-reducing mic', 'Automatic light correction', 'Universal monitor clip'],
        'filters' => ['Resolution' => '720p'],
    ],
    [
        'name' => 'Transcend JetFlash 790 64GB USB 3.1 Pen Drive',
        'category' => 'pen-drive', 'brand' => 'transcend', 'model' => 'TS64GJF790K',
        'price' => 850, 'warranty' => '5 Years',
        'key_features' => ['64GB capacity', 'USB 3.1 Gen 1 speeds', 'Capless sliding design', 'Free RecoveRx software'],
        'filters' => ['Capacity' => '64GB', 'Interface' => 'USB 3.1'],
    ],

    // ─── Cable & converter (brand zones) ─────────────────────────────
    [
        'name' => 'UGREEN 5-in-1 USB-C Hub with 4K HDMI & 100W PD',
        'category' => 'type-c-hub-5-in-1', 'brand' => 'ugreen', 'model' => 'CM418',
        'price' => 2950, 'warranty' => '1 Year', 'flags' => ['featured'],
        'key_features' => ['4K@30Hz HDMI output', '100W Power Delivery pass-through', '3 x USB 3.0 ports', 'Aluminium shell'],
        'filters' => ['Ports' => '5 in 1'],
    ],
    [
        'name' => 'UGREEN 4K HDMI 2.0 Braided Cable (2 Meter)',
        'category' => 'hdmi-cable', 'brand' => 'ugreen', 'model' => 'HD119',
        'price' => 890, 'warranty' => '1 Year', 'flags' => ['best'],
        'key_features' => ['4K@60Hz, 18Gbps', 'Nylon braided jacket', 'Gold-plated connectors', 'HDR & ARC support'],
        'filters' => ['Length' => '2m', 'Version' => 'HDMI 2.0'],
    ],
    [
        'name' => 'DTECH USB 3.0 to VGA Display Adapter',
        'category' => 'usb-to-vga', 'brand' => 'dtech', 'model' => 'DT-6534',
        'price' => 1250, 'warranty' => '6 Months',
        'key_features' => ['Adds an extra VGA monitor', 'Up to 1920 x 1080', 'USB 3.0 interface', 'Mirror & extend modes'],
        'filters' => ['Interface' => 'USB 3.0'],
    ],
    [
        'name' => 'DTECH HDMI to VGA Converter with Audio',
        'category' => 'hdmi-to-vga', 'brand' => 'dtech', 'model' => 'DT-6527',
        'price' => 750, 'warranty' => '6 Months',
        'key_features' => ['1080p output', '3.5mm audio out', 'Micro-USB aux power', 'Plug & play'],
        'filters' => ['Interface' => 'HDMI'],
    ],
    [
        'name' => 'Vention 1 x 2 HDMI Splitter 4K',
        'category' => 'hdmi-splitter', 'brand' => 'vention', 'model' => 'AKOH0',
        'price' => 1950, 'warranty' => '1 Year', 'flags' => ['new'],
        'key_features' => ['One input, two displays', '4K@30Hz output', 'HDCP 1.4', 'USB powered'],
        'filters' => ['Ports' => '1 x 2'],
    ],
    [
        'name' => 'Vention USB-C to HDMI 4K Adapter',
        'category' => 'type-c-to-hdmi', 'brand' => 'vention', 'model' => 'TDCBB',
        'price' => 1350, 'warranty' => '1 Year',
        'key_features' => ['4K@60Hz output', 'Thunderbolt 3/4 compatible', 'Aluminium alloy case', 'Plug & play'],
        'filters' => ['Interface' => 'USB-C'],
    ],

    // ─── Printing ────────────────────────────────────────────────────
    [
        'name' => 'Canon PIXMA G3010 All-in-One Wireless Ink Tank Printer',
        'category' => 'printer', 'brand' => 'canon', 'model' => 'PIXMA G3010',
        'price' => 17500, 'regular_price' => 18500, 'warranty' => '1 Year', 'flags' => ['deal', 'featured'],
        'key_features' => ['Print, scan & copy', 'Wireless printing from phone', 'Refillable ink tanks', 'Up to 6,000 pages (black)'],
        'filters' => ['Type' => 'Ink Tank', 'Function' => 'All-in-One', 'Connectivity' => 'WiFi'],
        'specs' => [
            'Print' => ['Technology' => 'Refillable ink tank', 'Speed' => 'Approx. 9.1 ipm (black) / 5.0 ipm (color)', 'Resolution' => 'Up to 4800 x 1200 dpi', 'Page Yield' => '6,000 (black) / 7,000 (color)'],
            'Scan & Copy' => ['Scanner' => 'Flatbed CIS', 'Scan Resolution' => '600 x 1200 dpi'],
            'Connectivity' => ['Interface' => 'Hi-Speed USB, WiFi', 'Mobile' => 'Canon PRINT app'],
        ],
    ],
    [
        'name' => 'Epson EcoTank L3250 Wi-Fi All-in-One Ink Tank Printer',
        'category' => 'printer', 'brand' => 'epson', 'model' => 'L3250',
        'price' => 18900, 'warranty' => '1 Year', 'flags' => ['best'],
        'key_features' => ['Print, scan & copy', 'Wi-Fi & Wi-Fi Direct', 'Spill-free refilling', 'Up to 4,500 pages (black)'],
        'filters' => ['Type' => 'Ink Tank', 'Function' => 'All-in-One', 'Connectivity' => 'WiFi'],
    ],
    [
        'name' => 'HP LaserJet Pro M111w Wireless Laser Printer',
        'category' => 'printer', 'brand' => 'hp', 'model' => '7MD68A',
        'price' => 16200, 'warranty' => '1 Year',
        'key_features' => ['Mono laser printing', 'Up to 20 ppm', 'WiFi & HP Smart app', 'Compact design'],
        'filters' => ['Type' => 'Laser', 'Function' => 'Print Only', 'Connectivity' => 'WiFi'],
    ],
    [
        'name' => 'HP 150A Black Original LaserJet Toner Cartridge',
        'category' => 'toner', 'brand' => 'hp', 'model' => 'W1500A',
        'price' => 6100, 'warranty' => 'No Warranty',
        'key_features' => ['Original HP toner', 'Approx. 975 pages yield', 'For HP LaserJet M111 / M141 series', 'Sharp black text'],
        'filters' => ['Color' => 'Black'],
    ],

    // ─── Software ────────────────────────────────────────────────────
    [
        'name' => 'Microsoft Windows 11 Pro 64-bit OEM License',
        'category' => 'windows', 'brand' => 'microsoft', 'model' => 'FQC-10528',
        'price' => 18500, 'warranty' => 'Lifetime License',
        'key_features' => ['Genuine OEM license', 'BitLocker & Remote Desktop', 'Hyper-V support', 'For one PC'],
        'filters' => ['License' => 'OEM', 'Users' => '1 PC'],
    ],
    [
        'name' => 'Microsoft Office Home 2024 (1 PC / Mac)',
        'category' => 'office', 'brand' => 'microsoft', 'model' => 'EP2-06606',
        'price' => 21000, 'warranty' => 'Lifetime License', 'flags' => ['new'],
        'key_features' => ['Word, Excel, PowerPoint & OneNote', 'One-time purchase', 'For 1 PC or Mac', 'Classic desktop apps'],
        'filters' => ['License' => 'Retail', 'Users' => '1 PC'],
    ],
    [
        'name' => 'Kaspersky Standard 1 Device 1 Year Antivirus',
        'category' => 'anti-virus', 'brand' => 'kaspersky', 'model' => 'KL1041',
        'price' => 750, 'warranty' => '1 Year Subscription', 'flags' => ['best'],
        'key_features' => ['Real-time antivirus protection', 'Anti-phishing & firewall', 'Performance optimization', '1 device, 1 year'],
        'filters' => ['License' => '1 Year', 'Users' => '1 Device'],
    ],
    [
        'name' => 'ESET NOD32 Antivirus 1 User 1 Year',
        'category' => 'anti-virus', 'brand' => 'eset', 'model' => 'NOD32-1U',
        'price' => 650, 'warranty' => '1 Year Subscription',
        'key_features' => ['Light on system resources', 'Anti-ransomware shield', 'Gamer mode', '1 user, 1 year'],
        'filters' => ['License' => '1 Year', 'Users' => '1 Device'],
    ],

    // ─── UPS ─────────────────────────────────────────────────────────
    [
        'name' => 'Apollo 650VA Offline UPS',
        'category' => 'ups', 'brand' => 'apollo', 'model' => 'AP-650VA',
        'price' => 3550, 'warranty' => '1 Year', 'flags' => ['best'],
        'key_features' => ['650VA / 360W', '10–15 minutes backup for a PC', 'AVR voltage protection', '2 output sockets'],
        'filters' => ['Capacity' => '650VA', 'Type' => 'Offline'],
    ],
    [
        'name' => 'Maxgreen 1200VA Offline UPS',
        'category' => 'ups', 'brand' => 'maxgreen', 'model' => 'MG-1200VA',
        'price' => 7600, 'warranty' => '1 Year',
        'key_features' => ['1200VA / 720W', 'Longer backup with 2 batteries', 'Microprocessor control', 'Cold start supported'],
        'filters' => ['Capacity' => '1200VA', 'Type' => 'Offline'],
    ],
    [
        'name' => 'Apollo 1KVA Online UPS',
        'category' => 'ups', 'brand' => 'apollo', 'model' => 'AP-1KVA-ONLINE',
        'price' => null, 'warranty' => '1 Year', 'stock' => 'call_for_price',
        'key_features' => ['True online double conversion', 'Pure sine wave output', 'For servers & sensitive equipment', 'LCD status display'],
        'filters' => ['Capacity' => '1000VA', 'Type' => 'Online'],
    ],

];
