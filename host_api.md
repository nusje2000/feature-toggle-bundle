# Host API Definition

The host is responsible to keep track of features and defines if features are enabled or disabled. Clients register new features on the host by using simple
HTTP calls. The host is made to handle multiple environments at the same time, so features can be enabled for one instance and disabled for another.

### GET|HEAD /

Retrieves all the environments.

```json5
// Response when using GET
[
    {
        "name": "environment", // string
        "hosts": [
            '0.0.0.0',
            'some-domain'
        ], // array<string>
        "features": [
            {
                "name": "feature_name_1", // string
                "enabled": true // boolean
            },
            {
                "name": "feature_name_2", // string
                "enabled": true // boolean
            },
        ],
    }
    // ...
]
```

### GET|HEAD /{environment}

Retrieves all the feature toggles for the given environment.

```json5
// Response when using GET
{
    "name": "environment", // string
    "hosts": [
        '0.0.0.0',
        'some-domain'
    ], // array<string>
    "features": [
        {
            "name": "feature_name_1", // string
            "enabled": true // boolean
        },
        {
            "name": "feature_name_2", // string
            "enabled": true // boolean
        },
    ],
}
```

*Will return a 404 status if the environment does not exist.*

### POST /create-environment

Creates a new environment. Will return a 201 status if the creation was succesfull.

```json5
// Request
{
    "name": "environment_name", // string
    "hosts": [
        "0.0.0.0",
        "www.host.com"
    ] // the IP/domain(s) of the environment
}
```

*Will return a 409 status if the environment is already exists.*

### GET|HEAD /{environment}/{feature_name}

Retrieves information about the feature toggle.

```json5
// Response when using GET
{
    "name": "feature_name", // string
    "enabled": true // boolean
}
```

*Will return a 404 status if the environment is not defined.*

*Will return a 404 status if the feature is not defined.*

### POST /{environment}/create-feature

Defines a new feature toggle with a default value. Will return a 201 status if the creation was succesfull.

```json5
// Request
{
    "name": "feature_name", // string
    "enabled": true // boolean
}
```

*Will return a 404 status if the environment is not defined.*

*Will return a 409 status if the feature is already defined.*

### PATCH /{environment}/{name}

Updates an existing feature toggle with a new value and/or description.
The state and description will only be updated if the keys are present in the payload.
Will return a 200 status if the update was succesfull.

```json5
// Request
{
    "enabled": true, // boolean
    "description": "FooBar" // string|null
}
```

*Will return a 404 status if the environment is not defined.*

*Will return a 404 status if the feature is not defined.*

### DELETE /{environment}/{name}

Removes an existing feature toggle. Will return a 200 status if the deletion was succesfull.

*Will return a 404 status if the feature or environment is not defined.*
