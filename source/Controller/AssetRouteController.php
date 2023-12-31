<?php

namespace JustFastImages\Controller;

use JustFastImages\Library\MediaHelper;

class AssetRouteController {

    public function __construct() {

        add_action( 'init', [ $this, 'add_asset_routes' ], PHP_INT_MAX );

    }

    public function add_asset_routes() {

        $this->add_route( '^asset/([0-9]+)[^/]*$', [ $this, 'handle_attachment_route' ] );
        $this->add_route( '^asset/([-_a-zA-Z0-9]+)/([0-9]+)[^/]*$', [ $this, 'handle_image_route' ] );

    }

    public function handle_attachment_route( $attachment_id ) {

        $mime_type = get_post_mime_type( $attachment_id );

        if ( $this->is_image_mime( $mime_type ) ) {
            // If is image, pass it along to the image route handler.

            $this->handle_image_route( 'full', $attachment_id );

        } else {
            // Otherwise serve file directly.

            $this->serve_file( $attachment_id );

        }

    }

    public function handle_image_route( $image_size, $attachment_id ) {

        // Limit the image sizes if configured.
        if ( 'full' === $image_size ) {

            $full_image_limit = $this->get_setting_full_image_limit();
            if ( $full_image_limit ) {
                $image_size = $full_image_limit;
            }

        } else if ( 'post-thumbnail' === $image_size ) {
                
            $featured_image_limit = $this->get_setting_featured_image_limit();
            if ( $featured_image_limit ) {
                $image_size = $featured_image_limit;
            }

        }

        $this->serve_image( $attachment_id, $image_size );

    }

    protected function serve_file( $attachment_id ) {

        $file_path = get_attached_file( $attachment_id );
        $mime_type = get_post_mime_type( $attachment_id );

        if ( ! $file_path ) {
            status_header( 404 );
            exit;
        }

        header(
            sprintf(
                'Content-Type: %s',
                $mime_type
            )
        );

        $this->set_cache_headers();

        readfile( $file_path );

    }

    protected function serve_image( $attachment_id, $image_size ) {

        $file_path = get_attached_file( $attachment_id );
        $mime_type = get_post_mime_type( $attachment_id );

        // Skip optimising GIFs to prevent issues.
        if ( 'image/gif' === $mime_type ) {
            $this->serve_file( $attachment_id );
            return;
        }

        // Optimise and serve image.

        $image = wp_get_image_editor( $file_path );

        if ( is_wp_error( $image ) ) {
            // If the image could not be loaded, serve the original file as a fallback.

            $this->serve_file( $attachment_id );
            return;
        }
        
        // Resize the image.

        $media_helper = new MediaHelper();
        $image_sizes = $media_helper->get_image_sizes();

        if ( isset( $image_sizes[$image_size] ) ) {
            
            $image_size_definition = $image_sizes[$image_size];

            $width  = $image_size_definition['width'] ?? 0;
            $height = $image_size_definition['height'] ?? 0;
            $crop   = $image_size_definition['crop'] ?? false;

            if ( $width || $height ) {
                $image->resize( $width, $height, $crop );
            }

        }

        // Convert and stream the image.

        $this->set_cache_headers();

        $image->set_quality( $this->get_setting_webp_quality() );
        $stream_status = $image->stream( 'image/webp' );
        if ( ! $stream_status || is_wp_error( $stream_status ) ) {
            $image->stream();
        }

    }

    protected function set_cache_headers() {

        $cache_expires = 60 * 60 * 24 * 365; // 1 year.
        $cache_expires = apply_filters( 'just_fast_images_cache_expires', $cache_expires );

        header(
            sprintf(
                'Cache-Control: public, max-age=%d',
                $cache_expires
            )
        );

    }

    protected function add_route( $regex, $callback ) {

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_uri = trim( $request_uri, '/' );

        $matched = preg_match( '#' . $regex . '#', $request_uri, $matches );
        if ( $matched ) {

            // Disable the attachment rewriting so that we get the original file path.
            RewriteAttachmentsController::disable();

            call_user_func_array( $callback, array_slice( $matches, 1 ) );
            exit;
        }

    }

    protected function get_setting_webp_quality() {
        
        $model = new \JustFastImages\Model\SettingsModel();

        $option_value = $model->get_value( 'webp_quality', 80 );

        return $option_value;

    }

    protected function get_setting_full_image_limit() {
        
        $model = new \JustFastImages\Model\SettingsModel();

        $option_value = $model->get_value( 'full_image_limit' );

        return $option_value;

    }

    protected function get_setting_featured_image_limit() {
        
        $model = new \JustFastImages\Model\SettingsModel();

        $option_value = $model->get_value( 'featured_image_limit' );

        return $option_value;

    }

    protected function is_image_mime( $mime_type ) {

        $is_image = false;

        if ( preg_match( '#^image/#', $mime_type ) ) {
            $is_image = true;
        }

        return $is_image;

    }

}
