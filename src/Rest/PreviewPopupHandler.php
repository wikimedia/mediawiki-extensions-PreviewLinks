<?php

namespace MediaWiki\Extension\PreviewLinks\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\PreviewLinks\IPagePreviewProcessor;
use MediaWiki\Extension\PreviewLinks\PagePreviewProcessorFactory;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageProps;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\TitleFactory;
use MWException;
use RepoGroup;
use Wikimedia\ParamValidator\ParamValidator;

class PreviewPopupHandler extends SimpleHandler {

	/**
	 * @param TitleFactory $titleFactory
	 * @param PagePreviewProcessorFactory $pagePreviewProcessorFactory
	 * @param PageProps $pageProps
	 * @param RepoGroup $repoGroup
	 * @param NamespaceInfo $namespaceInfo
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly PagePreviewProcessorFactory $pagePreviewProcessorFactory,
		private readonly PageProps $pageProps,
		private readonly RepoGroup $repoGroup,
		private readonly NamespaceInfo $namespaceInfo,
		private readonly PermissionManager $permissionManager
		) {
	}

	/**
	 * @return Response
	 * @throws MWException
	 */
	public function run() {
		$params = $this->getValidatedParams();
		$pageTitle = $this->titleFactory->newFromText( urldecode( $params['pagetitle'] ) );

		if ( !$pageTitle ) {
			return $this->getResponseFactory()->createJson( [] );
		}

		$user = RequestContext::getMain()->getUser();
		if ( !$this->permissionManager->userCan( 'read', $user, $pageTitle ) ) {
			return $this->getResponseFactory()->createJson( [
				'extract_html' => Message::newFromKey( 'previewlinks-empty-preview-label' )->text()
			] );
		}

		$previewProcessor = $this->pagePreviewProcessorFactory->getProcessorForTitle( $pageTitle );
		if ( !$previewProcessor ) {
			return $this->getResponseFactory()->createHttpError(
				404, [ 'Cannot provide preview for requested page' ] );
		}

		if ( $pageTitle->isSpecialPage() ) {
			return $this->getResponseFactory()->createJson( [] );
		}

		if ( !$this->namespaceInfo->isContent( $pageTitle->getNamespace() ) ) {
			return $this->getResponseFactory()->createJson( [] );
		}

		$previewTextMsg = $previewProcessor->getPreviewText( $pageTitle, $user );
		$data = [
			'title' => $pageTitle->getFullText(),
			'displaytitle' => $this->getDisplayTitle( $pageTitle ),
			'pageid' => $pageTitle->getId(),
			'namespace_id' => $pageTitle->getNamespace(),
			'namespace_text' => $pageTitle->getNsText(),
			'extract' => $previewTextMsg->text(),
			'extract_html' => strip_tags( $previewTextMsg->parse() ),
			'description' => $previewTextMsg->text(),
			'dir' => 'ltr',
		];

		[ $thumbnail, $orig ] = $this->createThumbnail( $previewProcessor, $pageTitle, $user );
		if ( !empty( $thumbnail ) ) {
			return $this->getResponseFactory()->createJson( array_merge( [
				'thumbnail' => $thumbnail,
				'originalimage' => $orig
			], $data ) );
		}

		return $this->getResponseFactory()->createJson( $data );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'pagetitle' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}

	/**
	 * @param IPagePreviewProcessor $previewProcessor
	 * @param Title $pageTitle
	 * @param User $user
	 * @return array
	 */
	private function createThumbnail( $previewProcessor, $pageTitle, $user ) {
		$file = $previewProcessor->getPreviewFile( $pageTitle, $user );
		if ( !$file ) {
			$pageProps = $this->pageProps->getAllProperties( $pageTitle );
			$pageProps = $pageProps[$pageTitle->getArticleID()] ?? [];
			if ( isset( $pageProps['page_image_free'] ) ) {
				$imageTitleText = $pageProps['page_image_free'];
				$imageTitle = $this->titleFactory->newFromText( 'File:' . $imageTitleText );
				$file = $this->repoGroup->findFile( $imageTitle );
				if ( !$file ) {
					return [ null, null ];
				}
			} else {
				return [ null, null ];
			}
		}

		$width = $file->getWidth();
		$height = $file->getHeight();
		$params = [
			'width' => 320,
			'height' => 200
		];
		if ( $height > $width ) {
			$params = [
				'width' => 203,
				'height' => 250
			];
		}

		if ( $width <= $params['width'] && $height <= $params['height'] ) {
			return [ [
				'source' => $file->getUrl(),
				'width' => $params['width'],
				'height' => $params['height']
			], [
				'source' => $file->getUrl(),
				'width' => $params['width'],
				'height' => $params['height']
			] ];
		}

		$transformedFile = $file->transform( $params );
		$url = $transformedFile->getUrl();
		if ( !$url ) {
			return [ null, null ];
		}
		return [ [
			'source' => $url,
			'width' => $params[ 'width' ],
			'height' => $params[ 'height' ]
		], [
			'source' => $file->getUrl(),
			'width' => $width,
			'height' => $height,
		] ];
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	private function getDisplayTitle( $title ) {
		$pageProps = $this->pageProps->getAllProperties( $title );
		$pageProps = $pageProps[$title->getArticleID()] ?? [];
		if ( !isset( $pageProps['displaytitle'] ) ) {
			return $title->getText();
		}
		return $pageProps['displaytitle'];
	}
}
