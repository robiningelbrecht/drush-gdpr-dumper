# Drush GDPR Dumper

A drop-in replacement for drush sql-dump that optionally sanitizes DB fields for better GDPR conformity.

It is based on the [machbarmacher/gdpr-dump](https://github.com/machbarmacher/gdpr-dump) library, 
and can in principle dump any database that PDO supports. 

## Drush

If you want to create an sql dump on live servers for local purposes (as a developer, themer, ...), 
you should use following command:

```
drush sql-dump-gdpr > file.sql
```

instead of 

```
drush sql-dump > file.sql
```

`drush sql-dump-gdpr` will automatically strip all GDPR related data from the sql dump to help you 
be GDPR compliant YO!

## Configuration

This module can be configured by navigating to `admin/config/development/gdpr-dumper`.
On this page you can configure the sanitization and anonymization 
of every column of every table in your database.

## Events

The module dispatches one event:
* `GdprDumperEvents::GDPR_REPLACEMENTS`
 
This allows developers to alter the replacements through event subscribers on run-time.
[machbarmacher/gdpr-dump](https://github.com/machbarmacher/gdpr-dump) contains more info about 
the **gdpr-replacement** options. 

The provided yml file expects the same structure as explained in the readme above.

## TODO

* Provide a way to allow to export the structure of a table without the data.

Happy GDPR'ing!
