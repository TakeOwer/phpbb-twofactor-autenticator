<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor;

class ext extends \phpbb\extension\base
{
	/**
	 * Needs phpBB 3.3 and the PHP functions the codes rely on.
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');

		return phpbb_version_compare($config['version'], '3.3.0', '>=')
			&& function_exists('hash_hmac')
			&& function_exists('random_int')
			&& function_exists('hash_equals');
	}
}
