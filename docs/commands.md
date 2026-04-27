# Artisan Commands

The package registers a set of artisan commands for managing records in the model configured under
`webdav-server.auth.account_model`. All commands are grouped under the `laravel-webdav-server:account` namespace.

Run the root command to see the full list:

```bash
php artisan laravel-webdav-server
```

```
 laravel-webdav-server
  laravel-webdav-server:account:create  Create a WebDAV account record in the configured account model.
  laravel-webdav-server:account:list    List WebDAV accounts from the configured account model.
  laravel-webdav-server:account:show    Show one WebDAV account from the configured account model.
  laravel-webdav-server:account:update  Update an existing WebDAV account in the configured account model.
```

## `account:create`

Create a new WebDAV account in the configured account model. The password is hashed before storage.

```text
php artisan laravel-webdav-server:account:create <username> <secret> [options]
```

| Argument / Option   | Required | Description                                                        |
|---------------------|----------|--------------------------------------------------------------------|
| `username`          | yes      | Username used for HTTP Basic Auth                                  |
| `secret`            | yes      | Plain-text credential; hashed with `Hash::make()` before storage   |
| `--display-name=`   | no       | Principal display name shown to WebDAV clients                     |
| `--user-id=`        | no       | Linked Laravel user identifier                                     |
| `--disabled`        | no       | Create the account in a disabled state (default: enabled)          |

Example - minimal account:

```bash
php artisan laravel-webdav-server:account:create alice s3cr3t
```

```
 INFO  Created WebDAV account 'alice'.

 +--------------+-------+
 | Field        | Value |
 +--------------+-------+
 | username     | alice |
 | enabled      | yes   |
 | user_id      | -     |
 | display_name | alice |
 +--------------+-------+
```

Example - account with display name and linked user:

```bash
php artisan laravel-webdav-server:account:create bob s3cr3t \
    --display-name="Bob Smith" \
    --user-id=42
```

```
 INFO  Created WebDAV account 'bob'.

 +--------------+-----------+
 | Field        | Value     |
 +--------------+-----------+
 | username     | bob       |
 | enabled      | yes       |
 | user_id      | 42        |
 | display_name | Bob Smith |
 +--------------+-----------+
```

Example - disabled account:

```bash
php artisan laravel-webdav-server:account:create service-account s3cr3t --disabled
```

```
 INFO  Created WebDAV account 'service-account'.

 +--------------+-----------------+
 | Field        | Value           |
 +--------------+-----------------+
 | username     | service-account |
 | enabled      | no              |
 | user_id      | -               |
 | display_name | service-account |
 +--------------+-----------------+
```

Error - username already taken:

```bash
php artisan laravel-webdav-server:account:create alice s3cr3t
```

```
 ERROR  A WebDAV account with username 'alice' already exists.
```

## `account:list`

List all accounts from the configured account model, ordered by username.

```text
php artisan laravel-webdav-server:account:list
```

Example - accounts present:

```
 +----------+---------+---------+-----------+
 | Username | Enabled | User ID | Display Name |
 +----------+---------+---------+-----------+
 | alice    | yes     | -       | alice     |
 | bob      | yes     | 42      | Bob Smith |
 +----------+---------+---------+-----------+
```

Example - no accounts found:

```
 WARN  No WebDAV accounts found.
```

## `account:show`

Show the stored fields of one account by username.

```text
php artisan laravel-webdav-server:account:show <username>
```

| Argument   | Required | Description                        |
|------------|----------|------------------------------------|
| `username` | yes      | Username of the account to display |

Example:

```bash
php artisan laravel-webdav-server:account:show bob
```

```
 +--------------------+----------------------------------+
 | Field              | Value                            |
 +--------------------+----------------------------------+
 | model              | App\Models\WebDavAccount         |
 | username           | bob                              |
 | enabled            | yes                              |
 | user_id            | 42                               |
 | display_name       | Bob Smith                        |
 +--------------------+----------------------------------+
```

Error - username not found:

