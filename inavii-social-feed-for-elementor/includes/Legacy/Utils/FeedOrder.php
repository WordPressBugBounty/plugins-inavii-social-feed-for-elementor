<?php
declare( strict_types=1 );


namespace Inavii\Instagram\Includes\Legacy\Utils;

class FeedOrder {
	private const KEY_DATE         = '_inavii_date';
	private const KEY_COMMENTS     = '_inavii_comments_count';
	private const KEY_LIKES        = '_inavii_likes_count';
	private const KEY_LAST_REQUEST = '_inavii_last_requested';

	public $key;
	public $valueType;
	public $order;
	public $isRandom = false;

	public function __construct( string $key, string $valueType, string $order, bool $isRandom = false ) {
		$this->key       = $key;
		$this->valueType = $valueType;
		$this->order     = $order;
		$this->isRandom  = $isRandom;
	}

	public static function create( string $order ): FeedOrder {
		switch ( $order ) {
			case 'likeCount':
				return new self( self::KEY_LIKES, 'NUMERIC', 'DESC' );
			case 'commentCount':
				return new self( self::KEY_COMMENTS, 'NUMERIC', 'DESC' );
			case 'mostRecentFirst':
				return new self( self::KEY_DATE, 'CHAR', 'DESC' );
			case 'oldestFirst':
				return new self( self::KEY_DATE, 'CHAR', 'ASC' );
			case 'lastRequested':
				return new self( self::KEY_LAST_REQUEST, 'CHAR', 'DESC' );
			case 'random':
				return new self( '', '', '', true );
			default:
				return self::create( 'mostRecentFirst' );
		}
	}
}
