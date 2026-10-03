<?php

namespace Modules\LoanManagement\Services;

class CmsHomeService
{
    public static function fields(): array
    {
        $fields = [];
        foreach (['announcement', 'hero', 'brands', 'guide', 'catalog', 'cart', 'about', 'contact', 'footer'] as $section) {
            $fields[$section] = [ucfirst($section).' Section', 'boolean', true];
        }
        foreach (['home' => 'Home', 'products' => 'Products Catalog', 'how' => 'Installment Guide', 'cart' => 'Cart', 'about' => 'About Us', 'contact' => 'Contact'] as $key => $label) {
            $fields['menu_'.$key] = [$label.' Menu', 'boolean', true];
            $fields['label_'.$key] = [$label.' Menu Label', 'text', $label];
        }
        return $fields + [
            'tagline' => ['Brand Tagline', 'text', 'Showroom & Installment'],
            'announcement_text' => ['Announcement Text', 'text', 'Showroom & Installment'],
            'hero_eyebrow' => ['Hero Eyebrow', 'text', 'Installment Shopping'],
            'brands_title' => ['Brand Strip Title', 'text', 'Explore our brands'],
            'guide_title' => ['Guide Title', 'text', 'Apply in a few minutes'],
            'guide_description' => ['Guide Description', 'textarea', 'Choose a product, submit your request, and track your account.'],
            'step_1_title' => ['Step 1 Title', 'text', 'Choose product'],
            'step_1_body' => ['Step 1 Description', 'textarea', 'Add products from the catalog to your installment cart.'],
            'step_2_title' => ['Step 2 Title', 'text', 'Submit request'],
            'step_2_body' => ['Step 2 Description', 'textarea', 'Register once and send your selected items to our team.'],
            'step_3_title' => ['Step 3 Title', 'text', 'Track your account'],
            'step_3_body' => ['Step 3 Description', 'textarea', 'Log in to view your loan records and payment history.'],
            'catalog_title' => ['Catalog Title', 'text', 'Products Catalog'],
            'catalog_description' => ['Catalog Description', 'textarea', 'Find your next product and submit an installment request. All requests are subject to review.'],
            'about_title' => ['About Title', 'text', 'About Us'],
            'contact_title' => ['Contact Title', 'text', 'Contact Us'],
            'contact_phone' => ['Contact Phone', 'text', ''],
            'contact_email' => ['Contact Email', 'email', ''],
            'contact_address' => ['Contact Address', 'textarea', ''],
            'contact_hours' => ['Opening Hours', 'text', ''],
            'footer_text' => ['Footer Text', 'textarea', 'For your applications, installment account, and payment history, visit the Customer Portal.'],
        ];
    }

    public static function normalize($values): array
    {
        $values = is_array($values) ? $values : [];
        $result = [];
        foreach (self::fields() as $key => [$label, $type, $default]) {
            $value = $values[$key] ?? $default;
            $result[$key] = $type === 'boolean' ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                : mb_substr(trim(strip_tags(is_scalar($value) ? (string) $value : $default)), 0, $type === 'textarea' ? 1200 : 220);
        }
        return $result;
    }

    public static function groups(): array
    {
        $groups = [];
        foreach (self::fields() as $key => $field) {
            $group = match (true) {
                str_starts_with($key, 'menu_'), str_starts_with($key, 'label_') => 'Navigation',
                $field[1] === 'boolean' => 'Section Visibility',
                str_starts_with($key, 'guide_'), str_starts_with($key, 'step_') => 'Installment Guide',
                str_starts_with($key, 'catalog_') => 'Products Catalog',
                str_starts_with($key, 'contact_'), str_starts_with($key, 'about_'), str_starts_with($key, 'footer_') => 'About, Contact & Footer',
                default => 'Brand & Hero',
            };
            $groups[$group][$key] = $field;
        }
        return $groups;
    }
}
