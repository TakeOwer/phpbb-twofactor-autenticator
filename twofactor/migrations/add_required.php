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

class add_required extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['tfa_required']);
	}

	public static function depends_on()
	{
		return array('\salvocortesiano\twofactor\migrations\install_tfa');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('tfa_version', '1.1.1')),

			// Off by default: switching it on is a deliberate decision
			array('config.add', array('tfa_required', 0)),

			// Comma separated group ids excused from the requirement
			array('config.add', array('tfa_exempt_groups', '')),
		);
	}
}
