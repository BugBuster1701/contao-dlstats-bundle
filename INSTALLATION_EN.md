# Installation of Contao 4/5 dlstats Bundle

There are two types of installation.

* with the Contao-Manager, for Contao Managed-Editon
* via the command line, for Contao Managed-Editon


## Installation with Contao-Manager

* search for package: `bugbuster/contao-dlstats-bundle`
* install the package
* update the database


## Installation via command line

Installation in a Composer-based Contao 5.2+ Managed-Edition:

* `composer require "bugbuster/contao-dlstats-bundle"`
* `php bin/console contao:migrate`

(for Contao 5.3 use "... contao-dlstats-bundle:^1.4")

(for Contao 4.13 use "... contao-dlstats-bundle:^1.3")
