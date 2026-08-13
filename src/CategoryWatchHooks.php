<?php
/**
 * Core MediaWiki hook handlers for CategoryWatch extension
 *
 * Copyright (C) 2017, 2018  NicheWork, LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace CategoryWatch;

use MediaWiki\Page\Hook\CategoryAfterPageAddedHook;
use MediaWiki\Page\Hook\CategoryAfterPageRemovedHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;

class CategoryWatchHooks implements
	CategoryAfterPageAddedHook,
	CategoryAfterPageRemovedHook,
	GetPreferencesHook
{

	public function __construct( private readonly CategoryWatchNotifier $notifier ) {
	}

	/**
	 * Hook for page being added to a category.
	 *
	 * @param \Category $category that page is being added to
	 * @param \WikiPage $wikiPage that is being added
	 */
	public function onCategoryAfterPageAdded( $category, $wikiPage ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$this->notifier->notifyPageAdded( $category, $wikiPage );
	}

	/**
	 * Hook for page being taken out of a category.
	 *
	 * @param \Category $category that page is being removed from
	 * @param \WikiPage $wikiPage that is being removed
	 * @param int $id Page ID that this happened in. (not given pre 1.27ish)
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function onCategoryAfterPageRemoved( $category, $wikiPage, $id ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$this->notifier->notifyPageRemoved( $category, $wikiPage );
	}

	/**
	 * Preferences for catwatch
	 *
	 * @param \User $user User whose preferences are being modified
	 * @param array &$preferences Preferences description array, to be fed to an HTMLForm object
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences['categorywatch-page-watch'] = [
			'type' => 'toggle',
			'label-message' => 'categorywatch-page-watch-pref',
			'section' => 'watchlist/advancedwatchlist'
		];
	}
}
