<?php

// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Recognize\AppInfo;

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		//internal ADMIN API
		['name' => 'admin#avx', 'url' => '/admin/avx', 'verb' => 'GET'],
		['name' => 'admin#nodejs', 'url' => '/admin/nodejs', 'verb' => 'GET'],
		['name' => 'admin#get_setting', 'url' => '/admin/settings/{setting}', 'verb' => 'GET'],
		['name' => 'admin#set_setting', 'url' => '/admin/settings/{setting}', 'verb' => 'PUT'],
	],
];
