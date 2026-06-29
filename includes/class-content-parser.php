<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Auto_JSONLD_Content_Parser {

    /**
     * Extract FAQ items from a section with id="faq" or class="faq".
     * Scans for h2/h3 + following p pairs inside that section only.
     */
    public static function extract_faq( $content ) {
        if ( empty( $content ) ) return [];

        $faq_pattern = '/<(?:section|div)[^>]+(?:id=["\']faq["\']|class=["\'][^"\'>]*\bfaq\b[^"\'>]*["\'])[^>]*>(.*?)<\/(?:section|div)>/is';
        if ( ! preg_match( $faq_pattern, $content, $section_match ) ) return [];

        $items = [];
        preg_match_all( '/<h[23][^>]*>(.*?)<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/is', $section_match[1], $matches, PREG_SET_ORDER );

        foreach ( $matches as $match ) {
            $question = trim( wp_strip_all_tags( $match[1] ) );
            $answer   = trim( wp_strip_all_tags( $match[2] ) );
            if ( $question && $answer ) {
                $items[] = [ 'question' => $question, 'answer' => $answer ];
            }
        }
        return $items;
    }

    /**
     * Get the first image URL from post content.
     */
    public static function extract_first_image( $content ) {
        if ( empty( $content ) ) return '';
        preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $match );
        return $match[1] ?? '';
    }

    /**
     * Build an ImageObject with dimensions if available.
     */
    public static function get_image_object( $url ) {
        if ( empty( $url ) ) return null;
        $obj = [ '@type' => 'ImageObject', 'url' => esc_url( $url ) ];
        $id  = attachment_url_to_postid( $url );
        if ( $id ) {
            $meta = wp_get_attachment_metadata( $id );
            if ( ! empty( $meta['width'] ) )  $obj['width']  = (int) $meta['width'];
            if ( ! empty( $meta['height'] ) ) $obj['height'] = (int) $meta['height'];
        }
        return $obj;
    }

    /**
     * Build OpeningHoursSpecification from settings.
     */
    public static function get_opening_hours() {
        $day_map = [
            'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday',
            'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
        ];
        $specs = [];
        foreach ( $day_map as $key => $day ) {
            $hours = Auto_JSONLD_Settings::get( 'business_hours_' . $key );
            if ( $hours && strtolower( $hours ) !== 'closed' ) {
                $parts  = explode( '-', $hours );
                $specs[] = [
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'https://schema.org/' . $day,
                    'opens'     => trim( $parts[0] ?? '' ),
                    'closes'    => trim( $parts[1] ?? '' ),
                ];
            }
        }
        return $specs;
    }

    /**
     * Get sameAs array from social profile settings.
     */
    public static function get_same_as() {
        return array_values( array_filter([
            Auto_JSONLD_Settings::get( 'social_facebook' ),
            Auto_JSONLD_Settings::get( 'social_twitter' ),
            Auto_JSONLD_Settings::get( 'social_linkedin' ),
            Auto_JSONLD_Settings::get( 'social_instagram' ),
            Auto_JSONLD_Settings::get( 'social_youtube' ),
        ]));
    }
}
