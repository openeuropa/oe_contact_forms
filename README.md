# OpenEuropa Contact Forms

This is a Drupal module that defines the European Commission corporate forms.

OE Contact forms are an extension to drupal core contact forms, by providing corporate specific configuration on the contact form creation page.

Corporate forms provide new configurable fields, out of the box:

- Country of residence - Skos reference to the Country vocabulary
- Phone (Optional)
- Topic (Optional)

OE Contact forms exposes new permissions which restricts access to contact_form entities based on operation and corporate status, see [Permissions](#permissions).

Corporate forms can be configured to denies access to canonical url (if the "Allow canonical URL" is set to FALSE) allowing only entry point to be the url alias if needed.

The display of corporate forms is controlled and provide a configurable header text, a privacy policy required checkbox and text, and above mentioned fields (country and phone if set, topic label and options are configurable).

Corporate forms have a tailored behaviour, once the mandatory fields are filled out and the form is submitted, the confirmation message will include the privacy policy test and the submitted field values. The email sent will have the subject altered (configurable) and email recipients added in conformity with the topic chosen (multiple recipients can be configured). If auto-reply is configured the email body of the auto-reply will include the submitted field values.
Corporate forms can be configured to be exposed as blocks.

The OpenEuropa Contact Forms project provides storage for Contact messages in the form of fully-fledged entities using Contact Storage. The messages can then be automatically exported for each available Contact form.

## Permissions

Granular permissions are exposed via contributed modules for handling contact forms and contact message entities:

- Access corporate contact form
- View contact messages
- Update contact messages
- Delete contact messages
- Export contact form messages

## Development setup

You can build the development site by running the following steps:

* Install the Composer dependencies:

```bash
composer install
```

A post command hook (`drupal:site-setup`) is triggered automatically after `composer install`.
It will make sure that the necessary symlinks are properly setup in the development site.
It will also perform token substitution in development configuration files such as `behat.yml.dist`.

* Install test site by running:

```bash
./vendor/bin/run drupal:site-install
```

The development site web root should be available in the `build` directory.

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and 
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running, 
regardless of your local host configuration.

#### Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new 
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Usage

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

Then:

```bash
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development site
should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).

#### Running the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

To run the behat tests:

```bash
docker-compose exec web ./vendor/bin/behat
```

## Contributing

Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the available versions, see the [tags on this repository](https://github.com/openeuropa/oe_contact_forms/tags).
