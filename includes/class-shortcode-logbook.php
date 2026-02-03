<?php

class ABC_Shortcode_Logbook {
    public function register(): void {
        add_shortcode('abc_estimator_pro', [$this, 'render_shortcode']);
    }

    public function render_shortcode(): string {
        if (!current_user_can('edit_posts')) {
            return '<p>Please log in to access the Estimator Log Book.</p>';
        }

        wp_enqueue_style('abc-suite-frontend');
        wp_enqueue_script('abc-suite-frontend');
        ob_start();
        ?>
        <div class="abc-estimator-frontend">
            <div class="abc-estimator-header">
                <div class="abc-estimator-controls">
                    <input type="text" id="abc-frontend-search" placeholder="Search invoice #, client, job name, keywords...">
                    <span class="spinner" id="abc-spinner" style="display:none;">Loading...</span>
                </div>
                <div>
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=' . ABC_CPT_ABC_Estimate::POST_TYPE)); ?>" target="_blank" rel="noopener">+ New Estimate</a>
                </div>
            </div>
            <table class="abc-logbook-table widefat striped">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Job / Client</th>
                        <th>Stage</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="abc-log-results"></tbody>
            </table>
            <p id="abc-no-results" style="display:none; color:#666; margin-top:20px;">No estimates found.</p>
        </div>
        <?php
        return ob_get_clean();
    }
}
