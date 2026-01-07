<?php

namespace MediaWiki\Extension\PreviewLinks;

use MediaWiki\Registration\ExtensionRegistry;
use Wikimedia\ObjectFactory\ObjectFactory;

class PagePreviewProcessorFactory {

	/**
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		private readonly ObjectFactory $objectFactory ) {
	}

	/**
	 * @param Title $title
	 * @return IPagePreviewProcessor|null
	 */
	public function getProcessorForTitle( $title ): ?IPagePreviewProcessor {
		$processors = ExtensionRegistry::getInstance()->getAttribute(
			'PreviewLinksPreviewProcessors'
		);

		foreach ( $processors as $key => $spec ) {
			$provider = $this->objectFactory->createObject( $spec );
			if ( !( $provider instanceof IPagePreviewProcessor ) ) {
				continue;
			}
			if ( $provider->applies( $title ) ) {
				return $provider;
			}
		}
		return null;
	}

}
