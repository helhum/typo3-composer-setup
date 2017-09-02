# helhum/typo3-composer-setup

This is a composer package that uses typo3/cms-composer-installers
to generate entry points for the web directory instead of symlinking them.

In case `typo3/cms` is required instead of the individual TYPO3 core packages,
this package will also set up symlinks to all required core extensions.

If package `helhum/typo3-console` is installed in your project as well
(which is optional), several TYPO3 Console commands are executed
to every command run, so that PackageStates.php, TYPO3 folder structure
and (if in dev mode) TYPO3 extensions are properly set up.

This package requires `typo3/cms-composer-installers` `^1.4`, which requires PHP > 7.0

## Installation

`composer require helhum/typo3-composer-setup`
