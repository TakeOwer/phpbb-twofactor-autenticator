<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\migrations;

class add_promo extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['tfa_promo']);
	}

	public static function depends_on()
	{
		return array('\salvocortesiano\twofactor\migrations\add_required');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('tfa_version', '1.2.2')),

			// Invitation banner, off until the administrator asks for it
			array('config.add', array('tfa_promo', 0)),
		);
	}
}
