E-Mail Autoconfigure
====================

Some E-Mail clients gather configuration information before setting up mail accounts. This project allows to provide clients like Outlook and Thunderbird the proper mail server configuration, so that users can simply enter their email and password to setup a new mail account.

Installation
------------

### Apache Webserver

You need an Apache webserver with PHP5 preconfigures. You can then configure your Virtual Host like this

```
<VirtualHost *:443>
  ServerName autodisvocer.{{$DOMAIN}}
  ServerAlias autodiscover.{{$DOMAIN}} autoconfig.{{$DOMAIN}}

  <Location />
    Options -Indexes
    AllowOverride All
  </Location>

  ...
</VirtualHost>
```

Now copy `settings.json.sample` to your Virtual Host directory root and apply your configuration variables.


### Autoconfig for multiple domains on the same server

When a user puts his E-Mail address `user@example.org` into his mail client, it will probably do a GET request on https://autodiscover.example.org/autodiscover/autodiscover.xml

If you have multiple domains hosted on your mailserver, you can redirect those requests to your main-autoconfig server. Add this configuration to your existing Virtual Host configuration:

```
<VirtualHost *:443>
  ServerName example.org
  SSLEngine On
  ...

  RewriteEngine On
  RewriteCond %{HTTP_HOST} ^autodiscover\. [NC]
  RewriteRule ^/(.*)      https://autodiscover.{{$DOMAIN}}/$1 [L,R=301,NE]

  RewriteCond %{HTTP_HOST} ^autoconfig\. [NC]
  RewriteRule ^/(.*)      https://autoconfig.{{$DOMAIN}}/$1 [L,R=301,NE]
  ...
</VirtualHost>

<VirtualHost *:80>
  ServerName example.org
  
  RewriteEngine On
  RewriteCond %{HTTP_HOST} ^autodiscover\. [NC]
  RewriteRule ^/(.*)      https://autodiscover.{{$DOMAIN}}/$1 [L,R=301,NE]

  RewriteCond %{HTTP_HOST} ^autoconfig\. [NC]
  RewriteRule ^/(.*)      https://autoconfig.{{$DOMAIN}}/$1 [L,R=301,NE]
  ...
</VirtualHost>

```


### DNS Setup

For the case you are using Bind and have the autoconfig HTTP server running on the same IP your `www.` subdomain resolves to, you can use this DNS records to configure your nameserver

```
autoconfig              IN      CNAME   www
autodiscover            IN      CNAME   www

@                       IN      MX 10   {{$MX_DOMAIN}}.
@                       IN      TXT     "mailconf=https://autoconfig.{{$DOMAIN}}/mail/config-v1.1.xml"
_imaps._tcp             SRV 0 1 993     {{$MX_DOMAIN}}.
_submission._tcp        SRV 0 1 465     {{$MX_DOMAIN}}.
_autodiscover._tcp      SRV 0 0 443     autodiscover.{{$DOMAIN}}.
```

Instead of a CNAME, you can of course also choose an A-record

```
autoconfig              IN      A      {{$AUTODISCOVER_IP}}
autodiscover            IN      A      {{$AUTODISCOVER_IP}}
```

Replace above variables with data according to this table

Variable         | Description
-----------------|-------------------------------------------------------------
MX_DOMAIN        | The hostname name of your MX server
DOMAIN           | Your apex/bare/naked Domain
AUTODISCOVER_IP  | IP of the Autoconfig HTTP

ToDo
----

 * Allow other authentication methods (currently always required)
 * Support nginx HTTP server
 * Add client support table
 * Create a Makefile for easy installation