# Events

This package dispatches Laravel events for mutating WebDAV node operations.

Use these events when application code should react to WebDAV-side filesystem changes without replacing the package
nodes.

## Event Namespace

All WebDAV node events live under:

```php
N3XT0R\LaravelWebdavServer\Events\WebDav
```

Concrete event classes:

- `DirectoryCreatedEvent`
- `DirectoryDeletedEvent`
- `FileCreatedEvent`
- `FileUpdatedEvent`
- `FileDeletedEvent`

## Common Event Data

All WebDAV node events extend `NodeEvent`.

Every event provides:

- `string $disk` - the Laravel filesystem disk used for the operation
- `string $path` - the resolved storage path on that disk
- `WebDavPrincipalValueObject $principal` - the authenticated WebDAV principal for the request

File events extend `FileEvent` and additionally provide:

- `int $bytes` - the number of bytes written for the file operation

## Dispatch Timing

The package dispatches these events only after the underlying filesystem mutation succeeds.

If authorization fails, stream reading fails, or the filesystem operation throws before completion, the event is not
dispatched.

## `DirectoryCreatedEvent`

Purpose:

- indicates that a WebDAV directory was created

Dispatched when:

- `AbstractStorageCollection::createDirectory()` successfully creates a child directory through the resolved Laravel
  filesystem

Typical WebDAV operation:

- `MKCOL`

Event data:

- `disk`
- `path`
- `principal`

## `DirectoryDeletedEvent`

Purpose:

- indicates that a WebDAV directory was deleted

Dispatched when:

- `StorageDirectory::delete()` successfully removes the target directory after recursively deleting nested files and
  directories

Typical WebDAV operation:

- `DELETE` for a directory node

Event data:

- `disk`
- `path`
- `principal`

## `FileCreatedEvent`

Purpose:

- indicates that a new WebDAV file was created

Dispatched when:

- `AbstractStorageCollection::createFile()` successfully writes a new child file
- the event is dispatched for string payloads, stream payloads, and `null` payloads that become an empty file

Typical WebDAV operation:

- `PUT` for a new file

Event data:

- `disk`
- `path`
- `principal`
- `bytes`

## `FileUpdatedEvent`

Purpose:

- indicates that an existing WebDAV file was overwritten

Dispatched when:

- `StorageFile::put()` successfully overwrites the current file
- the event is dispatched for both string payloads and stream payloads

Typical WebDAV operation:

- `PUT` for an existing file

Event data:

- `disk`
- `path`
- `principal`
- `bytes`

## `FileDeletedEvent`

Purpose:

- indicates that a WebDAV file was deleted

Dispatched when:

- `StorageFile::delete()` successfully deletes the current file

Typical WebDAV operation:

- `DELETE` for a file node

Event data:

- `disk`
- `path`
- `principal`

## Notes

- These events are Laravel events. Applications can listen to them with normal Laravel event listeners, subscribers,
  or queued listeners.
- The package does not register any listeners for these events.
- The events describe WebDAV-triggered node mutations inside the package runtime. They are not general filesystem
  events for all Laravel storage writes.
