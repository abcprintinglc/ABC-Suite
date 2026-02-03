<?php
/** @var array $data */
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate #<?php echo esc_html($data['meta']['invoice']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { margin-bottom: 10px; }
        .meta { margin-bottom: 20px; }
        .meta span { display: inline-block; margin-right: 15px; }
        pre { background: #f4f4f4; padding: 10px; }
    </style>
</head>
<body>
    <h1>Estimate / Job</h1>
    <div class="meta">
        <span><strong>Invoice:</strong> <?php echo esc_html($data['meta']['invoice']); ?></span>
        <span><strong>Status:</strong> <?php echo esc_html($data['meta']['status']); ?></span>
        <span><strong>Workflow:</strong> <?php echo esc_html($data['meta']['workflow_status']); ?></span>
    </div>
    <div class="meta">
        <span><strong>Order Date:</strong> <?php echo esc_html($data['meta']['order_date']); ?></span>
        <span><strong>Approval Date:</strong> <?php echo esc_html($data['meta']['approval_date']); ?></span>
        <span><strong>Due Date:</strong> <?php echo esc_html($data['meta']['due_date']); ?></span>
        <span><strong>Rush:</strong> <?php echo esc_html($data['meta']['rush'] ? 'Yes' : 'No'); ?></span>
    </div>
    <h3>Line Items</h3>
    <pre><?php echo esc_html($data['meta']['line_items_json']); ?></pre>
</body>
</html>
