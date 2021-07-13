### Accessing features

For simple feature checks, there is a FeatureToggle interface. This acts as a mediator for checking feature states within an environment.

```php
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;use Nusje2000\FeatureToggleBundle\RepositoryFeatureToggle;

/** @var FeatureRepository $repository */

$toggle = new RepositoryFeatureToggle($repository, 'some-env');

// Fetching features
$toggle->get('feature-name');
$toggle->exists('feature-name');
$toggle->isEnabled('feature-name');
$toggle->isDisabled('feature-name');

// Feature assertions, will throw exceptions if condition is not met
$toggle->assertDefined('feature-name');
$toggle->assertEnabled('feature-name');
$toggle->assertDefined('feature-name');
```

### Configuration reference

```yaml
nusje2000_feature_toggle:
    logger: 'service_id' # should reference a psr logger or false to disable the logging
    repository:
        cache_adapter: 'adapter_id' # should reference a symfony cache adapter
        fallback:
            environment: 'fallback_service_id' # or 'static' for configured defaults
            feature: 'fallback_service_id' # or 'static' for configured defaults
        service: # when using a custom repository
            environment: 'environment_repository.service_id'
            feature: 'feature_repository.service_id'
        remote: # when using the API of another host
            host: 'host.domain' # the API host
            cache_store: 'service_id' # the service id of the cache store
            base_path: '/api/feature-toggle' # default
        static: false # uses an internal array as storage (changes are therefore not persistent)
    environment: # required when application is a client
        name: 'environment-name' # custom name for the environment
        hosts: [ 'localhost' ] # when using the api, this will be called by the host to invalidate the cache
        features: # list of features, these will be used as default values
            feature_1: true
            feature_2: true
            feature_3: false
        access_control:
            - { path: '^/feature-protected', ips: [ 127.0.0.1 ], port: 8080, features: { some_feature: true } }
            - { path: '^/feature-protected', ips: [ 127.0.0.1 ], features: { some_feature: true } }
            - { path: '^/feature-protected', host: symfony\.com$, features: { some_feature: true } }
            - { path: '^/feature-protected', methods: [ POST, PUT ], features: { some_feature: true } }
```

#### Access control

By using access control you can protect http routes from being access if certain requirements are not met.

```yaml
nusje2000_feature_toggle:
    environment:
        access_control:
            - { path: '^/feature-1-protected', ips: [ 127.0.0.1 ], port: 8080, features: { feature_1: true } }
            - { path: '^/feature-2-protected', ips: [ 127.0.0.1 ], features: { feature_2: true } }
            - { path: '^/feature-3-protected', host: symfony\.com$, features: { feature_3: false } }
            - { path: '^/feature-4-and-5-protected', methods: [ POST, PUT ], features: { feature_4: false, feature_5: true } }
```

##### Defining route requirements

- **path**: a regex representing the path that should be protected
- **host**: a regex representing the host that should be protected
- **methods**: a list of methods that the condition is applied to
- **ips**: a list of ip addresses that the condition is applied to
- **port**: the port that the condition is applied to

##### Defining feature requirements

- `features: {feature_1: false}`: requires feature_1 to be disabled
- `features: {feature_1: true}`: requires feature_1 to be enabled
- `features: {feature_1: true, feature_1: false}`: requires feature_1 to be enabled **and** feature_2 to be disabled

```yaml
# paths matching "^/feature-1-protected" accessed from ip address "127.0.0.1" and port 8080 should only be accessible if feature_1 is enabled
- { path: '^/feature-1-protected', ips: [ 127.0.0.1 ], port: 8080, features: { feature_1: true } }

# paths matching "^/feature-2-protected" accessed from ip address "127.0.0.1" should only be accessible if feature_2 is enabled
- { path: '^/feature-2-protected', ips: [ 127.0.0.1 ], features: { feature_2: true } }

# paths matching "^/feature-3-protected" accessed via a host matching "symfony\.com$" should only be accessible if feature_3 is disabled
- { path: '^/feature-3-protected', host: symfony\.com$, features: { feature_3: false } }

# paths matching "^/feature-4-and-5-protected" accessed via a method "POST" or "PUT" should only be accessible if feature_4 is disabled and feature_5 is enabled
- { path: '^/feature-4-and-5-protected', methods: [ POST, PUT ], features: { feature_4: false, feature_5: true } }
```

#### Caching

```yaml
nusje2000_feature_toggle:
    repository:
        cache_adapter: 'some_adapter_id'
```

Using a cache does require a way to invalidate the cache.

```yaml
services:
    nusje2000_feature_toggle.cache.invalidator:
        class: Nusje2000\FeatureToggleBundle\Cache\FileStoreInvalidator
        arguments:
            - '/path/to/storage'
```

#### Loading the host routes

```yaml
feature_toggle_host:
    resource: '@Nusje2000FeatureToggleBundle/Resources/config/routing/host.xml'
```

#### Loading the client routes

```yaml
feature_toggle_client:
    resource: '@Nusje2000FeatureToggleBundle/Resources/config/routing/client.xml'
```

### Advanced usage

#### Using the API

See [host api definition](./host_api.md) and [client api definition](./client_api.md) for the documentation.
