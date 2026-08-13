<?php

namespace CategoryWatch\Tests\Unit;

use CategoryWatch\CategoryWatchNotifier;
use CategoryWatch\Hook;
use EchoEvent;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use User;

/**
 * @covers \CategoryWatch\Hook
 */
class HookIntegrationTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( !class_exists( EchoEvent::class ) ) {
			$this->markTestSkipped( 'Echo extension is not loaded' );
		}
	}

	public function testUserLocaterDelegatesToNotifierService(): void {
		$title = $this->createMock( Title::class );
		$event = $this->createMock( EchoEvent::class );
		$event->method( 'getTitle' )->willReturn( $title );

		$watchers = [ $this->createMock( User::class ) ];
		$notifier = $this->createMock( CategoryWatchNotifier::class );
		$notifier->expects( $this->once() )->method( 'getWatchers' )->with( $title )->willReturn( $watchers );
		$this->setService( 'CategoryWatch.Notifier', $notifier );

		$this->assertSame( $watchers, Hook::userLocater( $event ) );
	}
}
