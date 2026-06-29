<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Auto_JSONLD_Schema_Engine {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 1 );
        add_action( 'wp_head', [ $this, 'output_robots' ], 1 );
        add_action( 'wp_head', [ $this, 'output_canonical' ], 1 );
    }

    public function output() {
        global $post;
        $graph           = [];
        $default_schemas = Auto_JSONLD_Settings::get( 'default_schemas', [ 'website', 'organization', 'breadcrumb' ] );
        $empty_meta      = [ 'seo_title' => '', 'seo_description' => '', 'seo_image' => '', 'canonical' => '', 'noindex' => 0, 'nofollow' => 0, 'schemas' => [], 'custom_schema' => '', 'service_name' => '', 'service_description' => '', 'service_area' => '', 'service_price' => '', 'itemlist_name' => '', 'itemlist_urls' => '' ];

        // Global schemas - always output
        $global_types = new Auto_JSONLD_Schema_Types( $post, $empty_meta );
        if ( in_array( 'website', $default_schemas ) )      $graph[] = $global_types->website();
        if ( in_array( 'organization', $default_schemas ) ) $graph[] = $global_types->organization();

        if ( is_singular() && $post ) {
            $meta    = wp_parse_args( get_post_meta( $post->ID, '_auto_jsonld', true ) ?: [], $empty_meta );
            $types   = new Auto_JSONLD_Schema_Types( $post, $meta );
            $enabled = ! empty( $meta['schemas'] ) ? $meta['schemas'] : $default_schemas;

            foreach ( $enabled as $key ) {
                switch ( $key ) {
                    case 'webpage':       $graph[] = $types->webpage(); break;
                    case 'article':       $graph[] = $types->article( 'Article' ); break;
                    case 'blogposting':   $graph[] = $types->article( 'BlogPosting' ); break;
                    case 'service':       $graph[] = $types->service(); break;
                    case 'localbusiness': $graph[] = $types->local_business(); break;
                    case 'aboutpage':     $graph[] = $types->about_page(); break;
                    case 'contactpage':   $graph[] = $types->contact_page(); break;
                    case 'breadcrumb':    $graph[] = $types->breadcrumb(); break;
                    case 'itemlist':      $graph[] = $types->item_list(); break;
                    case 'faq':
                        $faq = $types->faq( Auto_JSONLD_Content_Parser::extract_faq( $post->post_content ) );
                        if ( $faq ) $graph[] = $faq;
                        break;
                }
            }

            // Append custom schema if valid JSON
            if ( ! empty( $meta['custom_schema'] ) ) {
                $custom = json_decode( $meta['custom_schema'], true );
                if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
                    $graph[] = $custom;
                }
            }
        } else {
            // Non-singular pages (archives, home, etc.)
            if ( in_array( 'breadcrumb', $default_schemas ) ) {
                $graph[] = [
                    '@type'           => 'BreadcrumbList',
                    '@id'             => home_url('/') . '#breadcrumb',
                    'itemListElement' => [ [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/') ] ],
                ];
            }
        }

        $graph = array_values( array_filter( $graph ) );
        if ( empty( $graph ) ) return;

        echo '<script type="application/ld+json">' . "\n"
            . wp_json_encode(
                [ '@context' => 'https://schema.org', '@graph' => $graph ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
            . "\n</script>\n";
    }

    public function output_robots() {
        global $post;
        if ( ! is_singular() || ! $post ) return;
        $meta     = get_post_meta( $post->ID, '_auto_jsonld', true ) ?: [];
        $parts    = array_filter([
            ! empty( $meta['noindex'] )  ? 'noindex'  : '',
            ! empty( $meta['nofollow'] ) ? 'nofollow' : '',
        ]);
        if ( $parts ) {
            echo '<meta name="robots" content="' . esc_attr( implode( ', ', $parts ) ) . '">' . "\n";
        }
    }

    public function output_canonical() {
        global $post;
        if ( ! is_singular() || ! $post ) return;
        $meta      = get_post_meta( $post->ID, '_auto_jsonld', true ) ?: [];
        $canonical = ! empty( $meta['canonical'] ) ? $meta['canonical'] : get_permalink( $post->ID );
        echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    }
}
