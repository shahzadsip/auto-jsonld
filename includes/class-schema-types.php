<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Auto_JSONLD_Schema_Types {

    private $post;
    private $meta;
    private $site_url;
    private $page_url;
    private $lang;

    public function __construct( $post, $meta ) {
        $this->post     = $post;
        $this->meta     = $meta;
        $this->site_url = home_url( '/' );
        $this->page_url = ! empty( $meta['canonical'] ) ? $meta['canonical'] : ( $post ? get_permalink( $post->ID ) : home_url('/') );
        $this->lang     = get_bloginfo( 'language' );
    }

    public function website() {
        return [
            '@type'           => 'WebSite',
            '@id'             => $this->site_url . '#website',
            'name'            => Auto_JSONLD_Settings::get( 'org_name', get_bloginfo( 'name' ) ),
            'url'             => $this->site_url,
            'inLanguage'      => $this->lang,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [ '@type' => 'EntryPoint', 'urlTemplate' => $this->site_url . '?s={search_term_string}' ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function organization() {
        $org_type  = Auto_JSONLD_Settings::get( 'org_type', 'Organization' );
        $same_as   = Auto_JSONLD_Content_Parser::get_same_as();
        $knows     = Auto_JSONLD_Settings::get( 'knows_about' );
        $logo_url  = Auto_JSONLD_Settings::get( 'org_logo', get_site_icon_url( 512 ) );

        $schema = [
            '@type' => $org_type,
            '@id'   => $this->site_url . '#organization',
            'name'  => Auto_JSONLD_Settings::get( 'org_name', get_bloginfo( 'name' ) ),
            'url'   => Auto_JSONLD_Settings::get( 'org_url', $this->site_url ),
            'logo'  => Auto_JSONLD_Content_Parser::get_image_object( $logo_url ),
        ];

        if ( $same_as )   $schema['sameAs']     = $same_as;
        if ( $knows )     $schema['knowsAbout'] = array_map( 'trim', explode( ',', $knows ) );

        $phone     = Auto_JSONLD_Settings::get( 'org_phone' );
        $email     = Auto_JSONLD_Settings::get( 'org_email' );
        $founding  = Auto_JSONLD_Settings::get( 'org_founding_date' );
        $employees = Auto_JSONLD_Settings::get( 'org_employees' );

        if ( $phone )     $schema['telephone']         = $phone;
        if ( $email )     $schema['email']             = $email;
        if ( $founding )  $schema['foundingDate']      = $founding;
        if ( $employees ) $schema['numberOfEmployees'] = [ '@type' => 'QuantitativeValue', 'value' => (int) $employees ];

        $address = $this->get_address();
        if ( $address ) $schema['address'] = $address;

        $hours = Auto_JSONLD_Content_Parser::get_opening_hours();
        if ( $hours ) $schema['openingHoursSpecification'] = $hours;

        return $schema;
    }

    public function webpage() {
        $title = ! empty( $this->meta['seo_title'] ) ? $this->meta['seo_title'] : get_the_title( $this->post->ID );
        $image = $this->get_image();
        $schema = [
            '@type'      => 'WebPage',
            '@id'        => $this->page_url . '#webpage',
            'url'        => $this->page_url,
            'name'       => $title,
            'inLanguage' => $this->lang,
            'isPartOf'   => [ '@id' => $this->site_url . '#website' ],
        ];
        if ( ! empty( $this->meta['seo_description'] ) ) $schema['description']          = $this->meta['seo_description'];
        if ( $image )                                     $schema['primaryImageOfPage']   = Auto_JSONLD_Content_Parser::get_image_object( $image );
        return $schema;
    }

    public function article( $type = 'Article' ) {
        $author_data    = $this->post ? get_userdata( $this->post->post_author ) : null;
        $title          = ! empty( $this->meta['seo_title'] ) ? $this->meta['seo_title'] : get_the_title( $this->post->ID );
        $image          = $this->get_image();
        $author_name    = Auto_JSONLD_Settings::get( 'author_name' ) ?: ( $author_data ? $author_data->display_name : '' );
        $author_url     = Auto_JSONLD_Settings::get( 'author_url' ) ?: get_author_posts_url( $this->post->post_author );
        $author_same_as = array_values( array_filter([
            Auto_JSONLD_Settings::get( 'author_linkedin' ),
            Auto_JSONLD_Settings::get( 'author_twitter' ),
        ]));

        $author = [ '@type' => 'Person', '@id' => $author_url . '#author', 'name' => $author_name, 'url' => $author_url ];
        if ( $author_same_as ) $author['sameAs'] = $author_same_as;

        $schema = [
            '@type'         => $type,
            '@id'           => $this->page_url . '#article',
            'headline'      => $title,
            'url'           => $this->page_url,
            'inLanguage'    => $this->lang,
            'datePublished' => get_the_date( 'c', $this->post->ID ),
            'dateModified'  => get_the_modified_date( 'c', $this->post->ID ),
            'author'        => $author,
            'publisher'     => [ '@id' => $this->site_url . '#organization' ],
            'isPartOf'      => [ '@id' => $this->page_url . '#webpage' ],
        ];
        if ( ! empty( $this->meta['seo_description'] ) ) $schema['description'] = $this->meta['seo_description'];
        if ( $image ) $schema['image'] = Auto_JSONLD_Content_Parser::get_image_object( $image );
        $this->apply_focus_keyword( $schema );
        return $schema;
    }

    /**
     * Inject the focus keyword as an `about` entity and `keywords` string.
     * This is the single biggest SEO win for case-study / portfolio pages:
     * it tells Google the exact topic the page is built around.
     */
    private function apply_focus_keyword( &$schema ) {
        $keyword = ! empty( $this->meta['focus_keyword'] ) ? trim( $this->meta['focus_keyword'] ) : '';
        if ( ! $keyword ) return;
        $schema['about']    = [ '@type' => 'Thing', 'name' => $keyword ];
        $schema['keywords'] = $keyword;
    }

    public function faq( $items ) {
        if ( empty( $items ) ) return null;
        $entities = [];
        foreach ( $items as $item ) {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $item['answer'] ],
            ];
        }
        return [ '@type' => 'FAQPage', '@id' => $this->page_url . '#faqpage', 'mainEntity' => $entities ];
    }

    public function service() {
        $name   = ! empty( $this->meta['service_name'] ) ? $this->meta['service_name'] : get_the_title( $this->post->ID );
        $schema = [
            '@type'    => 'Service',
            '@id'      => $this->page_url . '#service',
            'name'     => $name,
            'provider' => [ '@id' => $this->site_url . '#organization' ],
            'url'      => $this->page_url,
        ];
        if ( ! empty( $this->meta['service_type'] ) )        $schema['serviceType'] = $this->meta['service_type'];
        if ( ! empty( $this->meta['service_description'] ) ) $schema['description'] = $this->meta['service_description'];
        if ( ! empty( $this->meta['service_area'] ) )        $schema['areaServed']  = $this->meta['service_area'];
        if ( ! empty( $this->meta['service_price'] ) )       $schema['offers']      = [ '@type' => 'Offer', 'price' => $this->meta['service_price'] ];
        $this->apply_focus_keyword( $schema );
        return $schema;
    }

    /**
     * CreativeWork: rich modeling for portfolio / case-study pages.
     * More descriptive than Article for showcasing real client projects:
     * captures the project, who it was built for, the tech used, and timeline.
     */
    public function creative_work() {
        $author_data = $this->post ? get_userdata( $this->post->post_author ) : null;
        $name        = ! empty( $this->meta['seo_title'] ) ? $this->meta['seo_title'] : get_the_title( $this->post->ID );
        $image       = $this->get_image();
        $author_name = Auto_JSONLD_Settings::get( 'author_name' ) ?: ( $author_data ? $author_data->display_name : '' );
        $author_url  = Auto_JSONLD_Settings::get( 'author_url' ) ?: get_author_posts_url( $this->post->post_author );

        $schema = [
            '@type'         => 'CreativeWork',
            '@id'           => $this->page_url . '#project',
            'name'          => $name,
            'url'           => $this->page_url,
            'inLanguage'    => $this->lang,
            'datePublished' => get_the_date( 'c', $this->post->ID ),
            'dateModified'  => get_the_modified_date( 'c', $this->post->ID ),
            'creator'       => [ '@type' => 'Person', '@id' => $author_url . '#author', 'name' => $author_name, 'url' => $author_url ],
            'publisher'     => [ '@id' => $this->site_url . '#organization' ],
            'isPartOf'      => [ '@id' => $this->page_url . '#webpage' ],
        ];

        $description = ! empty( $this->meta['seo_description'] ) ? $this->meta['seo_description'] : '';
        if ( $description ) $schema['description'] = $description;
        if ( $image )      $schema['image']       = Auto_JSONLD_Content_Parser::get_image_object( $image );

        // Tech stack feeds keywords (comma separated pills on the page).
        if ( ! empty( $this->meta['project_tech'] ) ) {
            $schema['keywords'] = $this->meta['project_tech'];
        }
        // Who the project was built for.
        if ( ! empty( $this->meta['project_client'] ) ) {
            $schema['about'] = [ '@type' => 'Organization', 'name' => $this->meta['project_client'] ];
        }
        // Optional explicit project timeline (overrides post dates if given).
        if ( ! empty( $this->meta['project_start'] ) ) $schema['datePublished'] = $this->meta['project_start'];
        if ( ! empty( $this->meta['project_end'] ) )   $schema['dateModified']  = $this->meta['project_end'];

        // Focus keyword still applies (sets/overrides `about` topic + keywords).
        $this->apply_focus_keyword( $schema );
        return $schema;
    }

    public function local_business() {
        $org          = $this->organization();
        $org['@type'] = Auto_JSONLD_Settings::get( 'org_type', 'LocalBusiness' );
        $org['@id']   = $this->site_url . '#localbusiness';
        return $org;
    }

    public function about_page() {
        $s = $this->webpage();
        $s['@type'] = 'AboutPage';
        $s['@id']   = $this->page_url . '#aboutpage';
        return $s;
    }

    public function contact_page() {
        $s = $this->webpage();
        $s['@type'] = 'ContactPage';
        $s['@id']   = $this->page_url . '#contactpage';
        return $s;
    }

    public function breadcrumb() {
        $items = [ [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $this->site_url ] ];
        $pos   = 2;

        if ( $this->post && ! is_front_page() ) {
            // Build ancestor trail so it renders as Home / Case Studies / Ocean Money
            // instead of a flat two-level crumb. Pages walk their parent chain;
            // posts use their primary category.
            $trail = [];

            if ( is_page() || $this->post->post_type === 'page' ) {
                $ancestors = array_reverse( get_post_ancestors( $this->post->ID ) );
                foreach ( $ancestors as $ancestor_id ) {
                    $trail[] = [ 'name' => get_the_title( $ancestor_id ), 'item' => get_permalink( $ancestor_id ) ];
                }
            } else {
                $cats = get_the_category( $this->post->ID );
                if ( ! empty( $cats ) ) {
                    $cat     = $cats[0];
                    $cat_url = get_category_link( $cat->term_id );
                    if ( $cat_url ) {
                        $trail[] = [ 'name' => $cat->name, 'item' => $cat_url ];
                    }
                }
            }

            foreach ( $trail as $crumb ) {
                $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'name' => $crumb['name'], 'item' => $crumb['item'] ];
            }

            // Current page is the last crumb (no `item` per Google guidance).
            $items[] = [ '@type' => 'ListItem', 'position' => $pos, 'name' => get_the_title( $this->post->ID ) ];
        }

        return [ '@type' => 'BreadcrumbList', '@id' => $this->page_url . '#breadcrumb', 'itemListElement' => $items ];
    }

    public function item_list() {
        $name  = ! empty( $this->meta['itemlist_name'] ) ? $this->meta['itemlist_name'] : get_the_title( $this->post->ID );
        $urls  = array_filter( array_map( 'trim', explode( "\n", $this->meta['itemlist_urls'] ?? '' ) ) );
        $items = [];
        $pos   = 1;
        foreach ( $urls as $url ) {
            $items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'url' => esc_url( $url ) ];
        }
        return [ '@type' => 'ItemList', '@id' => $this->page_url . '#itemlist', 'name' => $name, 'itemListElement' => $items ];
    }

    private function get_image() {
        if ( ! empty( $this->meta['seo_image'] ) ) return $this->meta['seo_image'];
        $thumb = $this->post ? get_the_post_thumbnail_url( $this->post->ID, 'full' ) : '';
        if ( $thumb ) return $thumb;
        return $this->post ? Auto_JSONLD_Content_Parser::extract_first_image( $this->post->post_content ) : '';
    }

    private function get_address() {
        $street  = Auto_JSONLD_Settings::get( 'org_address' );
        $city    = Auto_JSONLD_Settings::get( 'org_city' );
        if ( ! $street && ! $city ) return null;
        return array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street,
            'addressLocality' => $city,
            'addressRegion'   => Auto_JSONLD_Settings::get( 'org_state' ),
            'postalCode'      => Auto_JSONLD_Settings::get( 'org_zip' ),
            'addressCountry'  => Auto_JSONLD_Settings::get( 'org_country' ),
        ]);
    }
}
