<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Auto_JSONLD_Settings {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu() {
        add_options_page(
            'Auto JSON-LD Schema Settings',
            'Auto JSON-LD',
            'manage_options',
            'auto-jsonld-settings',
            [ $this, 'render_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'auto_jsonld_settings', 'auto_jsonld_options', [
            'sanitize_callback' => [ $this, 'sanitize' ]
        ]);
    }

    public function sanitize( $input ) {
        $clean = [];
        $text_fields = [
            'org_name', 'org_url', 'org_logo', 'org_phone', 'org_email',
            'org_address', 'org_city', 'org_state', 'org_zip', 'org_country',
            'org_founding_date', 'org_employees', 'org_type',
            'social_facebook', 'social_twitter', 'social_linkedin',
            'social_instagram', 'social_youtube',
            'author_name', 'author_url', 'author_linkedin', 'author_twitter',
            'knows_about',
            'business_hours_mon', 'business_hours_tue', 'business_hours_wed',
            'business_hours_thu', 'business_hours_fri', 'business_hours_sat', 'business_hours_sun',
        ];
        foreach ( $text_fields as $field ) {
            $clean[ $field ] = isset( $input[ $field ] ) ? sanitize_text_field( $input[ $field ] ) : '';
        }
        $clean['default_schemas'] = isset( $input['default_schemas'] ) && is_array( $input['default_schemas'] )
            ? array_map( 'sanitize_text_field', $input['default_schemas'] )
            : [];
        return $clean;
    }

    public static function get( $key, $default = '' ) {
        $options = get_option( 'auto_jsonld_options', [] );
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }

    public function render_page() {
        $options = get_option( 'auto_jsonld_options', [] );
        $org_types = [
            'Organization', 'LocalBusiness', 'LegalService', 'MedicalBusiness',
            'FinancialService', 'EducationalOrganization', 'Restaurant',
            'Store', 'Hotel', 'RealEstateAgent', 'ITService',
        ];
        $default_schema_options = [
            'website'       => 'WebSite (Global)',
            'organization'  => 'Organization (Global)',
            'breadcrumb'    => 'BreadcrumbList',
            'webpage'       => 'WebPage',
            'article'       => 'Article (Blog Posts)',
            'faq'           => 'FAQPage (Auto-detected)',
            'service'       => 'Service',
            'localbusiness' => 'LocalBusiness',
        ];
        ?>
        <div class="wrap">
            <h1>Auto JSON-LD Schema Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'auto_jsonld_settings' ); ?>

                <h2>Organization / Business Info</h2>
                <table class="form-table">
                    <tr><th>Organization Name</th><td><input type="text" name="auto_jsonld_options[org_name]" value="<?php echo esc_attr( $options['org_name'] ?? get_bloginfo('name') ); ?>" class="regular-text"></td></tr>
                    <tr><th>Website URL</th><td><input type="text" name="auto_jsonld_options[org_url]" value="<?php echo esc_attr( $options['org_url'] ?? home_url('/') ); ?>" class="regular-text"></td></tr>
                    <tr><th>Logo URL</th><td><input type="text" name="auto_jsonld_options[org_logo]" value="<?php echo esc_attr( $options['org_logo'] ?? get_site_icon_url(512) ); ?>" class="regular-text"></td></tr>
                    <tr><th>Phone</th><td><input type="text" name="auto_jsonld_options[org_phone]" value="<?php echo esc_attr( $options['org_phone'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Email</th><td><input type="text" name="auto_jsonld_options[org_email]" value="<?php echo esc_attr( $options['org_email'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Organization Type</th><td>
                        <select name="auto_jsonld_options[org_type]">
                            <?php foreach ( $org_types as $type ) : ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected( $options['org_type'] ?? 'Organization', $type ); ?>><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th>Founding Date</th><td><input type="text" name="auto_jsonld_options[org_founding_date]" value="<?php echo esc_attr( $options['org_founding_date'] ?? '' ); ?>" placeholder="YYYY-MM-DD" class="regular-text"></td></tr>
                    <tr><th>Number of Employees</th><td><input type="text" name="auto_jsonld_options[org_employees]" value="<?php echo esc_attr( $options['org_employees'] ?? '' ); ?>" class="small-text"></td></tr>
                    <tr><th>Knows About (comma separated)</th><td><input type="text" name="auto_jsonld_options[knows_about]" value="<?php echo esc_attr( $options['knows_about'] ?? '' ); ?>" class="large-text" placeholder="Web Design, SEO, WordPress Development"></td></tr>
                </table>

                <h2>Address (for LocalBusiness)</h2>
                <table class="form-table">
                    <tr><th>Street Address</th><td><input type="text" name="auto_jsonld_options[org_address]" value="<?php echo esc_attr( $options['org_address'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>City</th><td><input type="text" name="auto_jsonld_options[org_city]" value="<?php echo esc_attr( $options['org_city'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>State / Region</th><td><input type="text" name="auto_jsonld_options[org_state]" value="<?php echo esc_attr( $options['org_state'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>ZIP / Postal Code</th><td><input type="text" name="auto_jsonld_options[org_zip]" value="<?php echo esc_attr( $options['org_zip'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Country Code</th><td><input type="text" name="auto_jsonld_options[org_country]" value="<?php echo esc_attr( $options['org_country'] ?? '' ); ?>" class="small-text" placeholder="US"></td></tr>
                </table>

                <h2>Business Hours (for LocalBusiness)</h2>
                <table class="form-table">
                    <?php
                    $days = [ 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' ];
                    foreach ( $days as $key => $label ) :
                    ?>
                    <tr><th><?php echo esc_html($label); ?></th><td><input type="text" name="auto_jsonld_options[business_hours_<?php echo $key; ?>]" value="<?php echo esc_attr( $options['business_hours_' . $key] ?? '' ); ?>" placeholder="09:00-17:00 or Closed" class="regular-text"></td></tr>
                    <?php endforeach; ?>
                </table>

                <h2>Social Profiles (E-E-A-T)</h2>
                <table class="form-table">
                    <tr><th>Facebook</th><td><input type="text" name="auto_jsonld_options[social_facebook]" value="<?php echo esc_attr( $options['social_facebook'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Twitter / X</th><td><input type="text" name="auto_jsonld_options[social_twitter]" value="<?php echo esc_attr( $options['social_twitter'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>LinkedIn</th><td><input type="text" name="auto_jsonld_options[social_linkedin]" value="<?php echo esc_attr( $options['social_linkedin'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Instagram</th><td><input type="text" name="auto_jsonld_options[social_instagram]" value="<?php echo esc_attr( $options['social_instagram'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>YouTube</th><td><input type="text" name="auto_jsonld_options[social_youtube]" value="<?php echo esc_attr( $options['social_youtube'] ?? '' ); ?>" class="regular-text"></td></tr>
                </table>

                <h2>Default Author Info (E-E-A-T)</h2>
                <table class="form-table">
                    <tr><th>Author Name</th><td><input type="text" name="auto_jsonld_options[author_name]" value="<?php echo esc_attr( $options['author_name'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Author Profile URL</th><td><input type="text" name="auto_jsonld_options[author_url]" value="<?php echo esc_attr( $options['author_url'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Author LinkedIn</th><td><input type="text" name="auto_jsonld_options[author_linkedin]" value="<?php echo esc_attr( $options['author_linkedin'] ?? '' ); ?>" class="regular-text"></td></tr>
                    <tr><th>Author Twitter / X</th><td><input type="text" name="auto_jsonld_options[author_twitter]" value="<?php echo esc_attr( $options['author_twitter'] ?? '' ); ?>" class="regular-text"></td></tr>
                </table>

                <h2>Default Schemas (applied globally)</h2>
                <table class="form-table">
                    <tr><td>
                        <?php foreach ( $default_schema_options as $key => $label ) : ?>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox" name="auto_jsonld_options[default_schemas][]" value="<?php echo esc_attr($key); ?>"
                                <?php checked( in_array( $key, $options['default_schemas'] ?? ['website','organization','breadcrumb'] ) ); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </td></tr>
                </table>

                <?php submit_button( 'Save Settings' ); ?>
            </form>
        </div>
        <?php
    }
}
