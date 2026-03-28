<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Domain;

final class MediaPost {

	private string $igMediaId;
	private string $mediaType;
	private string $mediaProductType;
	private string $url;
	private string $permalink;
	private string $username;
	private string $videoUrl;
	private string $postedAt;
	private int $commentsCount;
	private int $likesCount;
	private string $caption;
	private string $childrenJson;

	public function __construct(
		string $igMediaId,
		string $mediaType,
		string $mediaProductType,
		string $url,
		string $permalink,
		string $username,
		string $videoUrl,
		string $postedAt,
		int $commentsCount,
		int $likesCount,
		string $caption,
		string $childrenJson
	) {
		$this->igMediaId        = $igMediaId;
		$this->mediaType        = $mediaType;
		$this->mediaProductType = $mediaProductType;
		$this->url              = $url;
		$this->permalink        = $permalink;
		$this->username         = $username;
		$this->videoUrl         = $videoUrl;
		$this->postedAt         = $postedAt;
		$this->commentsCount    = $commentsCount;
		$this->likesCount       = $likesCount;
		$this->caption          = $caption;
		$this->childrenJson     = $childrenJson;
	}

	public static function fromInstagram( array $raw ): ?self {
		$igId = isset( $raw['id'] ) ? (string) $raw['id'] : '';
		if ( $igId === '' ) {
			return null;
		}

		$postedAt = self::normalizePostedAt( $raw['timestamp'] ?? null );
		if ( $postedAt === null ) {
			return null;
		}

		$mediaType = isset( $raw['media_type'] ) ? (string) $raw['media_type'] : '';
		$mediaUrl  = isset( $raw['media_url'] ) ? (string) $raw['media_url'] : '';
		$thumbUrl  = isset( $raw['thumbnail_url'] ) ? (string) $raw['thumbnail_url'] : '';

		$children     = self::normalizeChildren( $raw['children'] ?? null );
		$childrenJson = $children !== null ? wp_json_encode( $children ) : '';

		$displayUrl = self::displayUrl( $children, $mediaType, $mediaUrl, $thumbUrl );

		$videoUrl = '';
		if ( $mediaType === 'VIDEO' ) {
			$videoUrl = $mediaUrl;
		}

		$caption = isset( $raw['caption'] ) ? (string) $raw['caption'] : '';
		$caption = html_entity_decode( $caption, ENT_QUOTES, 'UTF-8' );

		$mediaProductType = '';
		if ( isset( $raw['media_product_type'] ) && (string) $raw['media_product_type'] !== '' ) {
			$mediaProductType = (string) $raw['media_product_type'];
		}

		return new self(
			$igId,
			$mediaType,
			$mediaProductType,
			$displayUrl,
			(string) ( $raw['permalink'] ?? '' ),
			(string) ( $raw['username'] ?? '' ),
			$videoUrl,
			$postedAt,
			(int) ( $raw['comments_count'] ?? 0 ),
			(int) ( $raw['like_count'] ?? 0 ),
			$caption,
			$childrenJson
		);
	}

	/**
	 * @param array $rawItems
	 * @return MediaPost[]
	 */
	public static function fromInstagramList( array $rawItems ): array {
		$out = [];
		foreach ( $rawItems as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$post = self::fromInstagram( $raw );
			if ( $post ) {
				$out[] = $post;
			}
		}

		return $out;
	}

	/**
	 * @return array
	 */
	public function toDbRow(): array {
		return [
			'ig_media_id'        => $this->igMediaId,
			'media_type'         => $this->mediaType,
			'media_product_type' => $this->mediaProductType,
			'url'                => $this->url,
			'permalink'          => $this->permalink,
			'username'           => $this->username,
			'video_url'          => $this->videoUrl,
			'posted_at'          => $this->postedAt,
			'comments_count'     => $this->commentsCount,
			'likes_count'        => $this->likesCount,
			'caption'            => $this->caption,
			'children_json'      => $this->childrenJson,
		];
	}

	/**
	 * @param mixed $childrenNode
	 * @return array|null
	 */
	private static function normalizeChildren( $childrenNode ): ?array {
		if ( ! is_array( $childrenNode ) ) {
			return null;
		}
		if ( ! isset( $childrenNode['data'] ) || ! is_array( $childrenNode['data'] ) ) {
			return null;
		}

		$out = [];
		foreach ( $childrenNode['data'] as $c ) {
			if ( ! is_array( $c ) ) {
				continue;
			}

			$cid = isset( $c['id'] ) ? (string) $c['id'] : '';
			if ( $cid === '' ) {
				continue;
			}

			$ctype  = isset( $c['media_type'] ) ? (string) $c['media_type'] : '';
			$curl   = isset( $c['media_url'] ) ? (string) $c['media_url'] : '';
			$cthumb = isset( $c['thumbnail_url'] ) ? (string) $c['thumbnail_url'] : '';

			if ( $ctype === 'VIDEO' ) {
				// IG hashtag responses often don't provide thumbnail_url for VIDEO.
				// Do not fallback to MP4 as an image URL.
				$cDisplayUrl = $cthumb;
			} else {
				$cDisplayUrl = $curl;
			}
			$cVideoUrl = ( $ctype === 'VIDEO' ) ? $curl : '';

			$out[] = [
				'ig_media_id' => $cid,
				'media_type'  => $ctype,
				'media_url'   => $curl,
				'url'         => $cDisplayUrl,
				'video_url'   => $cVideoUrl,
			];
		}

		return $out === [] ? null : $out;
	}

	/**
	 * @param array|null $children
	 */
	private static function displayUrl( ?array $children, string $mediaType, string $mediaUrl, string $thumbUrl ): string {
		if ( $children !== null && isset( $children[0]['url'] ) ) {
			return (string) $children[0]['url'];
		}

		if ( $mediaType === 'VIDEO' ) {
			// Without thumbnail_url we return empty, so frontend can detect "no image".
			return $thumbUrl;
		}

		return $mediaUrl;
	}

	/**
	 * Normalize IG "timestamp" (ISO8601 or unix timestamp) to UTC MySQL DATETIME.
	 *
	 * @param mixed $value
	 */
	private static function normalizePostedAt( $value ): ?string {
		if ( $value === null || $value === '' ) {
			return null;
		}

		try {
			if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
				$ts = (int) $value;
				if ( $ts <= 0 ) {
					return null;
				}
				$dt = ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( new \DateTimeZone( 'UTC' ) );
				return $dt->format( 'Y-m-d H:i:s' );
			}

			if ( is_string( $value ) ) {
				$dt = new \DateTimeImmutable( $value, new \DateTimeZone( 'UTC' ) );
				return $dt->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			}
		} catch ( \Throwable $e ) {
			return null;
		}

		return null;
	}
}
