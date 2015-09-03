# Stateless authentication with JSON web tokens for TYPO3 Flow

## Author and Copyright

Martin Helmich  
Mittwald CM Service GmbH & Co. KG

This package is [MIT-licensed](LICENSE.txt).

## Synopsis

This package implements an authentication provider for TYPO3 Flow that
authenticates users based on [JSON Web Tokens](http://jwt.io). JWTs can be
supplied using a custom HTTP header (`X-JWT`).

## Installation

You can install this package using Composer:

    $ composer require mittwald-flow/jwt-auth

## Basic considerations and design choices

-   This package does *authentication only*. It will not issue new tokens. This
    package's only purpose is to authenticate users by JSON Web Tokens that are
    issued by a trusted, third-party identity provider.

-   JWT authentication is stateless. This means that when using JWT
    authentication, Flow will not start any kind of session, but authenticate
    you each time anew based on the access token.

-   Accounts authenticated by this package are not persistent. This is done on
    purpose, since claims for a user might change when the same user
    authenticates with a different token.

## Configuration

There are several settings that you need to configure in your TYPO3 Flow
settings.

### Verification key

This package needs a key to authenticate tokens. This can either be a random
character string for tokens that use a symmetric authentication code (HMAC)
or an RSA public key. To configure this key, you can use one of two
settings:
  
1.  `Mw.JwtAuth.security.key` to directly specify the key:

    ```yaml
    Mw:
      JwtAuth:
        security:
          key: |
            -----BEGIN PUBLIC KEY-----
            MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuurXQ9FbDxK9EQL9gw/f
            KJVdo/33j8zDOxemH6fV/KWp/fEMwez77GC3J5ze/A1o/ue4FVz/8fJ8PMGO3ag9
            drIHyWgs4FYBpQZ1BqA78b6nWJeJ8Zbsv71r+Bpb5UUJBBHZ85Sa13sl3ZN0L0E0
            XD/NYD1Sh31qoccZU57l6g4PWScxUZYGWc/OeT07HbUjaFzL/YpQZUKH+KoqoIOD
            UiZkf44ear4dGzNeR0UQ01VIZj7RaJ1uhAZVsNLoqPKGyjmgEZz70DDbMlxEXiMi
            Q/2Thd3bklr0IpZpL7JwHw9MrVS32NkustFgG6uYv/mvw10Zll9CCAUib3QIGlZV
            uQIDAQAB
            -----END PUBLIC KEY-----
    ```

2.  `Mw.JwtAuth.security.keyUrl` to specify a `fopen`-able URL from which
    the key can be retrieved:
   
    ```yaml
    Mw:
      JwtAuth:
        security:
          keyUrl: https://identity.service.consul/key
    ```

When you specify both settings, the `Mw.JwtAuth.security.key` setting will
take precedence.

### Claim-to-account mapping

You can also configure how the claims encoded in the JWT should be mapped
to the TYPO3 Flow user account. For instance, when the JWT claims contain a
field that describes a user type, you can map this on a TYPO3 Flow role.

Consider a JWT claim like the following:

```json
{
    "sub": "my-username",
    "type": "customer"
}
```

By default, the `sub` claim will be used as account identifier for the Flow
user. You can change this by setting the `Mw.JwtAuth.claimMapping.accountIdentifierField`
option.

Furthermore, you can configure which claim contains the user role and how
to map claim values to known user roles:

```yaml
Mw:
  JwtAuth:
    claimMapping:
      roleField: type
      roles:
        customer: My.ExamplePackage:Customer
        employee: My.ExamplePackage:Employee
```

### Token sources

You can also configure how the authentication provider should extract the
JWT from the HTTP request. A JWT can be contained within a cookie, a custom
request header or a query argument. You can configure the token sources
using the `Mw.JwtAuth.security.tokenSources`:
 
```yaml
Mw:
  JwtAuth:
    security:
      tokenSources:
        - from: header
          name: X-Your-Custom-Header
        - from: cookie
          name: MyCookieName
```

This setting can contain a list of multiple token sources. Each of those
will be tried in sequence until one of them matches.