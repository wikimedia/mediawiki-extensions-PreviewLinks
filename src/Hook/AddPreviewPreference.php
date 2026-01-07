<?php

namespace MediaWiki\Extension\PreviewLinks\Hook;

use MediaWiki\Preferences\Hook\GetPreferencesHook;

class AddPreviewPreference implements GetPreferencesHook {

	/**
	 * @inheritDoc
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences['previewlinks-show-preview'] = [
			'type' => 'toggle',
			'label-message' => 'previewlinks-preference-show-preview-label',
			'help-message' => 'previewlinks-preference-show-preview-help',
			'section' => 'rendering/preview'
		];
	}

}
