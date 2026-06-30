<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Auto_JSONLD_Meta_Box {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register' ] );
        add_action( 'save_post', [ $this, 'save' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function register() {
        $post_types = get_post_types( [ 'public' => true ], 'names' );
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'auto_jsonld_meta_box',
                'JSON-LD Schema & SEO',
                [ $this, 'render' ],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function enqueue( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;
        wp_enqueue_style( 'auto-jsonld-meta-box', AUTO_JSONLD_URL . 'admin/css/meta-box.css', [], AUTO_JSONLD_VERSION );
        wp_enqueue_script( 'auto-jsonld-meta-box', AUTO_JSONLD_URL . 'admin/js/meta-box.js', [ 'jquery' ], AUTO_JSONLD_VERSION, true );
    }

    public function render( $post ) {
        wp_nonce_field( 'auto_jsonld_meta_box', 'auto_jsonld_nonce' );
        $meta = get_post_meta( $post->ID, '_auto_jsonld', true ) ?: [];

        $schema_options = [
            'webpage'       => [ 'label' => 'WebPage',       'icon' => 'globe' ],
            'article'       => [ 'label' => 'Article',        'icon' => 'edit' ],
            'blogposting'   => [ 'label' => 'BlogPosting',    'icon' => 'admin-post' ],
            'creativework'  => [ 'label' => 'Portfolio / Case Study', 'icon' => 'portfolio' ],
            'faq'           => [ 'label' => 'FAQPage',        'icon' => 'editor-help' ],
            'service'       => [ 'label' => 'Service',        'icon' => 'admin-tools' ],
            'localbusiness' => [ 'label' => 'LocalBusiness',  'icon' => 'building' ],
            'aboutpage'     => [ 'label' => 'AboutPage',      'icon' => 'info' ],
            'contactpage'   => [ 'label' => 'ContactPage',    'icon' => 'phone' ],
            'itemlist'      => [ 'label' => 'ItemList',       'icon' => 'list-view' ],
            'breadcrumb'    => [ 'label' => 'Breadcrumb',     'icon' => 'arrow-right-alt' ],
        ];

        $enabled_schemas = $meta['schemas'] ?? [];
        $custom_schema   = $meta['custom_schema'] ?? '';
        ?>
        <div id="auto-jsonld-box">
            <div class="ajld-tabs">
                <button type="button" class="ajld-tab active" data-tab="seo">SEO Settings</button>
                <button type="button" class="ajld-tab" data-tab="schema">Schema Types</button>
                <button type="button" class="ajld-tab" data-tab="custom">Custom Schema</button>
                <button type="button" class="ajld-tab" data-tab="preview">Preview</button>
            </div>

            <!-- SEO Tab -->
            <div class="ajld-tab-content active" id="ajld-tab-seo">
                <p class="ajld-hint">Leave blank to use auto-detected values from the page.</p>
                <table class="ajld-table">
                    <tr>
                        <th><label for="ajld_seo_title">SEO Title</label></th>
                        <td><input type="text" id="ajld_seo_title" name="auto_jsonld[seo_title]" value="<?php echo esc_attr( $meta['seo_title'] ?? '' ); ?>" placeholder="Leave blank to use page title" class="widefat"></td>
                    </tr>
                    <tr>
                        <th><label for="ajld_seo_desc">Meta Description</label></th>
                        <td><textarea id="ajld_seo_desc" name="auto_jsonld[seo_description]" rows="3" class="widefat" placeholder="Leave blank to skip"><?php echo esc_textarea( $meta['seo_description'] ?? '' ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="ajld_focus_keyword">Focus Keyword</label></th>
                        <td>
                            <input type="text" id="ajld_focus_keyword" name="auto_jsonld[focus_keyword]" value="<?php echo esc_attr( $meta['focus_keyword'] ?? '' ); ?>" placeholder="e.g. fintech platform development" class="widefat">
                            <p class="ajld-hint">The one keyword this page targets. Added to schema as <code>about</code> + <code>keywords</code> so Google knows the page topic.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ajld_seo_image">OG Image URL</label></th>
                        <td><input type="text" id="ajld_seo_image" name="auto_jsonld[seo_image]" value="<?php echo esc_attr( $meta['seo_image'] ?? '' ); ?>" placeholder="Leave blank to use featured image" class="widefat"></td>
                    </tr>
                    <tr>
                        <th><label for="ajld_canonical">Canonical URL</label></th>
                        <td><input type="text" id="ajld_canonical" name="auto_jsonld[canonical]" value="<?php echo esc_attr( $meta['canonical'] ?? '' ); ?>" placeholder="Leave blank to use page URL" class="widefat"></td>
                    </tr>
                    <tr>
                        <th>Robots</th>
                        <td>
                            <label><input type="checkbox" name="auto_jsonld[noindex]" value="1" <?php checked( $meta['noindex'] ?? 0, 1 ); ?>> No Index</label>&nbsp;&nbsp;
                            <label><input type="checkbox" name="auto_jsonld[nofollow]" value="1" <?php checked( $meta['nofollow'] ?? 0, 1 ); ?>> No Follow</label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Schema Types Tab -->
            <div class="ajld-tab-content" id="ajld-tab-schema">
                <p class="ajld-hint">Select which schema types to inject on this page. FAQPage is auto-detected from <code>id="faq"</code> section in your content.</p>
                <div class="ajld-schema-grid">
                    <?php foreach ( $schema_options as $key => $info ) : ?>
                    <label class="ajld-schema-card <?php echo in_array( $key, $enabled_schemas ) ? 'active' : ''; ?>">
                        <input type="checkbox" name="auto_jsonld[schemas][]" value="<?php echo esc_attr($key); ?>" <?php checked( in_array( $key, $enabled_schemas ) ); ?>>
                        <span class="dashicons dashicons-<?php echo esc_attr($info['icon']); ?> ajld-schema-icon"></span>
                        <span class="ajld-schema-label"><?php echo esc_html($info['label']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="ajld-schema-fields" id="ajld-fields-service" style="<?php echo in_array('service', $enabled_schemas) ? '' : 'display:none'; ?>">
                    <h4>Service Details</h4>
                    <table class="ajld-table">
                        <tr><th>Service Name</th><td><input type="text" name="auto_jsonld[service_name]" value="<?php echo esc_attr($meta['service_name'] ?? ''); ?>" class="widefat" placeholder="Leave blank to use page title"></td></tr>
                        <tr><th>Description</th><td><textarea name="auto_jsonld[service_description]" rows="2" class="widefat" placeholder="Leave blank to skip"><?php echo esc_textarea($meta['service_description'] ?? ''); ?></textarea></td></tr>
                        <tr><th>Area Served</th><td><input type="text" name="auto_jsonld[service_area]" value="<?php echo esc_attr($meta['service_area'] ?? ''); ?>" class="widefat" placeholder="e.g. New York, USA"></td></tr>
                        <tr><th>Price Range</th><td><input type="text" name="auto_jsonld[service_price]" value="<?php echo esc_attr($meta['service_price'] ?? ''); ?>" class="widefat" placeholder="e.g. $500 - $5000"></td></tr>
                    </table>
                </div>

                <div class="ajld-schema-fields" id="ajld-fields-service-extra" style="<?php echo in_array('service', $enabled_schemas) ? '' : 'display:none'; ?>">
                    <h4>Service Type (for "Hire X Developer" pages)</h4>
                    <table class="ajld-table">
                        <tr><th>Service Type</th><td><input type="text" name="auto_jsonld[service_type]" value="<?php echo esc_attr($meta['service_type'] ?? ''); ?>" class="widefat" placeholder="e.g. React Developer, Mobile App Development"></td></tr>
                    </table>
                </div>

                <div class="ajld-schema-fields" id="ajld-fields-creativework" style="<?php echo in_array('creativework', $enabled_schemas) ? '' : 'display:none'; ?>">
                    <h4>Portfolio / Case Study Details</h4>
                    <table class="ajld-table">
                        <tr><th>Client / Project Name</th><td><input type="text" name="auto_jsonld[project_client]" value="<?php echo esc_attr($meta['project_client'] ?? ''); ?>" class="widefat" placeholder="e.g. Ocean Money"></td></tr>
                        <tr><th>Tech Stack (comma separated)</th><td><input type="text" name="auto_jsonld[project_tech]" value="<?php echo esc_attr($meta['project_tech'] ?? ''); ?>" class="widefat" placeholder="React, Flutter, Node.js, Web3"></td></tr>
                        <tr><th>Start Date</th><td><input type="text" name="auto_jsonld[project_start]" value="<?php echo esc_attr($meta['project_start'] ?? ''); ?>" class="widefat" placeholder="YYYY-MM-DD (optional)"></td></tr>
                        <tr><th>End / Launch Date</th><td><input type="text" name="auto_jsonld[project_end]" value="<?php echo esc_attr($meta['project_end'] ?? ''); ?>" class="widefat" placeholder="YYYY-MM-DD (optional)"></td></tr>
                    </table>
                </div>

                <div class="ajld-schema-fields" id="ajld-fields-itemlist" style="<?php echo in_array('itemlist', $enabled_schemas) ? '' : 'display:none'; ?>">
                    <h4>ItemList Details</h4>
                    <table class="ajld-table">
                        <tr><th>List Name</th><td><input type="text" name="auto_jsonld[itemlist_name]" value="<?php echo esc_attr($meta['itemlist_name'] ?? ''); ?>" class="widefat" placeholder="e.g. Our Portfolio"></td></tr>
                        <tr><th>Item URLs (one per line)</th><td><textarea name="auto_jsonld[itemlist_urls]" rows="4" class="widefat" placeholder="https://example.com/project-1"><?php echo esc_textarea($meta['itemlist_urls'] ?? ''); ?></textarea></td></tr>
                    </table>
                </div>
            </div>

            <!-- Custom Schema Tab -->
            <div class="ajld-tab-content" id="ajld-tab-custom">
                <p class="ajld-hint">Add custom JSON-LD schema. This will be appended to the schema graph. Must be valid JSON.</p>
                <textarea name="auto_jsonld[custom_schema]" id="ajld_custom_schema" rows="12" class="widefat code" placeholder='{ "@type": "Event", "name": "My Event" }'><?php echo esc_textarea( $custom_schema ); ?></textarea>
                <p><button type="button" id="ajld-validate-json" class="button">Validate JSON</button> <span id="ajld-json-status"></span></p>
            </div>

            <!-- Preview Tab -->
            <div class="ajld-tab-content" id="ajld-tab-preview">
                <p class="ajld-hint">Save the post first, then click below to preview the generated schema.</p>
                <button type="button" id="ajld-preview-btn" class="button button-primary" data-url="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">Load Schema Preview</button>
                <pre id="ajld-preview-output" style="background:#1e1e1e;color:#d4d4d4;padding:16px;margin-top:12px;overflow:auto;max-height:400px;display:none;"></pre>
            </div>
        </div>
        <?php
    }

    public function save( $post_id, $post ) {
        if ( ! isset( $_POST['auto_jsonld_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['auto_jsonld_nonce'], 'auto_jsonld_meta_box' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $data        = [];
        $text_fields = [ 'seo_title', 'seo_description', 'seo_image', 'canonical', 'focus_keyword', 'custom_schema', 'service_name', 'service_type', 'service_description', 'service_area', 'service_price', 'project_client', 'project_tech', 'project_start', 'project_end', 'itemlist_name', 'itemlist_urls' ];
        $raw         = $_POST['auto_jsonld'] ?? [];

        foreach ( $text_fields as $field ) {
            $data[ $field ] = sanitize_textarea_field( $raw[ $field ] ?? '' );
        }
        $data['noindex']  = isset( $raw['noindex'] ) ? 1 : 0;
        $data['nofollow'] = isset( $raw['nofollow'] ) ? 1 : 0;
        $data['schemas']  = isset( $raw['schemas'] ) && is_array( $raw['schemas'] )
            ? array_map( 'sanitize_text_field', $raw['schemas'] )
            : [];

        update_post_meta( $post_id, '_auto_jsonld', $data );
    }
}
