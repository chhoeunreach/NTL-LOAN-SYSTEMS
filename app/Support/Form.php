<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Form
{
    public static function open(array $options = []): HtmlString
    {
        $method = strtoupper((string) ($options['method'] ?? 'POST'));
        $htmlMethod = in_array($method, ['GET', 'POST'], true) ? $method : 'POST';
        $attributes = $options;
        unset($attributes['method'], $attributes['url'], $attributes['files']);

        $attributes['method'] = strtolower($htmlMethod);
        $attributes['action'] = $options['url'] ?? '#';

        if (! empty($options['files'])) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        $html = '<form'.self::attributes($attributes).'>';
        if ($htmlMethod === 'POST') {
            $html .= csrf_field();
        }
        if (! in_array($method, ['GET', 'POST'], true)) {
            $html .= method_field($method);
        }

        return new HtmlString($html);
    }

    public static function close(): HtmlString
    {
        return new HtmlString('</form>');
    }

    public static function label(string $name, ?string $value = null, array $options = []): HtmlString
    {
        $options['for'] = $options['for'] ?? $name;

        return new HtmlString('<label'.self::attributes($options).'>'.e($value ?? $name).'</label>');
    }

    public static function text(string $name, $value = null, array $options = []): HtmlString
    {
        $options['type'] = 'text';
        $options['name'] = $name;
        $options['value'] = old($name, $value);

        return new HtmlString('<input'.self::attributes($options).'>');
    }

    public static function select(string $name, array $list = [], $selected = null, array $options = []): HtmlString
    {
        $placeholder = $options['placeholder'] ?? null;
        unset($options['placeholder']);
        $options['name'] = $name;

        $html = '<select'.self::attributes($options).'>';
        if ($placeholder !== null) {
            $html .= '<option value="">'.e((string) $placeholder).'</option>';
        }

        foreach ($list as $value => $label) {
            $attrs = ['value' => $value];
            if ((string) $value === (string) old($name, $selected)) {
                $attrs['selected'] = 'selected';
            }
            $html .= '<option'.self::attributes($attrs).'>'.e((string) $label).'</option>';
        }

        return new HtmlString($html.'</select>');
    }

    private static function attributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $html .= ' '.e((string) $key);
            if ($value !== true) {
                $html .= '="'.e((string) $value).'"';
            }
        }

        return $html;
    }
}
