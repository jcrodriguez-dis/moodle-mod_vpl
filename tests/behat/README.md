# VPL Behat tests

The acceptance tests target Moodle 4.5 through 5.3. Run them on each Moodle
version in that range; a passing run on one version does not validate the others.

## Running the tests

The test are running in a specific environment with no automatic launch.

The tags `@mod_vpl_submission_upload`, `@mod_vpl_restricted_editor` and
`@mod_vpl_local_execution_servers` select the upload-form, restricted-editor
and server-configuration workflows. They do not require a running jail server.
Upload scenarios require `@_file_upload` as well as a JavaScript-capable browser.

## VPL editor steps

The VPL steps wait for file transfers and saves to finish before continuing,
rather than sleeping for a fixed number of seconds.

Inline file drops use `containing`; `\n` in the contents represents a newline:

```gherkin
When I drop the file "answer.py" containing "print(42)\n" on "#vpl_tabs" in VPL
And I click on "#vpl_ide_save" in VPL
```

Fixture drops read files from [datafiles](datafiles). Separate multiple fixture
names with `|`:

```gherkin
When I drop the files "hello.c|hello.py" on "#vpl_tabs" in VPL
```

Upload-form tests identify filepickers by field name because replacement
filepickers do not always have labels:

```gherkin
When I upload the VPL fixture "hello.c" to the file field "file0"
```

After submitting the upload form, check its success or error notice and press
`Continue` before checking the submission contents. The notice page does not
redirect automatically.

Restricted-editor tests use WebDriver key presses for permitted typing. The
paste check dispatches a clipboard event and verifies that it is cancelled;
it does not use the operating system clipboard.

## Compatibility and assertions

Use Moodle's shared Behat steps and VPL's own form controls instead of
version-specific core modal markup. After changing files or settings, reopen
the page to check persistence. Deletion scenarios should also verify that
cancelling leaves the data intact.

PHP syntax checks and Gherkin parsing do not replace browser runs. Runtime
validation must take place on the external Moodle test installations.
