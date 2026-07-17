# KeyRights tests

`AccessPolicyTest.php` is a deterministic, dependency-free access-control test and runs in CI.

`InstallerSafetyTest.php` checks the non-destructive uninstall contract without
executing uninstall code against a Bitrix database.

`integration/http_access.php` is an opt-in HTTP test. It uses a non-admin test account and checks:

- `crypt/rights/remove` returns HTTP 403;
- an item cannot be updated through another section ID.

Configure the variables documented at the top of the script and run:

```sh
php tests/integration/http_access.php
```

Use a disposable test user and test item. The script does not perform successful writes.
Set `KEYRIGHTS_TEST_REQUIRED=1` to make missing integration configuration fail
instead of reporting an explicit skip.
