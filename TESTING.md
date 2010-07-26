Unit Testing Kohana
===

Guidelines for writing unit tests
---

 * Use @covers - This helps provide proper code coverage
 * Use providers when appropriate - This helps keep your tests simple and makes it easy to add new test cases.
 * When a new feature of bug fix is applied, create a test for it. This may only consist of adding a provider.

How to use the tests
---

Simply run `phpunit` from this directory.  PHPUnit will grab the config settings stored in phpunit.xml and run
the tests for kohana.   If everything goes ok phpunit should print a series of dots (each dot represents a test that's passed) 
followed by something along the lines of `OK (520 tests, 1939 assertions)`.

If the result is instead something like `Ok but skipped or incomplete tests` then this just means that some tests were unable to run
on your system or their implementation is not quite finished.

By default code coverage is not calculated, if you want to collect it then you need to run `./phpunitcc` which will
run phpunit with the config in `code_coverage.xml`.  Once the tests have finished running open `code_coverage/index.html` 
in your browser.

Things to Test (TODO)
---

* Need extra tests for Validate to make sure filters(), rules(), callbacks() will convert the field name to a label if a label
  does not exist

Known failing tests
---

NONE

 * If any other tests fail for your system, please [file a bug](http://dev.kohanaframework.org/projects/kohana3/issues/new)
