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

use salvocortesiano\twofactor\core\manager;
use salvocortesiano\twofactor\core\totp;

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
		global $phpbb_container, $request, $template, $user, $config;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		/** @var manager $tfa */
		$tfa = $phpbb_container->get('salvocortesiano.twofactor.manager');

		$language->add_lang('tfa', 'salvocortesiano/twofactor');

		$this->tpl_name = 'ucp_tfa';
		$this->page_title = $language->lang('UCP_TFA_SETTINGS');

		$user_id = (int) $user->data['user_id'];
		$errors = array();
		$new_backup_codes = array();

		add_form_key('tfa_ucp');

		// The secret being enrolled lives in the session until confirmed, so a
		// half finished setup never becomes an active method.
		$pending = $request->variable('tfa_pending', '', true);

		if ($request->is_set_post('submit') || $request->is_set_post('tfa_action'))
		{
			if (!check_form_key('tfa_ucp'))
			{
				$errors[] = $language->lang('FORM_INVALID');
			}

			$action = $request->variable('tfa_action', '');

			if (empty($errors) && $action === 'enable_totp')
			{
				if (!$tfa->method_allowed(manager::METHOD_TOTP))
				{
					$errors[] = $language->lang('TFA_ERR_METHOD_OFF');
				}
				else
				{
					$code = trim($request->variable('tfa_confirm', '', true));

					if ($pending === '' || totp::base32_decode($pending) === '')
					{
						$errors[] = $language->lang('TFA_ERR_NO_SECRET');
					}
					else if (!totp::verify($pending, $code))
					{
						$errors[] = $language->lang('TFA_ERR_WRONG');
					}
					else
					{
						$tfa->save_method($user_id, manager::METHOD_TOTP, $pending);
						$pending = '';

						if (!$tfa->count_backup_codes($user_id))
						{
							$new_backup_codes = $tfa->generate_backup_codes($user_id);
						}

						$tfa->mark_session_verified($user_id, $user->data['session_id']);
					}
				}
			}

			if (empty($errors) && $action === 'disable_totp')
			{
				$tfa->delete_method($user_id, manager::METHOD_TOTP);
			}

			if (empty($errors) && $action === 'enable_email')
			{
				if (!$tfa->method_allowed(manager::METHOD_EMAIL))
				{
					$errors[] = $language->lang('TFA_ERR_METHOD_OFF');
				}
				else if (empty($user->data['user_email']))
				{
					$errors[] = $language->lang('TFA_ERR_NO_EMAIL');
				}
				else
				{
					$tfa->save_method($user_id, manager::METHOD_EMAIL);

					if (!$tfa->count_backup_codes($user_id))
					{
						$new_backup_codes = $tfa->generate_backup_codes($user_id);
					}

					$tfa->mark_session_verified($user_id, $user->data['session_id']);
				}
			}

			if (empty($errors) && $action === 'disable_email')
			{
				$tfa->delete_method($user_id, manager::METHOD_EMAIL);
			}

			if (empty($errors) && $action === 'new_backup')
			{
				$new_backup_codes = $tfa->generate_backup_codes($user_id);
			}
		}

		$methods = $tfa->user_methods($user_id);
		$allowed = $tfa->allowed_methods();

		// Offer a secret to enrol with when TOTP is available but not set up
		$secret = '';

		if (in_array(manager::METHOD_TOTP, $allowed, true) && !isset($methods[manager::METHOD_TOTP]))
		{
			$secret = ($pending !== '' && totp::base32_decode($pending) !== '') ? $pending : totp::create_secret();
		}

		foreach ($new_backup_codes as $code)
		{
			$template->assign_block_vars('tfa_backup_code', array('CODE' => $code));
		}

		$template->assign_vars(array
		(
			'S_TFA_ERROR'       => (bool) count($errors),
			'TFA_ERROR_MSG'     => implode('<br />', $errors),

			'S_TFA_TOTP_ALLOWED'  => in_array(manager::METHOD_TOTP, $allowed, true),
			'S_TFA_EMAIL_ALLOWED' => in_array(manager::METHOD_EMAIL, $allowed, true),
			'S_TFA_TOTP_ON'       => isset($methods[manager::METHOD_TOTP]),
			'S_TFA_EMAIL_ON'      => isset($methods[manager::METHOD_EMAIL]),
			'S_TFA_ANY_ON'        => (bool) count($methods),
			'S_TFA_NEW_CODES'     => (bool) count($new_backup_codes),

			'TFA_SECRET'        => $secret,
			'TFA_SECRET_HUMAN'  => totp::readable($secret),
			'TFA_QR_URI'        => $secret !== '' ? totp::uri($secret, $user->data['username'], $config['sitename']) : '',
			'TFA_BACKUP_LEFT'   => $tfa->count_backup_codes($user_id),
			'TFA_USER_EMAIL'    => $user->data['user_email'],

			'U_TFA_ACTION'      => $this->u_action,
		));
	}
}
