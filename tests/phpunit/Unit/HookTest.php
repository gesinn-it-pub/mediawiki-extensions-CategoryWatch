<?php

namespace CategoryWatch\Tests\Unit;

use CategoryWatch\Hook;
use EchoEvent;
use MediaWikiUnitTestCase;
use User;

/**
 * @covers \CategoryWatch\Hook
 */
class HookTest extends MediaWikiUnitTestCase {

	private function requireEcho(): void {
		if ( !class_exists( EchoEvent::class ) ) {
			$this->markTestSkipped( 'Echo extension is not loaded' );
		}
	}

	public static function provideBundleTypes(): array {
		return [
			'add is bundled' => [ 'categorywatch-add', 'categorywatch' ],
			'remove is bundled' => [ 'categorywatch-remove', 'categorywatch' ],
			'unrelated type is left alone' => [ 'some-other-type', null ],
		];
	}

	/**
	 * @dataProvider provideBundleTypes
	 */
	public function testOnEchoGetBundleRulesSetsBundleStringForOwnTypes( string $type, ?string $expected ): void {
		$this->requireEcho();
		$event = $this->createMock( EchoEvent::class );
		$event->method( 'getType' )->willReturn( $type );

		$bundleString = null;
		Hook::onEchoGetBundleRules( $event, $bundleString );

		$this->assertSame( $expected, $bundleString );
	}

	public function testOnBeforeCreateEchoEventRegistersNotificationTypes(): void {
		$notifications = [];
		$notificationCategories = [];
		$icons = [];

		Hook::onBeforeCreateEchoEvent( $notifications, $notificationCategories, $icons );

		$this->assertArrayHasKey( 'categorywatch-add', $notifications );
		$this->assertArrayHasKey( 'categorywatch-remove', $notifications );
		$this->assertArrayHasKey( 'categorywatch', $notificationCategories );
		$this->assertSame(
			'CategoryWatch/assets/catwatch.svg',
			$icons['categorywatch']['path']
		);
	}

	public function testOnBeforeCreateEchoEventWiresUserLocatorsAndFilters(): void {
		$notifications = [];
		$notificationCategories = [];
		$icons = [];

		Hook::onBeforeCreateEchoEvent( $notifications, $notificationCategories, $icons );

		foreach ( [ 'categorywatch-add', 'categorywatch-remove' ] as $type ) {
			$this->assertSame(
				[ 'CategoryWatch\\Hook::userLocater' ],
				$notifications[$type]['user-locators']
			);
			$this->assertSame(
				[ 'CategoryWatch\\Hook::userFilter' ],
				$notifications[$type]['user-filters']
			);
			$this->assertSame(
				'CategoryWatch\\EchoEventPresentationModel',
				$notifications[$type]['presentation-model']
			);
		}
	}

	public function testUserFilterReturnsEventAgent(): void {
		$this->requireEcho();
		$agent = $this->createMock( User::class );
		$event = $this->createMock( EchoEvent::class );
		$event->method( 'getAgent' )->willReturn( $agent );

		$this->assertSame( [ $agent ], Hook::userFilter( $event ) );
	}
}
