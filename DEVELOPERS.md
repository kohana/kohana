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

    > git remote add kohana git://github.com/kohana/kohana.git

This tells git about the kohana repository and gives it a name which we can use to refer to it when fetching changes from the repository.

## Developing locally

There are 3 branches in the kohana/kohana repository:

* **master** This branch always points to the latest release tag. In essence it points to the last stable edition of the codebase
* **3.0.x**  This is a release branch for development of the 3.0.x series, i.e. 3.0, 3.0.3, 3.0.8 etc.
* **3.1.x**  This is a release branch for development of the 3.1.x series, i.e. 3.1, 3.1.4, 3.1.14 etc.

To work on a specific release branch you need to check it out then check out the appropriate system branch (the system repo uses 3.0 and 3.1 instead of 3.0.x and 3.1.x).

i.e. to work on 3.0.x you'd do the following

	> git checkout 3.0.x
	Switched to branch '3.0.x'
	
	> cd system
	> git checkout 3.0
	Switched to branch 3.0

**Note:** The last step is necessary due to the way that git submodules work.

It is highly recommended that you run the unit tests while developing to ensure that any changes you make don't break the api.  *See TESTING.md for more info*

### Creating new features

New features or API breaking modifications should be developed in separate branches so as to isolate them until they're both stable and **tests have been written for the feature**.
When a new feature is complete and tested it can be merged into its respective release branch.

If a change you make intentionally breaks the api then please correct the relevant tests.

### Bug fixing 

If you're making a bugfix then before you start create a unit test which reproduces the bug, using the @ticket notation to reference the bug's ticket number.  When you run the tests then this test should fail.

Once you've written the bugfix run the tests again before you commit to make sure that the fix actually works, then commit the fix and the test.

There is no need to create separate branches for bugfixes, creating them in the main release branch is perfectly acceptable.

## Merging Changes from Remote Repositories

Now that you have a remote repository, you can pull changes in the remote "kohana" repository into your local repository:

    > git pull kohana master

**Note:** Before you pull changes you should make sure that any modifications you've made locally have been committed

Sometimes a commit you've made locally will conflict with one made in the "kohana" one.

There are a couple of scenarios where this might happen:

### The conflict is to do with a few unrelated commits and you want to keep changes made in both commits

You'll need to manually modify the files to resolve the conflict, see the "Resolving a merge" section [in the git-scm book](http://book.git-scm.com/3_basic_branching_and_merging.html) for more info

### You've fixed something locally which someone else has already done in the remote repo

The simplest way to fix this is to remove all the changes that you've made locally.

You can do this using 

    > git reset --hard kohana

### You've fixed something locally which someone else has already fixed but you also have separate commits you'd like to keep

If this is the case then you'll want to use a tool called rebase.  First of all we need to get rid of the conflicts created due to the merge:

    > git reset --hard HEAD

Then we find the hash of the offending local commit and run:

    > git rebase -i {commit hash}

i.e.

	> git rebase -i 57d0b28

A text editor will open with a list of commits, delete the line containing the offending commit and then save & close your editor.

Git will remove the commit and you can then pull/merge the remote changes.
