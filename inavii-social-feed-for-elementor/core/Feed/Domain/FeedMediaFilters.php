<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Feed\Domain;

use Inavii\Instagram\Feed\Domain\Policy\ProFeaturesPolicy;

final class FeedMediaFilters {
	private const ORDER_RECENT     = 'recent';
	private const ORDER_TOP_RECENT = 'top_recent';
	private const ORDER_POPULAR    = 'popular';
	private const ORDER_LIKES      = 'likes';
	private const ORDER_COMMENTS   = 'comments';

	private const DEFAULT_MEDIA_TYPES = [ 'IMAGE', 'VIDEO', 'CAROUSEL_ALBUM' ];

	private string $orderBy;
	private array $typesOfPosts;
	private array $captionInclude;
	private array $captionExclude;
	private array $hashtagInclude;
	private array $hashtagExclude;
	private bool $moderationEnabled;
	private string $moderationMode;
	private array $moderationPostIds;

	private function __construct(
		string $orderBy,
		array $typesOfPosts,
		array $captionInclude,
		array $captionExclude,
		array $hashtagInclude,
		array $hashtagExclude,
		bool $moderationEnabled,
		string $moderationMode,
		array $moderationPostIds
	) {
		$this->orderBy           = $orderBy;
		$this->typesOfPosts      = $typesOfPosts;
		$this->captionInclude    = $captionInclude;
		$this->captionExclude    = $captionExclude;
		$this->hashtagInclude    = $hashtagInclude;
		$this->hashtagExclude    = $hashtagExclude;
		$this->moderationEnabled = $moderationEnabled;
		$this->moderationMode    = $moderationMode;
		$this->moderationPostIds = $moderationPostIds;
	}

	public static function fromArray( array $filters, ?ProFeaturesPolicy $proFeatures = null ): self {
		$proFeatures = $proFeatures ?? new ProFeaturesPolicy();

		$orderBy = self::normalizeOrderBy( $filters['orderBy'] ?? self::ORDER_RECENT );

		$hasTypesKey  = array_key_exists( 'typesOfPosts', $filters );
		$typesOfPosts = self::normalizeTypesOfPosts( $hasTypesKey ? $filters['typesOfPosts'] : null, $hasTypesKey );

		$captionFilter = isset( $filters['captionFilter'] ) && is_array( $filters['captionFilter'] )
			? $filters['captionFilter']
			: [];

		$hashtagFilter = isset( $filters['hashtagFilter'] ) && is_array( $filters['hashtagFilter'] )
			? $filters['hashtagFilter']
			: [];

		$moderationEnabled = self::toBool( $filters['moderationEnabled'] ?? false );
		$moderationMode    = self::normalizeModerationMode( $filters['moderationMode'] ?? 'hide' );
		$moderationPostIds = [];

		if ( isset( $filters['moderationSelection'] ) && is_array( $filters['moderationSelection'] ) ) {
			$moderationSelection = $filters['moderationSelection'];
			if ( array_key_exists( 'selectedState', $moderationSelection ) ) {
				$moderationMode = self::normalizeModerationMode( $moderationSelection['selectedState'] );
			}

			$moderationPostIds = self::normalizeTerms( $moderationSelection['postIds'] ?? [] );
		}

		if ( ! $moderationEnabled ) {
			$moderationPostIds = [];
		}

		if ( ! $proFeatures->canUseCaptionFiltering() ) {
			$captionFilter = [];
		}

		if ( ! $proFeatures->canUseHashtagFiltering() ) {
			$hashtagFilter = [];
		}

		if ( ! $proFeatures->canUseModerationFiltering() ) {
			$moderationEnabled = false;
			$moderationMode    = 'hide';
			$moderationPostIds = [];
		}

		if ( ! $proFeatures->canUseTypesOfPostsFiltering() ) {
			$typesOfPosts = self::DEFAULT_MEDIA_TYPES;
		}

		if ( ! $proFeatures->canUseEngagementSorting() && in_array( $orderBy, [ self::ORDER_LIKES, self::ORDER_COMMENTS ], true ) ) {
			$orderBy = self::ORDER_RECENT;
		}

		return new self(
			$orderBy,
			$typesOfPosts,
			self::normalizeTerms( $captionFilter['include'] ?? [] ),
			self::normalizeTerms( $captionFilter['exclude'] ?? [] ),
			self::normalizeHashtagTerms( $hashtagFilter['include'] ?? [] ),
			self::normalizeHashtagTerms( $hashtagFilter['exclude'] ?? [] ),
			$moderationEnabled,
			$moderationMode,
			$moderationPostIds
		);
	}

	public function toArray(): array {
		return [
			'orderBy'             => $this->orderBy,
			'typesOfPosts'        => $this->typesOfPosts,
			'captionFilter'       => [
				'include' => $this->captionInclude,
				'exclude' => $this->captionExclude,
			],
			'hashtagFilter'       => [
				'include' => $this->hashtagInclude,
				'exclude' => $this->hashtagExclude,
			],
			'moderationEnabled'   => $this->moderationEnabled,
			'moderationMode'      => $this->moderationMode,
			'moderationSelection' => [
				'selectedState' => $this->moderationMode,
				'postIds'       => $this->moderationPostIds,
			],
		];
	}

	public function toQueryArgs(): array {
		return [
			'orderBy'           => $this->orderBy,
			'typesOfPosts'      => $this->typesOfPosts,
			'captionInclude'    => $this->captionInclude,
			'captionExclude'    => $this->captionExclude,
			'hashtagInclude'    => $this->hashtagInclude,
			'hashtagExclude'    => $this->hashtagExclude,
			'moderationEnabled' => $this->moderationEnabled,
			'moderationMode'    => $this->moderationMode,
			'moderationPostIds' => $this->moderationPostIds,
		];
	}

	private static function normalizeOrderBy( $value ): string {
		$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		if ( in_array( $value, [ self::ORDER_RECENT, self::ORDER_TOP_RECENT, self::ORDER_POPULAR, self::ORDER_LIKES, self::ORDER_COMMENTS ], true ) ) {
			return $value;
		}

		return self::ORDER_RECENT;
	}

	private static function normalizeModerationMode( $value ): string {
		$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';

		return $value === 'show' ? 'show' : 'hide';
	}

	private static function normalizeTypesOfPosts( $value, bool $hasTypesKey ): array {
		if ( ! $hasTypesKey ) {
			return self::DEFAULT_MEDIA_TYPES;
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$types = [];
		foreach ( $value as $type ) {
			if ( ! is_scalar( $type ) ) {
				continue;
			}

			$type = strtoupper( trim( (string) $type ) );
			if ( in_array( $type, self::DEFAULT_MEDIA_TYPES, true ) ) {
				$types[] = $type;
			}
		}

		return array_values( array_unique( $types ) );
	}

	private static function normalizeTerms( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$out = [];
		foreach ( $value as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}

			$item = trim( (string) $item );
			if ( $item !== '' ) {
				$out[] = $item;
			}
		}

		return array_values( array_unique( $out ) );
	}

	private static function normalizeHashtagTerms( $value ): array {
		$terms = self::normalizeTerms( $value );

		$out = [];
		foreach ( $terms as $term ) {
			$term = ltrim( strtolower( $term ), '#' );
			$term = trim( $term );

			if ( $term !== '' ) {
				$out[] = $term;
			}
		}

		return array_values( array_unique( $out ) );
	}

	private static function toBool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value !== 0;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array( $value, [ '1', 'true', 'yes', 'on' ], true );
		}

		return false;
	}
}
