## Gitify Watch

Gitify Watch is a MODX plugin to complement the Gitify command line tool. It hooks into various MODX events,
and will automatically extract and commit changes you make. 

The primary purpose of Gitify Watch is to be sure changes made directly on production are immediately pushed to the git
remote, so it is easy to keep a development server up to date. With project-specific development, it could also be a
starting point for building a complete workflow away from the command line. 

## Installation

There are roughly two options for installing Gitify Watch. For both options it is important to install Scheduler from the modmore package provider first. 

### 1. Install via the modmore package provider

Coming soon, Gitify Watch will be available as installable package from modmore.com. 

### 2. Manual installation from github

Clone the repository. Add a config.core.php to the root of the repository, pointing to a MODX core directory.
Open `_bootstrap/index.php` from the browser to set up dependencies. 

## Getting started

Gitify Watch depends on environment configuration that you will need to add to your projects' .gitify directory. It also
hooks into your git repository (e.g. committing and pushing), so it is important it is set up properly for that.

The environment configuration is based on host name (i.e. domainname). With it, your .gitify directory might look like this:



