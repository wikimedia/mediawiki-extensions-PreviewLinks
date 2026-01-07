<?php

namespace MediaWiki\Extension\PreviewLinks\Tests\Unit;

use MediaWiki\Content\TextContent;
use MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor;
use MediaWiki\Language\RawMessage;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use WikiPage;

class DefaultPreviewProcessorTest extends MediaWikiUnitTestCase {

	private WikiPageFactory $wikiPageFactoryMock;
	private DefaultPreviewProcessor $processor;

	protected function setUp(): void {
		$this->wikiPageFactoryMock = $this->createMock( WikiPageFactory::class );
		$this->processor = new DefaultPreviewProcessor( $this->wikiPageFactoryMock );
	}

	/**
	 * @covers MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor::applies
	 */
	public function testAppliesAlwaysTrue() {
		$title = $this->createMock( Title::class );
		$this->assertTrue( $this->processor->applies( $title ) );
	}

	/**
	 * @covers MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor::getPreviewText
	 */
	public function testGetPreviewTextReturnsEmptyIfNotTextContent() {
		$title = $this->createMock( Title::class );
		$wikiPage = $this->createMock( WikiPage::class );
		$this->wikiPageFactoryMock->method( 'newFromTitle' )->willReturn( $wikiPage );
		$wikiPage->method( 'getContent' )->willReturn( null );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$result = $this->processor->getPreviewText( $title, $user );
		$actualResult = new RawMessage( '' );
		$this->assertSame( $actualResult->getKey(), $result->getKey() );
	}

	/**
	 * @covers MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor::getPreviewText
	 */
	public function testGetPreviewTextReturnsMessageIfNoValidLine() {
		$title = $this->createMock( Title::class );
		$wikiPage = $this->createMock( WikiPage::class );
		$this->wikiPageFactoryMock->method( 'newFromTitle' )->willReturn( $wikiPage );
		$content = $this->createMock( TextContent::class );
		$content->method( 'getText' )->willReturn( "{{Template}}\n" );
		$wikiPage->method( 'getContent' )->willReturn( $content );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$result = $this->processor->getPreviewText( $title, $user );
		$this->assertSame( 'previewlinks-empty-preview-label', $result->getKey() );
	}

	/**
	 * @covers MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor::getPreviewText
	 */
	public function testGetPreviewTextReturnsIfOneValidLine() {
		$title = $this->createMock( Title::class );
		$wikiPage = $this->createMock( WikiPage::class );
		$this->wikiPageFactoryMock->method( 'newFromTitle' )->willReturn( $wikiPage );
		$content = $this->createMock( TextContent::class );
		$content->method( 'getText' )->willReturn( "{{Template}}\n==Section==\n" );
		$wikiPage->method( 'getContent' )->willReturn( $content );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$result = $this->processor->getPreviewText( $title, $user );
		$this->assertSame( "==Section==\n...", $result->fetchMessage() );
	}

	/**
	 * @covers MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor::getPreviewText
	 */
	public function testGetPreviewTextReturnsFirstValidLineStrippedAndLimited() {
		$title = $this->createMock( Title::class );
		$wikiPage = $this->createMock( WikiPage::class );
		$this->wikiPageFactoryMock->method( 'newFromTitle' )->willReturn( $wikiPage );
		$content = $this->createMock( TextContent::class );
		$content->method( 'getText' )->willReturn(
			"{{Template}}\nValidLine123 with {{template}} and <b>html</b>\nAnotherLine" );
		$wikiPage->method( 'getContent' )->willReturn( $content );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$result = $this->processor->getPreviewText( $title, $user, 50 );
		if ( !$result instanceof RawMessage ) {
			return;
		}
		$this->assertSame( "ValidLine123 with  and <b>html</b>\nAnotherLine\n...", $result->fetchMessage() );
	}

	/**
	 * @covers MediaWiki\Extension\PreviewLinks\Processor\DefaultPreviewProcessor::getPreviewText
	 */
	public function testGetPreviewTextRespectsLimit() {
		$title = $this->createMock( Title::class );
		$wikiPage = $this->createMock( WikiPage::class );
		$this->wikiPageFactoryMock->method( 'newFromTitle' )->willReturn( $wikiPage );
		$content = $this->createMock( TextContent::class );
		$longText = str_repeat( "A\n", 500 );
		$content->method( 'getText' )->willReturn( $longText . "\nAnotherLine" );
		$wikiPage->method( 'getContent' )->willReturn( $content );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$result = $this->processor->getPreviewText( $title, $user, 100 );
		if ( !$result instanceof RawMessage ) {
			return;
		}
		$this->assertLessThanOrEqual( 104, strlen( $result->fetchMessage() ) );
	}
}
