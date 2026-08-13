<?php
/**
 * Hooks for CategoryWatch extension
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

use EchoEvent;
use MediaWiki\MediaWikiServices;

class Hook {
	/**
	 * Explain bundling
	 *
	 * @param EchoEvent $event to bundle
	 * @param string &$bundleString to use
	 */
	public static function onEchoGetBundleRules( EchoEvent $event, &$bundleString ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		switch ( $event->getType() ) {
			case 'categorywatch-add':
			case 'categorywatch-remove':
				$bundleString = 'categorywatch';
				break;
		}
	}

	/**
	 * Define the CategoryWatch notifications
	 *
	 * @param array &$notifications assoc array of notification types
	 * @param array &$notificationCategories assoc array describing
	 *        categories
	 * @param array &$icons assoc array of icons we define
	 */
	public static function onBeforeCreateEchoEvent(
		array &$notifications, array &$notificationCategories, array &$icons
	) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$icons['categorywatch']['path'] = 'CategoryWatch/assets/catwatch.svg';

		$notifications['categorywatch-add'] = [
			'bundle' => [
				'web' => true,
				'email' => true,
				'expandable' => true,
			],
			'title-message' => 'categorywatch-add-title',
			'category' => 'categorywatch',
			'group' => 'neutral',
			'user-locators' => [ 'CategoryWatch\\Hook::userLocater' ],
			'user-filters' => [ 'CategoryWatch\\Hook::userFilter' ],
			'presentation-model' => 'CategoryWatch\\EchoEventPresentationModel',
		];

		$notifications['categorywatch-remove'] = [
			'bundle' => [
				'web' => true,
				'email' => true,
				'expandable' => true,
			],
			'title-message' => 'categorywatch-remove-title',
			'category' => 'categorywatch',
			'group' => 'neutral',
			'user-locators' => [ 'CategoryWatch\\Hook::userLocater' ],
			'user-filters' => [ 'CategoryWatch\\Hook::userFilter' ],
			'presentation-model' => 'CategoryWatch\\EchoEventPresentationModel',
		];

		$notificationCategories['categorywatch'] = [
			'priority' => 2,
			'tooltip' => 'echo-pref-tooltip-categorywatch'
		];
	}

	/**
	 * Get users that should be notified for this event.
	 *
	 * @param EchoEvent $event to be looked at
	 * @return array
	 */
	public static function userLocater( EchoEvent $event ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		$notifier = MediaWikiServices::getInstance()->getService( 'CategoryWatch.Notifier' );
		return $notifier->getWatchers( $event->getTitle() );
	}

	/**
	 * Filter out the person performing the action
	 *
	 * @param EchoEvent $event to be looked at
	 * @return array
	 */
	public static function userFilter( EchoEvent $event ) {
		wfDebugLog( 'CategoryWatch', __METHOD__ );
		return [ $event->getAgent() ];
	}
}
