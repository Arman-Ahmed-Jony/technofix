<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Starter content for pages, FAQs and services — DRAFT copy for the owner to review and edit
 * in the admin before launch. Safe to re-run: existing records are matched by slug / question.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $sort => $page) {
            Page::updateOrCreate(['slug' => $page['slug']], [...$page, 'sort' => $sort]);
        }

        foreach ($this->services() as $sort => $service) {
            Service::updateOrCreate(['slug' => $service['slug']], [...$service, 'sort' => $sort]);
        }

        foreach ($this->faqs() as $sort => [$group, $question, $answer]) {
            Faq::updateOrCreate(['question' => $question], ['group' => $group, 'answer' => $answer, 'sort' => $sort]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'title' => 'About TechnoFix', 'slug' => 'about', 'footer_group' => 'company',
                'excerpt' => 'Genuine computers, CCTV and networking gear with expert repair and installation — all in one place.',
                'content' => '<p>TechnoFix is a computer, security and IT shop in Bangladesh. We sell genuine laptops, desktop components, monitors, CCTV and access-control systems, networking equipment, printers, software and accessories — and we repair, install and maintain them too.</p>'
                    .'<h2>What we stand for</h2><ul><li><strong>100% genuine products</strong> sourced from official distributors, with official warranty.</li><li><strong>Honest advice</strong> — we help you choose what you actually need, within your budget.</li><li><strong>Service after sale</strong> — our technicians repair, upgrade and install what we sell.</li></ul>'
                    .'<h2>Why shop with us</h2><p>Order online with cash on delivery anywhere in Bangladesh, build your own PC with our PC Builder, or visit our store to talk to an expert. Businesses can ask us for quotes, bulk pricing and on-site installation.</p>',
            ],
            [
                'title' => 'Delivery Information', 'slug' => 'delivery', 'footer_group' => 'help',
                'excerpt' => 'Where we deliver, how long it takes and how to pay.',
                'content' => '<h2>Where we deliver</h2><p>We deliver all over Bangladesh. Delivery charges depend on your district and are shown at checkout before you place your order. You can also choose store pickup.</p>'
                    .'<h2>How long it takes</h2><p>Estimated delivery times are shown next to each delivery option at checkout. Most orders inside Dhaka arrive within 1–2 working days and outside Dhaka within 2–4 working days.</p>'
                    .'<h2>Order confirmation</h2><p>After you place an order, our team calls you to confirm the items, price and address. Please keep your phone reachable.</p>'
                    .'<h2>Paying on delivery</h2><p>Pay the delivery person in cash when your order arrives. Please check the package for visible damage before accepting it.</p>',
            ],
            [
                'title' => 'Warranty Policy', 'slug' => 'warranty', 'footer_group' => 'help',
                'excerpt' => 'How brand warranty works for products bought from TechnoFix.',
                'content' => '<p>Every product we sell comes with the official warranty shown on its product page. Warranty periods and terms are set by each brand.</p>'
                    .'<h2>To claim warranty</h2><ol><li>Keep your invoice and the original box, serial number stickers and accessories.</li><li>Contact us with your order number and a short description of the problem.</li><li>Bring the product to our store or send it by courier; we handle the claim with the brand.</li></ol>'
                    .'<h2>Not covered</h2><p>Physical or liquid damage, burnt components, damaged or removed serial stickers, unauthorised repairs, and normal wear and tear are usually not covered by brand warranty.</p>'
                    .'<p>Repair times depend on the brand and parts availability. We keep you updated throughout.</p>',
            ],
            [
                'title' => 'Return & Refund Policy', 'slug' => 'refund-policy', 'footer_group' => 'help',
                'excerpt' => 'What to do if something arrives damaged, faulty or wrong.',
                'content' => '<h2>Check at delivery</h2><p>Please check your package in front of the delivery person. If it is damaged or incorrect, you may refuse it and contact us.</p>'
                    .'<h2>Faulty or wrong items</h2><p>If a product is faulty on arrival or not what you ordered, contact us within 3 days of delivery with your order number and photos. After inspection we will replace it or refund you.</p>'
                    .'<h2>Refunds</h2><p>Approved refunds for cash-on-delivery orders are paid by bKash, Nagad or bank transfer, usually within 7 working days.</p>'
                    .'<h2>Not returnable</h2><p>Opened software and licence keys, products with physical or liquid damage, and items missing original packaging or accessories cannot be returned.</p>',
            ],
            [
                'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'footer_group' => 'company',
                'excerpt' => 'How TechnoFix collects and uses your information.',
                'content' => '<p>We respect your privacy. This policy explains what we collect when you use our website and how we use it.</p>'
                    .'<h2>What we collect</h2><ul><li>Order and contact details you give us: name, mobile number, email and delivery address.</li><li>Messages and service requests you send us.</li><li>Basic technical data such as your browser and pages visited, to keep the site working and improve it.</li></ul>'
                    .'<h2>How we use it</h2><p>To process and deliver orders, confirm them by phone, provide warranty and repair support, answer your questions and, with your consent, tell you about offers.</p>'
                    .'<h2>Sharing</h2><p>We share delivery details only with our courier partners to deliver your order. We never sell your personal information.</p>'
                    .'<h2>Contact</h2><p>To see, correct or delete your information, contact us using the details on our contact page.</p>',
            ],
            [
                'title' => 'Terms & Conditions', 'slug' => 'terms', 'footer_group' => 'company',
                'excerpt' => 'The terms that apply when you shop at TechnoFix.',
                'content' => '<h2>Orders</h2><p>An order is confirmed after our team verifies it with you by phone. We may cancel orders with incorrect information or items that become unavailable.</p>'
                    .'<h2>Prices and stock</h2><p>Prices are in Bangladeshi Taka (BDT) and may change without notice. The price confirmed at the time of your order applies. Product images are for illustration; specifications may vary slightly by batch.</p>'
                    .'<h2>Payment</h2><p>Orders are currently paid by cash on delivery.</p>'
                    .'<h2>Warranty and returns</h2><p>Covered by our Warranty Policy and Return &amp; Refund Policy.</p>'
                    .'<h2>Governing law</h2><p>These terms are governed by the laws of Bangladesh.</p>',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        $how = '<h2>How it works</h2><ol><li>Call, WhatsApp or send us a request with the problem.</li><li>We check the device and give you a clear quote — no work starts without your approval.</li><li>We fix it with genuine parts, test it, and hand it back or deliver it.</li></ol>';

        return [
            ['name' => 'Laptop Repair', 'slug' => 'laptop-repair', 'icon' => 'device-laptop',
                'summary' => 'Screen, keyboard, battery, hinge, charging port and motherboard repair for all major brands.',
                'content' => '<h2>What we fix</h2><ul><li>Broken or flickering screens</li><li>Keyboard, touchpad and hinge problems</li><li>Battery and charging-port issues</li><li>Overheating, fan and thermal-paste service</li><li>Motherboard and power faults</li></ul>'.$how],
            ['name' => 'Desktop & PC Service', 'slug' => 'desktop-repair', 'icon' => 'device-desktop',
                'summary' => 'Troubleshooting, upgrades, deep cleaning, thermal paste and custom PC assembly.',
                'content' => '<h2>What we do</h2><ul><li>No-power, no-display and restart problems</li><li>RAM, SSD and graphics-card upgrades</li><li>Deep cleaning and thermal-paste replacement</li><li>Custom PC assembly and cable management</li></ul>'.$how],
            ['name' => 'CCTV Installation', 'slug' => 'cctv-installation', 'icon' => 'device-cctv',
                'summary' => 'Site survey, camera and DVR/NVR installation, mobile viewing setup and maintenance.',
                'content' => '<h2>What we do</h2><ul><li>Site survey and camera placement plan</li><li>IP and analog camera installation, DVR/NVR setup</li><li>Remote viewing on your phone</li><li>Maintenance and troubleshooting of existing systems</li></ul>'.$how],
            ['name' => 'Networking Setup', 'slug' => 'networking-setup', 'icon' => 'router',
                'summary' => 'Office LAN, WiFi coverage, router configuration, fiber and structured cabling.',
                'content' => '<h2>What we do</h2><ul><li>Router and WiFi setup, mesh and range extension</li><li>Office LAN design and structured cabling</li><li>Switch, ONU and fiber installation</li><li>Network troubleshooting</li></ul>'.$how],
            ['name' => 'Printer Service', 'slug' => 'printer-service', 'icon' => 'printer',
                'summary' => 'Ink tank refill, head cleaning, toner, roller and paper-jam fixes.',
                'content' => '<h2>What we do</h2><ul><li>Ink tank refill and print-head cleaning</li><li>Toner and cartridge replacement</li><li>Roller, paper-feed and paper-jam fixes</li><li>Driver and network printing setup</li></ul>'.$how],
            ['name' => 'Software & Data Recovery', 'slug' => 'software-data-recovery', 'icon' => 'database',
                'summary' => 'Windows installation, virus removal, backup and data recovery from damaged drives.',
                'content' => '<h2>What we do</h2><ul><li>Windows and driver installation</li><li>Virus and malware removal</li><li>Backup and data transfer</li><li>Data recovery from failing or damaged drives</li></ul>'.$how],
        ];
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function faqs(): array
    {
        return [
            ['Orders & delivery', 'Do I need an account to order?', 'No. You can check out as a guest with just your name, mobile number and address.'],
            ['Orders & delivery', 'How do I know my order is confirmed?', 'Our team calls you after you place the order to confirm the items, price and address.'],
            ['Orders & delivery', 'How long does delivery take and how much does it cost?', 'Delivery options, charges and estimated times for your district are shown at checkout before you place your order.'],
            ['Orders & delivery', 'Can I pick up my order from the store?', 'Yes — choose "Store pickup" at checkout (if available) and we will call you when it is ready.'],
            ['Payment', 'Which payment methods do you accept?', 'Cash on delivery all over Bangladesh. bKash, Nagad and card payments are coming soon.'],
            ['Payment', 'Do you offer EMI?', 'EMI may be available on selected products with selected bank credit cards. Call us to check for the product you want.'],
            ['Warranty & returns', 'Are your products genuine?', 'Yes. We sell only genuine products from official channels, with the official brand warranty.'],
            ['Warranty & returns', 'What if my product arrives damaged or faulty?', 'Check the package at delivery. If something is wrong, contact us quickly with your order number — see our Return & Refund Policy.'],
            ['Repair & services', 'Do you repair devices that were not bought from you?', 'Yes. Call or WhatsApp us with the device and problem and we will give you a quote.'],
            ['Repair & services', 'Do you give a price before repairing?', 'Always. We diagnose first and no work starts without your approval.'],
        ];
    }
}
