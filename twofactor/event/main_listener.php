<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	/** @var \salvocortesiano\twofactor\core\manager */
	protected $manager;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\controller\helper */
	protected $controller_helper;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var bool */
	protected $done = false;

	public function __construct(\salvocortesiano\twofactor\core\manager $manager, \phpbb\user $user, \phpbb\controller\helper $controller_helper, \phpbb\request\request $request, $root_path, $php_ext, \phpbb\template\template $template, \phpbb\language\language $language, \phpbb\config\config $config)
	{
		$this->manager = $manager;
		$this->user = $user;
		$this->controller_helper = $controller_helper;
		$this->request = $request;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->template = $template;
		$this->language = $language;
		$this->config = $config;
	}

	/**
	 * Is the visitor already on our own setup page?
	 *
	 * Without this check the forced setup would send them there in a loop.
	 */
	protected function is_setup_page()
	{
		$page_name = isset($this->user->page['page_name']) ? $this->user->page['page_name'] : '';

		return strpos($page_name, 'ucp.') === 0 && strpos($this->request->variable('i', ''), 'twofactor') !== false;
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup_after'  => 'check_second_factor',
			'core.page_header_after' => 'assign_promo',
		);
	}

	/**
	 * Invite a user to switch two factor on, from every board page.
	 *
	 * A convenience only: any failure here is swallowed so the page still
	 * renders normally.
	 */
	public function assign_promo()
	{
		if (defined('ADMIN_START') || defined('IN_ADMIN') || defined('IN_INSTALL'))
		{
			return;
		}

		try
		{
			if (empty($this->user->data['is_registered']) || (int) $this->user->data['user_id'] === ANONYMOUS)
			{
				return;
			}

			$state = $this->manager->promo_state((int) $this->user->data['user_id']);

			if ($state === '')
			{
				return;
			}

			// The banner lives on board pages, so its wording comes from the
			// front end language file, which is not loaded by default here.
			$this->language->add_lang('tfa', 'salvocortesiano/twofactor');

			// Built here rather than in the template: the sentence carries the
			// member's name and the board name, which need substitution.
			$message = $this->language->lang(
				($state === 'done') ? 'TFA_PROMO_DONE' : 'TFA_PROMO_TEXT',
				$this->user->data['username'],
				$this->config['sitename']
			);

			$this->template->assign_vars(array(
				'S_TFA_PROMO'      => ($state === 'invite'),
				'S_TFA_PROMO_DONE' => ($state === 'done'),
				'TFA_PROMO_MSG'    => $message,
				'U_TFA_SETUP'      => append_sid($this->root_path . 'ucp.' . $this->php_ext, 'i=-salvocortesiano-twofactor-ucp-main_module&amp;mode=settings'),
			));
		}
		catch (\Exception $e)
		{
		}
		catch (\Throwable $e)
		{
		}
	}

	/**
	 * Send a signed in user to the challenge page until they clear it.
	 *
	 * The check runs once per request and deliberately lets a few things
	 * through: the challenge page itself, logging out, and the installer or
	 * the command line. Without those exits a user could get stuck with no way
	 * back.
	 */
	public function check_second_factor()
	{
		if ($this->done)
		{
			return;
		}

		$this->done = true;

		if (defined('IN_INSTALL') || defined('IN_CRON') || defined('IN_CHECK_BAN') || PHP_SAPI === 'cli')
		{
			return;
		}

		// Guests have nothing to verify
		if (empty($this->user->data['is_registered']) || (int) $this->user->data['user_id'] === ANONYMOUS)
		{
			return;
		}

		$user_id = (int) $this->user->data['user_id'];

		// Nothing set up: either leave them alone, or send them to the setup
		// page when the board makes it compulsory for their groups.
		if (!$this->manager->user_has_tfa($user_id))
		{
			if ($this->manager->needs_setup($user_id) && !$this->is_setup_page())
			{
				redirect(append_sid($this->root_path . 'ucp.' . $this->php_ext, 'i=-salvocortesiano-twofactor-ucp-main_module&mode=settings'));
			}

			return;
		}

		if ($this->manager->session_verified($user_id, $this->user->data['session_id']))
		{
			return;
		}

		// Let the user log out, and never trap the challenge page itself
		$page_name = isset($this->user->page['page_name']) ? $this->user->page['page_name'] : '';
		$mode      = $this->request->variable('mode', '');

		if (strpos($page_name, 'ucp.') === 0 && in_array($mode, array('login', 'logout'), true))
		{
			return;
		}

		if (strpos($this->request->server('REQUEST_URI', ''), 'twofactor/verify') !== false)
		{
			return;
		}

		redirect($this->controller_helper->route('salvocortesiano_twofactor_verify'));
	}
}
