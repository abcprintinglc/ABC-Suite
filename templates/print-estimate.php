<?php
/** @var array $data */
$post_id = (int) $data['post']->ID;
$invoice = (string) ($data['meta']['invoice'] ?? '');
$status = (string) ($data['meta']['status'] ?? 'estimate');
$client = (string) get_post_meta($post_id, 'abc_client_name', true);
$email = (string) get_post_meta($post_id, 'abc_client_email', true);
$job_description = (string) get_post_meta($post_id, 'abc_job_description', true);
$promised = (string) get_post_meta($post_id, 'abc_promised_date', true);
$ordered = (string) get_post_meta($post_id, 'abc_ordered_date', true);
$total = (string) get_post_meta($post_id, 'abc_estimate_total', true);
$line_items = json_decode((string) ($data['meta']['line_items_json'] ?? '[]'), true);
if (!is_array($line_items)) {
    $line_items = [];
}
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate #<?php echo esc_html($invoice); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 18px; color: #111827; }
        .sheet { border: 1px solid #d1d5db; padding: 16px; }
        .top { display:grid; grid-template-columns: 1fr auto; gap: 14px; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 14px; }
        .invoice { font-size: 24px; font-weight: 700; }
        .status { border:1px solid #111827; padding: 6px 10px; font-weight: 700; text-transform: uppercase; }
        .meta { display:grid; grid-template-columns: repeat(3, minmax(120px, 1fr)); gap:10px; margin-bottom: 14px; }
        .meta div { border:1px solid #e5e7eb; padding:8px; }
        .label { font-size: 11px; text-transform: uppercase; color:#6b7280; display:block; margin-bottom:4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border:1px solid #d1d5db; padding:8px; text-align:left; }
        th { background:#f3f4f6; }
        .total { margin-top:10px; text-align:right; font-size:18px; font-weight:700; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="top">
        <div>
            <div class="invoice">Job Jacket / Estimate #<?php echo esc_html($invoice); ?></div>
            <div><?php echo esc_html($job_description); ?></div>
        </div>
        <div class="status"><?php echo esc_html($status); ?></div>
    </div>

    <div class="meta">
        <div><span class="label">Client</span><?php echo esc_html($client); ?></div>
        <div><span class="label">Email</span><?php echo esc_html($email); ?></div>
        <div><span class="label">Order Date</span><?php echo esc_html((string) ($data['meta']['order_date'] ?? '')); ?></div>
        <div><span class="label">Ordered</span><?php echo esc_html($ordered); ?></div>
        <div><span class="label">Promised</span><?php echo esc_html($promised); ?></div>
        <div><span class="label">Due Date</span><?php echo esc_html((string) ($data['meta']['due_date'] ?? '')); ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Client</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($line_items) : ?>
                <?php foreach ($line_items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($item['item'] ?? $item['custom_product_name'] ?? $item['name'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($item['qty'] ?? $item['quantity'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($item['client'] ?? $client)); ?></td>
                        <td><?php echo esc_html((string) ($item['total'] ?? $item['sell_price'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">No line items.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total">Estimate Total: $<?php echo esc_html(number_format((float) $total, 2)); ?></div>
</div>
</body>
</html>
