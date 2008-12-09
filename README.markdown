# Kohana PHP Framework, version 3.0 (dev)

This is the current development version of [Kohana](http://kohanaphp.com/).

## Forking, Merging, and Tracking Origin

With git, there is no central repository. In order to keep your local fork in sync with "origin",
you will to set up the main repository as a remote:

    git remote add shadowhand git://github.com/shadowhand/kohana.git

This adds "shadowhand" as a remote repository that can be pulled from. (Only do this once!)

    git checkout -b shadowhand/master

This creates a local branch "shadowhand/master" of the master branch of the "shadowhand" respository.
(Only do this once!)

    git checkout shadowhand/master

This switches to your new "shadowhand/master" branch.

    git pull shadowhand master

This pulls all of the changes in the remote into the local "shadowhand/master" branch.

    git checkout master

This switches back to your local master branch.

    git merge shadowhand/master

This merges all of the changes in the "shadowhand/master" branch into your master branch.

    git push

This pushes all the merged changes into your local fork. At this point, your fork is now in sync
with the origin repository!