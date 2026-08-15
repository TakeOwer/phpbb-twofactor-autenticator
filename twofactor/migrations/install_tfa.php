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

class install_tfa extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['tfa_version']);
	}

	public static function depends_on()
	{
		return array();
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'tfa_methods' => array(
					'COLUMNS' => array(
						'method_id'     => array('UINT', null, 'auto_increment'),
						'user_id'       => array('ULINT', 0),
						'method_type'   => array('VCHAR:16', ''),
						'method_secret' => array('VCHAR:64', ''),
						'method_active' => array('BOOL', 1),
						'created_at'    => array('TIMESTAMP', 0),
						'last_used'     => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY' => 'method_id',
					'KEYS' => array(
						'tfa_user' => array('INDEX', 'user_id'),
					),
				),
				$this->table_prefix . 'tfa_backup' => array(
					'COLUMNS' => array(
						'backup_id' => array('UINT', null, 'auto_increment'),
						'user_id'   => array('ULINT', 0),
						'code_hash' => array('VCHAR:64', ''),
						'used_at'   => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY' => 'backup_id',
					'KEYS' => array(
						'tfa_backup_user' => array('INDEX', 'user_id'),
					),
				),
				$this->table_prefix . 'tfa_sessions' => array(
					'COLUMNS' => array(
						'session_id'  => array('CHAR:32', ''),
						'user_id'     => array('ULINT', 0),
						'verified_at' => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY' => 'session_id',
				),
				$this->table_prefix . 'tfa_email' => array(
					'COLUMNS' => array(
						'user_id'    => array('ULINT', 0),
						'code_hash'  => array('VCHAR:64', ''),
						'expires_at' => array('TIMESTAMP', 0),
						'attempts'   => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'user_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'tfa_methods',
				$this->table_prefix . 'tfa_backup',
				$this->table_prefix . 'tfa_sessions',
				$this->table_prefix . 'tfa_email',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('tfa_version', '1.0.0')),

			// Which methods the board offers
			array('config.add', array('tfa_enable_totp', 1)),
			array('config.add', array('tfa_enable_email', 1)),

			// How long an emailed code lives, and how many tries it gets
			array('config.add', array('tfa_code_ttl', 600)),
			array('config.add', array('tfa_max_attempts', 5)),

			// ACP module
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_TFA_TITLE')),
			array('module.add', array('acp', 'ACP_TFA_TITLE', array(
				'module_basename' => '\salvocortesiano\twofactor\acp\main_module',
				'modes'           => array('settings'),
			))),

			// UCP module, under the user's profile tab
			array('module.add', array('ucp', 'UCP_PROFILE', array(
				'module_basename' => '\salvocortesiano\twofactor\ucp\main_module',
				'modes'           => array('settings'),
			))),
		);
	}
}
