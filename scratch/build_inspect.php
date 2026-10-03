<?php
$html = file_get_contents(__DIR__ . '/inspect_template.html');
$lm = file_get_contents(__DIR__ . '/../Resources/assets/css/loan-management.css');
$tw = file_get_contents(__DIR__ . '/../Resources/assets/admin-loan-app/index-tfrm5V5v.css');
$blade = file_get_contents(__DIR__ . '/../Resources/views/admin_loan/index.blade.php');
preg_match('/@section\(\'loan_css\'\)(.*?)@endsection/s', $blade, $m);
$bladeCss = preg_replace('/@.*$/m', '', $m[1] ?? '');
$html = str_replace(['__LM_CSS__', '__TAILWIND_CSS__', '__ADMIN_BLADE_CSS__'], [$lm, $tw, $bladeCss], $html);
file_put_contents(__DIR__ . '/inspect_rendered.html', $html);
echo "Rendered inspect_rendered.html\n";
