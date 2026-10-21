Rgpd
====

*[Version française](README_fr.md)*

GDPR toolbox for [Kanboard](https://kanboard.org): a footer and a purge
of inactive user accounts, both configured from the settings pages.

Features
--------

### Footer settings

- Displayed on every page (application, login form and public boards or tasks),
  and only when at least one item is defined.
- Sits at the bottom of the screen when the page is shorter than the viewport,
  and right after the content when the page is longer.
- Two kinds of items, sorted together by weight from 1 (first) to 10 (last):
  - **links**: name, URL and weight,
  - **PDF documents**: name, uploaded file and weight.
- Every entry opens in a new tab and carries its own CSS class derived from its
  name, for instance `link-mentions-legales` for an item named
  "Mentions légales", so that a theme can style each entry individually.

### Purge of inactive users

- Accounts are selected on their last login date. The threshold is either a
  rolling inactivity duration (recomputed at each run) or a fixed date, which
  cannot be in the future.
- Accounts with the `app-admin` role and the account running the purge are
  never removed. Accounts that never logged in are kept unless the matching
  option is enabled.
- Kanboard does not record when an account is created, so the plugin records
  the first time it sees each account and uses that date for the accounts that
  never logged in: such an account is only removed once it has been known for
  longer than the threshold. The accounts that exist when the plugin is
  installed are dated from the installation. This date is never earlier than
  the real creation date: an account can be kept longer than expected, never
  removed too early.
- **Purge now** removes the matching accounts immediately.
- The automatic purge is scheduled by a single date and time, moved forward
  by one period (week, month or year) after each run.
- The purge preview lists the accounts that the button would remove now, and
  those the next scheduled run would remove.
- Every run is recorded: date, manual (with its author) or automatic,
  threshold used and number of removed accounts. Identities are not kept.

Installation
------------

1. Copy the `Rgpd` directory into the `plugins` directory of Kanboard.
2. The database tables are created automatically on the next request.
3. The settings live under *Settings > Footer settings* and *Settings > User purge*.

Scheduling the automatic purge
------------------------------

The settings page holds one date and one time: the next automatic purge. The
system scheduler only provides a heartbeat, by calling a command that checks
whether that date is passed:

```
*/15 * * * * cd /path/to/kanboard && ./cli rgpd:purge >/dev/null 2>&1
```

Each call does one of three things:

- the date is not reached yet: nothing happens,
- the date is passed and no automatic purge ran for it: the accounts are
  purged, the run is logged, and the date moves forward by one period,
- the date is passed but the log already holds a run for it: nothing is
  purged, only the date moves forward.

The command is therefore safe to call as often as you like, and the purge
never runs twice for the same due date. Calling it every 15 minutes keeps the
chosen time of day accurate; a less frequent heartbeat simply delays the purge.

The date and time are read in the timezone of the application, not in the one
of the connected user, so that the web interface and the command line always
agree.

Without this command, the settings page still works but the automatic purge
never runs: only the *Purge now* button removes accounts.

Running the purge from a terminal
---------------------------------

Connect to the server over SSH and go to the root directory of Kanboard, the
one holding the `cli` file next to `index.php`, `app/` and `plugins/`. Depending
on the installation it is usually `/var/www/kanboard` or `/var/www/html`:

```
cd /var/www/kanboard
```

Run the commands as the user the web server runs as, `www-data` on Debian and
Ubuntu, so that the files Kanboard writes keep the right owner:

```
sudo -u www-data ./cli rgpd:purge --dry-run
```

| Command | Effect |
| --- | --- |
| `./cli rgpd:purge --dry-run` | Lists the accounts that would be removed, removes nothing |
| `./cli rgpd:purge --force` | Purges right now, whatever the schedule, which is left untouched |
| `./cli rgpd:purge` | What the scheduler calls: acts only when the scheduled date is passed |

Start with `--dry-run`, it is the only command of the three that changes
nothing:

```
$ sudo -u www-data ./cli rgpd:purge --dry-run
Accounts whose last login is older than 2025-09-17 13:29
  #36 user35 (2024-04-20 10:13)
  #45 user44 (2024-04-22 11:05)
  #40 user39 (2024-05-19 00:41)
3 user account(s) would be removed
```

The `cd` is a convenience: Kanboard resolves its own paths, so an absolute path
works from any directory, which is handy in a crontab.

```
sudo -u www-data php /var/www/kanboard/cli rgpd:purge --dry-run
```

A purge started from a terminal has no connected user, so it is recorded in the
log as an automatic run.

Notes
-----

- The PDF documents are served by the plugin at a public URL, since they must
  be reachable from the login page and from the public boards.
- The bottom-of-the-screen placement relies on the CSS `:has()` selector, so
  that the layout of the pages without a footer is left untouched. On a browser
  without support for it, the footer simply follows the content.
- The login page, public boards and public tasks are rendered without the
  application layout, so the plugin replaces their templates to append the
  footer below the content, as a direct child of the page body. The replacement
  is declared once every plugin has been initialized and renders the template
  that was already in place, so another plugin replacing the same views keeps
  working.

GDPR limitations
----------------

The purge relies on the user removal of Kanboard, which does not erase every
trace of the person:

- removed: the account itself, its login history, its sessions, its avatar,
  its own activity stream and its private projects,
- kept: the comments written by the person, which are detached from the
  account but keep their text, and the tasks the person created, which keep
  their content,
- kept: the name or username of the person wherever it appears in free text,
  such as `@mentions` in comments and descriptions, or in the activity stream
  of other users.

A purge therefore removes the account, it does not anonymize the content the
person produced. If your retention policy requires it, clean that content
separately.

The plugin does not notify the persons concerned before their account is
removed. Informing them in advance, for instance by email a few weeks before
the purge, is recommended; the *Purge preview* page lists the accounts that the
next scheduled run would remove.

Requirements
------------

- Kanboard >= 1.2.20

License
-------

This plugin is distributed under the [MIT License](LICENSE).
