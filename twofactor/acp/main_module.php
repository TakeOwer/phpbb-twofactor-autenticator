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

use salvocortesiano\twofactor\core\manager;

class main_module
{
	/** @var string */
	public $u_action;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template, $db, $user, $table_prefix;

		/** @var \phpbb\config\config $config */
		$config = $phpbb_container->get('config');
		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		/** @var manager $tfa */
		$tfa = $phpbb_container->get('salvocortesiano.twofactor.manager');

		$language->add_lang('tfa', 'salvocortesiano/twofactor');
		$language->add_lang('acp_tfa', 'salvocortesiano/twofactor');

		$this->tpl_name = 'acp_tfa_settings';
		$this->page_title = $language->lang('ACP_TFA_SETTINGS');

		$errors = array();

		add_form_key('tfa_acp');

		// Emergency actions, run straight from this page so nobody has to open
		// phpMyAdmin and paste a statement by hand.
		$action = $request->variable('action', '');

		if (in_array($action, array('reset_self', 'drop_required'), true))
		{
			if (!check_link_hash($request->variable('hash', ''), 'tfa_emergency'))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$question = ($action === 'reset_self')
				? $language->lang('TFA_CONFIRM_RESET_SELF', $user->data['username'])
				: $language->lang('TFA_CONFIRM_DROP_REQUIRED');

			if (confirm_box(true))
			{
				if ($action === 'reset_self')
				{
					$tfa->reset_user((int) $user->data['user_id']);
					$message = $language->lang('TFA_DONE_RESET_SELF', $user->data['username']);
				}
				else
				{
					$config->set('tfa_required', 0);
					$message = $language->lang('TFA_DONE_DROP_REQUIRED');
				}

				if ($request->is_ajax())
				{
					$json = new \phpbb\json_response();
					$json->send(array(
						'MESSAGE_TITLE' => $language->lang('INFORMATION'),
						'MESSAGE_TEXT'  => $message,
					));
				}

				trigger_error($message . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $question, build_hidden_fields(array(
					'action' => $action,
					'hash'   => generate_link_hash('tfa_emergency'),
				)));
			}
		}

		if ($request->is_set_post('submit') || $request->is_set_post('tfa_reset_user'))
		{
			if (!check_form_key('tfa_acp'))
			{
				$errors[] = $language->lang('FORM_INVALID');
			}

			// Rescue an account that can no longer pass its second factor
			if (empty($errors) && $request->is_set_post('tfa_reset_user'))
			{
				$username = trim($request->variable('tfa_username', '', true));

				if ($username === '')
				{
					$errors[] = $language->lang('TFA_ERR_NO_USERNAME');
				}
				else
				{
					$sql = 'SELECT user_id, username
						FROM ' . USERS_TABLE . "
						WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
					$result = $db->sql_query($sql);
					$row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);

					if (!$row)
					{
						$errors[] = $language->lang('TFA_ERR_NO_SUCH_USER', $username);
					}
					else
					{
						$tfa->reset_user($row['user_id']);

						trigger_error($language->lang('TFA_USER_RESET_DONE', $row['username']) . adm_back_link($this->u_action));
					}
				}
			}

			if (empty($errors) && $request->is_set_post('submit'))
			{
				// Requirement and the groups excused from it
				$config->set('tfa_required', (int) $request->variable('tfa_required', 0));

				$exempt = array_filter(array_map('intval', $request->variable('tfa_exempt_groups', array(0))));
				$config->set('tfa_exempt_groups', implode(',', array_unique($exempt)));

				$config->set('tfa_enable_totp', (int) $request->variable('tfa_enable_totp', 0));
				$config->set('tfa_enable_email', (int) $request->variable('tfa_enable_email', 0));
				$config->set('tfa_promo', (int) $request->variable('tfa_promo', 0));
				$config->set('tfa_code_ttl', max(60, min(3600, (int) $request->variable('tfa_code_ttl', 600))));
				$config->set('tfa_max_attempts', max(1, min(20, (int) $request->variable('tfa_max_attempts', 5))));

				trigger_error($language->lang('TFA_SETTINGS_SAVED') . adm_back_link($this->u_action));
			}
		}

		// How many accounts are protected, per method
		$counts = array(manager::METHOD_TOTP => 0, manager::METHOD_EMAIL => 0);

		$sql = 'SELECT method_type, COUNT(method_id) AS total
			FROM ' . $phpbb_container->getParameter('salvocortesiano.twofactor.table.methods') . '
			WHERE method_active = 1
			GROUP BY method_type';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$counts[$row['method_type']] = (int) $row['total'];
		}

		$db->sql_freeresult($result);

		// Every group, so the administrator can excuse some of them
		$exempt = $tfa->exempt_groups();

		$sql = 'SELECT group_id, group_name, group_type
			FROM ' . GROUPS_TABLE . '
			ORDER BY group_type DESC, group_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$name = ($row['group_type'] == GROUP_SPECIAL)
				? $language->lang('G_' . $row['group_name'])
				: $row['group_name'];

			$template->assign_block_vars('tfa_group', array(
				'ID'       => (int) $row['group_id'],
				'NAME'     => $name,
				'S_SPECIAL' => ($row['group_type'] == GROUP_SPECIAL),
				'S_EXEMPT' => in_array((int) $row['group_id'], $exempt, true),
			));
		}

		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'TFA_TABLE_PREFIX' => $table_prefix,
			'TFA_ADMIN_NAME'   => $user->data['username'],
			'TFA_ADMIN_CLEAN'  => utf8_clean_string($user->data['username']),
			'U_TFA_RESET_SELF' => $this->u_action . '&amp;action=reset_self&amp;hash=' . generate_link_hash('tfa_emergency'),
			'U_TFA_DROP_REQ'   => $this->u_action . '&amp;action=drop_required&amp;hash=' . generate_link_hash('tfa_emergency'),

			'S_ERROR'          => (bool) count($errors),
			'ERROR_MSG'        => implode('<br />', $errors),
			'U_ACTION'         => $this->u_action,

			'TFA_REQUIRED'     => !empty($config['tfa_required']),
			'TFA_ENABLE_TOTP'  => !empty($config['tfa_enable_totp']),
			'TFA_ENABLE_EMAIL' => !empty($config['tfa_enable_email']),
			'TFA_PROMO'        => !empty($config['tfa_promo']),
			'TFA_CODE_TTL'     => (int) $config['tfa_code_ttl'],
			'TFA_MAX_ATTEMPTS' => (int) $config['tfa_max_attempts'],

			'TFA_COUNT_TOTP'   => $counts[manager::METHOD_TOTP],
			'TFA_COUNT_EMAIL'  => $counts[manager::METHOD_EMAIL],
		));
	}
}
