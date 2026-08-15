# Two Factor Authentication — Setup and usage guide

Author: Salvo Cortesiano — https://netshadows.de/ombra/
Copyright (c) 2026-08-11 20:00 CEST — GPL-2.0

*Questa guida è disponibile anche [in italiano](GUIDA.it.md).*

This guide walks through the first configuration, from installing the extension to signing in with
a code. Read the whole of Part 1 before switching anything on: the order matters, and it is what
keeps you from locking yourself out.

---

## Part 1 — Administrator: first configuration

### Step 1. Install the extension

1. Upload the folder so that it sits at `ext/salvocortesiano/twofactor/` inside your board.
2. Go to **ACP → Customise → Manage extensions**.
3. Find *Two Factor Authentication* and click **Enable**.

The extension creates its four tables at this point. If enabling fails, nothing is left behind:
disable it, fix the cause, enable again.

### Step 2. Check that email works — before you rely on it

If you plan to offer the email method, test the board's mail first. Sign out, click *I forgot my
password*, and confirm the message arrives. On free hosting, outgoing mail is often the thing that
quietly does not work.

**If mail does not arrive, leave the email method switched off.** A second factor that cannot
deliver its code is a locked door.

### Step 3. Choose the methods

Go to **ACP → Extensions → Two factor authentication**.

| Setting | What it does | Suggested |
|---|---|---|
| Authentication app | Six digit codes from Google Authenticator and similar apps | On |
| Code by email | A code sent to the account address | On only if Step 2 passed |
| Life of an emailed code | How long a sent code stays valid | 600 seconds |
| Allowed attempts | Wrong tries before the code is thrown away | 5 |

Click **Submit**. Nothing has changed for your users yet: you have only decided what they *may*
use. Nobody is forced into anything.

### Step 4. Set it up on your own account first

Do this before telling anyone else. Follow Part 2 below, on your own account, and complete a full
cycle: enable, sign out, sign back in with a code. That way you learn the flow, and you find out
immediately if something on your board misbehaves.

### Step 5. Save your escape route

Copy the following into your notes, replacing `phpbb_` with your board's table prefix and
`yourusername` with your own username in lower case:

```sql
DELETE m FROM phpbb_tfa_methods m
  JOIN phpbb_users u ON u.user_id = m.user_id
  WHERE u.username_clean = 'yourusername';
```

You run this in phpMyAdmin only in the worst case: nobody can sign in any more. It removes the
second factor from that account and leaves everything else untouched. The same statement is shown
in the extension panel, so you can always find it again.

### Step 6. Optional: make verification compulsory

Do this only after completing Step 4 and confirming everything works on your own account.

In the **Requirement** section of the panel:

1. Tick **Make two step verification compulsory**.
2. Under **Groups excused from the requirement**, tick the groups to leave out. Consider excusing
   technical groups and anyone without a working email address.
3. Press **Submit**.

From then on, anyone who has not set it up is taken to the setup page on their next visit and can
do nothing else until they pick a method. They choose app or email themselves, among the methods
you enabled.

A note: if you remove every available method while the requirement is on, the requirement switches
itself off — nobody is left staring at a page with no options.

### Step 7. Optional: invite your users

If you would rather not force anyone but still want the word out, tick **Invite users to switch it
on** in the methods section. A green banner appears at the top of every page with a link to the
setup page: people who have not set it up see the invitation, and once they turn it on the message changes to a
confirmation. The banner cannot be dismissed and carries the member name and the board name.

The two settings work together: you can leave the invitation on and the requirement off, which is
the gentlest way to spread two step verification.

---

## Part 2 — User: turning it on

Anyone can follow these steps, including you.

### With an authentication app (recommended)

1. Install an app on your phone if you do not have one: Google Authenticator, Microsoft
   Authenticator, Authy, Aegis, 1Password. Any of them works.
2. On the board, go to **User Control Panel → Profile → Two factor authentication**.
3. In the app, choose to add an account and **scan the QR code** shown on the page.
   Cannot scan it? Choose manual entry in the app and type the key printed underneath instead.
4. The app now shows a six digit code. Type it into **Confirmation code** and press **Enable**.
5. Your **backup codes** appear. Copy them somewhere safe now — see the warning below.

The QR code is only for this one step. From here on, the app just shows numbers.

### With email codes

1. Go to **User Control Panel → Profile → Two factor authentication**.
2. Check that the address shown is one you actually read.
3. Press **Enable** in the *Code by email* section.

### Your backup codes

Eight codes, each usable once, shown a single time when you enable your first method. They are
stored hashed, so nobody — not even an administrator — can show them to you again.

Print them, or save them somewhere that is not the forum and not the phone holding your
authenticator app. If you lose them you can generate a new set from the same page, which cancels
the old ones.

---

## Part 3 — Signing in from then on

1. Enter your username and password as usual.
2. The verification screen appears.
3. Type the six digit code from your app.
   Using email instead? Press **Email me a code**, wait for the message, then type the code.
   Lost access to both? Type one of your backup codes in the same box.
4. You are in. The session stays verified until you sign out.

The code changes every thirty seconds. If a code is refused even though it looks right, the usual
cause is the phone clock drifting: enable automatic date and time on the phone and try again.

---

## Part 4 — When something goes wrong

**A user cannot sign in any more.** Go to **ACP → Extensions → Two factor authentication**, type
their username under *Unlock a user*, and press the button. Their second factor is removed and
they can set it up again. Confirm the person is who they claim to be before doing this — it is,
by design, a way past their protection.

**A user is stuck on the setup page.** That is the requirement doing its job: they must pick a
method to go on. If that is impossible for them — no phone, no working email — put their group
among the excused ones, or switch the requirement off.

**You turned the requirement on and now you cannot get past it either.** Add the Administrators
group to the excused ones. If you can no longer reach the ACP, switch the requirement off from
phpMyAdmin:

```sql
UPDATE phpbb_config SET config_value = '0' WHERE config_name = 'tfa_required';
```

**You cannot sign in either, and no other administrator can.** Use the SQL statement from Step 5
in phpMyAdmin.

**The codes are always refused.** Check the phone clock first. If the phone is right, the server
clock may be off; the extension already allows thirty seconds of drift either way.

**Emails do not arrive.** Check the spam folder, then the board's own mail settings. Meanwhile you
can still sign in with a backup code.

In the extension panel, under *If you get locked out*, there are two **Run now** buttons that do
the work for you without opening phpMyAdmin: one removes verification from your own account, the
other switches the requirement off for everyone. The SQL statements are shown beside them, already
filled in with your table prefix and your username, for when the ACP is no longer reachable.

---

## Part 5 — Removing the extension

**ACP → Manage extensions → Disable** stops the second factor at once: everyone signs in with a
password only. The settings survive, so you can enable it again later.

**Delete data** additionally removes the four tables and every stored secret. That cannot be
undone.
