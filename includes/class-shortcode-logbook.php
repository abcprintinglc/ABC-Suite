<?php

class ABC_Shortcode_Logbook {
    public function register(): void {
        add_shortcode('abc_estimator_pro', [$this, 'render_shortcode']);
    }

    public function render_shortcode(): string {
        wp_enqueue_style('abc-suite-frontend');
        wp_enqueue_script('abc-suite-frontend');

        $posts = get_posts([
            'post_type' => ABC_CPT_ABC_Estimate::POST_TYPE,
            'numberposts' => 20,
        ]);

        ob_start();
        ?>
        <div class="abc-estimator-pro">
            <div class="abc-estimator-header">
                <input type="search" class="abc-search" placeholder="Search estimates">
                <a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE)); ?>">New Estimate</a>
            </div>
            <table class="abc-estimator-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post) : ?>
                        <tr>
                            <td><?php echo esc_html(get_post_meta($post->ID, 'abc_invoice_number', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($post->ID, 'abc_status', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($post->ID, 'abc_due_date', true)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
