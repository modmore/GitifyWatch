## Gitify Watch

Gitify Watch is a MODX plugin to complement the Gitify command line tool. It hooks into various MODX events,
and will automatically extract and commit changes you make. 

The primary purpose of Gitify Watch is to be sure changes made directly on production are immediately pushed to the git
remote, so it is easy to keep a development server up to date. With project-specific development, it could also be a
starting point for building a complete workflow away from the command line. 

## Installation

Gitify Watch needs a bit of set up. Here's the steps you will need to take.

### 1. Install dependencies

Before installing Gitify Watch, make sure the following are installed:

- Scheduler. Install from modmore.com package provider. Remember to set up the cron job too.
- Gitify. Follow the Gitify installation here: https://github.com/modmore/Gitify/wiki/1.-Installation

### 2. Install the GitifyWatch Package

First, install the package via Package Management. It is available from the modmore.com package provider for free.
This will install the GitifyWatch plugin and a Scheduler task (gitifywatch:extract) which will handle the magic. 

### 3. Point GitifyWatch to your Gitify install

Go to System > System Settings and choose the GitifyWatch namespace in the namespace dropdown.
Point the gitifywatch.gitify_path setting to where you installed Gitify on the server. 
This needs to point to the Gitify **directory**, not the Gitify **file** inside that directory.

### 4. Configure the Gitify environments

You can now edit a resource or an element, and GitifyWatch will see it. It will then schedule a gitifywatch:extract run which, when executed,
will extract stuff using Gitify extract. But you still need to configure the environment in your .gitify file. 

The environments block looks something like this:

```` yaml
environments:
    modmore.com:
        name: Production
        branch: master
        auto_commit_and_push: true
    modmore.dev:
        name: Development
        branch: develop
    defaults:
        auto_commit_and_push: false
        commit_delay: 60
````

Each key is a host (domain), with the exception of `defaults` which is a reserved key for setting default values across all your environments. Each key contains the necessary information as to what Gitify Watch needs to do on that domain.

 * **name**: what to call this environment in the commit message
 * **branch**: the branch this environment is on. This currently doesn't make Gitify Watch switch to that branch, but it is necessary for pushing to remote
 * **auto_commit_and_push**: when enabled, Gitify Watch will automatically commit and push all changes it detects to resources and elements. Should probably make sure this is disabled on your dev site. 
 * **remote**: if your remote is not called `origin`, specify its name here.
 * **commit_delay**: how quickly do you want changes to be committed and pushed? Set to `instant` to have it commit as soon as possible (i.e. next time Scheduler runs), or specify a number in minutes to delay it by that much time. 
 
### 5. Make sure the git repositry is configured properly
Gitify Watch doesn't support pushing to password protected repositories, so make sure a simple `git push origin branch` works without prompting for a password. Also make sure the remote is set properly. 

