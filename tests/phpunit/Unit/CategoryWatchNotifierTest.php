<?php

namespace CategoryWatch\Tests\Unit;

use Category;
use CategoryWatch\CategoryWatchNotifier;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserOptionsLookup;
use MediaWikiUnitTestCase;
use Title;
use User;
use WatchedItemStoreInterface;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use WikiPage;

/**
 * @covers \CategoryWatch\CategoryWatchNotifier
 */
class CategoryWatchNotifierTest extends MediaWikiUnitTestCase {

	/**
	 * @return class-string ReadOnlyMode's FQCN on this MW version: \ReadOnlyMode
	 *         on MW < 1.41, \Wikimedia\Rdbms\ReadOnlyMode on MW >= 1.41
	 */
	private static function readOnlyModeClass(): string {
		return class_exists( \Wikimedia\Rdbms\ReadOnlyMode::class )
			? \Wikimedia\Rdbms\ReadOnlyMode::class
			: \ReadOnlyMode::class;
	}

	private function newNotifier(
		?WatchedItemStoreInterface $watchedItemStore = null,
		?ILoadBalancer $loadBalancer = null,
		?UserOptionsLookup $userOptionsLookup = null,
		?UserFactory $userFactory = null,
		$readOnlyMode = null
	): CategoryWatchNotifier {
		return new CategoryWatchNotifier(
			$watchedItemStore ?? $this->createMock( WatchedItemStoreInterface::class ),
			$loadBalancer ?? $this->createMock( ILoadBalancer::class ),
			$userOptionsLookup ?? $this->createMock( UserOptionsLookup::class ),
			$userFactory ?? $this->createMock( UserFactory::class ),
			$readOnlyMode ?? $this->createMock( self::readOnlyModeClass() )
		);
	}

	public static function provideAutomaticallyWatchingUserTitles(): array {
		return [
			'matches pattern' => [ 'Automatically watched by Foo', 'Foo' ],
			'does not match' => [ 'SomeOtherCategory', false ],
			'empty text' => [ '', false ],
		];
	}

	/**
	 * @dataProvider provideAutomaticallyWatchingUserTitles
	 */
	public function testAutomaticallyWatchingUser( string $titleText, $expected ): void {
		$notifier = $this->newNotifier();
		$title = $this->createMock( Title::class );
		$title->method( 'getText' )->willReturn( $titleText );

		$this->assertSame( $expected, $notifier->automaticallyWatchingUser( $title ) );
	}

