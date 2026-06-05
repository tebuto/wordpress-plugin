## Keycloak Client

1. Create a new client `wordpress-plugin` in the `tebuto-therapists` Realm
2. Set `Valid redirect URIs` to `*`
3. Add the `offline_access` scope to the client
4. Enable PKCE via the `Advanced Settings > Proof Key for Code Exchange Code Challenge Method > S256`
5. Set the login theme to `tebuto`

## SVN

1. Source code is located in the `tebuto-online-terminbuchung` directory
2. The `tags` directory contains the release versions
3. To release a new version, create a new tag with the version number via SVN e.g. 
    ```sh
    svn copy tebuto-online-terminbuchung tags/1.0.0         
    svn --username=tebuto commit -m "Initial plugin upload – Version 1.0.0"
    ```
