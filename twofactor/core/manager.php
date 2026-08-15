<?php
/**
 * Two Factor Authentication - phpBB extension
 *
 * @author     Salvo Cortesiano
 * @copyright  (c) 2026-08-11 20:00 CEST Salvo Cortesiano
 * @link       https://netshadows.de/ombra/
 * @license    GNU General Public License, version 2 (GPL-2.0)
 */

namespace salvocortesiano\twofactor\core;

class manager
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var string */
	protected $methods_table;

	/** @var string */
	protected $backup_table;

	/** @var string */
	protected $session_table;

	/** @var string */
	protected $email_table;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var array */
	protected $group_cache = array();

	const METHOD_TOTP  = 'totp';
	const METHOD_EMAIL = 'email';

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\user $user, \phpbb\language\language $language, $methods_table, $backup_table, $session_table, $email_table, $root_path, $php_ext)
	{
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->methods_table = $methods_table;
		$this->backup_table = $backup_table;
		$this->session_table = $session_table;
		$this->email_table = $email_table;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/* ---------------------------------------------------------------- */
	/* Board policy                                                      */
	/* ---------------------------------------------------------------- */

	/**
	 * Methods the administrator has switched on, board wide.
	 *
	 * @return array of method names
	 */
	public function allowed_methods()
	{
		$allowed = array();

		if (!empty($this->config['tfa_enable_totp']))
		{
			$allowed[] = self::METHOD_TOTP;
		}

		if (!empty($this->config['tfa_enable_email']))
		{
			$allowed[] = self::METHOD_EMAIL;
		}

		return $allowed;
	}

	public function method_allowed($method)
	{
		return in_array($method, $this->allowed_methods(), true);
	}

	/**
	 * Is two factor compulsory on this board?
	 */
	public function is_required()
	{
		return !empty($this->config['tfa_required']) && count($this->allowed_methods()) > 0;
	}

	/**
	 * Group ids the administrator has excused from the requirement.
	 *
	 * @return array of int
	 */
	public function exempt_groups()
	{
		$raw = trim((string) $this->config['tfa_exempt_groups']);

		if ($raw === '')
		{
			return array();
		}

		return array_filter(array_map('intval', explode(',', $raw)));
	}

	/**
	 * The groups a user belongs to, pending memberships excluded.
	 */
	public function user_groups($user_id)
	{
		$user_id = (int) $user_id;

		if (isset($this->group_cache[$user_id]))
		{
			return $this->group_cache[$user_id];
		}

		$groups = array();

		$sql = 'SELECT group_id
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_id = ' . $user_id . '
				AND user_pending = 0';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}

		$this->db->sql_freeresult($result);

		$this->group_cache[$user_id] = $groups;

		return $groups;
	}

	/**
	 * Is this user excused from the requirement?
	 *
	 * Being excused only means nobody forces them to set it up. If they turn it
	 * on themselves they are still asked for a code, which is what they chose.
	 */
	public function is_exempt($user_id)
	{
		$exempt = $this->exempt_groups();

		if (empty($exempt))
		{
			return false;
		}

		return (bool) array_intersect($exempt, $this->user_groups($user_id));
	}

	/**
	 * Does this user still have to choose a method?
	 */
	public function needs_setup($user_id)
	{
		return $this->is_required() && !$this->is_exempt($user_id) && !$this->user_has_tfa($user_id);
	}

	/**
	 * Which banner, if any, this user should see on board pages.
	 *
	 * 'invite' asks them to switch it on, 'done' confirms they have. Someone
	 * being forced into setup gets neither: they are taken to the page anyway.
	 *
	 * @return string 'invite', 'done', or an empty string
	 */
	public function promo_state($user_id)
	{
		if (empty($this->config['tfa_promo']) || empty($this->allowed_methods()))
		{
			return '';
		}

		if ($this->user_has_tfa($user_id))
		{
			return 'done';
		}

		return $this->needs_setup($user_id) ? '' : 'invite';
	}

	/* ---------------------------------------------------------------- */
	/* What a given user has set up                                      */
	/* ---------------------------------------------------------------- */

	/**
	 * Active methods for a user, keyed by method name.
	 *
	 * Methods the administrator has since switched off are left out, so nobody
	 * is ever challenged with something the board no longer offers.
	 */
	public function user_methods($user_id)
	{
		$rows = array();

		$sql = 'SELECT method_type, method_secret, last_used
			FROM ' . $this->methods_table . '
			WHERE user_id = ' . (int) $user_id . '
				AND method_active = 1';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($this->method_allowed($row['method_type']))
			{
				$rows[$row['method_type']] = $row;
			}
		}

		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Does this user have to pass a second factor?
	 */
	public function user_has_tfa($user_id)
	{
		return (bool) count($this->user_methods($user_id));
	}

	/**
	 * Store, or replace, one method for a user.
	 */
	public function save_method($user_id, $method, $secret = '')
	{
		$this->delete_method($user_id, $method);

		$data = array(
			'user_id'       => (int) $user_id,
			'method_type'   => (string) $method,
			'method_secret' => (string) $secret,
			'method_active' => 1,
			'created_at'    => time(),
			'last_used'     => 0,
		);

		$this->db->sql_query('INSERT INTO ' . $this->methods_table . ' ' . $this->db->sql_build_array('INSERT', $data));
	}

	public function delete_method($user_id, $method)
	{
		$sql = 'DELETE FROM ' . $this->methods_table . '
			WHERE user_id = ' . (int) $user_id . "
				AND method_type = '" . $this->db->sql_escape($method) . "'";
		$this->db->sql_query($sql);
	}

	/**
	 * Remove every trace of two factor for a user, backup codes included.
	 */
	public function reset_user($user_id)
	{
		$user_id = (int) $user_id;

		foreach (array($this->methods_table, $this->backup_table, $this->email_table) as $table)
		{
			$this->db->sql_query('DELETE FROM ' . $table . ' WHERE user_id = ' . $user_id);
		}

		$this->db->sql_query('DELETE FROM ' . $this->session_table . ' WHERE user_id = ' . $user_id);
	}

	protected function touch_method($user_id, $method)
	{
		$sql = 'UPDATE ' . $this->methods_table . '
			SET last_used = ' . time() . '
			WHERE user_id = ' . (int) $user_id . "
				AND method_type = '" . $this->db->sql_escape($method) . "'";
		$this->db->sql_query($sql);
	}

	/* ---------------------------------------------------------------- */
	/* Verification                                                      */
	/* ---------------------------------------------------------------- */

	/**
	 * Check a TOTP code against the secret stored for this user.
	 */
	public function verify_totp($user_id, $code)
	{
		$methods = $this->user_methods($user_id);

		if (!isset($methods[self::METHOD_TOTP]))
		{
			return false;
		}

		if (!totp::verify($methods[self::METHOD_TOTP]['method_secret'], $code))
		{
			return false;
		}

		$this->touch_method($user_id, self::METHOD_TOTP);

		return true;
	}

	/**
	 * Create an email code, store only its hash, and return the plain code so
	 * the caller can send it.
	 *
	 * @return string
	 */
	public function create_email_code($user_id)
	{
		$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

		$this->db->sql_query('DELETE FROM ' . $this->email_table . ' WHERE user_id = ' . (int) $user_id);

		$data = array(
			'user_id'    => (int) $user_id,
			'code_hash'  => hash('sha256', $code),
			'expires_at' => time() + max(60, (int) $this->config['tfa_code_ttl']),
			'attempts'   => 0,
		);

		$this->db->sql_query('INSERT INTO ' . $this->email_table . ' ' . $this->db->sql_build_array('INSERT', $data));

		return $code;
	}

	/**
	 * Check an emailed code.
	 *
	 * The row is removed on success, and after too many wrong tries, so a code
	 * cannot be guessed by brute force.
	 */
	public function verify_email_code($user_id, $code)
	{
		$code = preg_replace('/\D/', '', (string) $code);

		$sql = 'SELECT code_hash, expires_at, attempts
			FROM ' . $this->email_table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}

		if ((int) $row['expires_at'] < time())
		{
			$this->db->sql_query('DELETE FROM ' . $this->email_table . ' WHERE user_id = ' . (int) $user_id);

			return false;
		}

		if ((int) $row['attempts'] >= max(1, (int) $this->config['tfa_max_attempts']))
		{
			$this->db->sql_query('DELETE FROM ' . $this->email_table . ' WHERE user_id = ' . (int) $user_id);

			return false;
		}

		if (!hash_equals($row['code_hash'], hash('sha256', $code)))
		{
			$this->db->sql_query('UPDATE ' . $this->email_table . '
				SET attempts = attempts + 1
				WHERE user_id = ' . (int) $user_id);

			return false;
		}

		$this->db->sql_query('DELETE FROM ' . $this->email_table . ' WHERE user_id = ' . (int) $user_id);
		$this->touch_method($user_id, self::METHOD_EMAIL);

		return true;
	}

	/* ---------------------------------------------------------------- */
	/* Backup codes                                                      */
	/* ---------------------------------------------------------------- */

	/**
	 * Replace the user's backup codes and return the new ones in clear text.
	 * They are stored hashed, so this is the only moment they can be shown.
	 *
	 * @return array
	 */
	public function generate_backup_codes($user_id, $count = 8)
	{
		$this->db->sql_query('DELETE FROM ' . $this->backup_table . ' WHERE user_id = ' . (int) $user_id);

		$codes = array();
		$rows  = array();

		for ($i = 0; $i < $count; $i++)
		{
			$code = '';

			for ($j = 0; $j < 10; $j++)
			{
				$code .= random_int(0, 9);
			}

			$codes[] = substr($code, 0, 5) . '-' . substr($code, 5, 5);

			$rows[] = array(
				'user_id'   => (int) $user_id,
				'code_hash' => hash('sha256', $code),
				'used_at'   => 0,
			);
		}

		$this->db->sql_multi_insert($this->backup_table, $rows);

		return $codes;
	}

	public function count_backup_codes($user_id)
	{
		$sql = 'SELECT COUNT(backup_id) AS total
			FROM ' . $this->backup_table . '
			WHERE user_id = ' . (int) $user_id . '
				AND used_at = 0';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * Spend a backup code. Each one works exactly once.
	 */
	public function verify_backup_code($user_id, $code)
	{
		$code = preg_replace('/\D/', '', (string) $code);

		if (strlen($code) !== 10)
		{
			return false;
		}

		$sql = 'SELECT backup_id
			FROM ' . $this->backup_table . "
			WHERE user_id = " . (int) $user_id . "
				AND used_at = 0
				AND code_hash = '" . $this->db->sql_escape(hash('sha256', $code)) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}

		$this->db->sql_query('UPDATE ' . $this->backup_table . '
			SET used_at = ' . time() . '
			WHERE backup_id = ' . (int) $row['backup_id']);

		return true;
	}

	/* ---------------------------------------------------------------- */
	/* Session state                                                     */
	/* ---------------------------------------------------------------- */

	/**
	 * Has this browsing session already cleared the second factor?
	 */
	public function session_verified($user_id, $session_id)
	{
		$sql = 'SELECT verified_at
			FROM ' . $this->session_table . "
			WHERE session_id = '" . $this->db->sql_escape($session_id) . "'
				AND user_id = " . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function mark_session_verified($user_id, $session_id)
	{
		if ($this->session_verified($user_id, $session_id))
		{
			return;
		}

		$data = array(
			'session_id'  => (string) $session_id,
			'user_id'     => (int) $user_id,
			'verified_at' => time(),
		);

		$this->db->sql_query('INSERT INTO ' . $this->session_table . ' ' . $this->db->sql_build_array('INSERT', $data));
	}

	/**
	 * Drop rows whose phpBB session no longer exists, plus expired email codes.
	 */
	public function cleanup()
	{
		$this->db->sql_query('DELETE FROM ' . $this->session_table . '
			WHERE session_id NOT IN (SELECT session_id FROM ' . SESSIONS_TABLE . ')');

		$this->db->sql_query('DELETE FROM ' . $this->email_table . '
			WHERE expires_at < ' . time());
	}
}
