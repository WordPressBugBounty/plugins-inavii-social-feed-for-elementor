<?php
declare(strict_types=1);

namespace Inavii\Instagram\Media\Dto;

use Inavii\Instagram\Config\Env;

final class MediaItemDto {

	/**
	 * Convert DB rows (snake_case) into API payload (camelCase).
	 *
	 * @param array $rows
	 * @return array
	 */
	public static function fromDbRows( array $rows, array $childrenLocal = [] ): array {
		if ( $rows === [] ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$out[] = self::mapRow( $row, $childrenLocal );
		}

		return $out;
	}

	private static function mapRow( array $row, array $childrenLocal ): array {
		$parentId = isset( $row['id'] ) ? (int) $row['id'] : 0;
		$fileMap  = $parentId > 0 && isset( $childrenLocal[ $parentId ] ) ? $childrenLocal[ $parentId ] : [];

		$children = self::mapChildren( self::decodeChildren( $row['children_json'] ?? null ), $fileMap );

		$mediaProductType = isset( $row['media_product_type'] ) ? (string) $row['media_product_type'] : '';
		$mediaProductType = $mediaProductType !== '' ? $mediaProductType : '';

		$videoUrl = isset( $row['video_url'] ) ? (string) $row['video_url'] : '';

		$filePath = isset( $row['file_path'] ) ? (string) $row['file_path'] : '';
		$filePath = $filePath !== '' ? $filePath : null;
		$fileUrl  = self::toMediaUrl( $filePath );

		$fileThumbPath = isset( $row['file_thumb_path'] ) ? (string) $row['file_thumb_path'] : '';
		$fileThumbPath = $fileThumbPath !== '' ? $fileThumbPath : null;
		$fileThumbUrl  = self::toMediaUrl( $fileThumbPath );

		$remoteUrl = (string) ( $row['url'] ?? '' );
		$remoteUrl = $remoteUrl !== '' ? $remoteUrl : null;

		$displayUrl = $fileUrl ?? $remoteUrl;
		$thumbUrl   = $fileThumbUrl ?? $fileUrl ?? $remoteUrl;
		$mediaUrl   = self::buildMediaUrl( $thumbUrl, $displayUrl );

		return [
			'id'               => (int) ( $row['id'] ?? 0 ),
			'sourceKey'        => (string) ( $row['source_key'] ?? '' ),
			'mediaId'          => (string) ( $row['ig_media_id'] ?? '' ),

			'mediaType'        => (string) ( $row['media_type'] ?? '' ),
			'mediaProductType' => $mediaProductType,
			'mediaUrl'         => $mediaUrl,
			'permalink'        => (string) ( $row['permalink'] ?? '' ),
			'videoUrl'         => $videoUrl,

			'username'         => (string) ( $row['username'] ?? '' ),
			'date'             => (string) ( $row['posted_at'] ?? '' ),
			'commentsCount'    => (int) ( $row['comments_count'] ?? 0 ),
			'likeCount'        => (int) ( $row['likes_count'] ?? 0 ),
			'caption'          => (string) ( $row['caption'] ?? '' ),

			'children'         => $children,
		];
	}

	/**
	 * @param mixed $value
	 * @return array
	 */
	private static function decodeChildren( $value ): array {
		if ( ! is_string( $value ) || $value === '' ) {
			return [];
		}

		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		return array_values( $decoded );
	}

	private static function mapChildren( array $children, array $fileMap ): array {
		if ( $children === [] ) {
			return [];
		}

		$out = [];
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$childId = isset( $child['ig_media_id'] ) ? (string) $child['ig_media_id'] : '';
			$file    = $childId !== '' && isset( $fileMap[ $childId ] ) ? $fileMap[ $childId ] : [];

			$mediaType = '';
			if ( isset( $child['media_type'] ) ) {
				$mediaType = (string) $child['media_type'];
			} elseif ( isset( $child['mediaType'] ) ) {
				$mediaType = (string) $child['mediaType'];
			}

			$fileUrl = isset( $file['file_path'] ) ? (string) $file['file_path'] : '';
			$fileUrl = $fileUrl !== '' ? self::toMediaUrl( $fileUrl ) : null;

			$url = isset( $child['url'] ) ? (string) $child['url'] : '';
			if ( $url === '' && $mediaType !== 'VIDEO' && isset( $child['media_url'] ) ) {
				$url = (string) $child['media_url'];
			}
			$url = $url !== '' ? $url : null;

			$mediaUrl = $fileUrl ?? $url;
			$mediaUrl = $mediaUrl !== null ? $mediaUrl : '';

			$videoUrl = '';
			if ( isset( $child['video_url'] ) ) {
				$videoUrl = (string) $child['video_url'];
			} elseif ( isset( $child['videoUrl'] ) ) {
				$videoUrl = (string) $child['videoUrl'];
			}

			$permalink = '';
			if ( isset( $child['permalink'] ) ) {
				$permalink = (string) $child['permalink'];
			}

			$out[] = [
				'id'        => $childId !== '' ? $childId : null,
				'mediaType' => $mediaType,
				'mediaUrl'  => self::buildMediaUrl( $mediaUrl, $mediaUrl ),
				'videoUrl'  => $videoUrl,
				'permalink' => $permalink,
			];
		}

		return $out;
	}

	private static function toMediaUrl( ?string $path ): ?string {
		if ( $path === null || $path === '' ) {
			return null;
		}

		if ( strpos( $path, 'http://' ) === 0 || strpos( $path, 'https://' ) === 0 || strpos( $path, '//' ) === 0 ) {
			return $path;
		}

		$base = Env::$uploads_url !== '' ? Env::$uploads_url : Env::$media_url;
		if ( $base === '' ) {
			return $path;
		}

		return rtrim( $base, '/\\' ) . '/' . ltrim( $path, '/\\' );
	}

	/**
	 * @return array{thumbnail:string,large?:string}
	 */
	private static function buildMediaUrl( ?string $thumbnail, ?string $large ): array {
		$thumbnail = $thumbnail ?? '';
		$large  = $large ?? '';

		if ( $thumbnail === '' && $large !== '' ) {
			$thumbnail = $large;
		}

		$out = [
			'thumbnail' => $thumbnail,
		];

		if ( $large !== '' && $large !== $thumbnail ) {
			$out['large'] = $large;
		}

		return $out;
	}
}
