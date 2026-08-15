<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\controller;

use salvocortesiano\twofactor\core\manager;

class verify
{
	/** @var manager */
	protected $manager;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\controller\helper */
	protected $controller_helper;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	public function __construct(manager $manager, \phpbb\user $user, \phpbb\template\template $template, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\controller\helper $controller_helper, \phpbb\config\config $config, $root_path, $php_ext)
	{
		$this->manager = $manager;
		$this->user = $user;
		$this->template = $template;
		$this->language = $language;
		$this->request = $request;
		$this->controller_helper = $controller_helper;
		$this->config = $config;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * The challenge page.
	 */
	public function handle()
	{
		$this->language->add_lang('tfa', 'salvocortesiano/twofactor');

		$user_id = (int) $this->user->data['user_id'];

		if (empty($this->user->data['is_registered']) || $user_id === ANONYMOUS)
		{
			redirect(append_sid($this->root_path . 'ucp.' . $this->php_ext, 'mode=login'));
		}

		$methods = $this->manager->user_methods($user_id);

		// Nothing to check: send them on their way
		if (empty($methods) || $this->manager->session_verified($user_id, $this->user->data['session_id']))
		{
			$this->manager->mark_session_verified($user_id, $this->user->data['session_id']);

			redirect(append_sid($this->root_path . 'index.' . $this->php_ext));
		}

		$errors = array();
		$sent   = false;

		add_form_key('tfa_verify');

		// Send a fresh code by email on request
		if ($this->request->is_set_post('tfa_send_email') && isset($methods[manager::METHOD_EMAIL]))
		{
			if (!check_form_key('tfa_verify'))
			{
				$errors[] = $this->language->lang('FORM_INVALID');
			}
			else
			{
				$this->send_email_code($user_id);
				$sent = true;
			}
		}

		if ($this->request->is_set_post('tfa_submit'))
		{
			if (!check_form_key('tfa_verify'))
			{
				$errors[] = $this->language->lang('FORM_INVALID');
			}
			else
			{
				$code = trim($this->request->variable('tfa_code', '', true));
				$ok   = false;

				if ($code === '')
				{
					$errors[] = $this->language->lang('TFA_ERR_EMPTY');
				}
				else
				{
					if (isset($methods[manager::METHOD_TOTP]) && $this->manager->verify_totp($user_id, $code))
					{
						$ok = true;
					}
					else if (isset($methods[manager::METHOD_EMAIL]) && $this->manager->verify_email_code($user_id, $code))
					{
						$ok = true;
					}
					else if ($this->manager->verify_backup_code($user_id, $code))
					{
						$ok = true;
					}

					if (!$ok)
					{
						$errors[] = $this->language->lang('TFA_ERR_WRONG');
					}
				}

				if ($ok)
				{
					$this->manager->mark_session_verified($user_id, $this->user->data['session_id']);
					$this->manager->cleanup();

					$redirect = $this->request->variable('redirect', '');
					$redirect = ($redirect !== '') ? append_sid($this->root_path . $redirect) : append_sid($this->root_path . 'index.' . $this->php_ext);

					redirect($redirect);
				}
			}
		}

		$this->template->assign_vars(array(
			'S_TFA_ERROR'      => (bool) count($errors),
			'TFA_ERROR_MSG'    => implode('<br />', $errors),
			'S_TFA_HAS_TOTP'   => isset($methods[manager::METHOD_TOTP]),
			'S_TFA_HAS_EMAIL'  => isset($methods[manager::METHOD_EMAIL]),
			'S_TFA_EMAIL_SENT' => $sent,
			'TFA_BACKUP_LEFT'  => $this->manager->count_backup_codes($user_id),
			'U_TFA_ACTION'     => $this->controller_helper->route('salvocortesiano_twofactor_verify'),
			'U_TFA_LOGOUT'     => append_sid($this->root_path . 'ucp.' . $this->php_ext, 'mode=logout', true, $this->user->session_id),
		));

		return $this->controller_helper->render('tfa_verify_body.html', $this->language->lang('TFA_VERIFY_TITLE'));
	}

	/**
	 * Email a fresh code to the address on the account.
	 */
	protected function send_email_code($user_id)
	{
		$code = $this->manager->create_email_code($user_id);

		if (!class_exists('messenger'))
		{
			include $this->root_path . 'includes/functions_messenger.' . $this->php_ext;
		}

		$messenger = new \messenger(false);
		$messenger->template('@salvocortesiano_twofactor/tfa_code', $this->user->data['user_lang']);
		$messenger->to($this->user->data['user_email'], $this->user->data['username']);
		$messenger->assign_vars(array(
			'USERNAME'  => htmlspecialchars_decode($this->user->data['username']),
			'TFA_CODE'  => $code,
			'TFA_TTL'   => max(1, (int) round($this->config['tfa_code_ttl'] / 60)),
		));
		$messenger->send(NOTIFY_EMAIL);
	}
}
