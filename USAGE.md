### Accessing features

For simple feature checks, there is a FeatureToggle interface. This acts as a mediator for checking feature states within an environment.

```php
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Nusje2000\FeatureToggleBundle\RepositoryFeatureToggle;

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
    repository:
        cache_adapter: 'service.id' // service used for caching, not enabled by default
        service: // when using a custom repository
            environment: 'environment_repository.service_id'
            feature: 'feature_repository.service_id'
        remote: // when using the API of another host
            host: 'host.domain' // the API host
            base_path: '/api/feature-toggle' // default
        static: false // uses an internal array as storage (changes are therefore not persistent)
    environment: // required when application is a client
        name: 'environment-name' // custom name for the environment
        hosts: [ 'localhost' ] // when using the api, this will be called by the host to invalidate the cache
        features: // list of features, these will be used as default values
            feature_1: true
            feature_2: true
            feature_3: false
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

#### Custom repositories

TODO

#### Using the API

TODO
