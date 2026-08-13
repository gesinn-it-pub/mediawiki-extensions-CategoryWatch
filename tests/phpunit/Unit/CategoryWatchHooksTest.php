<?php

namespace CategoryWatch\Tests\Unit;

use Category;
use CategoryWatch\CategoryWatchHooks;
use CategoryWatch\CategoryWatchNotifier;
use MediaWikiUnitTestCase;
use User;
use WikiPage;

/**
 * @covers \CategoryWatch\CategoryWatchHooks
 */
class CategoryWatchHooksTest extends MediaWikiUnitTestCase {

	public function testOnCategoryAfterPageAddedDelegatesToNotifier(): void {
		$cat = $this->createMock( Category::class );
		$page = $this->createMock( WikiPage::class );

		$notifier = $this->createMock( CategoryWatchNotifier::class );
		$notifier->expects( $this->once() )->method( 'notifyPageAdded' )->with( $cat, $page );

		( new CategoryWatchHooks( $notifier ) )->onCategoryAfterPageAdded( $cat, $page );
	}

	public function testOnCategoryAfterPageRemovedDelegatesToNotifier(): void {
		$cat = $this->createMock( Category::class );
		$page = $this->createMock( WikiPage::class );

		$notifier = $this->createMock( CategoryWatchNotifier::class );
		$notifier->expects( $this->once() )->method( 'notifyPageRemoved' )->with( $cat, $page );

		( new CategoryWatchHooks( $notifier ) )->onCategoryAfterPageRemoved( $cat, $page, 42 );
	}

	public function testOnGetPreferencesAddsCategorywatchToggle(): void {
		$notifier = $this->createMock( CategoryWatchNotifier::class );
		$user = $this->createMock( User::class );
		$preferences = [];

		( new CategoryWatchHooks( $notifier ) )->onGetPreferences( $user, $preferences );

		$this->assertArrayHasKey( 'categorywatch-page-watch', $preferences );
		$this->assertSame( 'toggle', $preferences['categorywatch-page-watch']['type'] );
	}
}
