# Nusje2000/FeatureToggleBundle

## Motivation

At the company I work for, we needed a way to hide unfinished features from custommers. We work with scheduled releases every week and want to deploy there
versions as fast as possible. This bundle provides an implementation of feature toggles, that can be easely intergrated with the symfony framework.

We have 10+ deployed symfony projects all with their own features that can be enabled and disabled. Currently, we use the parameters.yaml file for this, but
this has been proven to not be efficient. Each time we release a new feature, we have to ssh to the server and edit the file manually. This is fearly
time-consuming considdering we sometimes have to do this to multiple environments on different servers.

## Structure

### Environments

An environment is a set of feature toggles. Let's say you have multiple projects running that all use syfmony, by using this bundle, you can have all the
features acros these applications managed in one place. Multiple instances of the same applications (maybe across multiple servers) can also be seen as
different environments and all can have their own set of features.

#### Environments when only having a single application

When just writing a simple project that will be deployed on a single server, you will just have one environment. This bundle will also cover this usecase.

### Features

A feature is a component within your application, within this application a feature can have two states: disabled and enabled. A feature is coupled to an
environment and different environments can have a different set of features.

### Repository

Features and environments are stored in repositories. Some different repository implementations will be inculded within this bundle, but they are also easy to
write yourself.

## Usage

For a detailed guide on how to use this bundle, see the [documentation](USAGE.md).
