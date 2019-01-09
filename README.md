# GDPR Dump

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

`drush sql-dump-gdpr` will automatically strip all GDPR related data from the sql dump so 
you are fully GDPR compliant YO!

## Configuration

This module can be configured by editing the `gdpr_dumper.settings.yml` [file](https://github.com/robiningelbrecht/gdpr-dumper/blob/master/config/install/gdpr_dumper.settings.yml).

[machbarmacher/gdpr-dump](https://github.com/machbarmacher/gdpr-dump) contains more info about 
the **gdpr-expressions** and **gdpr-replacement** options. 

The provided yml file expects the same structure as explained in the readme above.

Happy GDPR'ing!