```bash
php artisan laravel-webdav-server:account:show unknown
```

```
 ERROR  No WebDAV account found for username 'unknown'.
```

## `account:update`

Update one or more fields on an existing account. Only the options you pass are applied; omitted options leave the
field unchanged. At least one change option must be provided.

```text
php artisan laravel-webdav-server:account:update <username> [options]
```

| Option                 | Description                                                               |
|------------------------|---------------------------------------------------------------------------|
| `--new-username=`      | Replace the current Basic Auth username                                   |
| `--secret=`            | Replace the stored credential with a newly hashed value                   |
| `--display-name=`      | Replace the stored display name                                           |
| `--clear-display-name` | Set the display name to `null`; mutually exclusive with `--display-name`  |
| `--user-id=`           | Replace the linked Laravel user identifier                                |
| `--clear-user-id`      | Set the linked user identifier to `null`; mutually exclusive with `--user-id` |
| `--enable`             | Mark the account as enabled; mutually exclusive with `--disable`          |
| `--disable`            | Mark the account as disabled; mutually exclusive with `--enable`          |

Example - change credential:

```bash
php artisan laravel-webdav-server:account:update alice --secret=n3w-s3cr3t
```

```
 INFO  Updated WebDAV account 'alice'.

 +--------------+-------+
 | Field        | Value |
 +--------------+-------+
 | username     | alice |
 | enabled      | yes   |
 | user_id      | -     |
 | display_name | alice |
 +--------------+-------+
```

Example - rename and disable:

```bash
php artisan laravel-webdav-server:account:update alice --new-username=alice2 --disable
```

```
 INFO  Updated WebDAV account 'alice2'.

 +--------------+--------+
 | Field        | Value  |
 +--------------+--------+
 | username     | alice2 |
 | enabled      | no     |
 | user_id      | -      |
 | display_name | alice  |
 +--------------+--------+
```

Example - link a user and set a display name:

```bash
php artisan laravel-webdav-server:account:update alice2 --user-id=7 --display-name="Alice Doe"
```

```
 INFO  Updated WebDAV account 'alice2'.

 +--------------+-----------+
 | Field        | Value     |
 +--------------+-----------+
 | username     | alice2    |
 | enabled      | no        |
 | user_id      | 7         |
 | display_name | Alice Doe |
 +--------------+-----------+
```

Example - clear display name and re-enable:

```bash
php artisan laravel-webdav-server:account:update alice2 --clear-display-name --enable
```

```
 INFO  Updated WebDAV account 'alice2'.

 +--------------+--------+
 | Field        | Value  |
 +--------------+--------+
 | username     | alice2 |
 | enabled      | yes    |
 | user_id      | 7      |
 | display_name | -      |
 +--------------+--------+
```

Error - no options passed:

```bash
php artisan laravel-webdav-server:account:update alice2
```

```
 WARN  No changes requested.
```

Error - conflicting options:

```bash
php artisan laravel-webdav-server:account:update alice2 --enable --disable
```

```
 ERROR  Use either --enable or --disable, not both.
```

Error - new username already taken:

```bash
php artisan laravel-webdav-server:account:update alice2 --new-username=bob
```

```
 ERROR  A WebDAV account with username 'bob' already exists.
```

Error - account not found:

```bash
php artisan laravel-webdav-server:account:update unknown --enable
```

```
 ERROR  No WebDAV account found for username 'unknown'.
```

## Optional Columns

Fields shown as `-` in any command output indicate that the corresponding column is either not configured in
`webdav-server.auth` or that the stored value is currently `null`.

The column mapping is configured under `webdav-server.auth`:

| Config key                                | Default         | Command field   |
|-------------------------------------------|-----------------|-----------------|
| `webdav-server.auth.enabled_column`       | `enabled`       | `enabled`       |
| `webdav-server.auth.user_id_column`       | `user_id`       | `user_id`       |
| `webdav-server.auth.display_name_column`  | username column | `display_name`  |

Set any of these to `null` or an empty string in the config to disable that column entirely.
