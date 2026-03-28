<?php
declare(strict_types=1);

namespace Inavii\Instagram\Di;

use Inavii\Instagram\Config\Install;
use Inavii\Instagram\Database\Database;
use Inavii\Instagram\Database\DatabaseStatusStore;
use Inavii\Instagram\Database\Tables\AccountsTable;
use Inavii\Instagram\Database\Tables\FeedFrontCacheTable;
use Inavii\Instagram\Database\Tables\LogsTable;
use Inavii\Instagram\Database\Tables\MediaTable;
use Inavii\Instagram\Database\Tables\MediaChildrenTable;
use Inavii\Instagram\Database\Tables\SourcesTable;
use Inavii\Instagram\Database\Tables\FeedSourcesTable;
use Inavii\Instagram\Front\Application\Contracts\GlobalFeedHooksRuntime;
use Inavii\Instagram\Front\Application\Contracts\GlobalFeedRendererRuntime;
use Inavii\Instagram\Front\Application\NoopGlobalFeedHooks;
use Inavii\Instagram\Front\Application\NoopGlobalFeedRenderer;
use Inavii\Instagram\Logger\Storage\LoggerRepository;
use Inavii\Instagram\Media\Fetcher\AccountPostsFetcher;
use Inavii\Instagram\Media\Fetcher\MediaSourceFetcher;
use Inavii\Instagram\Media\Fetcher\UnsupportedSourceFetcher;
use Inavii\Instagram\Media\Source\Domain\Source;
use Inavii\Instagram\Media\Storage\MediaProFilterSqlBuilder;
use Inavii\Instagram\Media\Storage\NoopMediaProFilterSqlBuilder;
use Psr\Container\ContainerInterface;
use function DI\autowire;
use function DI\factory;

/**
 * Free container module definitions.
 */
final class FreeModule {

	/** @return array<string,mixed> */
	public static function definitions(): array {
		return [
			// --- Database (tables + installer) ---
			DatabaseStatusStore::class       => autowire( DatabaseStatusStore::class ),
			AccountsTable::class             => autowire( AccountsTable::class ),

			'inavii.db.tables'               => factory(
				function ( ContainerInterface $c ) {
					return [
						$c->get( AccountsTable::class ),
						$c->get( MediaTable::class ),
						$c->get( MediaChildrenTable::class ),
						$c->get( SourcesTable::class ),
						$c->get( FeedSourcesTable::class ),
						$c->get( FeedFrontCacheTable::class ),
						$c->get( LogsTable::class ),
					];
				}
			),

			Database::class                  => factory(
				function ( ContainerInterface $c ) {
					/** @var array $tables */
					$tables = $c->get( 'inavii.db.tables' );
					return new Database( $tables, $c->get( DatabaseStatusStore::class ) );
				}
			),

			Install::class                   => autowire( Install::class ),

			LoggerRepository::class          => autowire( LoggerRepository::class ),
			MediaProFilterSqlBuilder::class  => autowire( NoopMediaProFilterSqlBuilder::class ),

			'inavii.media.source_fetchers'   => factory(
				function ( ContainerInterface $c ): array {
					return [
						$c->get( AccountPostsFetcher::class ),
						new UnsupportedSourceFetcher( Source::KIND_TAGGED, 'Tagged posts require the Pro version.' ),
						new UnsupportedSourceFetcher( Source::KIND_HASHTAG, 'Hashtag sources require the Pro version.' ),
					];
				}
			),

			MediaSourceFetcher::class        => factory(
				function ( ContainerInterface $c ): MediaSourceFetcher {
					/** @var array $fetchers */
					$fetchers = $c->get( 'inavii.media.source_fetchers' );

					return new MediaSourceFetcher( $fetchers );
				}
			),

			GlobalFeedHooksRuntime::class    => autowire( NoopGlobalFeedHooks::class ),
			GlobalFeedRendererRuntime::class => autowire( NoopGlobalFeedRenderer::class ),
		];
	}
}
