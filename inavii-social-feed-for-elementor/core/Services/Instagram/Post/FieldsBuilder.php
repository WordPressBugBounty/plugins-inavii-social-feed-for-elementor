<?php

namespace Inavii\Instagram\Services\Instagram\Post;

use Inavii\Instagram\Utils\VersionChecker;
class FieldsBuilder {
    private $fields;

    private $isBusiness;

    public function __construct( bool $isBusiness ) {
        $this->fields = [];
        $this->isBusiness = $isBusiness;
        $this->addBaseFields();
        $this->addChildFields();
    }

    private function addBaseFields() : void {
        $this->addField( 'id' );
        $this->addField( 'media_type' );
        $this->addField( 'media_url' );
        $this->addField( 'thumbnail_url' );
        $this->addField( 'permalink' );
        $this->addField( 'username' );
        $this->addField( 'timestamp' );
        if ( $this->isBusiness ) {
            $this->addField( 'media_product_type' );
        }
    }

    private function addChildFields() : void {
        $this->addField( 'children{media_url,thumbnail_url,media_type}' );
    }

    private function addField( string $field ) : void {
        $this->fields[] = $field;
    }

    public function getAllFieldsAsString() : string {
        return implode( ',', $this->fields );
    }

}
