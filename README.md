# helhum/typo3-composer-setup

This is a composer package that uses typo3/cms-composer-installers
to generate entry points for the web directory instead of symlinking them.

This package requires `typo3/cms-composer-installers` `^1.4`, which requires PHP > 7.0

## Installation

`composer require helhum/typo3-composer-setup`

Please note, that requiring this package alon, will not result in a complete setup.
You need either `helhum/typo3-no-symlink-install` or `helhum/typo3-secure-web` as well.
