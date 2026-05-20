<?php
/**
 * Plugin Name: Location Slug Changer (SQL)
 * Plugin URI:  https://github.com/shohiduldev
 * Description: SQL দিয়ে location page slug bulk change করো। By Shohidul Dev | #shohiduldev
 * Version:     1.0.0
 * Author:      Shohidul Dev
 * Author URI:  https://shohiduldev.com
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class LSC_Location_Slug_Changer {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_post_lsc_run_sql', [ $this, 'handle_form' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
    }

    public function add_menu() {
        add_menu_page(
            'Location Slug Changer',
            'Slug Changer',
            'manage_options',
            'lsc-slug-changer',
            [ $this, 'render_page' ],
            'dashicons-admin-links',
            80
        );
    }

    public function enqueue_styles( $hook ) {
        if ( $hook !== 'toplevel_page_lsc-slug-changer' ) return;
        wp_add_inline_style( 'wp-admin', $this->inline_css() );
    }

    public function render_page() {
        global $wpdb;

        $message    = '';
        $results    = [];
        $preview    = [];
        $mode       = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'preview';

        // Load location pages for preview
        $post_type  = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'page';
        $meta_key   = isset( $_GET['meta_key'] ) ? sanitize_text_field( $_GET['meta_key'] ) : '';
        $old_prefix = isset( $_GET['old_prefix'] ) ? sanitize_text_field( $_GET['old_prefix'] ) : '';
        $new_prefix = isset( $_GET['new_prefix'] ) ? sanitize_text_field( $_GET['new_prefix'] ) : '';
        $dry_run    = isset( $_GET['dry_run'] ) ? (bool) $_GET['dry_run'] : true;

        if ( isset( $_POST['lsc_submit'] ) && check_admin_referer( 'lsc_action', 'lsc_nonce' ) ) {
            $post_type  = sanitize_key( $_POST['post_type'] ?? 'page' );
            $meta_key   = sanitize_text_field( $_POST['meta_key'] ?? '' );
            $old_prefix = sanitize_text_field( $_POST['old_prefix'] ?? '' );
            $new_prefix = sanitize_text_field( $_POST['new_prefix'] ?? '' );
            $dry_run    = isset( $_POST['dry_run'] );

            if ( empty( $old_prefix ) || empty( $new_prefix ) ) {
                $message = '<div class="lsc-alert lsc-alert-error">⚠️ Old prefix ও New prefix দুটোই দিতে হবে।</div>';
            } else {
                $results = $this->process_slugs( $post_type, $meta_key, $old_prefix, $new_prefix, $dry_run );
                if ( $dry_run ) {
                    $message = '<div class="lsc-alert lsc-alert-info">👀 Preview mode — কোনো পরিবর্তন হয়নি। নিচে দেখো কোন slug বদলাবে।</div>';
                } else {
                    $count = count( array_filter( $results, fn($r) => $r['status'] === 'updated' ) );
                    $message = "<div class='lsc-alert lsc-alert-success'>✅ {$count} টি slug সফলভাবে update হয়েছে।</div>";
                }
            }
        }

        // Load all post types for dropdown
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        ?>
        <div class="lsc-wrap">
            <div class="lsc-header">
                <div class="lsc-logo">🔗</div>
                <div>
                    <h1>Location Slug Changer</h1>
                    <p class="lsc-sub">SQL-powered bulk slug updater &nbsp;·&nbsp; <strong>Shohidul Dev</strong> &nbsp;<span class="lsc-tag">#shohiduldev</span></p>
                </div>
            </div>

            <?php echo $message; ?>

            <div class="lsc-card">
                <form method="POST">
                    <?php wp_nonce_field( 'lsc_action', 'lsc_nonce' ); ?>

                    <div class="lsc-grid">
                        <div class="lsc-field">
                            <label>Post Type</label>
                            <select name="post_type">
                                <?php foreach ( $post_types as $pt ) : ?>
                                    <option value="<?php echo esc_attr( $pt->name ); ?>"
                                        <?php selected( $post_type, $pt->name ); ?>>
                                        <?php echo esc_html( $pt->labels->singular_name ); ?> (<?php echo esc_html( $pt->name ); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="lsc-field">
                            <label>Meta Key (optional)</label>
                            <input type="text" name="meta_key"
                                value="<?php echo esc_attr( $meta_key ); ?>"
                                placeholder="যেমন: _location_type (খালি রাখলে সব page)">
                        </div>

                        <div class="lsc-field">
                            <label>Old Slug Prefix / Pattern</label>
                            <input type="text" name="old_prefix" required
                                value="<?php echo esc_attr( $old_prefix ); ?>"
                                placeholder="যেমন: location- বা dhaka-">
                        </div>

                        <div class="lsc-field">
                            <label>New Slug Prefix / Replace With</label>
                            <input type="text" name="new_prefix" required
                                value="<?php echo esc_attr( $new_prefix ); ?>"
                                placeholder="যেমন: loc- বা bd-dhaka-">
                        </div>
                    </div>

                    <div class="lsc-custom-sql-box">
                        <h3>📝 Custom SQL Preview</h3>
                        <p class="lsc-sql-preview">
                            <?php
                            if ( $old_prefix && $new_prefix ) {
                                $table = $wpdb->prefix . 'posts';
                                echo esc_html(
                                    "UPDATE {$table} SET post_name = REPLACE(post_name, '{$old_prefix}', '{$new_prefix}') " .
                                    "WHERE post_type = '{$post_type}' AND post_status = 'publish' " .
                                    "AND post_name LIKE '{$old_prefix}%';"
                                );
                            } else {
                                echo 'Prefix দিলে এখানে SQL দেখাবে...';
                            }
                            ?>
                        </p>
                    </div>

                    <div class="lsc-actions">
                        <label class="lsc-toggle">
                            <input type="checkbox" name="dry_run" value="1" <?php checked( ! isset( $_POST['lsc_submit'] ) || $dry_run ); ?>>
                            <span class="lsc-toggle-track"></span>
                            <span>Dry Run (preview only, কোনো change হবে না)</span>
                        </label>

                        <button type="submit" name="lsc_submit" class="lsc-btn">
                            ⚡ Run Slug Changer
                        </button>
                    </div>
                </form>
            </div>

            <?php if ( ! empty( $results ) ) : ?>
            <div class="lsc-card lsc-results">
                <h2>Results <span class="lsc-count"><?php echo count( $results ); ?></span></h2>
                <div class="lsc-table-wrap">
                    <table class="lsc-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Post Title</th>
                                <th>Old Slug</th>
                                <th>New Slug</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $results as $i => $row ) : ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo esc_html( $row['title'] ); ?></td>
                                <td><code><?php echo esc_html( $row['old_slug'] ); ?></code></td>
                                <td><code class="lsc-new"><?php echo esc_html( $row['new_slug'] ); ?></code></td>
                                <td>
                                    <span class="lsc-badge lsc-badge-<?php echo esc_attr( $row['status'] ); ?>">
                                        <?php echo esc_html( $row['status'] ); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="lsc-footer">
                Built by <strong>Shohidul Dev</strong> · <a href="https://github.com/shohiduldev" target="_blank">#shohiduldev</a>
            </div>
        </div>
        <?php
    }

    /**
     * Core logic: find matching posts, replace slug via wpdb, flush rewrite rules
     */
    private function process_slugs( $post_type, $meta_key, $old_prefix, $new_prefix, $dry_run ) {
        global $wpdb;

        $args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        // Filter by meta key if provided
        if ( $meta_key ) {
            $args['meta_query'] = [
                [ 'key' => $meta_key, 'compare' => 'EXISTS' ],
            ];
        }

        // Filter by old prefix using name__in via raw SQL for performance
        $like   = $wpdb->esc_like( $old_prefix ) . '%';
        $args['post_name__in'] = []; // placeholder; we filter manually

        $query = new WP_Query( $args );
        $ids   = $query->posts;

        // Now filter by slug prefix
        $results = [];

        foreach ( $ids as $id ) {
            $post = get_post( $id );
            if ( strpos( $post->post_name, $old_prefix ) !== 0 ) continue;

            $old_slug = $post->post_name;
            $new_slug = $new_prefix . substr( $old_slug, strlen( $old_prefix ) );

            // Check duplicate
            $exists = get_page_by_path( $new_slug, OBJECT, $post_type );
            if ( $exists && $exists->ID !== $id ) {
                $results[] = [
                    'title'    => $post->post_title,
                    'old_slug' => $old_slug,
                    'new_slug' => $new_slug,
                    'status'   => 'duplicate',
                ];
                continue;
            }

            if ( ! $dry_run ) {
                // Direct SQL update for speed + reliability
                $wpdb->update(
                    $wpdb->posts,
                    [ 'post_name' => $new_slug ],
                    [ 'ID' => $id ],
                    [ '%s' ],
                    [ '%d' ]
                );
                clean_post_cache( $id );
                $status = 'updated';
            } else {
                $status = 'preview';
            }

            $results[] = [
                'title'    => $post->post_title,
                'old_slug' => $old_slug,
                'new_slug' => $new_slug,
                'status'   => $status,
            ];
        }

        // Flush rewrite rules after real update
        if ( ! $dry_run ) {
            flush_rewrite_rules();
        }

        return $results;
    }

    private function inline_css() {
        return '
        .lsc-wrap { max-width: 1100px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .lsc-header { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
        .lsc-logo { font-size: 40px; }
        .lsc-header h1 { margin: 0; font-size: 26px; color: #1a1a2e; }
        .lsc-sub { margin: 4px 0 0; color: #666; font-size: 13px; }
        .lsc-tag { background: #e8f0fe; color: #1a73e8; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; }
        .lsc-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .lsc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .lsc-field label { display: block; font-weight: 600; font-size: 13px; color: #374151; margin-bottom: 6px; }
        .lsc-field input, .lsc-field select { width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: border .2s; box-sizing: border-box; }
        .lsc-field input:focus, .lsc-field select:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .lsc-custom-sql-box { background: #0f172a; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
        .lsc-custom-sql-box h3 { color: #94a3b8; margin: 0 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; }
        .lsc-sql-preview { color: #7dd3fc; font-family: "Courier New", monospace; font-size: 13px; margin: 0; word-break: break-all; }
        .lsc-actions { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .lsc-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: #374151; }
        .lsc-toggle input { display: none; }
        .lsc-toggle-track { width: 42px; height: 24px; background: #d1d5db; border-radius: 99px; position: relative; transition: background .2s; flex-shrink: 0; }
        .lsc-toggle input:checked + .lsc-toggle-track { background: #2563eb; }
        .lsc-toggle-track::after { content: ""; width: 18px; height: 18px; background: #fff; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
        .lsc-toggle input:checked + .lsc-toggle-track::after { transform: translateX(18px); }
        .lsc-btn { background: #2563eb; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .2s; margin-left: auto; }
        .lsc-btn:hover { background: #1d4ed8; }
        .lsc-alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .lsc-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .lsc-alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .lsc-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .lsc-results h2 { margin: 0 0 20px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .lsc-count { background: #2563eb; color: #fff; font-size: 13px; padding: 2px 10px; border-radius: 99px; }
        .lsc-table-wrap { overflow-x: auto; }
        .lsc-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .lsc-table th { text-align: left; padding: 10px 14px; background: #f8fafc; border-bottom: 2px solid #e5e7eb; font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; }
        .lsc-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; }
        .lsc-table tr:last-child td { border-bottom: none; }
        .lsc-table code { background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 13px; }
        .lsc-new { background: #dcfce7 !important; color: #166534; }
        .lsc-badge { padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
        .lsc-badge-updated { background: #dcfce7; color: #166534; }
        .lsc-badge-preview { background: #eff6ff; color: #1e40af; }
        .lsc-badge-duplicate { background: #fef3c7; color: #92400e; }
        .lsc-badge-skipped { background: #f3f4f6; color: #6b7280; }
        .lsc-footer { text-align: center; color: #9ca3af; font-size: 13px; padding: 20px 0 10px; }
        .lsc-footer a { color: #2563eb; text-decoration: none; }
        @media (max-width: 768px) { .lsc-grid { grid-template-columns: 1fr; } }
        ';
    }
}

new LSC_Location_Slug_Changer();
