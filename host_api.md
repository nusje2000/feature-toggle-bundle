# Host API Definition

The host is responsible to keep track of features and defines if features are enabled or disabled. Clients register new features on the host by using simple
HTTP calls. The host is made to handle multiple environments at the same time, so features can be enabled for one instance and disabled for another.

### GET /api/feature-toggle/{environment}

Retrieves all the feature toggles for the given environment.

```json5
// Response
[
    {
        "name": "feature_name_1", // string
        "environment": "environment", // string
        "enabled": true // boolean
    },
    {
        "name": "feature_name_2", // string
        "environment": "environment", // string
        "enabled": true // boolean
    },
    // ...
]
```

*Will return a 404 status if the environment does not exist.*

### POST /api/feature-toggle/{environment}

Creates a new environment. The IP address will be saved from where the creation occoured, and the environment can only be updated from that IP address.

```json5
// Response
[
    {
        "name": "feature_name_1", // string
        "whitelist": [
            "0.0.0.0"
        ], // array<string>
    },
]
```

*Will return a 409 status if the environment is already exists.*

### GET /api/feature-toggle/{environment}/{feature_name}

Retrieves information about the feature toggle.

```json5
// Response
{
    "name": "feature_name", // string
    "environment": "environment", // string
    "enabled": true // boolean
}
```

*Will return a 404 status if the feature is not defined.*

### POST /api/feature-toggle/{environment}/{name}

Defines a new feature toggle with a default value.

```json5
// Request
{
    "enabled": true // boolean
}
```

```json5
// Response
{
    "name": "feature_name", // string
    "environment": "environment", // string
    "enabled": true // boolean
}
```

*Will return a 409 status if the feature is already defined.*

### PATCH /api/feature-toggle/{environment}/{name}

Updates an existing feature toggle with a new value.

```json5
// Request
{
    "enabled": true // boolean
}
```

*Will return a 404 status if the feature is not defined.*

### DELETE /api/feature-toggle/{environment}/{name}

Removes an existing feature toggle.

```json5
// Request
{
    "enabled": true // boolean
}
```

*Will return a 404 status if the feature is not defined.*
