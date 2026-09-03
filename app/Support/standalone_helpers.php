<?php

if (! function_exists('isMobile')) {
    function isMobile(): bool
    {
        $agent = request()->userAgent() ?? '';

        return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $agent);
    }
}

if (! function_exists('module_path')) {
    function module_path(string $module, string $path = ''): string
    {
        $base = strcasecmp($module, 'LoanManagement') === 0
            ? base_path()
            : base_path('Modules/'.$module);

        return $path === '' ? $base : $base.'/'.ltrim($path, '/');
    }
}

if (! function_exists('lm_label')) {
    function lm_label(string $translationKey, string $english, ?string $khmer = null): string
    {
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        $language = session('user.language', config('app.locale'));

        return $language === 'km' && $khmer !== null ? $khmer : $english;
    }
}
