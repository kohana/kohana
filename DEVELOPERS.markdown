# Distributed Source Control Management

Unlike SVN, git does not used a central repository. This is why git is "distributed" and SVN is
"centralized". Although this makes git an extremely powerful system for collaborators, tracking
changes between various collaborators can quickly become difficult as multiple forks are created.

Please read the following before working with this code:

1. [Dealing with newlines](http://github.com/guides/dealing-with-newlines-in-git)
2. [Submitting changes from your fork](http://github.com/guides/fork-a-project-and-submit-your-modifications)
3. [Using SSH keys with github](http://github.com/guides/how-to-not-have-to-type-your-password-for-every-push)

## Managing Remote Repositories

First, you will need to tell git about the remote repository:

    git remote add shadowhand git://github.com/shadowhand/kohana.git

This adds "shadowhand" as a remote repository that can be pulled from.

    git checkout -b shadowhand/master

This creates a local branch "shadowhand/master" of the master branch of the "shadowhand" repository.

## Merging Changes from Remote Repositories

Now that you have a remote repository, you can pull changes into your local repository:

    git checkout shadowhand/master

This switches to the previously created "shadowhand/master" branch.

    git pull shadowhand master

This pulls all of the changes in the remote into the local "shadowhand/master" branch.

    git checkout master

This switches back to your local master branch.

    git merge shadowhand/master

This merges all of the changes in the "shadowhand/master" branch into your master branch.

    git push

This pushes all the merged changes into your local fork. At this point, your fork is now in sync
with the origin repository!