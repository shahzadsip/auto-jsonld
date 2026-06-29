<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Auto_JSONLD_OpenGraph {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 2 );
    }

    public function output() {
        global $post;
        if ( ! is_singular() || ! $post ) return;

        $meta    = get_post_meta( $post->ID, '_auto_jsonld', true ) ?: [];
        $title   = ! empty( $meta['seo_title'] )       ? $meta['seo_title']       : get_the_title( $post->ID );
        $desc    = ! empty( $meta['seo_description'] ) ? $meta['seo_description'] : '';
        $url     = ! empty( $meta['canonical'] )       ? $meta['canonical']       : get_permalink( $post->ID );
        $image   = ! empty( $meta['seo_image'] )       ? $meta['seo_image']       : get_the_post_thumbnail_url( $post->ID, 'full' );
        $type    = is_singular( 'post' ) ? 'article' : 'website';
        $site    = Auto_JSONLD_Settings::get( 'org_name', get_bloginfo( 'name' ) );
        $twitter = Auto_JSONLD_Settings::get( 'social_twitter' );

        $tags = [
            [ 'property' => 'og:type',      'content' => $type ],
            [ 'property' => 'og:title',     'content' => $title ],
            [ 'property' => 'og:url',       'content' => $url ],
            [ 'property' => 'og:site_name', 'content' => $site ],
            [ 'name'     => 'twitter:card',  'content' => 'summary_large_image' ],
            [ 'name'     => 'twitter:title', 'content' => $title ],
        ];

        if ( $desc ) {
            $tags[] = [ 'property' => 'og:description',      'content' => $desc ];
            $tags[] = [ 'name'     => 'twitter:description', 'content' => $desc ];
            $tags[] = [ 'name'     => 'description',         'content' => $desc ];
        }
        if ( $image ) {
            $tags[] = [ 'property' => 'og:image',      'content' => $image ];
            $tags[] = [ 'name'     => 'twitter:image', 'content' => $image ];
        }
        if ( $twitter ) {
            $handle = '@' . ltrim( basename( $twitter ), '@' );
            $tags[] = [ 'name' => 'twitter:site',    'content' => $handle ];
            $tags[] = [ 'name' => 'twitter:creator', 'content' => $handle ];
        }
        if ( is_singular( 'post' ) ) {
            $tags[] = [ 'property' => 'article:published_time', 'content' => get_the_date( 'c', $post->ID ) ];
            $tags[] = [ 'property' => 'article:modified_time',  'content' => get_the_modified_date( 'c', $post->ID ) ];
        }

        foreach ( $tags as $tag ) {
            if ( isset( $tag['property'] ) ) {
                echo '<meta property="' . esc_attr( $tag['property'] ) . '" content="' . esc_attr( $tag['content'] ) . '">' . "\n";
            } else {
                echo '<meta name="' . esc_attr( $tag['name'] ) . '" content="' . esc_attr( $tag['content'] ) . '">' . "\n";
            }
        }
    }
}