	public function testGetWatchersReturnsUsersFromWatchlistTable(): void {
		$target = $this->createConfiguredMock( Title::class, [
			'getNamespace' => NS_CATEGORY,
			'getDBkey' => 'SomeCategory',
			'getText' => 'SomeCategory',
		] );

		$db = $this->createMock( IDatabase::class );
		$db->method( 'selectFieldValues' )->willReturn( [ '7', '9' ] );

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )->with( DB_REPLICA )->willReturn( $db );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromId' )->willReturnCallback(
			fn ( $id ) => $this->createConfiguredMock( User::class, [ 'getId' => (int)$id ] )
		);

		$notifier = $this->newNotifier( null, $loadBalancer, null, $userFactory );

		$watchers = $notifier->getWatchers( $target );

		$this->assertCount( 2, $watchers );
		$this->assertSame( 7, $watchers[0]->getId() );
		$this->assertSame( 9, $watchers[1]->getId() );
	}

	public function testGetWatchersAppendsAutomaticallyWatchingUser(): void {
		$target = $this->createConfiguredMock( Title::class, [
			'getNamespace' => NS_CATEGORY,
			'getDBkey' => 'Automatically watched by Foo',
			'getText' => 'Automatically watched by Foo',
		] );

		$db = $this->createMock( IDatabase::class );
		$db->method( 'selectFieldValues' )->willReturn( [] );

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )->willReturn( $db );

		$fooUser = $this->createMock( User::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromName' )->with( 'Foo' )->willReturn( $fooUser );

		$notifier = $this->newNotifier( null, $loadBalancer, null, $userFactory );

		$watchers = $notifier->getWatchers( $target );

		$this->assertSame( [ $fooUser ], $watchers );
	}

	public function testAddUserBatchForWatchReturnsFalseWhenReadOnly(): void {
		$readOnlyMode = $this->createMock( self::readOnlyModeClass() );
		$readOnlyMode->method( 'isReadOnly' )->willReturn( true );
		$notifier = $this->newNotifier( null, null, null, null, $readOnlyMode );

		$target = $this->createMock( Title::class );

		$this->assertFalse( $notifier->addUserBatchForWatch( [ $this->createMock( User::class ) ], $target ) );
	}

	public function testAddUserBatchForWatchReturnsTrueForEmptyWatcherList(): void {
		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->expects( $this->never() )->method( 'getConnection' );
		$notifier = $this->newNotifier( null, $loadBalancer );

		$target = $this->createMock( Title::class );

		$this->assertTrue( $notifier->addUserBatchForWatch( [], $target ) );
	}

	public function testAddUserBatchForWatchInsertsWatchlistRows(): void {
		$target = $this->createConfiguredMock( Title::class, [
			'getNamespace' => NS_CATEGORY,
			'getDBkey' => 'SomeCategory',
		] );

		$user = $this->createConfiguredMock( User::class, [ 'getId' => 42 ] );

		$db = $this->createMock( IDatabase::class );
		$db->expects( $this->once() )
			->method( 'insert' )
			->with(
				'watchlist',
				[ [
					'wl_user' => 42,
					'wl_namespace' => NS_CATEGORY,
					'wl_title' => 'SomeCategory',
					'wl_notificationtimestamp' => null,
				] ],
				$this->anything(),
				'IGNORE'
			);

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )->with( DB_PRIMARY )->willReturn( $db );

		$notifier = $this->newNotifier( null, $loadBalancer );

		$this->assertTrue( $notifier->addUserBatchForWatch( [ $user ], $target ) );
	}

	public function testNotifyPageAddedSkipsEventWhenNoOneWatchesAndAppliesPageWatchPref(): void {
		$catTitle = $this->createConfiguredMock( Title::class, [
			'getNamespace' => NS_CATEGORY,
			'getDBkey' => 'SomeCategory',
			'getText' => 'SomeCategory',
		] );
		$cat = $this->createConfiguredMock( Category::class, [ 'getTitle' => $catTitle ] );

		$pageTitle = $this->createMock( Title::class );
		$page = $this->createMock( WikiPage::class );
		$page->method( 'getTitle' )->willReturn( $pageTitle );
		$page->method( 'getUser' )->willReturn( 1 );
		$page->method( 'getRevisionRecord' )->willReturn( null );

		$watchedItemStore = $this->createMock( WatchedItemStoreInterface::class );
		$watchedItemStore->method( 'countWatchers' )->willReturn( 0 );

		$db = $this->createMock( IDatabase::class );
		$db->method( 'selectFieldValues' )->willReturn( [] );
		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )->willReturn( $db );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->never() )->method( 'newFromId' )->with( 1 );

		$notifier = $this->newNotifier( $watchedItemStore, $loadBalancer, null, $userFactory );

		// No watchers and no auto-watching user -> EchoEvent::create must not be reached.
		$notifier->notifyPageAdded( $cat, $page );

		$this->addToAssertionCount( 1 );
	}

	public function testNotifyPageRemovedSkipsEventWhenNoOneWatches(): void {
		$catTitle = $this->createMock( Title::class );
		$cat = $this->createConfiguredMock( Category::class, [ 'getTitle' => $catTitle ] );
		$page = $this->createMock( WikiPage::class );

		$watchedItemStore = $this->createMock( WatchedItemStoreInterface::class );
		$watchedItemStore->method( 'countWatchers' )->willReturn( 0 );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->never() )->method( 'newFromId' );

		$notifier = $this->newNotifier( $watchedItemStore, null, null, $userFactory );

		// No watchers -> EchoEvent::create must not be reached.
		$notifier->notifyPageRemoved( $cat, $page );

		$this->addToAssertionCount( 1 );
	}
}
