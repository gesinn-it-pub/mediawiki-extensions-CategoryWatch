<?php
/**
 * Notification logic for CategoryWatch extension
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

use Category;
use EchoEvent;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserOptionsLookup;
use Title;
use WatchedItemStoreInterface;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\ReadOnlyMode;
use WikiPage;

class CategoryWatchNotifier {

	/**
	 * @param WatchedItemStoreInterface $watchedItemStore to check/read watchers
	 * @param ILoadBalancer $loadBalancer for direct watchlist table access
	 * @param UserOptionsLookup $userOptionsLookup to read user preferences
	 * @param UserFactory $userFactory to construct User objects
	 * @param ReadOnlyMode $readOnlyMode result of MediaWikiServices::getReadOnlyMode()
	 */
	public function __construct(
		private readonly WatchedItemStoreInterface $watchedItemStore,
		private readonly ILoadBalancer $loadBalancer,
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly UserFactory $userFactory,
		private readonly ReadOnlyMode $readOnlyMode
	) {
	}

	/**
	 * Handle a page being added to a category: notify watchers of the
	 * category and let per-user "watch pages in watched categories"
	 * preference add them to the page's watchlist.
	 *
	 * @param Category $cat that page is being added to
	 * @param WikiPage $page that is being added
	 */
	public function notifyPageAdded( Category $cat, WikiPage $page ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );

		$catTitle = $cat->getTitle();

		# Is anyone watching the category?
		$hasWatchers = $this->watchedItemStore->countWatchers( $catTitle ) > 0;
		if ( $this->automaticallyWatchingUser( $catTitle ) || $hasWatchers ) {
			# Send them a notification!
			$user = $this->userFactory->newFromId( $page->getUser() );

			$revRecord = $page->getRevisionRecord();

			EchoEvent::create( [
				'type' => 'categorywatch-add',
				'title' => $catTitle,
				'agent' => $user,
				'extra' => [
					'pageid' => $page->getId(),
					'revid' => $revRecord ? $revRecord->getId() : null,
				],
			] );
		}
		$watchers = [];
		foreach ( $this->getWatchers( $catTitle ) as $watcher ) {
			if ( $this->userOptionsLookup->getOption( $watcher, 'categorywatch-page-watch' ) ) {
				$watchers[] = $watcher;
			}
		}
		$this->addUserBatchForWatch( $watchers, $page->getTitle() );
	}

	/**
	 * Handle a page being removed from a category: notify watchers.
	 *
	 * @param Category $cat that page is being removed from
	 * @param WikiPage $page that is being removed
	 */
	public function notifyPageRemoved( Category $cat, WikiPage $page ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		# Is anyone watching the category?
		if ( $this->watchedItemStore->countWatchers( $cat->getTitle() ) > 0 ) {
			# Send them a notification!
			$user = $this->userFactory->newFromId( $page->getUser() );

			$revId = null;
			$rev = $page->getRevisionRecord();
			if ( $rev ) {
				$revId = $rev->getId();
			}
			EchoEvent::create( [
				'type' => 'categorywatch-remove',
				'title' => $cat->getTitle(),
				'agent' => $user,
				'extra' => [
					'pageid' => $page->getId(),
					'revid' => $revId
				],
			] );
		}
	}

	/**
	 * Find the watchers for a title
	 *
	 * @param Title $target to check
	 *
	 * @return array
	 */
	public function getWatchers( Title $target ) {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$result = $dbr->selectFieldValues(
			'watchlist',
			'wl_user',
			[
				'wl_namespace' => $target->getNamespace(),
				'wl_title' => $target->getDBkey(),
			],
			__METHOD__
		);

		$result = array_map( function ( $userID ) {
			return $this->userFactory->newFromId( $userID );
		}, $result );

		$user = $this->automaticallyWatchingUser( $target );
		if ( $user ) {
			$result[] = $this->userFactory->newFromName( $user );
		}

		return $result;
	}

	/**
	 * Get name of possible automatically watching user which means category is
	 * of the form "Automatically watched by <USERNAME>"
	 *
	 * @param Title $title category title
	 * @return string|false
	 */
	public function automaticallyWatchingUser( Title $title ) {
		return preg_match( "/^Automatically watched by (.*)$/", $title->getText(), $matches )
			? $matches[1]
			: false;
	}

	/**
	 * Mirror of WatchedItemStore::addWatchBatchForUser
	 *
	 * @param array $watchers list of users
	 * @param Title $target title to add them to
	 * @return bool
	 */
	public function addUserBatchForWatch( array $watchers, Title $target ) {
		if ( $this->readOnlyMode->isReadOnly() ) {
			return false;
		}

		if ( !$watchers ) {
			return true;
		}

		$rows = [];
		foreach ( $watchers as $user ) {
			$rows[] = [
				'wl_user' => $user->getId(),
				'wl_namespace' => $target->getNamespace(),
				'wl_title' => $target->getDBkey(),
				'wl_notificationtimestamp' => null,
			];
			// WatchedItemStore instantiates a WatchedItem instance
			// with the HashBagOStuff here, but it shouldn't be needed
			// on small non-farm wikis. See
			// https://gerrit.wikimedia.org/r/#/c/319255/2/includes/WatchedItemStore.php
		}

		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		foreach ( array_chunk( $rows, 100 ) as $toInsert ) {
			// Use INSERT IGNORE to avoid overwriting the notification timestamp
			// if there's already an entry for this page
			$dbw->insert( 'watchlist', $toInsert, __METHOD__, 'IGNORE' );
		}
		// WatchedItemStore instantiates a WatchedItem instance with
		// the HashBagOStuff here, but it shouldn't be needed on small
		// non-farm wikis.
		// https://gerrit.wikimedia.org/r/#/c/319255/2/includes/WatchedItemStore.php

		return true;
	}
}
