<?php

namespace JustFastImages\Controller;

class RewriteAttachmentsController {

    /**
     * Lock to prevent infinite recursion when filtering the default image.
     * @var bool
     */
    protected $lock = false;

    /**
     * Whether the controller is enabled.
     * @var bool
     */
    protected static $enabled = true;

    /**
     * Disable the controller.
     */
    public static function disable() {

        self::$enabled = false;

    }

    public function __construct() {
    
        add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], PHP_INT_MAX, 2 );
        add_filter( 'wp_get_attachment_image_src', [ $this, 'wp_get_attachment_image_src' ], PHP_INT_MAX, 4 );
        add_filter( 'wp_get_attachment_metadata', [ $this, 'wp_get_attachment_metadata' ], PHP_INT_MAX, 2 );

    }

    public function wp_get_attachment_url( $url, $attachment_id ) {

        if ( ! self::$enabled ) {
            return $url;
        }

        if ( ! $this->lock ) {
            $this->lock = true;

            $url = $this->get_attachment_url( $attachment_id );

            $this->lock = false;
        }

        return $url;

    }

    public function wp_get_attachment_image_src( $image, $attachment_id, $size, $icon ) {

        if ( ! self::$enabled ) {
            return $image;
        }

        if ( is_array( $size ) ) {
            $size = array_shift( $size );
        }

        if ( ! $this->lock ) {
            $this->lock = true;

            $url = $this->get_attachment_url( $attachment_id, $size );
            $image[0] = $url;

            $this->lock = false;
        }

        return $image;

    }

    public function wp_get_attachment_metadata( $data, $attachment_id ) {

        if ( ! self::$enabled ) {
            return $data;
        }

        $data['file'] = "$attachment_id";

        foreach ( $data['sizes'] as $size => $size_data ) {
            $data['sizes'][$size]['file'] = "$attachment_id";
        }

        return $data;

    }

    protected function get_attachment_url( $attachment_id, $size = '' ) {

        if ( $size ) {

            $attachment_path = sprintf(
                'asset/%s/%d',
                $size,
                $attachment_id
            );

        } else {

            $attachment_path = sprintf(
                'asset/%d',
                $attachment_id
            );

        }

        $url = home_url( $attachment_path );

        return $url;

    }

}