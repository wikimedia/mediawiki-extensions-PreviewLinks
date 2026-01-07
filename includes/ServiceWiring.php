<?php

use MediaWiki\Extension\PreviewLinks\PagePreviewProcessorFactory;
use MediaWiki\MediaWikiServices;

return [
	'PreviewLinks.PreviewProcessorFactory' =>
		static function ( MediaWikiServices $services ): PagePreviewProcessorFactory {
			return new PagePreviewProcessorFactory(
				$services->getObjectFactory()
			);
		}
];
