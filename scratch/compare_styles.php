<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <style>
        <?php echo file_get_contents(__DIR__ . '/../Resources/assets/css/loan-management.css'); ?>
    </style>
    <style>
        :root {
            --lm-primary: #ea580c;
            --lm-primary-dark: #c2410c;
            --lm-primary-light: #f97316;
            --lm-primary-50: #fff7ed;
            --lm-primary-100: #ffedd5;
            --lm-primary-200: #fed7aa;
        }
    </style>
</head>
<body style="background: #fff; padding: 40px; width: 280px;">
    <h3>Without Tailwind (Dashboard Reports)</h3>
    <a href="#" class="lm-menu-link active tone-slate">
        <i class="fa fa-line-chart lm-menu-icon"></i>
        <span class="lm-menu-label">Dashboard Reports</span>
    </a>

    <h3 style="margin-top: 30px;">With Tailwind (Admin Installment)</h3>
    <div id="tailwind-container">
        <!-- We will test with Tailwind v4 injected -->
    </div>
</body>
</html>
