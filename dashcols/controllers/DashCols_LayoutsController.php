<?php namespace Craft;

/**
 * DashCols by Mats Mikkel Rummelhoff
 *
 * @author      Mats Mikkel Rummelhoff <http://mmikkel.no>
 * @package     DashCols
 * @since       Craft 2.3
 * @copyright   Copyright (c) 2015, Mats Mikkel Rummelhoff
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @link        https://github.com/mmikkel/dashcols-craft
 */

class DashCols_LayoutsController extends BaseController
{

	/**
	 * @access public
	 * @return mixed
	 */
	public function actionGetIndex( array $variables = array() )
	{

		// Layout targets
		$variables[ 'listings' ] = array(
			'entries' => Craft::t( 'All entries' ),
			'singles' => Craft::t( 'Singles' ),
		);
		$variables[ 'channels' ] = craft()->dashCols->getChannels();
		$variables[ 'structures' ] = craft()->dashCols->getStructures();
		$variables[ 'categoryGroups' ] = craft()->dashCols->getCategoryGroups();

		// Get tabs
		$variables[ 'tabs' ] = craft()->dashCols->getCpTabs();
		$variables[ 'selectedTab' ] = 'layouts';
	
		// Render
		return $this->renderTemplate( 'dashCols/_layouts', $variables );

	}

	/**
	 * @access public
	 * @return mixed
	 */
	public function actionEditSectionLayout( array $variables = array() )
	{

		if ( ! isset( $variables[ 'sectionHandle' ] ) ) {
			throw new HttpException( 404 );
		}

		$variables[ 'section' ] = craft()->dashCols->getSectionByHandle( $variables[ 'sectionHandle' ] );
		if ( ! $variables[ 'section' ] ) {
			throw new HttpException( 404 );
		}

		$variables[ 'sectionId' ] = $variables[ 'section' ]->id;
		
		// Get layout model
		if ( ! $variables[ 'layout' ] = craft()->dashCols->getLayoutBySectionId( $variables[ 'sectionId' ] ) ) {
			$variables[ 'layout' ] = new DashCols_LayoutModel();
		}

		$variables[ 'crumb' ] = array(
			'label' => Craft::t( $variables[ 'section' ]->name ),
			'url' => UrlHelper::getUrl( 'dashcols/layouts/section/' . $variables[ 'section' ]->handle ),
		);

		return $this->renderEditLayout( $variables );

	}

	/**
	 * @access public
	 * @return mixed
	 */
	public function actionEditCategoryGroupLayout( array $variables = array() )
	{

		if ( ! isset( $variables[ 'categoryGroupHandle' ] ) ) {
			throw new HttpException( 404 );
		}

		$variables[ 'section' ] = craft()->dashCols->getCategoryGroupByHandle( $variables[ 'categoryGroupHandle' ] );
		if ( ! $variables[ 'section' ] ) {
			throw new HttpException( 404 );
		}

		$variables[ 'categoryGroupId' ] = $variables[ 'section' ]->id;
		
		// Get layout model
		if ( ! $variables[ 'layout' ] = craft()->dashCols->getLayoutByCategoryGroupId( $variables[ 'categoryGroupId' ] ) ) {
			$variables[ 'layout' ] = new DashCols_LayoutModel();
		}

		$variables[ 'crumb' ] = array(
			'label' => Craft::t( $variables[ 'section' ]->name ),
			'url' => UrlHelper::getUrl( 'dashcols/layouts/category-group/' . $variables[ 'section' ]->handle ),
		);

		return $this->renderEditLayout( $variables );

	}

	/**
	 * @access public
	 * @return mixed
	 */
	public function actionEditListingLayout( array $variables = array() )
	{

		if ( ! isset( $variables[ 'listingHandle' ] ) || ! in_array( $variables[ 'listingHandle' ], array( 'entries', 'singles' ) ) ) {
			throw new HttpException( 404 );
		}

		$variables[ 'section' ] = craft()->dashCols->getListingByHandle( $variables[ 'listingHandle' ] );
		if ( ! $variables[ 'section' ] ) {
			throw new HttpException( 404 );
		}

		// Get layout model
		if ( ! $variables[ 'layout' ] = craft()->dashCols->getLayoutByListingHandle( $variables[ 'listingHandle' ] ) ) {
			$variables[ 'layout' ] = new DashCols_LayoutModel();
		}

		$variables[ 'crumb' ] = array(
			'label' => Craft::t( $variables[ 'section' ]->name ),
			'url' => UrlHelper::getUrl( 'dashcols/layouts/listing/' . $variables[ 'listingHandle' ] ),
		);

		return $this->renderEditLayout( $variables );

	}

	/**
	 * @access protected
	 * @return mixed
	 */
	protected function renderEditLayout( array $variables = array() )
	{

		// Get tabs & breadcrumbs
		$variables[ 'tabs' ] = craft()->dashCols->getCpTabs();
		$variables[ 'selectedTab' ] = 'layouts';
		$variables[ 'crumbs' ] = array(
			array(
				'label' => Craft::t( 'DashCols' ),
				'url' => UrlHelper::getUrl( 'dashcols' ),
			),
			array(
				'label' => Craft::t( 'Edit Layouts' ),
				'url' => UrlHelper::getUrl( 'dashcols/layouts' ),
			),
		);
		$variables[ 'crumbs' ][] = $variables[ 'crumb' ];
		unset( $variables[ 'crumb' ] );

		// Set selected tab
		$variables[ 'selectedTab' ] = 'layouts';

		// Render
		return $this->renderTemplate( 'dashCols/_layouts/_edit', $variables );

	}

	public function actionSaveLayout()
	{

		$this->requirePostRequest();
		
		$request = craft()->request;

		$layout = new DashCols_LayoutModel();
		$layout->id = ( $layoutId = $request->getPost( 'layoutId' ) ) ? $layoutId : null;
		
		$layout->sectionId = $request->getPost( 'sectionId' );
		$layout->categoryGroupId = $request->getPost( 'categoryGroupId' );
		$layout->listingHandle = $request->getPost( 'listingHandle' );

		if ( $layout->sectionId ) {
			$section = craft()->dashCols->getSectionById( $layout->sectionId );
		} else if ( $layout->categoryGroupId ) {
			$section = craft()->dashCols->getCategoryGroupById( $layout->categoryGroupId );
		} else if ( $layout->listingHandle ) {
			$section = craft()->dashCols->getListingByHandle( $layout->listingHandle );
		} else {
			throw new HttpException( 404 );
		}

		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Asset;

		$layout->setFieldLayout( $fieldLayout );

		if ( craft()->dashCols->saveLayout( $layout ) ) {
			craft()->userSession->setNotice( Craft::t( 'Layout for ' . $section->name . ' saved!' ) );
			$this->redirectToPostedUrl( $layout );
		} else {
			craft()->userSession->setError( Craft::t( 'Something went wrong. Layout not saved.' ) );
		}

		craft()->urlManager->setRouteVariables( array(
			'layout' => $layout,
		) );

	}

}
