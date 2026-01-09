<?php

namespace MediaWiki\Extension\PreviewLinks;

use File;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

interface IPagePreviewProcessor {

	/**
	 * @param Title $title
	 * @return bool
	 */
	public function applies( $title ): bool;

	/**
	 * @param Title $title
	 * @param User $user
	 * @return File|null
	 */
	public function getPreviewFile( $title, $user ): File|null;

	/**
	 * @param Title $title
	 * @param User $user
	 * @param int $limit
	 * @return Message
	 */
	public function getPreviewText( $title, $user, $limit = 250 ): Message;
}
