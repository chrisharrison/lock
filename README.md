# lock

Avoid simultaneous execution of PHP code by first gaining a lock.


[![Build Status](https://travis-ci.org/chrisharrison/lock.svg)](https://travis-ci.org/chrisharrison/lock)
[![Version](https://img.shields.io/packagist/v/chrisharrison/lock.svg)](https://packagist.org/packages/chrisharrison/lock)

## Installation ##

Through Composer, obviously:

```
composer require chrisharrison/lock
```

## Why? ##

If you have code that will potentially cause an unwanted race condition when two parallel processed run it at the same time, you may want to introduce a lock so that only one process can execute that code while others wait.

## Usage ##

First create a `LockGuard`:
```$php
$lockGuard = new LockGuard(
    $maxAttempts,
    $attemptIntervalSeconds,
    $lockDriver,
    $lockInspector
);
```

* `$maxAttempts`: `int` _Maximum number of attempts to gain a lock before it gives up._
* `$attemptIntervalSeconds`: `int` _Number of seconds between attempts to gain a lock_
* `$lockDriver`: `LockDriver` _An instance of a class which deals with persisting the lock to whatever storage mechanism_
* `$lockInspector`: `LockInspector` _An instance of a class which deals with testing validity of a lock_

Once you've create a `LockGuard` you can use it to protect code within a lock:

```$php
$flag = false;

$uniqueProcessId = '<ANY-UNIQUE-STRING>';
$lockUntil = DateTimeImmutable::createFromFormat('U', time()+300); // In 5 mins time

$didExecute = $lockGuard->protect('uniq-process-id', $lockUnitl, function () use (&$flag) {
  $flag = true
});
```

The above will attempt to set `$flag` to `true`.

If there is a lock that hasn't expired and was not created by the same process (identified by `$uniqueProcessId`) then the code will execute and the method will return `true`. Else `false`.
As soon as the code has been successfully executed, the lock will be released. This happens even if the `$lockUntil` time has not been reached. However if the code hasn't completed after the `$lockUntil` time has been reached then the lock will expire and other processes can execute again. This is to mitigate situations where a lock is never released.

### Typical usage ###

```$php
$lockPath = 'lock.json';

$maxAttempts = 5;
$attemptIntervalSeconds = 3;
$lockDriver = new FilesystemLockDriver($lockPath);
$lockInspector = new DefaultLockInspector;

$lockGuard = new LockGuard(
    $maxAttempts,
    $attemptIntervalSeconds,
    $lockDriver,
    $lockInspector
);
```

The `FilesystemLockDriver` persists the lock as JSON to a file using the local filesystem.
You could create other `LockDriver`s that use other methods, such as [FlySystem](https://flysystem.thephpleague.com/docs/) to make use of S3.