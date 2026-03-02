<?php

class ABC_Shortcode_Logbook {
    public function register(): void {
        add_shortcode('abc_estimator_pro', [$this, 'render_shortcode']);
        add_shortcode('abc_designer_layout', [$this, 'render_designer_layout']);
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
                    <select id="abc-frontend-client"><option value="">All clients</option></select>
                    <select id="abc-frontend-year"><option value="">All years</option></select>
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

    public function render_designer_layout(): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to access the designer layout.', 'abc-suite') . '</p>';
        }

        wp_enqueue_style('abc-suite-frontend');

        $corner_radio_name = 'abc_corner_type_' . wp_generate_password(6, false, false);

        ob_start();
        ?>
        <div class="abc-designer-layout" aria-label="<?php echo esc_attr__('Designer workspace mockup', 'abc-suite'); ?>">
            <aside class="abc-designer-sidebar">
                <div class="abc-designer-sidebar-tabs">
                    <button type="button" class="is-active"><?php echo esc_html__('Options', 'abc-suite'); ?></button>
                    <button type="button"><?php echo esc_html__('Design', 'abc-suite'); ?></button>
                </div>

                <div class="abc-designer-panel">
                    <p class="abc-designer-count"><?php echo esc_html__('Total Photo Slots: 0', 'abc-suite'); ?></p>
                    <button type="button" class="abc-designer-upload"><?php echo esc_html__('Upload Photos', 'abc-suite'); ?></button>

                    <h4><?php echo esc_html__('Corner Type:', 'abc-suite'); ?></h4>
                    <ul>
                        <li>
                            <label>
                                <input type="radio" name="<?php echo esc_attr($corner_radio_name); ?>" checked>
                                <span><?php echo esc_html__('Rounded', 'abc-suite'); ?></span>
                                <span>+$0.01 ea.</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="<?php echo esc_attr($corner_radio_name); ?>">
                                <span><?php echo esc_html__('Square', 'abc-suite'); ?></span>
                            </label>
                        </li>
                    </ul>
                </div>
            </aside>

            <section class="abc-designer-main">
                <header class="abc-designer-toolbar">
                    <button type="button"><?php echo esc_html__('Upload photos', 'abc-suite'); ?></button>
                    <button type="button"><?php echo esc_html__('Add photo slot', 'abc-suite'); ?></button>
                    <button type="button"><?php echo esc_html__('Add text slot', 'abc-suite'); ?></button>
                    <button type="button" class="abc-designer-preview"><?php echo esc_html__('Preview', 'abc-suite'); ?></button>
                </header>

                <div class="abc-designer-canvas-wrap">
                    <div class="abc-designer-canvas" role="img" aria-label="<?php echo esc_attr__('Business card design area', 'abc-suite'); ?>">
                        <div class="abc-designer-shape">
                            <strong><?php echo esc_html__('BETTER BUILT', 'abc-suite'); ?></strong>
                            <span><?php echo esc_html__('HANDYMAN SERVICE', 'abc-suite'); ?></span>
                        </div>
                    </div>
                </div>

                <footer class="abc-designer-footer">
                    <div class="abc-designer-sides"><?php echo esc_html__('1. Front', 'abc-suite'); ?> &nbsp;&nbsp; <?php echo esc_html__('2. Back', 'abc-suite'); ?></div>
                    <button type="button" class="button button-primary"><?php echo esc_html__('Next', 'abc-suite'); ?></button>
                </footer>
            </section>
        </div>
        <?php

        return ob_get_clean();
    }
}
