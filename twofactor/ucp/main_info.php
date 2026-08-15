<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\ucp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\salvocortesiano\twofactor\ucp\main_module',
			'title'		=> 'UCP_TFA',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'UCP_TFA_SETTINGS',
					'auth'	=> 'ext_salvocortesiano/twofactor',
					'cat'	=> array('UCP_PROFILE'),
				),
			),
		);
	}
}
