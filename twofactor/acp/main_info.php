<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\acp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\salvocortesiano\twofactor\acp\main_module',
			'title'		=> 'ACP_TFA_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_TFA_SETTINGS',
					'auth'	=> 'ext_salvocortesiano/twofactor && acl_a_board',
					'cat'	=> array('ACP_TFA_TITLE'),
				),
			),
		);
	}
}
