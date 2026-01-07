<?php

namespace MediaWiki\Extension\PreviewLinks\Hook;

use MediaWiki\Context\RequestContext;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\User\Options\UserOptionsLookup;

class AddPreviewPopupLinks implements BeforePageDisplayHook {

	/**
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		private readonly UserOptionsLookup $userOptionsLookup
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$request = RequestContext::getMain()->getRequest();
		$action = $request->getText( 'veaction', $request->getText( 'action', 'view' ) );
		if ( $action !== 'view' ) {
			return;
		}

		$title = $out->getTitle();
		if ( !$title ) {
			return;
		}

		$user = $out->getUser();
		$userShowPreview = $this->userOptionsLookup->getOption( $user, 'previewlinks-show-preview' );
		if ( !$userShowPreview ) {
			return;
		}

		$out->addModules( [ 'ext.previewLinks' ] );
	}

}
