<?php

namespace CategoryWatch\Tests\Unit;

use CategoryWatch\CategoryWatchNotifier;
use CategoryWatch\Hook;
use EchoEvent;
use MediaWiki\Extension\Notifications\Controller\NotificationController;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use User;

/**
 * @covers \CategoryWatch\Hook
 * @group Database
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

	/**
	 * Runs a real Echo event without an agent (e.g. a page removed from a category by a deletion)
	 * through Echo's own locators and filters, as the notification job does.
	 */
	public function testEventWithoutAgentIsDeliveredToWatchers(): void {
		$watcher = $this->getMutableTestUser()->getUser();
		$notifier = $this->createMock( CategoryWatchNotifier::class );
		$notifier->method( 'getWatchers' )->willReturn( [ $watcher ] );
		$this->setService( 'CategoryWatch.Notifier', $notifier );

		$event = EchoEvent::create( [
			'type' => 'categorywatch-remove',
			'title' => Title::makeTitle( NS_CATEGORY, 'CategoryWatchHookTest' ),
			'extra' => [ 'pageid' => 0, 'revid' => null ],
		] );
		$this->assertInstanceOf( EchoEvent::class, $event );
		$this->assertNull( $event->getAgent() );

		$users = iterator_to_array( NotificationController::getUsersToNotifyForEvent( $event ), false );

		$this->assertCount( 1, $users );
		$this->assertSame( $watcher->getId(), $users[0]->getId() );
	}
}
