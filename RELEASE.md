# Release Workflow

TODO: fill this doc

## Authors

Update the [.mailmap](.mailmap) and [AUTHORS](AUTHORS) files:

    $ git log --use-mailmap | grep ^Author: | cut -f2- -d' ' | sort | uniq > AUTHORS
