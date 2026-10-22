<?php

namespace MediaWiki\Extension\PreviewLinks\Processor;

use MediaWiki\Content\TextContent;
use MediaWiki\Extension\PreviewLinks\IPagePreviewProcessor;
use MediaWiki\FileRepo\File\File;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Registration\ExtensionRegistry;

class DefaultPreviewProcessor implements IPagePreviewProcessor {

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct(
		private readonly WikiPageFactory $wikiPageFactory
		) {
	}

	/**
	 * @inheritDoc
	 */
	public function applies( $title ): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getPreviewFile( $title, $user ): ?File {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'PageImages' ) ) {
			return null;
		}
		$pageImage = MediaWikiServices::getInstance()
			->getService( 'PageImages.PageImages' )
			->getImage( $title );
		if ( !$pageImage ) {
			return null;
		}
		return $pageImage;
	}

	/**
	 * @inheritDoc
	 */
	public function getPreviewText( $title, $user, $limit = 350 ): Message {
		$wikiPage = $this->wikiPageFactory->newFromTitle( $title );
		$content = $wikiPage->getContent();

		if ( !( $content instanceof TextContent ) ) {
			return new RawMessage( '' );
		}
		$pageText = $content->getText();

		return $this->getPreviewContent( $pageText, $limit );
	}

	/**
	 * @param string $pageText
	 * @param int $limit
	 * @return Message
	 */
	protected function getPreviewContent( $pageText, $limit ) {
		$lines = explode( "\n", $pageText );
		$rawPreview = '';

		foreach ( $lines as $line ) {
			if ( !preg_match( '/^[\wÄÖÜäöüß=\']+/', $line ) ) {
				continue;
			}

			$rawPreview .= $line . "\n";
			if ( strlen( $rawPreview ) >= $limit ) {
				break;
			}
		}

		if ( !$rawPreview ) {
			return Message::newFromKey( 'previewlinks-empty-preview-label' );
		}

		// Strip templates
		$strippedPreview = preg_replace( '/\{\{.*\}\}/', '', $rawPreview );

		return new RawMessage( $strippedPreview . '...' );
	}

}
