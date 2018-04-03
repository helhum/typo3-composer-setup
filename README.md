# helhum/typo3-composer-setup

This is a composer package that uses typo3/cms-composer-installers
to generate entry points for the web directory instead of symlinking them.

This means no symlinks to files are required any more, not even the symlinked
autoload file inside the typo3/cms package

In case `typo3/cms` is required instead of the individual TYPO3 core packages,
this package will also set up symlinks to all required core extensions.
No other core extensions are exposed and detected by TYPO3 except the ones
that are required by composer installed packages (including the root package).

This package requires `typo3/cms-composer-installers` `^1.4`, which requires PHP > 7.0

In case you need the legacy `cli_dispatch.phps` entry point in TYPO3v7/v8 you can
install the [pagemachine/typo3-composer-legacy-cli](https://packagist.org/packages/pagemachine/typo3-composer-legacy-cli) package.

## Installation

`composer require helhum/typo3-composer-setup`
