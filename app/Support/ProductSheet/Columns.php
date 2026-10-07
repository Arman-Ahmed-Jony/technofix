<?php

namespace App\Support\ProductSheet;

use App\Models\Attribute;
use Illuminate\Support\Str;

/**
 * The product spreadsheet layout shared by the Excel export and the import: one row per product, fixed columns
 * (with the header aliases the importer recognises) and one "Spec: {field}" column per specification field.
 */
final class Columns
{
    public const SPEC_PREFIX = 'Spec: ';

    /** key => [header, aliases (lowercase), help for the instructions sheet] — in export order. */
    public const FIELDS = [
        'sku' => ['Product code', ['sku', 'code', 'product code', 'item code', 'product id'], 'Matches existing products. Leave empty for new products (a code like TF-01092 is created).'],
        'name' => ['Name', ['name', 'product name', 'title', 'product', 'product title'], 'Required for new products.'],
        'category' => ['Category', ['category', 'category name', 'sub category', 'subcategory'], 'Required for new products. Category name as in the admin (e.g. "Motherboard"), or its URL name. Use "Parent > Child" if two categories share a name.'],
        'brand' => ['Brand', ['brand', 'brand name', 'manufacturer'], 'Brand name. New brands can be created during import.'],
        'model' => ['Model', ['model', 'model no', 'model number', 'part number', 'part no', 'mpn'], ''],
        'price' => ['Price', ['price', 'selling price', 'sale price', 'special price', 'offer price', 'price (bdt)'], 'Selling price in taka (numbers only; commas and ৳ are fine). Empty = "Call for price".'],
        'regular_price' => ['Regular price', ['regular price', 'mrp', 'old price', 'list price', 'original price'], 'Optional. Shown struck-through when higher than Price.'],
        'cost_price' => ['Cost price', ['cost', 'cost price', 'purchase price', 'buying price'], 'Staff only — never shown on the website.'],
        'stock_status' => ['Availability', ['availability', 'stock status'], 'In Stock, Out of Stock, Pre-Order, Upcoming or Call for Price. Empty on new products = In Stock (or Call for Price when there is no price).'],
        'stock_qty' => ['Quantity', ['quantity', 'qty', 'stock qty', 'stock quantity', 'stock'], 'Units on hand (whole number).'],
        'warranty' => ['Warranty', ['warranty'], 'e.g. 1 Year, 3 Years, 6 Months.'],
        'status' => ['Status', ['status', 'visibility', 'published'], 'Published or Draft.'],
        'is_featured' => ['Featured', ['featured'], 'Yes / No'],
        'is_new' => ['New arrival', ['new', 'new arrival', 'is new'], 'Yes / No'],
        'is_best_seller' => ['Best seller', ['best seller', 'bestseller'], 'Yes / No'],
        'is_hot_deal' => ['Hot deal', ['hot deal', 'deal'], 'Yes / No'],
        'emi_available' => ['EMI', ['emi', 'emi available'], 'Yes / No'],
        'key_features' => ['Key features', ['key features', 'features', 'highlights'], 'One per line (Alt+Enter in Excel) or separated by |. Up to 8.'],
        'description' => ['Description', ['description', 'details', 'product description'], 'Plain text (blank lines start new paragraphs) or HTML.'],
        'related' => ['Related products', ['related', 'related products', 'related codes', 'similar products'], 'Product codes separated by commas, e.g. TF-01002, TF-01003. Shown under "You may also like". Replaces the current list.'],
        'accessories' => ['Accessories', ['accessories', 'accessory', 'add-ons', 'addons'], 'Product codes separated by commas. Shown as "Accessories for this product". Replaces the current list.'],
        'colors' => ['Colors', ['colors', 'colours', 'color', 'colour', 'color options'], 'Optional color versions separated by commas, each with an optional swatch code and "(sold out)", e.g. Black #111111, White #FFFFFF, Lilac (sold out). Replaces the current list; empty cell = no change.'],
        'slug' => ['URL', ['url', 'slug'], 'Optional web address, e.g. gigabyte-b760m-ds3h. Made from the name when empty.'],
        'meta_title' => ['SEO title', ['seo title', 'meta title'], 'Optional.'],
        'meta_description' => ['SEO description', ['seo description', 'meta description'], 'Optional.'],
    ];

    public static function header(string $field): string
    {
        return self::FIELDS[$field][0];
    }

    public static function specHeader(Attribute $attribute, bool $withCode = false): string
    {
        return self::SPEC_PREFIX.$attribute->name.($withCode ? " ({$attribute->code})" : '');
    }

    /** "  Product Code* " → "product code" */
    public static function normalise(?string $header): string
    {
        return Str::of((string) $header)->lower()->replaceMatches('/\s+/', ' ')->trim(" \t\n\r\0\x0B*:")->toString();
    }

    /**
     * Best guess for what a header means: a product field key, "spec:{attribute id}", or null (ignore).
     *
     * @param  array<int, array{name: string, code: string}>  $attributes  id => attribute
     */
    public static function guess(string $header, array $attributes): ?string
    {
        $normalised = self::normalise($header);

        foreach (self::FIELDS as $key => [$label, $aliases]) {
            if ($normalised === self::normalise($label) || in_array($normalised, $aliases, true)) {
                return $key;
            }
        }

        // "Spec: Chipset", "Spec: Memory Type (memory_type)" or just "Chipset".
        $name = trim(preg_replace('/^spec\s*:\s*/', '', $normalised));
        $code = preg_match('/\(([a-z0-9_]+)\)$/', $name, $m) ? $m[1] : null;
        $name = trim(preg_replace('/\([a-z0-9_]+\)$/', '', $name));

        foreach ($attributes as $id => $attribute) {
            if ($code ? $attribute['code'] === $code : self::normalise($attribute['name']) === $name) {
                return "spec:{$id}";
            }
        }

        return null;
    }
}
