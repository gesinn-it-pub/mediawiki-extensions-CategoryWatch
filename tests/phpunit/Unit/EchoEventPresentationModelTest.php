<?php

namespace CategoryWatch\Tests\Unit;

use CategoryWatch\EchoEventPresentationModel;
use EchoEvent;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use RequestContext;

/**
 * @covers \CategoryWatch\EchoEventPresentationModel
 * @group Database
 */
class EchoEventPresentationModelTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( !class_exists( EchoEvent::class ) ) {
			$this->markTestSkipped( 'Echo extension is not loaded' );
		}
	}

	private function newModel( EchoEvent $event ): EchoEventPresentationModel {
		$model = \EchoEventPresentationModel::factory(
			$event,
			RequestContext::getMain()->getLanguage(),
			$this->getTestUser()->getUser()
		);
		$this->assertInstanceOf( EchoEventPresentationModel::class, $model );
		return $model;
	}

	private function newEvent( string $type = 'categorywatch-add' ): EchoEvent {
		$event = $this->createMock( EchoEvent::class );
		$event->method( 'getType' )->willReturn( $type );
		return $event;
	}

	public function testGetIconTypeIsCategorywatch(): void {
		$model = $this->newModel( $this->newEvent() );

		$this->assertSame( 'categorywatch', $model->getIconType() );
	}

	public function testCanRenderIsTrueWhenEventHasTitle(): void {
		$event = $this->newEvent();
		$event->method( 'getTitle' )->willReturn( Title::makeTitle( NS_CATEGORY, 'SomeCategory' ) );
		$model = $this->newModel( $event );

		$this->assertTrue( $model->canRender() );
	}

	public function testCanRenderIsFalseWhenEventHasNoTitle(): void {
		$event = $this->newEvent();
		$event->method( 'getTitle' )->willReturn( false );
		$model = $this->newModel( $event );

		$this->assertFalse( $model->canRender() );
	}

	public function testGetPageTitleReturnsBadtitleWhenPageDoesNotExist(): void {
		$event = $this->newEvent();
		$event->method( 'getExtraParam' )->with( 'pageid' )->willReturn( 0 );
		$model = $this->newModel( $event );

		$this->assertSame( NS_SPECIAL, $model->getPageTitle()->getNamespace() );
	}

	public function testGetPageTitleReturnsPageTitleWhenPageExists(): void {
		$page = $this->getExistingTestPage( 'CategoryWatchTestPage' );
		$event = $this->newEvent();
		$event->method( 'getExtraParam' )->with( 'pageid' )->willReturn( $page->getId() );
		$model = $this->newModel( $event );

		$this->assertTrue( $model->getPageTitle()->equals( $page->getTitle() ) );
	}

	public function testGetPrimaryLinkUsesEventTitle(): void {
		$title = Title::makeTitle( NS_CATEGORY, 'SomeWatchedCategory' );
		$event = $this->newEvent();
		$event->method( 'getTitle' )->willReturn( $title );
		$model = $this->newModel( $event );

		$link = $model->getPrimaryLink();

		$this->assertSame( $title->getFullURL(), $link['url'] );
		$this->assertSame( $title->getPrefixedText(), $link['label'] );
	}

	private function newUnbundledEventWithAgent(): EchoEvent {
		$event = $this->newEvent();
		$event->method( 'getTitle' )->willReturn( Title::makeTitle( NS_CATEGORY, 'SomeWatchedCategory' ) );
		$event->method( 'getAgent' )->willReturn( $this->getTestUser()->getUser() );
		$event->method( 'getBundledEvents' )->willReturn( [] );
		$event->method( 'getExtraParam' )->with( 'pageid' )->willReturn( 0 );
		return $event;
	}

	public function testGetHeaderMessageForUnbundledEvent(): void {
		$model = $this->newModel( $this->newUnbundledEventWithAgent() );

		$this->assertStringContainsString( 'added to', $model->getHeaderMessage()->text() );
	}

	public function testGetCompactHeaderMessageIncludesViewingUserGender(): void {
		$model = $this->newModel( $this->newUnbundledEventWithAgent() );

		$this->assertNotSame( '', $model->getCompactHeaderMessage()->text() );
	}

	public function testGetRevisionEditSummaryForUnbundledEvent(): void {
		$model = $this->newModel( $this->newUnbundledEventWithAgent() );

		$this->assertStringContainsString( 'added to', $model->getRevisionEditSummary()->text() );
	}

	public function testGetBodyMessageForUnbundledEvent(): void {
		$model = $this->newModel( $this->newUnbundledEventWithAgent() );

		$this->assertStringContainsString( 'added', $model->getBodyMessage()->text() );
	}

	public function testGetSecondaryLinksForUnbundledEventIncludesAgentAndPageLink(): void {
		$model = $this->newModel( $this->newUnbundledEventWithAgent() );

		$this->assertCount( 2, $model->getSecondaryLinks() );
	}

	public function testJsonSerializeReturnsExpectedStructure(): void {
		$model = $this->newModel( $this->newUnbundledEventWithAgent() );

		$data = $model->jsonSerialize();

		$this->assertArrayHasKey( 'header', $data );
		$this->assertArrayHasKey( 'compactHeader', $data );
		$this->assertArrayHasKey( 'body', $data );
		$this->assertSame( 'categorywatch', $data['icon'] );
		$this->assertArrayHasKey( 'primary', $data['links'] );
		$this->assertArrayHasKey( 'secondary', $data['links'] );
	}
}
